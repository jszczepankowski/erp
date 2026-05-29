<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return trim((string) $value);
    }
}

if (! function_exists('sanitize_key')) {
    function sanitize_key($value)
    {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value));
    }
}

if (! function_exists('get_option')) {
    function get_option($name, $default = false)
    {
        return $default;
    }
}

if (! function_exists('wp_list_pluck')) {
    function wp_list_pluck(array $list, $field)
    {
        $values = [];
        foreach ($list as $item) {
            if (is_array($item) && array_key_exists($field, $item)) {
                $values[] = $item[$field];
            }
        }

        return $values;
    }
}
