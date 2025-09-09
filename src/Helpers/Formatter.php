<?php

declare(strict_types=1);

namespace ForgeAxiom\Framecore\Helpers;

final class Formatter
{
    /**
     * Formats a string to an array by a given delimiter.
     *
     * @param string $string The string to be formatted.
     * @param string $delimiter The boundary string. Default is '|'.
     * @param string $characterMask Characters to be trimmed. Default is '| '.
     * 
     * @return array The formatted array.
     */
    public static function formatByDelimiterToArray(
        string $string, 
        string $delimiter = '|', 
        string $characterMask = ' |'
    ): array {
        return explode($delimiter, trim($string, $characterMask));
    }

    /**
     * Formats a value to an array if it is a string.
     *
     * @param string|array $value The value to be checked and formatted.
     * 
     * @return array The formatted array or the given array.
     */
    public static function formatIfStringToArray(string | array $value): array
    {
        if (is_string($value)) {
            // Используем более гибкий метод, который мы обсудили, 
            // но с дефолтными параметрами он работает как раньше.
            return self::formatByDelimiterToArray($value); 
        }

        return $value;
    }
}