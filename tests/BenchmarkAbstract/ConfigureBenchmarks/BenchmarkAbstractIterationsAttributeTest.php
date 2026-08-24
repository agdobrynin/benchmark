<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkAbstract\ConfigureBenchmarks;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Iterations;
use Kaspi\Benchmark\BenchmarkAbstract;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkResults::class)]
#[CoversClass(BenchmarkAbstract::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(Iterations::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(Formatter::class)]
class BenchmarkAbstractIterationsAttributeTest extends TestCase
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
        $class = new class($this->benchmarkResults) extends BenchmarkAbstract {
            public readonly int $iterationsOnClass;

            /** @var list<BenchmarkMethod> */
            public readonly array $benchmarkMethods;

            #[Benchmark]
            public function doBench(): void {}
        };

        self::assertEquals(1, $class->iterationsOnClass);
        self::assertEquals(1, $class->benchmarkMethods[0]->iterations);
    }

    public function testIterationsOnClassWithNegativeInt(): void
    {
        $class = new
        #[Iterations(-10)]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            public readonly int $iterationsOnClass;
        };

        self::assertEquals(1, $class->iterationsOnClass);
    }

    public function testIterationsOnClassWithInt(): void
    {
        $class = new
        #[Iterations(10)]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            public readonly int $iterationsOnClass;
        };

        self::assertEquals(10, $class->iterationsOnClass);
    }

    public function testIterationsOnMethodWithNegativeInt(): void
    {
        $class = new
        #[Iterations(10)]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            public readonly int $iterationsOnClass;

            /** @var list<BenchmarkMethod> */
            public readonly array $benchmarkMethods;

            #[Benchmark]
            #[Iterations(-10)]
            public function doBenchmark(): void {}
        };

        self::assertEquals(10, $class->iterationsOnClass);
        self::assertEquals(1, $class->benchmarkMethods[0]->iterations);
    }

    public function testIterationsOnMethodWithInt(): void
    {
        $class = new
        #[Iterations(10)]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            public readonly int $iterationsOnClass;

            /** @var list<BenchmarkMethod> */
            public readonly array $benchmarkMethods;

            #[Benchmark]
            #[Iterations(2)]
            public function doBenchmark(): void {}
        };

        self::assertEquals(10, $class->iterationsOnClass);
        self::assertEquals(2, $class->benchmarkMethods[0]->iterations);
    }
}
