<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkAbstract;

use Kaspi\Benchmark\BenchmarkAbstract;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkAbstract::class)]
#[CoversClass(BenchmarkResults::class)]
#[CoversClass(TimeExecuteMemoryUsageIteration::class)]
class ResetBenchmarkResultsTest extends TestCase
{
    public function testResetResults(): void
    {
        $results = new BenchmarkResults('foo', 'bar');
        $results->attachIteration(
            'baz',
            new TimeExecuteMemoryUsageIteration(1, 2, 3, 4, 5, 6, 2)
        );

        self::assertTrue($results->getResults()->valid());

        $class = new class($results, false) extends BenchmarkAbstract {};

        // reset benchmark results inside method `doBenchmarks()`.
        $class->doBenchmarks();

        self::assertFalse($results->getResults()->valid());
    }
}
