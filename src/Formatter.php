<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Core;

use function floor;
use function flush;
use function log;
use function max;
use function min;
use function number_format;
use function round;
use function str_pad;
use function str_repeat;
use function strlen;
use function substr;

final class Formatter
{
    /**
     * @param int $bytes memory usage in bytes
     * @param int $precision number of decimal digits to round to
     * @return string
     */
    public static function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $bytes = max($bytes, 0);
        $pow = $bytes ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * @param float $hrTime microseconds
     * @param int $precision number of decimal digits to round to
     * @return string
     */
    public static function formatTimeExecute(float $hrTime, int $precision = 2): string
    {
        $milliseconds = round($hrTime / 1e+6, $precision);

        return $milliseconds > 1000
            ? round(($hrTime / 1e+9), $precision) . ' s'
            : $milliseconds . ' ms';
    }

    public static function progressBar(string $title, int $step, int $total, int $sizeTitle = 60, $sizeBar = 39): void
    {
        $normalizedTitle = strlen($title) > $sizeTitle
            ? substr($title, 0, $sizeTitle - 1) . '…'
            : str_pad($title, $sizeTitle, ' ');

        $percentage = (float)($step / $total);
        $sizeBarProgress = (int)floor($percentage * $sizeBar);

        $barProgressStr = str_repeat('=', $sizeBarProgress);
        if ($sizeBarProgress < $sizeBar) {
            $barProgressStr .= '>';
            $barProgressStr .= str_repeat(' ', $sizeBar - $sizeBarProgress - 1);
        } else {
            $barProgressStr .= '=';
        }

        $currentPercent = number_format($percentage * 100);

        print "\r$normalizedTitle [{$barProgressStr}] {$currentPercent}%";

        flush();
    }
}
