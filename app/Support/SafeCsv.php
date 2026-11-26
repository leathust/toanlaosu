<?php

namespace App\Support;

use RuntimeException;

final class SafeCsv
{
    /**
     * @param  array<int, mixed>  $fields
     */
    public static function row(array $fields): string
    {
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            throw new RuntimeException('Unable to create CSV buffer.');
        }

        fputcsv($stream, array_map(self::sanitize(...), $fields));
        rewind($stream);
        $row = stream_get_contents($stream);
        fclose($stream);

        return $row === false ? '' : $row;
    }

    private static function sanitize(mixed $value): string|int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $value = $value === null ? '' : (string) $value;

        return preg_match('/^[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
