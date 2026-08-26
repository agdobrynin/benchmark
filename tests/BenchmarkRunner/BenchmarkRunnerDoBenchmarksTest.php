<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner;

use Generator;
use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\AfterMethod;
use Kaspi\Benchmark\Attributes\BeforeMethod;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Iterations;
use Kaspi\Benchmark\Attributes\NumberOfTimes;
use Kaspi\Benchmark\Attributes\Parameters;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 */
#[CoversClass(BenchmarkRunner::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(Benchmark::class)]
#[UsesClass(Parameters::class)]
#[UsesClass(BenchmarkMethod::class)]
#[UsesClass(TimeExecuteMemoryUsageIteration::class)]
#[UsesClass(AfterMethod::class)]
#[UsesClass(BeforeMethod::class)]
#[UsesClass(Iterations::class)]
#[UsesClass(NumberOfTimes::class)]
#[UsesClass(Formatter::class)]
class BenchmarkRunnerDoBenchmarksTest extends TestCase
{
    protected BenchmarkResults $benchmarkResults;

    protected function setUp(): void
    {
        parent::setUp();
        $this->benchmarkResults = new BenchmarkResults('foo', 'bar');
    }

    public function testRunBenchmarkInvalidParametersReturnType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('provideParams() must be return an array or Generator, got string');

        $class = new class {
            #[Benchmark('do nothing')]
            #[Parameters([self::class, 'provideParams'])]
            public function doBenchOne(string $param): void {}

            public static function provideParams(): string
            {
                return 'bar';
            }
        };

        (new BenchmarkRunner($this->benchmarkResults, $class, false))
            ->doBenchmarks()
        ;
    }

    public function testRunBenchmarkInvalidParametersParameterGroupNameIsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter group name in the parameter source');

        $class = new class {
            #[Benchmark('do nothing')]
            #[Parameters([self::class, 'provideParams'])]
            public function doBenchOne(string $param): void {}

            public static function provideParams(): array
            {
                return [
                    '' => ['baz'],
                ];
            }
        };

        (new BenchmarkRunner($this->benchmarkResults, $class, false))
            ->doBenchmarks()
        ;
    }

    public function testRunBenchmarkInvalidParametersParameterGroupNameNotUnique(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter group named "dataset one" is not unique in the parameter source');

        $class = new class {
            #[Benchmark('do nothing')]
            #[Parameters([self::class, 'provideParams'])]
            public function doBenchOne(string $param): void {}

            public static function provideParams(): Generator
            {
                yield 'dataset one' => ['str1'];

                yield 'dataset one' => ['str2'];
            }
        };

        (new BenchmarkRunner($this->benchmarkResults, $class, false))
            ->doBenchmarks()
        ;
    }

    public function testRunBenchmarkInvalidParametersParameterGroupMustReturnParamsAsArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('provideParams() must return an array containing the parameters');

        $class = new class {
            #[Benchmark('do nothing')]
            #[Parameters([self::class, 'provideParams'])]
            public function doBenchOne(string $param): void {}

            public static function provideParams(): Generator
            {
                yield 'dataset one' => 'str1';
            }
        };

        (new BenchmarkRunner($this->benchmarkResults, $class, false))
            ->doBenchmarks()
        ;
    }

    public function testRunBenchmarkParametersParameterGroupNameAsIntegerOrString(): void
    {
        $class = new class {
            #[Benchmark('do nothing')]
            #[Parameters([self::class, 'provideParams'])]
            public function doBenchOne(string $param): void {}

            public static function provideParams(): array
            {
                return [
                    ['str1'],
                    ['str2'],
                    'foo parameter' => ['foo'],
                ];
            }
        };

        $benchResults = (new BenchmarkRunner($this->benchmarkResults, $class, false))
            ->doBenchmarks()
        ;

        $results = $benchResults->getResults();

        self::assertTrue($results->valid());
        self::assertEquals('do nothing with parameters name \'Set #0\'', $results->key());

        $results->next();

        self::assertEquals('do nothing with parameters name \'Set #1\'', $results->key());

        $results->next();

        self::assertEquals('do nothing with parameters name \'foo parameter\'', $results->key());

        $results->next();
        self::assertFalse($results->valid());
    }

    public function testRunBenchmarkInvokeBeforeMethodAndAfterMethodOnBenchMethod(): void
    {
        $class = new class {
            public array $methodCalls = [];

            #[Benchmark('do nothing')]
            #[AfterMethod('afterCall')]
            #[BeforeMethod('beforeCall')]
            public function doBenchOne(): void {}

            private function beforeCall(): void
            {
                $this->methodCalls[] = __FUNCTION__;
            }

            private function afterCall(): void
            {
                $this->methodCalls[] = __FUNCTION__;
            }
        };

        (new BenchmarkRunner($this->benchmarkResults, $class, false))
            ->doBenchmarks()
        ;

        self::assertEquals(['beforeCall', 'afterCall'], $class->methodCalls);
    }

    public function testProgressBar(): void
    {
        $class = new class {
            #[Benchmark('do nothing')]
            #[Iterations(5)]
            #[NumberOfTimes(2)]
            public function doBenchOne(): void {}
        };

        $this->expectOutputRegex('/\[bar\] do nothing\..+ \[([=]+)\] 100%/');

        (new BenchmarkRunner($this->benchmarkResults, $class, true))
            ->doBenchmarks()
        ;
    }

    public function testEmptyBenchmarkMethods(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Benchmark methods not found in the class');

        (new BenchmarkRunner($this->benchmarkResults, new class {}))
            ->doBenchmarks()
        ;
    }
}
