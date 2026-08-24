<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkAbstract\ConfigureBenchmarks;

use Generator;
use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Parameters;
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
#[CoversClass(Parameters::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(Formatter::class)]
#[CoversClass(BenchmarkMethod::class)]
class BenchmarkAbstractParametersAttributeTest extends TestCase
{
    protected const EXCEPTION_MESSAGE = 'Parameters for the benchmark method must be of a callable type or a list of callable types';
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

    public function testInvalidParametersAttributeOnClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new
        #[Parameters(['wrong'])]
        class($this->benchmarkResults) extends BenchmarkAbstract {};
    }

    public function testInvalidParametersAttributeOnMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new class($this->benchmarkResults) extends BenchmarkAbstract {
            #[Benchmark]
            #[Parameters(['wrong'])]
            public function doBenchmark(): void {}
        };
    }

    public function testParametersAttributeOnClassAndMethod(): void
    {
        $class = new
        #[Parameters('\uniqid')]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            /** @var list<BenchmarkMethod> */
            public readonly array $benchmarkMethods;
            public readonly array $parametersOnClass;

            #[Benchmark]
            public function doBenchmarkOne(): void {}

            #[Benchmark]
            #[Parameters([[self::class, 'fooParams'], '\rand'])]
            public function doBenchmarkTwo(int|string $randValue): void {}

            public static function fooParams(): Generator
            {
                yield 'set #1' => ['random123'];

                yield 'set #2' => ['random456'];
            }
        };

        self::assertCount(1, $class->parametersOnClass);
        self::assertEquals('\uniqid', $class->parametersOnClass[0]);

        self::assertCount(2, $class->benchmarkMethods);

        self::assertEquals(['\uniqid'], $class->benchmarkMethods[0]->parameters);

        self::assertCount(2, $class->benchmarkMethods[1]->parameters);
        self::assertEquals([$class::class, 'fooParams'], $class->benchmarkMethods[1]->parameters[0]);
        self::assertEquals('\rand', $class->benchmarkMethods[1]->parameters[1]);
    }
}
