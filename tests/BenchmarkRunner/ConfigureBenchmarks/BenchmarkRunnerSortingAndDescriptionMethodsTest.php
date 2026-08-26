<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Kaspi\Benchmark\Attributes\Benchmark;
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
#[UsesClass(BenchmarkGroup::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(Formatter::class)]
class BenchmarkRunnerSortingAndDescriptionMethodsTest extends TestCase
{
    public function testSort(): void
    {
        $class = new class {
            #[Benchmark('Lorem ipsum', priority: -1)]
            public function doBenchOne(): void {}

            #[Benchmark(priority: 3)]
            public function doBenchTwo(): void {}

            #[Benchmark(priority: 2)]
            public function doBenchThree(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(3, $group->benchmarkMethods);

        $methods = $group->benchmarkMethods;

        self::assertEquals('Do bench two', $methods[0]->description);
        self::assertEquals('doBenchTwo', $methods[0]->targetReflectionMethod->name);
        self::assertEquals(3, $methods[0]->priority);

        self::assertEquals('Do bench three', $methods[1]->description);
        self::assertEquals('doBenchThree', $methods[1]->targetReflectionMethod->name);
        self::assertEquals(2, $methods[1]->priority);

        self::assertEquals('Lorem ipsum', $methods[2]->description);
        self::assertEquals('doBenchOne', $methods[2]->targetReflectionMethod->name);
        self::assertEquals(-1, $methods[2]->priority);
    }
}
