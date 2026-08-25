<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
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
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(Formatter::class)]
class BenchmarkRunnerSortingAndDescriptionMethodsTest extends TestCase
{
    protected BenchmarkResults $benchmarkResults;

    protected function setUp(): void
    {
        parent::setUp();
        $this->benchmarkResults = new BenchmarkResults('foo', 'bar');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->benchmarkResults);
    }

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

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(3, $runner->benchmarkMethods);

        self::assertEquals('Do bench two', $runner->benchmarkMethods[0]->description);
        self::assertEquals('doBenchTwo', $runner->benchmarkMethods[0]->targetReflectionMethod->name);
        self::assertEquals(3, $runner->benchmarkMethods[0]->priority);

        self::assertEquals('Do bench three', $runner->benchmarkMethods[1]->description);
        self::assertEquals('doBenchThree', $runner->benchmarkMethods[1]->targetReflectionMethod->name);
        self::assertEquals(2, $runner->benchmarkMethods[1]->priority);

        self::assertEquals('Lorem ipsum', $runner->benchmarkMethods[2]->description);
        self::assertEquals('doBenchOne', $runner->benchmarkMethods[2]->targetReflectionMethod->name);
        self::assertEquals(-1, $runner->benchmarkMethods[2]->priority);
    }
}
