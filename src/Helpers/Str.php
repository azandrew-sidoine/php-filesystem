<?php

namespace Drewlabs\Filesystem\Helpers;

class Str
{
    /**
     * Returns true if $haystack ends with $needle substring
     * 
     * @param string $haystack 
     * @param string $needle 
     * @return bool 
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        if (function_exists('str_ends_with')) {
            return str_ends_with($haystack, $needle);
        }
        if ('' === $needle || $needle === $haystack) {
            return true;
        }

        if ('' === $haystack) {
            return false;
        }

        $needleLength = \strlen($needle);

        return $needleLength <= \strlen($haystack) && 0 === substr_compare($haystack, $needle, -$needleLength);
    }

    /**
     * Returns true if $haystack starts with $needle substring
     * 
     * @param string $haystack 
     * @param string $needle 
     * @return bool 
     */
    public static function startsWith(string $haystack, string $needle)
    {
        if (function_exists('str_starts_with')) {
            return str_starts_with($haystack, $needle);
        }
        return 0 === strncmp($haystack, $needle, \strlen($needle));
    }


    /**
     * Replace the last occurent of the {@see $needle} substring
     * 
     * @param string $search 
     * @param string $replace 
     * @param string $subject 
     * @return string 
     */
    public static function replaceLast(string $search, string $replace, string $subject)
    {
        $pos = strrpos($subject, $search);
        if (false !== $pos) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }
}
