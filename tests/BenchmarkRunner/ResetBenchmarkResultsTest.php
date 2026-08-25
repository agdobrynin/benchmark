<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Benchmark::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(BenchmarkRunner::class)]
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

        $res = $results->getResults();

        self::assertTrue($res->valid());
        self::assertEquals('baz', $res->key());

        $res->next();

        self::assertFalse($res->valid());

        $class = new class {
            #[Benchmark('do nothing')]
            public function doNothing(): void {}
        };

        // reset benchmark results inside method `doBenchmarks()`.
        $results = (new BenchmarkRunner($results, $class, false))
            ->doBenchmarks()
        ;

        $res = $results->getResults();

        self::assertTrue($res->valid());
        self::assertEquals('do nothing', $res->key());

        $res->next();

        self::assertFalse($res->valid());
    }
}
