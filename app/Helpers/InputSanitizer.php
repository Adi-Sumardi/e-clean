<?php

namespace App\Helpers;

class InputSanitizer
{
    /**
     * Sanitize string input
     */
    public static function sanitizeString(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Strip tags and trim
        $sanitized = strip_tags($input);
        $sanitized = trim($sanitized);

        // Convert special characters to HTML entities
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');

        return $sanitized;
    }

    /**
     * Sanitize search query input
     */
    public static function sanitizeSearch(?string $search): ?string
    {
        if ($search === null) {
            return null;
        }

        // Remove dangerous characters but allow spaces, letters, numbers
        $sanitized = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search);
        $sanitized = trim($sanitized);

        return $sanitized;
    }

    /**
     * Sanitize array of strings
     */
    public static function sanitizeArray(?array $array): ?array
    {
        if ($array === null) {
            return null;
        }

        return array_map(function($item) {
            return is_string($item) ? self::sanitizeString($item) : $item;
        }, $array);
    }
}
