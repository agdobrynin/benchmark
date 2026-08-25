<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Iterations;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkResults::class)]
#[CoversClass(BenchmarkRunner::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(Iterations::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(Formatter::class)]
class BenchmarkRunnerIterationsAttributeTest extends TestCase
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

    public function testIterationsNotDefined(): void
    {
        $class = new class {
            #[Benchmark]
            public function doBench(): void {}
        };

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(1, $runner->benchmarkMethods);
        self::assertEquals(1, $runner->benchmarkMethods[0]->iterations);
    }

    public function testIterationsOnClassWithNegativeInt(): void
    {
        $class = new #[Iterations(-10)] class() {
            #[Benchmark]
            public function doBench(): void {}
        };

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(1, $runner->benchmarkMethods);
        self::assertEquals(1, $runner->benchmarkMethods[0]->iterations);
    }

    public function testIterationsOnClassWithInt(): void
    {
        $class = new #[Iterations(10)] class() {
            #[Benchmark]
            public function doBench(): void {}
        };

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(1, $runner->benchmarkMethods);
        self::assertEquals(10, $runner->benchmarkMethods[0]->iterations);
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

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(2, $runner->benchmarkMethods);
        self::assertEquals(1, $runner->benchmarkMethods[0]->iterations);
        self::assertEquals(10, $runner->benchmarkMethods[1]->iterations);
    }

    public function testIterationsOnMethodWithInt(): void
    {
        $class = new #[Iterations(10)] class() {
            #[Benchmark]
            #[Iterations(2)]
            public function doBenchmark(): void {}
        };

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(1, $runner->benchmarkMethods);
        self::assertEquals(2, $runner->benchmarkMethods[0]->iterations);
    }
}
