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
                new TimeExecuteMemoryUsageInIteration(100, 200, 0, 10.11, 20.45, 2),
                new TimeExecuteMemoryUsageInIteration(200, 270, 270, 20.99, 40.21, 2),
            ]
        );

        $resOne->attachIterations(
            'Lorem ipsum dolor sit amet',
            [
                new TimeExecuteMemoryUsageInIteration(270, 280, 0, 45.11, 49.45, 2),
            ]
        );

        $resTwo = new BenchmarkResults('v2.0.x-dev', 'Foo group');
        $resTwo->attachIterations(
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras porta eleifend ante ut maximus.',
            [
                new TimeExecuteMemoryUsageInIteration(100, 200, 0, 10.11, 19.01, 2),
                new TimeExecuteMemoryUsageInIteration(200, 200, 0, 20.99, 36.81, 2),
            ]
        );

        $resTwo->attachIterations(
            'Lorem ipsum dolor sit amet',
            [
                new TimeExecuteMemoryUsageInIteration(200, 200, 0, 39.20, 43.11, 2),
            ]
        );

        yield [
            $resOne, $resTwo,
        ];
    }
}
