<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\NumberOfTimes;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkGroup;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\DTO\EnvBenchmark;
use Kaspi\Benchmark\Formatter;
use Kaspi\Benchmark\Services\EnvParams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkRunner::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(NumberOfTimes::class)]
#[UsesClass(BenchmarkGroup::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(Formatter::class)]
#[UsesClass(EnvBenchmark::class)]
#[UsesClass(EnvParams::class)]
class BenchmarkRunnerNumberOfTimesAttributeTest extends TestCase
{
    public function testNumberOfTimesNotDefined(): void
    {
        $class = new class {
            #[Benchmark]
            public function doBenchOne(): void {}

            #[Benchmark]
            #[NumberOfTimes(5)]
            public function doBenchTwo(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(2, $group->benchmarkMethods);

        self::assertEquals(1, $group->benchmarkMethods[0]->numberOfTimes);
        self::assertEquals(5, $group->benchmarkMethods[1]->numberOfTimes);
    }

    public function testNumberOfTimesOnClassWithNegativeInt(): void
    {
        $class = new #[NumberOfTimes(-2)] class() {
            #[Benchmark]
            public function doBenchOne(): void {}

            #[Benchmark]
            #[NumberOfTimes(-10)]
            public function doBenchTwo(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(2, $group->benchmarkMethods);
        self::assertEquals(1, $group->benchmarkMethods[0]->numberOfTimes);
        self::assertEquals(1, $group->benchmarkMethods[1]->numberOfTimes);
    }

    public function testNumberOfTimesOnClassWithInt(): void
    {
        $class = new #[NumberOfTimes(2)] class() {
            #[Benchmark]
            public function doBenchOne(): void {}

            #[Benchmark]
            #[NumberOfTimes(5)]
            public function doBenchTwo(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(2, $group->benchmarkMethods);
        self::assertEquals(2, $group->benchmarkMethods[0]->numberOfTimes);
        self::assertEquals(5, $group->benchmarkMethods[1]->numberOfTimes);
    }

    public function testNumberOfTImesOnMethodWithNegativeInt(): void
    {
        $class = new #[NumberOfTimes(2)] class() {
            #[Benchmark]
            public function doBenchOne(): void {}

            #[Benchmark]
            #[NumberOfTimes(-10)]
            public function doBenchTwo(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(2, $group->benchmarkMethods);
        self::assertEquals(2, $group->benchmarkMethods[0]->numberOfTimes);
        self::assertEquals(1, $group->benchmarkMethods[1]->numberOfTimes);
    }

    public function testNumberOfTimesOnMethodWithInt(): void
    {
        $class = new #[NumberOfTimes(2)] class() {
            #[Benchmark]
            #[NumberOfTimes(5)]
            public function doBenchmark(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(1, $group->benchmarkMethods);
        self::assertEquals(5, $group->benchmarkMethods[0]->numberOfTimes);
    }
}
