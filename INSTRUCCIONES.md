# Corrección de Filtración de Datos entre Clínicas

## Archivos a copiar a tu proyecto

### 1. NUEVO archivo (crear si no existe)
```
app/Models/Scopes/ClinicaScope.php
```
Crea la carpeta `Scopes` dentro de `app/Models/` y coloca este archivo.
Es el Global Scope que filtra automáticamente por clínica.

### 2. Reemplazar archivo existente
```
app/Models/Servicio.php
```
Agrega el Global Scope para que los tratamientos solo muestren
los de la clínica activa. Elimina la filtración manual en controllers.

### 3. Reemplazar archivo existente
```
app/Http/Controllers/PublicidadController.php
```
Ahora filtra por los `id_usuario` que pertenecen a la clínica activa,
evitando que se mezcle publicidad de otras clínicas.

### 4. Reemplazar archivo existente
```
app/Http/Controllers/TratamientoController.php
```
Doble verificación de `id_clinica` en todas las operaciones CRUD.

### 5. Reemplazar archivo existente
```
app/Http/Controllers/ConfiguracionController.php
```
La foto del doctor ahora siempre busca por `id_usuario` del autenticado,
eliminando la posibilidad de mezclar fotos entre doctores de la misma clínica.

---

## Resumen de bugs corregidos

| Problema                              | Causa                                              | Solución                                      |
|---------------------------------------|----------------------------------------------------|-----------------------------------------------|
| Tratamientos de otras clínicas        | Sin Global Scope en modelo `Servicio`              | `ClinicaScope` + filtro explícito             |
| Publicidades mezcladas                | `index()` no filtraba por clínica correctamente    | Filtrar por `id_usuario` de la clínica        |
| Fotos de doctores mezcladas           | Posible búsqueda por `id_doctor` sin validar       | Siempre buscar por `id_usuario` del Auth      |
| Sin restricción en `destroy` publicidad | Cualquier doctor podía borrar ads de otra clínica | Verificar pertenencia antes de eliminar       |

---

## IMPORTANTE: El ClinicaScope NO afecta las APIs de la app móvil

El scope verifica `Auth::check()` con el guard web. Las peticiones de la
app móvil usan `auth:sanctum` con un guard diferente, por lo que el scope
NO se activa en esas rutas y la app móvil sigue funcionando normalmente.

Si en algún controlador necesitas saltarte el scope (ej. comandos artisan):
```php
Servicio::withoutGlobalScope(\App\Models\Scopes\ClinicaScope::class)->get();
```
