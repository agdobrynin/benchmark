<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkAbstract\ConfigureBenchmarks;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\NumberOfTimes;
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
#[CoversClass(NumberOfTimes::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(Formatter::class)]
class BenchmarkAbstractNumberOfTimesAttributeTest extends TestCase
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
        $class = new class($this->benchmarkResults) extends BenchmarkAbstract {
            public readonly int $numberOfTimesOnClass;

            /** @var list<BenchmarkMethod> */
            public readonly array $benchmarkMethods;

            #[Benchmark]
            public function doBench(): void {}
        };

        self::assertEquals(1, $class->numberOfTimesOnClass);
        self::assertEquals(1, $class->benchmarkMethods[0]->numberOfTimes);
    }

    public function testNumberOfTimesOnClassWithNegativeInt(): void
    {
        $class = new
        #[NumberOfTimes(-2)]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            public readonly int $numberOfTimesOnClass;
        };

        self::assertEquals(1, $class->numberOfTimesOnClass);
    }

    public function testNumberOfTimesOnClassWithInt(): void
    {
        $class = new
        #[NumberOfTimes(2)]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            public readonly int $numberOfTimesOnClass;
        };

        self::assertEquals(2, $class->numberOfTimesOnClass);
    }

    public function testNumberOfTImesOnMethodWithNegativeInt(): void
    {
        $class = new
        #[NumberOfTimes(2)]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            public readonly int $numberOfTimesOnClass;

            /** @var list<BenchmarkMethod> */
            public readonly array $benchmarkMethods;

            #[Benchmark]
            #[NumberOfTimes(-10)]
            public function doBenchmark(): void {}
        };

        self::assertEquals(2, $class->numberOfTimesOnClass);
        self::assertEquals(1, $class->benchmarkMethods[0]->numberOfTimes);
    }

    public function testNumberOfTimesOnMethodWithInt(): void
    {
        $class = new
        #[NumberOfTimes(2)]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            public readonly int $numberOfTimesOnClass;

            /** @var list<BenchmarkMethod> */
            public readonly array $benchmarkMethods;

            #[Benchmark]
            #[NumberOfTimes(5)]
            public function doBenchmark(): void {}
        };

        self::assertEquals(2, $class->numberOfTimesOnClass);

        self::assertCount(1, $class->benchmarkMethods);
        self::assertEquals(5, $class->benchmarkMethods[0]->numberOfTimes);
    }
}
