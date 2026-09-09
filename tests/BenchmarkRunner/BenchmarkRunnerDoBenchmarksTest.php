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
use Kaspi\Benchmark\Attributes\RequiresPhp;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkResultsFile;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkGroup;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\DTO\EnvBenchmark;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\Formatter;
use Kaspi\Benchmark\Services\BenchmarkMetricsCollector;
use Kaspi\Benchmark\VO\BenchmarkTimeExecuteMemoryUsage;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use const PHP_VERSION_ID;

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
#[UsesClass(RequiresPhp::class)]
#[UsesClass(Formatter::class)]
#[UsesClass(EnvBenchmark::class)]
#[UsesClass(BenchmarkResultsFile::class)]
#[UsesClass(BenchmarkTimeExecuteMemoryUsage::class)]
class BenchmarkRunnerDoBenchmarksTest extends TestCase
{
    protected EnvBenchmark $env;

    protected function setUp(): void
    {
        parent::setUp();
        $this->env = new EnvBenchmark(PHP_VERSION_ID, false);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->env);
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

        (new BenchmarkRunner('foo', $this->env, $class))
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

        (new BenchmarkRunner('foo', $this->env, $class))
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

        (new BenchmarkRunner('foo', $this->env, $class))
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

        (new BenchmarkRunner('foo', $this->env, $class))
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

        $benchResults = (new BenchmarkRunner('foo', $this->env, $class))
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

        (new BenchmarkRunner('foo', $this->env, $class))
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

        (new BenchmarkRunner('v1.x-dev', $this->env, $class))
            ->doBenchmarks()
            ->current()
        ;
    }

    public function testEmptyBenchmarkMethods(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Benchmark methods not found in the class');

        (new BenchmarkRunner('foo', $this->env, new class {}))
            ->doBenchmarks()
            ->valid()
        ;
    }

    public function testSkepBenchmarkWithRequiresPhp(): void
    {
        $classOne = new #[RequiresPhp('20')] class {
            #[Benchmark]
            public function doBenchOne(): void {}
        };

        $classTwo = new class {
            #[Benchmark]
            #[RequiresPhp('22', '>=')]
            public function doBenchOne(): void {}

            #[Benchmark]
            public function doBenchTwo(): void {}
        };

        $this->expectOutputRegex('/(requires PHP version equals 20).*(requires PHP version greater than or equals 22)/sui');

        (new BenchmarkRunner('v1.x-dev', $this->env, $classOne, $classTwo))
            ->doBenchmarks()
            ->valid()
        ;
    }

    public function testRunner(): void
    {
        $classOne = new #[Iterations(8)] #[Group('Class One')] class {
            #[Benchmark]
            public function doBenchOne(): void {}
        };

        $classTwo = new #[Group('Class Two')] class {
            #[Benchmark]
            #[Iterations(3)]
            public function doBenchOne(): void {}

            #[Benchmark]
            #[Iterations(5)]
            public function doBenchTwo(): void {}
        };

        vfsStream::setup('var');
        $runner = new BenchmarkRunner('v1.x-dev', $this->env, $classOne, $classTwo);
        $runner->showProgressBar(false);

        $benchResultsFileSaver = new BenchmarkResultsFile(vfsStream::url('var/results.json'));

        foreach ($runner->doBenchmarks() as $result) {
            $benchResultsFileSaver->attach($result);
        }

        $benchResultsFileSaver->save();

        $benchResultsFileReader = new BenchmarkResultsFile(vfsStream::url('var/results.json'));

        $results = $benchResultsFileReader->read();

        /** @var BenchmarkResults $res1 */
        $res1 = $results->current();

        self::assertEquals('Class One', $res1->groupName);

        $itemsRes1 = $res1->getBenchmarkTimeExecuteMemoryUsageItems();
        self::assertEquals('Do bench one', $itemsRes1->key());
        self::assertEquals(8, $itemsRes1->current()->iterations);

        $itemsRes1->next();

        self::assertFalse($itemsRes1->valid());

        $results->next();

        /** @var BenchmarkResults $res2 */
        $res2 = $results->current();

        self::assertEquals('Class Two', $res2->groupName);

        $itemsRes2 = $res2->getBenchmarkTimeExecuteMemoryUsageItems();
        self::assertEquals('Do bench one', $itemsRes2->key());
        self::assertEquals(3, $itemsRes2->current()->iterations);

        $itemsRes2->next();

        self::assertEquals('Do bench two', $itemsRes2->key());
        self::assertEquals(5, $itemsRes2->current()->iterations);

        $itemsRes2->next();

        self::assertFalse($itemsRes2->valid());
    }
}
