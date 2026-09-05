<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkPrinter;

use Generator;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\Services\EnvParams;

final class PrinterDataSet
{
    public static function benchmarkResults(): Generator
    {
        $iterFixtures = [
            new TimeExecuteMemoryUsageInIteration(0, 0, 0, 0, 0, 0, 0, 0, 2),
            new TimeExecuteMemoryUsageInIteration(0, 0, 0, 0, 0, 0, 0, 0, 2),
        ];

        $benchDescriptionOne = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras porta eleifend ante ut maximus. Sed eget mi convallis, ultrices orci quis, aliquet dolor. Donec eget tellus eu mauris lacinia finibus.';
        $benchDescriptionTwo = 'Lorem ipsum dolor sit amet';

        $resOne = new BenchmarkResults('v1.0.0', 'Foo group', EnvParams::autoConfigureEnvBenchmark());
        $resOne->attachIterations($benchDescriptionOne, $iterFixtures);
        $resOne->attachIterations($benchDescriptionTwo, $iterFixtures);

        $resTwo = new BenchmarkResults('v2.0.x-dev', 'Foo group', EnvParams::autoConfigureEnvBenchmark());
        $resTwo->attachIterations($benchDescriptionOne, $iterFixtures);
        $resTwo->attachIterations($benchDescriptionTwo, $iterFixtures);

        yield [
            $resOne, $resTwo,
        ];
    }
}
