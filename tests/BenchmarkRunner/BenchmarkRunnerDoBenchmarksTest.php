<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner;

use Generator;
use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\AfterMethod;
use Kaspi\Benchmark\Attributes\BeforeMethod;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Group;
use Kaspi\Benchmark\Attributes\Iterations;
use Kaspi\Benchmark\Attributes\NumberOfTimes;
use Kaspi\Benchmark\Attributes\Parameters;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkGroup;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\DTO\EnvBenchmark;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\Formatter;
use Kaspi\Benchmark\Services\BenchmarkMetricsCollector;
use Kaspi\Benchmark\Services\EnvParams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 */
#[CoversClass(BenchmarkRunner::class)]
#[UsesClass(BenchmarkMetricsCollector::class)]
#[UsesClass(Group::class)]
#[UsesClass(BenchmarkGroup::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(Benchmark::class)]
#[UsesClass(Parameters::class)]
#[UsesClass(BenchmarkMethod::class)]
#[UsesClass(TimeExecuteMemoryUsageInIteration::class)]
#[UsesClass(AfterMethod::class)]
#[UsesClass(BeforeMethod::class)]
#[UsesClass(Iterations::class)]
#[UsesClass(NumberOfTimes::class)]
#[UsesClass(Formatter::class)]
#[UsesClass(EnvBenchmark::class)]
#[UsesClass(EnvParams::class)]
class BenchmarkRunnerDoBenchmarksTest extends TestCase
{
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

        (new BenchmarkRunner('foo', $class))
            ->showProgressBar(false)
            ->doBenchmarks()
            ->valid()
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

        (new BenchmarkRunner('foo', $class))
            ->showProgressBar(false)
            ->doBenchmarks()
            ->valid()
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

        (new BenchmarkRunner('foo', $class))
            ->showProgressBar(false)
            ->doBenchmarks()
            ->valid()
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

        (new BenchmarkRunner('foo', $class))
            ->showProgressBar(false)
            ->doBenchmarks()
            ->valid()
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

        $benchResults = (new BenchmarkRunner('foo', $class))
            ->showProgressBar(false)
            ->doBenchmarks()
        ;

        $results = $benchResults->current()->getResults();

        self::assertTrue($results->valid());
        self::assertEquals('do nothing with \'Data set #0\'', $results->key());

        $results->next();

        self::assertEquals('do nothing with \'Data set #1\'', $results->key());

        $results->next();

        self::assertEquals('do nothing with \'foo parameter\'', $results->key());

        $results->next();
        self::assertFalse($results->valid());

        $benchResults->next();

        self::assertFalse($benchResults->valid());
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

        (new BenchmarkRunner('foo', $class))
            ->showProgressBar(false)
            ->doBenchmarks()
            ->current()
        ;

        self::assertEquals(['beforeCall', 'afterCall'], $class->methodCalls);
    }

    public function testProgressBar(): void
    {
        $class = new #[Group('Foo group')] class {
            #[Benchmark('do nothing one')]
            #[Iterations(5)]
            public function doBenchOne(): void {}

            #[Benchmark('do nothing two')]
            #[Iterations(5)]
            public function doBenchTow(): void {}
        };

        $this->expectOutputRegex('/\n\rv1\.x-dev \[Foo group\]\n\n\rdo nothing one\..+ \[([=]+)\] 100%\n\rdo nothing two\..+ \[([=]+)\] 100%/');

        (new BenchmarkRunner('v1.x-dev', $class))
            ->doBenchmarks()
            ->current()
        ;
    }

    public function testEmptyBenchmarkMethods(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Benchmark methods not found in the class');

        (new BenchmarkRunner('foo', new class {}))
            ->doBenchmarks()
            ->valid()
        ;
    }
}
