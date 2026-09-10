<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkPrinter;

use Generator;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\EnvBenchmark;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;

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
        $os = 'Linux 6.18.33.2-microsoft-standard-WSL2 #1 SMP PREEMPT_DYNAMIC Thu Jun 18 21:54:43 UTC 2026 x86_64';

        $envOne = new EnvBenchmark(80100, false, $os);

        $resOne = new BenchmarkResults('v1.0.0', 'Foo group', $envOne);
        $resOne->attachIterations($benchDescriptionOne, $iterFixtures);
        $resOne->attachIterations($benchDescriptionTwo, $iterFixtures);

        $resTwo = new BenchmarkResults('v2.0.x-dev', 'Foo group', $envOne);
        $resTwo->attachIterations($benchDescriptionOne, $iterFixtures);
        $resTwo->attachIterations($benchDescriptionTwo, $iterFixtures);

        yield [
            $resOne, $resTwo,
        ];
    }
}
