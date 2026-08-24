<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkAbstract\ConfigureBenchmarks;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\BenchmarkAbstract;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkAbstract::class)]
#[CoversClass(BenchmarkResults::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(Formatter::class)]
class BenchmarkAbstractSortingAndDescriptionMethodsTest extends TestCase
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
        $class = new class($this->benchmarkResults) extends BenchmarkAbstract {
            /** @var list<BenchmarkMethod> */
            public readonly array $benchmarkMethods;

            #[Benchmark('Lorem ipsum', priority: -1)]
            public function doBenchOne(): void {}

            #[Benchmark(priority: 3)]
            public function doBenchTwo(): void {}

            #[Benchmark(priority: 2)]
            public function doBenchThree(): void {}
        };

        self::assertCount(3, $class->benchmarkMethods);

        self::assertEquals('Do bench two', $class->benchmarkMethods[0]->description);
        self::assertEquals('doBenchTwo', $class->benchmarkMethods[0]->targetReflectionMethod->name);
        self::assertEquals(3, $class->benchmarkMethods[0]->priority);

        self::assertEquals('Do bench three', $class->benchmarkMethods[1]->description);
        self::assertEquals('doBenchThree', $class->benchmarkMethods[1]->targetReflectionMethod->name);
        self::assertEquals(2, $class->benchmarkMethods[1]->priority);

        self::assertEquals('Lorem ipsum', $class->benchmarkMethods[2]->description);
        self::assertEquals('doBenchOne', $class->benchmarkMethods[2]->targetReflectionMethod->name);
        self::assertEquals(-1, $class->benchmarkMethods[2]->priority);
    }
}
