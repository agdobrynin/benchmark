<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkAbstract;

use Generator;
use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\AfterMethod;
use Kaspi\Benchmark\Attributes\BeforeMethod;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Iterations;
use Kaspi\Benchmark\Attributes\NumberOfTimes;
use Kaspi\Benchmark\Attributes\Parameters;
use Kaspi\Benchmark\BenchmarkAbstract;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkResults::class)]
#[CoversClass(BenchmarkAbstract::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(Parameters::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(TimeExecuteMemoryUsageIteration::class)]
#[CoversClass(AfterMethod::class)]
#[CoversClass(BeforeMethod::class)]
#[CoversClass(Iterations::class)]
#[CoversClass(NumberOfTimes::class)]
#[CoversClass(Formatter::class)]
class BenchmarkRunBenchmarkAbstractTest extends TestCase
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
        $this->expectExceptionMessage('provideParams must be return an array or Generator, got string');

        $class = new class($this->benchmarkResults, false) extends BenchmarkAbstract {
            #[Benchmark('do nothing')]
            #[Parameters([self::class, 'provideParams'])]
            public function doBenchOne(string $param): void {}

            public static function provideParams(): string
            {
                return 'bar';
            }
        };

        $class->doBenchmarks();
    }

    public function testRunBenchmarkInvalidParametersParameterGroupNameIsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter group name in the parameter source');

        $class = new class($this->benchmarkResults, false) extends BenchmarkAbstract {
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

        $class->doBenchmarks();
    }

    public function testRunBenchmarkInvalidParametersParameterGroupNameNotUnique(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter group named "dataset one" is not unique in the parameter source');

        $class = new class($this->benchmarkResults, false) extends BenchmarkAbstract {
            #[Benchmark('do nothing')]
            #[Parameters([self::class, 'provideParams'])]
            public function doBenchOne(string $param): void {}

            public static function provideParams(): Generator
            {
                yield 'dataset one' => ['str1'];

                yield 'dataset one' => ['str2'];
            }
        };

        $class->doBenchmarks();
    }

    public function testRunBenchmarkInvalidParametersParameterGroupMustReturnParamsAsArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('provideParams() must return an array containing the parameters');

        $class = new class($this->benchmarkResults, false) extends BenchmarkAbstract {
            #[Benchmark('do nothing')]
            #[Parameters([self::class, 'provideParams'])]
            public function doBenchOne(string $param): void {}

            public static function provideParams(): Generator
            {
                yield 'dataset one' => 'str1';
            }
        };

        $class->doBenchmarks();
    }

    public function testRunBenchmarkParametersParameterGroupNameAsIntegerOrString(): void
    {
        $class = new class($this->benchmarkResults, false) extends BenchmarkAbstract {
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

        $results = $class->doBenchmarks()->getResults();

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
        $class = new class($this->benchmarkResults, false) extends BenchmarkAbstract {
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

        $class->doBenchmarks();

        self::assertEquals(['beforeCall', 'afterCall'], $class->methodCalls);
    }

    public function testProgressBar(): void
    {
        $class = new class($this->benchmarkResults, true) extends BenchmarkAbstract {
            #[Benchmark('do nothing')]
            #[Iterations(5)]
            #[NumberOfTimes(2)]
            public function doBenchOne(): void {}
        };

        $this->expectOutputRegex('/\[bar\] do nothing\..+ \[([=]+)\] 100%/');

        $class->doBenchmarks();
    }
}
