<?php

namespace App\Extension;

class Utils
{
    public static function className(string $class): string
    {
        return ltrim(
            strrchr(
                '\\' . (is_object($class) ? get_class($class) : $class),
                '\\',
            ),
            '\\',
        );
    }

    public static function split(string|array|null $str, string $pattern = null): array
    {
        return is_array($str) ? $str : array_map(
            'trim',
            preg_split($pattern ?? '/[,;|]/', $str ?? '', 0, PREG_SPLIT_NO_EMPTY),
        );
    }

    public static function flatten(string|array|null ...$args): array
    {
        return array_merge(...array_map(static fn ($arg) => static::split($arg), $args));
    }

    public static function random(int $len = 8): string
    {
        return bin2hex(random_bytes(min(4, ($len - ($len % 2)) / 2)));
    }

    public static function truncate(string $str, int $max, bool $end = true, string $txt = '...'): string
    {
        if (strlen($str) - ($cut = strlen($txt)) > $max) {
            return ($end ? '' : $txt) . substr($str, 0, $max - $cut) . ($end ? $txt : '');
        }

        return $str;
    }

    public static function ellipsis(string $str, int $max, string $glue = '...'): string
    {
        if (strlen($str) - ($cut = strlen($glue)) > $max) {
            $mid = floor(($max - $cut) / 2);

            return substr($str, 0, $mid) . $glue . substr($str, -$mid);
        }

        return $str;
    }

    public static function walk(iterable $items, callable $fn): void
    {
        array_walk($items, static fn ($item, $key) => $fn($item, $key, $items));
    }

    public static function map(iterable $items, callable $fn, bool $assoc = true): array
    {
        $result = array();

        foreach ($items as $key => $item) {
            if ($assoc) {
                $result[$key] = $fn($item, $key, $items, $result);
            } else {
                $result[] = $fn($item, $key, $items, $result);
            }
        }

        return $result;
    }

    public static function reduce(iterable $items, callable $fn, $initials = null)
    {
        $result = $initials;

        foreach ($items as $key => $item) {
            $result = $fn($result, $item, $key, $items);
        }

        return $result;
    }

    public static function some(iterable $items, callable $fn, array &$found = null): bool
    {
        $found = null;

        foreach ($items as $key => $item) {
            if ($fn($item, $key, $items)) {
                $found['key'] = $key;
                $found['value'] = $item;

                return true;
            }
        }

        return false;
    }

    public static function all(iterable $items, callable $fn, array &$fail = null): bool
    {
        $fail = null;

        foreach ($items as $key => $item) {
            if (!$fn($item, $key, $items)) {
                $fail['key'] = $key;
                $fail['value'] = $item;

                return false;
            }
        }

        return true;
    }
}