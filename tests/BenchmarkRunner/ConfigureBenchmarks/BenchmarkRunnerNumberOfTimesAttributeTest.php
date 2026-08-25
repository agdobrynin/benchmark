<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\NumberOfTimes;
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
#[CoversClass(NumberOfTimes::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(Formatter::class)]
class BenchmarkRunnerNumberOfTimesAttributeTest extends TestCase
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

    public function testNumberOfTimesNotDefined(): void
    {
        $class = new class {
            #[Benchmark]
            public function doBenchOne(): void {}

            #[Benchmark]
            #[NumberOfTimes(5)]
            public function doBenchTwo(): void {}
        };

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(2, $runner->benchmarkMethods);

        self::assertEquals(1, $runner->benchmarkMethods[0]->numberOfTimes);
        self::assertEquals(5, $runner->benchmarkMethods[1]->numberOfTimes);
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

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(2, $runner->benchmarkMethods);
        self::assertEquals(1, $runner->benchmarkMethods[0]->numberOfTimes);
        self::assertEquals(1, $runner->benchmarkMethods[1]->numberOfTimes);
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

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(2, $runner->benchmarkMethods);
        self::assertEquals(2, $runner->benchmarkMethods[0]->numberOfTimes);
        self::assertEquals(5, $runner->benchmarkMethods[1]->numberOfTimes);
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

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(2, $runner->benchmarkMethods);
        self::assertEquals(2, $runner->benchmarkMethods[0]->numberOfTimes);
        self::assertEquals(1, $runner->benchmarkMethods[1]->numberOfTimes);
    }

    public function testNumberOfTimesOnMethodWithInt(): void
    {
        $class = new #[NumberOfTimes(2)] class() {
            #[Benchmark]
            #[NumberOfTimes(5)]
            public function doBenchmark(): void {}
        };

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(1, $runner->benchmarkMethods);
        self::assertEquals(5, $runner->benchmarkMethods[0]->numberOfTimes);
    }
}
