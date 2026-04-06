<?php

namespace App\Helpers;

class StringHelper
{
    /**
     * Capitaliza la primera letra de cada palabra y convierte el resto a minúsculas.
     * Ej: "MarCo AnTONio OsoRIO" -> "Marco Antonio Osorio"
     * También limpia los espacios múltiples entre palabras y a los extremos.
     *
     * @param string|null $string
     * @return string|null
     */
    public static function capitalizeName(?string $string): ?string
    {
        if (empty($string)) {
            return null;
        }

        // 1. Eliminar HTML/rich text
        $string = self::stripRichFormatting($string);

        // 2. Eliminar emojis y caracteres no permitidos (solo letras Unicode y espacios)
        $string = self::sanitizeText($string);

        if (empty($string)) {
            return null;
        }

        // 3. Eliminar espacios múltiples intermedios y a los extremos
        $string = preg_replace('/\s+/', ' ', trim($string));

        // 4. Convertir todo a minúsculas primero (manejando correctamente tildes y eñes)
        $string = mb_strtolower($string, 'UTF-8');

        // 5. Capitalizar cada palabra (manejando correctamente tildes y eñes)
        return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Elimina HTML tags, entidades HTML y formatos de rich text pegados.
     * Convierte a texto plano puro.
     *
     * @param string|null $string
     * @return string|null
     */
    public static function stripRichFormatting(?string $string): ?string
    {
        if (empty($string)) {
            return null;
        }

        // Eliminar HTML tags
        $string = strip_tags($string);

        // Decodificar entidades HTML (&amp; -> &, etc.)
        $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Eliminar control characters (excepto newlines y tabs)
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);

        return $string;
    }

    /**
     * Elimina emojis del texto.
     *
     * @param string $string
     * @return string
     */
    private static function removeEmojis(string $string): string
    {
        // Rangos de emojis Unicode comunes
        $patterns = [
            '/[\x{1F600}-\x{1F64F}]/u',  // Emoticons
            '/[\x{1F300}-\x{1F5FF}]/u',  // Misc Symbols and Pictographs
            '/[\x{1F680}-\x{1F6FF}]/u',  // Transport & Map
            '/[\x{1F1E0}-\x{1F1FF}]/u',  // Flags
            '/[\x{2600}-\x{26FF}]/u',    // Misc symbols
            '/[\x{2700}-\x{27BF}]/u',    // Dingbats
            '/[\x{FE00}-\x{FE0F}]/u',    // Variation Selectors
            '/[\x{1F900}-\x{1F9FF}]/u',  // Supplemental Symbols
            '/[\x{1FA00}-\x{1FA6F}]/u',  // Chess Symbols
            '/[\x{1FA70}-\x{1FAFF}]/u',  // Symbols Extended-A
            '/[\x{200D}]/u',             // Zero Width Joiner
            '/[\x{20E3}]/u',             // Combining Enclosing Keycap
            '/[\x{FE0F}]/u',             // Variation Selector-16
            '/[\x{E0020}-\x{E007F}]/u',  // Tags
            '/[\x{10000}-\x{10FFFF}]/u', // Catch-all for supplementary planes
        ];

        foreach ($patterns as $pattern) {
            $string = preg_replace($pattern, '', $string);
        }

        return $string;
    }

    /**
     * Sanitiza campos de NOMBRE: Solo permite letras Unicode (con tildes, eñe, etc.) y espacios.
     * Elimina emojis, números, símbolos, HTML.
     *
     * @param string|null $string
     * @return string|null
     */
    public static function sanitizeText(?string $string): ?string
    {
        if (empty($string)) {
            return null;
        }

        // 1. Eliminar emojis
        $string = self::removeEmojis($string);

        // 2. Solo permitir letras Unicode y espacios (tildes, eñe, etc.)
        $string = preg_replace('/[^\pL\s]/u', '', $string);

        // 3. Limpiar espacios múltiples y trim
        $string = preg_replace('/\s+/', ' ', trim($string));

        return $string;
    }

    /**
     * Sanitiza campos de SALUD (enfermedades, alergias, medicamentos):
     * Permite letras, números, puntuación médica básica (comas, puntos, paréntesis, guiones, barras).
     * Bloquea emojis y HTML.
     *
     * @param string|null $string
     * @return string|null
     */
    public static function sanitizeHealthText(?string $string): ?string
    {
        if (empty($string)) {
            return null;
        }

        // 1. Eliminar HTML
        $string = self::stripRichFormatting($string);

        // 2. Eliminar emojis
        $string = self::removeEmojis($string);

        // 3. Solo permitir letras, números, puntuación médica y espacios
        $string = preg_replace('/[^\pL\pN\s.,;:()\-\/\"\'\+\%°#]+/u', '', $string);

        // 4. Limpiar espacios múltiples y trim
        $string = preg_replace('/\s+/', ' ', trim($string));

        return $string;
    }

    /**
     * Sanitiza campos de DIRECCIÓN (calle, colonia, municipio):
     * Permite letras, números, puntos, comas, #, -, /, °.
     * Bloquea emojis y HTML.
     *
     * @param string|null $string
     * @return string|null
     */
    public static function sanitizeAddress(?string $string): ?string
    {
        if (empty($string)) {
            return null;
        }

        // 1. Eliminar HTML
        $string = self::stripRichFormatting($string);

        // 2. Eliminar emojis
        $string = self::removeEmojis($string);

        // 3. Solo permitir caracteres válidos de dirección
        $string = preg_replace('/[^\pL\pN\s.,#\-\/°]+/u', '', $string);

        // 4. Limpiar espacios múltiples y trim
        $string = preg_replace('/\s+/', ' ', trim($string));

        return $string;
    }

    /**
     * Sanitiza campos numéricos de dirección (num_exterior, num_interior):
     * Solo permite alfanuméricos, -, /, #.
     *
     * @param string|null $string
     * @return string|null
     */
    public static function sanitizeAddressNumber(?string $string): ?string
    {
        if (empty($string)) {
            return null;
        }

        $string = self::stripRichFormatting($string);
        $string = self::removeEmojis($string);
        $string = preg_replace('/[^a-zA-Z0-9\s\-\/#]/', '', $string);
        $string = trim($string);

        return $string;
    }
}
