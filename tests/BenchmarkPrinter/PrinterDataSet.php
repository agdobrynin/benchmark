<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkPrinter;

use Generator;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;

final class PrinterDataSet
{
    public static function benchmarkResults(): Generator
    {
        $resOne = new BenchmarkResults('v1.0.0', 'Foo group');
        $resOne->attachIterations(
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras porta eleifend ante ut maximus.',
            [
                new TimeExecuteMemoryUsageInIteration(100, 1000, 200, 2000, 0, 2000, 10.11, 20.45, 2),
                new TimeExecuteMemoryUsageInIteration(200, 2000, 370, 3700, 270, 2700, 20.99, 40.21, 2),
            ]
        );

        $resOne->attachIterations(
            'Lorem ipsum dolor sit amet',
            [
                new TimeExecuteMemoryUsageInIteration(270, 2700, 286, 2860, 0, 0, 45.11, 49.45, 2),
                new TimeExecuteMemoryUsageInIteration(280, 2800, 291, 2910, 0, 0, 50.21, 55.45, 2),
            ]
        );

        $resTwo = new BenchmarkResults('v2.0.x-dev', 'Foo group');
        $resTwo->attachIterations(
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras porta eleifend ante ut maximus.',
            [
                new TimeExecuteMemoryUsageInIteration(100, 1000, 200, 2000, 0, 0, 10.11, 19.01, 2),
                new TimeExecuteMemoryUsageInIteration(200, 2000, 200, 2000, 0, 0, 20.99, 36.81, 2),
            ]
        );

        $resTwo->attachIterations(
            'Lorem ipsum dolor sit amet',
            [
                new TimeExecuteMemoryUsageInIteration(200, 2000, 220, 2200, 0, 0, 39.20, 43.11, 2),
                new TimeExecuteMemoryUsageInIteration(220, 2200, 260, 2600, 0, 0, 44.20, 47.11, 2),
            ]
        );

        yield [
            $resOne, $resTwo,
        ];
    }
}
