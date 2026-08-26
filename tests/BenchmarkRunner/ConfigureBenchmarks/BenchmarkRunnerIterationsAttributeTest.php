<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Iterations;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkGroup;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkRunner::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(Iterations::class)]
#[UsesClass(BenchmarkGroup::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(Formatter::class)]
class BenchmarkRunnerIterationsAttributeTest extends TestCase
{
    public function testIterationsNotDefined(): void
    {
        $class = new class {
            #[Benchmark]
            public function doBench(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(1, $group->benchmarkMethods);
        self::assertEquals(1, $group->benchmarkMethods[0]->iterations);
    }

    public function testIterationsOnClassWithNegativeInt(): void
    {
        $class = new #[Iterations(-10)] class() {
            #[Benchmark]
            public function doBench(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);
        self::assertCount(1, $runner->benchmarkGroups[0]->benchmarkMethods);
        self::assertEquals(1, $runner->benchmarkGroups[0]->benchmarkMethods[0]->iterations);
    }

    public function testIterationsOnClassWithInt(): void
    {
        $class = new #[Iterations(10)] class() {
            #[Benchmark]
            public function doBench(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(1, $group->benchmarkMethods);
        self::assertEquals(10, $group->benchmarkMethods[0]->iterations);
    }

    public function testIterationsOnMethodWithNegativeInt(): void
    {
        $class = new #[Iterations(10)] class() {
            #[Benchmark]
            #[Iterations(-10)]
            public function doBenchOne(): void {}

            #[Benchmark]
            public function doBenchTwo(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(2, $group->benchmarkMethods);
        self::assertEquals(1, $group->benchmarkMethods[0]->iterations);
        self::assertEquals(10, $group->benchmarkMethods[1]->iterations);
    }

    public function testIterationsOnMethodWithInt(): void
    {
        $class = new #[Iterations(10)] class() {
            #[Benchmark]
            #[Iterations(2)]
            public function doBenchmark(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(1, $group->benchmarkMethods);
        self::assertEquals(2, $group->benchmarkMethods[0]->iterations);
    }
}
