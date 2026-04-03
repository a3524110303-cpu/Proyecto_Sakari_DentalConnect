<?php

namespace App\Http\Controllers;

use App\Models\PlanSaas;
use App\Models\SuscripcionClinica;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SuscripcionController extends Controller
{
    public function show()
    {
        $clinica = Auth::user()->clinica;

        $suscripcion = $clinica
            ?->suscripciones()
            ->with('plan')
            ->orderByDesc('created_at')
            ->first();

        return view('suscripciones.show', compact('suscripcion'));
    }

    public function checkout(Request $request, string $planSlug): RedirectResponse
    {
        $clinica = Auth::user()->clinica;

        if (!$clinica) {
            return redirect()->route('landing')
                ->with('error', 'No se encontro una clinica asociada al usuario.');
        }

        if ($clinica->suscripcionActiva()->exists()) {
            return redirect()->route('suscripciones.show')
                ->with('error', 'Tu clínica ya tiene un plan activo. Para cambiar de plan, debes gestionar tu suscripción actual.');
        }

        $plan = PlanSaas::where('slug', $planSlug)
            ->where('activo', true)
            ->firstOrFail();

        if (!$plan->stripe_price_id) {
            return redirect()->route('landing')
                ->with('error', 'Este plan aun no tiene Price ID de Stripe configurado.');
        }

        if (!config('services.stripe.secret')) {
            return redirect()->route('landing')
                ->with('error', 'Stripe no esta configurado en este entorno.');
        }

        // Preparamos los datos base para Stripe
        $sessionData = [
            'mode' => 'subscription',
            'line_items[0][price]' => $plan->stripe_price_id,
            'line_items[0][quantity]' => 1,
            'success_url' => route('suscripciones.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('suscripciones.cancel'),
            'metadata[id_clinica]' => (string) $clinica->id_clinica,
            'metadata[id_plan]' => (string) $plan->id_plan,
            'subscription_data[metadata][id_clinica]' => (string) $clinica->id_clinica,
            'subscription_data[metadata][id_plan]' => (string) $plan->id_plan,
        ];

        // Verificar si la clínica ya tiene un cliente registrado en Stripe
        $suscripcionPrevia = $clinica->suscripciones()->orderByDesc('created_at')->first();
        if ($suscripcionPrevia && $suscripcionPrevia->stripe_customer_id) {
            $sessionData['customer'] = $suscripcionPrevia->stripe_customer_id;
        } else {
            $sessionData['customer_email'] = Auth::user()->email;
        }

        $response = Http::asForm()
            ->withBasicAuth((string) config('services.stripe.secret'), '')
            ->post('https://api.stripe.com/v1/checkout/sessions', $sessionData);

        if (!$response->successful()) {
            Log::error('Error creando checkout Stripe', ['body' => $response->body()]);

            return redirect()->route('landing')
                ->with('error', 'No fue posible crear la sesion de pago en Stripe.');
        }

        $checkoutSession = (object) $response->json();

        SuscripcionClinica::updateOrCreate(
            [
                'id_clinica' => $clinica->id_clinica,
                'estado' => 'pending',
            ],
            [
                'id_plan' => $plan->id_plan,
                'stripe_checkout_session_id' => $checkoutSession->id,
                'moneda' => 'mxn',
                'monto_periodo' => $plan->precio_mensual,
            ]
        );

        return redirect()->away((string) $checkoutSession->url);
    }

    public function success(Request $request): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');

        if (!$sessionId || !config('services.stripe.secret')) {
            return redirect()->route('landing')->with('error', 'No se pudo verificar la compra.');
        }

        $response = Http::withBasicAuth((string) config('services.stripe.secret'), '')
            ->get('https://api.stripe.com/v1/checkout/sessions/' . $sessionId);

        if (!$response->successful()) {
            return redirect()->route('landing')->with('error', 'No se pudo verificar la sesion de Stripe.');
        }

        $session = (object) $response->json();

        if (($session->payment_status ?? null) !== 'paid') {
            return redirect()->route('landing')->with('error', 'El pago aun no se confirma en Stripe.');
        }

        return redirect()->route('suscripciones.show')
            ->with('success', 'Suscripcion procesada correctamente.');
    }

    public function cancel(): RedirectResponse
    {
        return redirect()->route('landing')->with('error', 'Pago cancelado por el usuario.');
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            if ($webhookSecret && !$this->isValidStripeSignature($payload, (string) $signature, (string) $webhookSecret)) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $event = json_decode($payload);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $eventType = $event->type ?? null;
        $eventObject = $event->data->object ?? null;

        if (!$eventType || !$eventObject) {
            return response()->json(['error' => 'Event not supported'], 400);
        }

        if ($eventType === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($eventObject);
        }

        if (in_array($eventType, ['customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted'], true)) {
            $this->handleSubscriptionEvent($eventObject);
        }

        if ($eventType === 'invoice.payment_succeeded') {
            $stripeSubscriptionId = (string) ($eventObject->subscription ?? '');
            if ($stripeSubscriptionId && config('services.stripe.secret')) {
                try {
                    $response = Http::withBasicAuth((string) config('services.stripe.secret'), '')
                        ->get('https://api.stripe.com/v1/subscriptions/' . $stripeSubscriptionId);

                    if ($response->successful()) {
                        $this->handleSubscriptionEvent((object) $response->json());
                    }
                } catch (\Throwable $e) {
                    Log::error('Error fetching subscription in invoice.payment_succeeded', ['error' => $e->getMessage()]);
                }
            }
        }

        return response()->json(['received' => true]);
    }

    private function isValidStripeSignature(string $payload, string $header, string $secret): bool
    {
        if (!$header) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $segment) {
            [$k, $v] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($k && $v) {
                $parts[$k][] = $v;
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (!$timestamp || empty($signatures)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function handleCheckoutCompleted(object $session): void
    {
        $metadata = (array) ($session->metadata ?? []);
        $idClinica = (int) ($metadata['id_clinica'] ?? 0);
        $idPlan = (int) ($metadata['id_plan'] ?? 0);

        if (!$idClinica || !$idPlan) {
            return;
        }

        $stripeSubscriptionId = $session->subscription ?? null;
        $periodoInicio = null;
        $periodoFin = null;

        // Si tenemos suscripción, la recuperamos para obtener las fechas del periodo
        if ($stripeSubscriptionId && config('services.stripe.secret')) {
            try {
                $response = Http::withBasicAuth((string) config('services.stripe.secret'), '')
                    ->get('https://api.stripe.com/v1/subscriptions/' . $stripeSubscriptionId);

                if ($response->successful()) {
                    $subscription = (object) $response->json();

                    Log::info('--- DEBUG CHECKOUT COMPLETED ---', [
                        'stripe_subscription_id' => $stripeSubscriptionId,
                        'current_period_start' => $subscription->current_period_start ?? 'MISSING',
                        'current_period_end' => $subscription->current_period_end ?? 'MISSING',
                    ]);

                    $periodoInicio = !empty($subscription->current_period_start)
                        ? Carbon::createFromTimestamp((int) $subscription->current_period_start)->toDateTimeString()
                        : null;
                    $periodoFin = !empty($subscription->current_period_end)
                        ? Carbon::createFromTimestamp((int) $subscription->current_period_end)->toDateTimeString()
                        : null;
                }
            } catch (\Throwable $e) {
                Log::error('Error recuperando suscripcion en checkout completed', ['error' => $e->getMessage()]);
            }
        }

        SuscripcionClinica::updateOrCreate(
            ['stripe_checkout_session_id' => $session->id],
            [
                'id_clinica' => $idClinica,
                'id_plan' => $idPlan,
                'stripe_customer_id' => $session->customer ?? null,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'estado' => ($session->payment_status ?? 'pending') === 'paid' ? 'active' : 'incomplete',
                'periodo_inicio' => $periodoInicio,
                'periodo_fin' => $periodoFin,
            ]
        );
    }

    private function handleSubscriptionEvent(object $subscription): void
    {
        $status = (string) ($subscription->status ?? 'pending');
        $stripeSubscriptionId = (string) ($subscription->id ?? '');

        if (!$stripeSubscriptionId) {
            return;
        }

        // 🔥 NUEVO: Extraemos el ID de la clínica de la metadata que enviamos
        $metadata = (array) ($subscription->metadata ?? []);
        $idClinica = (int) ($metadata['id_clinica'] ?? 0);

        $stripePriceId = (string) data_get($subscription, 'items.data.0.price.id', '');
        $plan = $stripePriceId ? PlanSaas::where('stripe_price_id', $stripePriceId)->first() : null;

        $record = SuscripcionClinica::where('stripe_subscription_id', $stripeSubscriptionId)->first();

        // Si no lo encuentra por ID de suscripción, busca por el ID de cliente
        if (!$record) {
            $record = SuscripcionClinica::where('stripe_customer_id', (string) ($subscription->customer ?? ''))
                ->orderByDesc('created_at')
                ->first();
        }

        // 🔥 NUEVO: Si aún no hay registro (condición de carrera), usamos el id_clinica
        if (!$record && $idClinica > 0) {
            $record = SuscripcionClinica::where('id_clinica', $idClinica)
                ->orderByDesc('created_at')
                ->first();
        }

        if (!$record) {
            Log::warning('Webhook Stripe sin suscripcion local asociada', ['stripe_subscription_id' => $stripeSubscriptionId]);
            return;
        }

        if ($plan) {
            $record->id_plan = $plan->id_plan;
            $record->monto_periodo = $plan->precio_mensual;
        }

        $record->stripe_customer_id = (string) ($subscription->customer ?? $record->stripe_customer_id);
        $record->stripe_subscription_id = $stripeSubscriptionId;
        $record->estado = $status;

        Log::info('--- DEBUG SUBSCRIPTION EVENT ---', [
            'event_subscription_id' => $stripeSubscriptionId,
            'current_period_start' => $subscription->current_period_start ?? 'MISSING',
            'current_period_end' => $subscription->current_period_end ?? 'MISSING',
        ]);

        $record->periodo_inicio = !empty($subscription->current_period_start)
            ? Carbon::createFromTimestamp((int) $subscription->current_period_start)->toDateTimeString()
            : null;
        $record->periodo_fin = !empty($subscription->current_period_end)
            ? Carbon::createFromTimestamp((int) $subscription->current_period_end)->toDateTimeString()
            : null;
        $record->auto_renovar = (bool) !($subscription->cancel_at_period_end ?? false);
        $record->save();
    }
}
