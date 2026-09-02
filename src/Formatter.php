<?php

declare(strict_types=1);

namespace Kaspi\Benchmark;

use function count;
use function floor;
use function flush;
use function log;
use function max;
use function min;
use function number_format;
use function preg_replace;
use function round;
use function str_pad;
use function str_repeat;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;
use function trim;
use function ucfirst;

final class Formatter
{
    /**
     * @param float $bytes     memory usage in bytes
     * @param int   $precision number of decimal digits to round to
     */
    public static function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow = (int) min($pow, count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * @param float $hrTime    nanoseconds
     * @param int   $precision number of decimal digits to round to
     */
    public static function formatTimeExecute(float $hrTime, int $precision = 2): string
    {
        if ($hrTime >= 1_000_000_000) {
            return round($hrTime / 1_000_000_000, $precision).' s';
        }

        if ($hrTime > 1_000) {
            return round($hrTime / 1_000_000, $precision).' ms';
        }

        return round($hrTime, $precision).' ns';
    }

    /**
     * @param non-empty-string $methodName
     *
     * @return non-empty-string
     */
    public static function methodToHuman(string $methodName): string
    {
        /** @var non-empty-string $converted */
        $converted = preg_replace(
            '/(?<! )[A-Z]/',
            ' $0',
            str_replace(['_', '-'], ' ', $methodName)
        );

        /** @var non-empty-string $normalizedName */
        $normalizedName = ucfirst(strtolower(trim($converted)));

        return '' !== $normalizedName ? $normalizedName : $methodName;
    }

    public static function progressBar(string $title, int $step, int $total, int $sizeTitle = 60, int $sizeBar = 39): void
    {
        $normalizedTitle = strlen($title) > $sizeTitle
            ? substr($title, 0, $sizeTitle - 1).'…'
            : str_pad($title, $sizeTitle, '.');

        $percentage = (float) ($step / $total);
        $sizeBarProgress = (int) floor($percentage * $sizeBar);

        $barProgressStr = str_repeat('=', $sizeBarProgress);
        if ($sizeBarProgress < $sizeBar) {
            $barProgressStr .= '>';
            $barProgressStr .= str_repeat(' ', $sizeBar - $sizeBarProgress - 1);
        }

        $currentPercent = number_format($percentage * 100);

        echo "\r{$normalizedTitle} [{$barProgressStr}] {$currentPercent}%";

        flush();
    }
}
