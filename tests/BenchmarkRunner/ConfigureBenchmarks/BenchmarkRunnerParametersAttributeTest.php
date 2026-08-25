<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Generator;
use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Parameters;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkRunner::class)]
#[CoversClass(BenchmarkResults::class)]
#[CoversClass(Parameters::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(Formatter::class)]
#[CoversClass(BenchmarkMethod::class)]
class BenchmarkRunnerParametersAttributeTest extends TestCase
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

        $class = new #[Parameters(['wrong'])] class() {};

        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testInvalidParametersAttributeOnMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new class {
            #[Benchmark]
            #[Parameters(['wrong'])]
            public function doBenchmark(): void {}
        };

        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testParametersAttributeOnClassAndMethod(): void
    {
        $class = new #[Parameters('\uniqid')] class() {
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

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertEquals(['\uniqid'], $runner->benchmarkMethods[0]->parameters);
        self::assertEquals([$class::class, 'fooParams'], $runner->benchmarkMethods[1]->parameters[0]);
        self::assertEquals('\rand', $runner->benchmarkMethods[1]->parameters[1]);
    }
}
