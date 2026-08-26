<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Generator;
use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Parameters;
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
#[CoversClass(Parameters::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(BenchmarkMethod::class)]
#[UsesClass(Formatter::class)]
#[UsesClass(BenchmarkGroup::class)]
#[UsesClass(BenchmarkResults::class)]
class BenchmarkRunnerParametersAttributeTest extends TestCase
{
    protected const EXCEPTION_MESSAGE = 'Parameters for the benchmark method must be of a callable type or a list of callable types';

    public function testInvalidParametersAttributeOnClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[Parameters(['wrong'])] class() {};

        new BenchmarkRunner('foo', $class);
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

        new BenchmarkRunner('foo', $class);
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

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $group = $runner->benchmarkGroups[0];

        self::assertCount(2, $group->benchmarkMethods);

        $methods = $group->benchmarkMethods;

        self::assertEquals(['\uniqid'], $methods[0]->parameters);

        self::assertEquals([$class::class, 'fooParams'], $methods[1]->parameters[0]);
        self::assertEquals('\rand', $methods[1]->parameters[1]);
    }
}
