<?php

declare(strict_types=1);

/*
 *   This file is part of php-docker-project
 *   Source: https://github.com/michaelbutler/php-docker-project
 *
 *   THIS HEADER MESSGAGE MAY BE MODIFIED IN .php-cs-fixer.dist.php
 *   in the project root folder.
 *
 *   (c) 2022-23 foo-example.com
 */

namespace MyApp\helper;

class StringUtil
{
    public const AMBIGUOUS_CHARACTERS_REGEX = '~[+/=oO0iIlL1]~';

    /**
     * Checks if a string is valid typical UTF-8. Treats things like NUL bytes as invalid, even if it might be allowed
     * in UTF-8 standard. Use for general user generated content to keep things sane.
     */
    public static function isTypicalUtf8(string $str): bool
    {
        if (!self::isRawUtf8($str)) {
            return false;
        }

        // Hex. char checks. What do these do?
        // UTF-8 encoded strings treat characters < 128 as the raw ASCII character. In other words, multi-byte
        // UTF characters will never consist of something < 128 in it. Thus, we can check for these non-printable
        // ASCII chars and reject it if we are looking only for sane UTF-8 strings.

        // Chars 00 to 08, hex
        if (preg_match('/[\x00-\x08]/', $str)) {
            return false;
        }

        // x09 - Tab \t
        // x0A - Line Feed

        // Chars 0B to 0C, hex
        if (preg_match('/[\x0B-\x0C]/', $str)) {
            return false;
        }

        // x0D - Carriage Return

        // Chars 0E to 1F, hex
        if (preg_match('/[\x0E-\x1F]/', $str)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a string is valid UTF-8, but no extra checks. Generally you should use isTypicalUtf8.
     */
    public static function isRawUtf8(string $str): bool
    {
        return mb_check_encoding($str, 'UTF-8');
    }

    /**
     * Can be used for secure keys and one time codes, etc.
     *
     * @param int $length Length of random string
     */
    public static function getRandomString(int $length): string
    {
        $str = '';
        do {
            $rand = base64_encode(random_bytes($length * 3));
            $rand = preg_replace(self::AMBIGUOUS_CHARACTERS_REGEX, '', $rand);
            $str .= $rand;
        } while (strlen($str) < $length);

        // Truncate result
        return substr($str, 0, $length);
    }

    /**
     * Convert a string for use in a URL, such as "Hello, world!" => 'hello-world'.
     *
     * @param string $str     Input string
     * @param array  $options Options
     */
    public static function slugify(string $str, array $options = []): string
    {
        static $char_map = null;

        if (null === $char_map) {
            $char_map = [
                // Latin
                'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
                'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
                'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
                'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
                'ß' => 'ss',
                'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
                'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
                'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
                'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
                'ÿ' => 'y',

                // Latin symbols
                '©' => '(c)',

                // Greek
                'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
                'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
                'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
                'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
                'Ϋ' => 'Y',
                'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
                'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
                'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
                'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
                'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',

                // Turkish
                'Ş' => 'S', 'İ' => 'I', /* 'Ü' => 'U', 'Ö' => 'O', */ 'Ğ' => 'G',
                'ş' => 's', 'ı' => 'i', /* 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', */ 'ğ' => 'g',

                // Russian
                'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
                'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
                'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
                'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
                'Я' => 'Ya',
                'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
                'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
                'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
                'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
                'я' => 'ya',

                // Ukrainian
                'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
                'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',

                // Czech
                'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
                'Ž' => 'Z',
                'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
                'ž' => 'z',

                // Polish
                'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ś' => 'S', 'Ź' => 'Z',
                'Ż' => 'Z',
                'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', /* 'ó' => 'o', */ 'ś' => 's', 'ź' => 'z',
                'ż' => 'z',

                // Latvian
                'Ā' => 'A', /* 'Č' => 'C', */ 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
                /* 'Š' => 'S', */ 'Ū' => 'u', // 'Ž' => 'Z',
                'ā' => 'a', /* 'č' => 'c', */ 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
                /* 'š' => 's', */ 'ū' => 'u', // 'ž' => 'z'
            ];
        }
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $str = (string) mb_convert_encoding($str, 'UTF-8', 'UTF-8');

        $defaults = [
            'delimiter' => '-',
            'limit' => null,
            'lowercase' => true,
            'replacements' => [],
            'transliterate' => true,
        ];

        // Merge options
        $options = array_merge($defaults, $options);

        // Transliterate characters to ASCII
        if ($options['transliterate']) {
            $str = str_replace(array_keys($char_map), $char_map, $str);
        }

        // Replace non-alphanumeric characters with our delimiter
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);

        // Make custom replacements
        if ($options['replacements']) {
            $str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);
        }

        // Remove duplicate delimiters
        $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);

        // Truncate slug to max. characters
        if ($options['limit'] > 0) {
            $str = mb_substr($str, 0, $options['limit'] ?: mb_strlen($str, 'UTF-8'), 'UTF-8');
        }

        // Remove delimiter from ends
        $str = trim($str, $options['delimiter']);

        return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
    }

    /**
     * Convert string to proper UTF-8 for safe keeping. Possibly removes chars to make it valid.
     * Leaves weird ASCII chars intact!
     */
    public static function toUtf8(string $str): string
    {
        if (self::isRawUtf8($str)) {
            return $str;
        }
        $orig = ini_set('mbstring.substitute_character', 'none');

        try {
            return mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        } finally {
            ini_set('mbstring.substitute_character', $orig);
        }
    }

    /**
     * Determine if an email address is valid, using a fairly loose check. Be sure to trim whitespace prior to calling.
     * Basically checks for one @ symbol, then one dot symbol, and at least 2 chars after the dot.
     */
    public static function isValidEmail(string $email): bool
    {
        if (!self::isTypicalUtf8($email)) {
            return false;
        }

        return (bool) preg_match('/^[^\s@]+@[^\s@.]+\.[^\s@]{2,}$/', $email);
    }
}
