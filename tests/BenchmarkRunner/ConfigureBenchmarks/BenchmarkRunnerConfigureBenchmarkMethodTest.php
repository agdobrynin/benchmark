<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Generator;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\EnvBenchmark;
use Kaspi\Benchmark\Services\EnvParams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 */
#[CoversClass(BenchmarkRunner::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(EnvBenchmark::class)]
#[UsesClass(EnvParams::class)]
class BenchmarkRunnerConfigureBenchmarkMethodTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testBenchmarkMethodFail(object $class): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must be declared with public and non-static modifiers.');

        new BenchmarkRunner('foo', $class);
    }

    public static function dataProvider(): Generator
    {
        yield 'public static method' => [
            new class {
                #[Benchmark]
                public static function doNothing(): void {}
            },
        ];

        yield 'protected method' => [
            new class {
                #[Benchmark]
                protected function doNothing(): void {}
            },
        ];

        yield 'private method' => [
            new class {
                #[Benchmark]
                private function doNothing(): void {}
            },
        ];
    }
}
