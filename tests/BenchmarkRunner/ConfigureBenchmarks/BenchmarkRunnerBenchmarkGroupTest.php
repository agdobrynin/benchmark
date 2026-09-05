<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Group;
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
#[CoversClass(Benchmark::class)]
#[CoversClass(Group::class)]
#[CoversClass(BenchmarkRunner::class)]
#[CoversClass(BenchmarkGroup::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(BenchmarkMethod::class)]
#[UsesClass(Formatter::class)]
#[UsesClass(EnvBenchmark::class)]
#[UsesClass(EnvParams::class)]
class BenchmarkRunnerBenchmarkGroupTest extends TestCase
{
    public function testBenchmarkRunnerGroup(): void
    {
        $classFoo = new #[Group('Foo description')] class() {
            #[Benchmark]
            public function doNothingOne(): void {}

            #[Benchmark]
            public function doNothingTwo(): void {}
        };

        $classBar = new #[Group('Bar description')] class() {
            #[Benchmark]
            public function doNothingOne(): void {}

            #[Benchmark]
            public function doNothingTwo(): void {}
        };

        $runner = new BenchmarkRunner('v0.0.1', $classFoo, $classBar);

        $runner->doBenchmarks();

        self::assertCount(2, $runner->benchmarkGroups);

        $group1 = $runner->benchmarkGroups[0];
        self::assertEquals('Foo description', $group1->name);

        $group2 = $runner->benchmarkGroups[1];
        self::assertEquals('Bar description', $group2->name);
    }

    public function testBenchmarkGroupIsEmptyString(): void
    {
        $class = new #[Group('')] class() {
            #[Benchmark]
            public function doNothing(): void {}
        };

        $runner = new BenchmarkRunner('v0.0.1', $class);

        self::assertMatchesRegularExpression('/^class@anonymous.+BenchmarkRunnerBenchmarkGroupTest\.php/', $runner->benchmarkGroups[0]->name);
    }

    public function testBenchmarkGroupNotDefined(): void
    {
        $class = new class {
            #[Benchmark]
            public function doNothing(): void {}
        };

        $benchFoo = new BenchFoo();

        $runner = new BenchmarkRunner('v0.0.1', $class, $benchFoo);

        self::assertCount(2, $runner->benchmarkGroups);
        self::assertMatchesRegularExpression('/^class@anonymous.+BenchmarkRunnerBenchmarkGroupTest\.php/', $runner->benchmarkGroups[0]->name);
        self::assertEquals('Bench foo', $runner->benchmarkGroups[1]->name);
    }
}

final class BenchFoo
{
    #[Benchmark]
    public function doNothing(): void {}
}
