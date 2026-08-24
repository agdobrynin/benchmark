<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkAbstract\ConfigureBenchmarks;

use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\AfterMethod;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\BenchmarkAbstract;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function array_column;

/**
 * @internal
 */
#[CoversClass(BenchmarkAbstract::class)]
#[CoversClass(AfterMethod::class)]
#[CoversClass(BenchmarkResults::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(Formatter::class)]
class BenchmarkAbstractAfterMethodAttributeTest extends TestCase
{
    protected const EXCEPTION_MESSAGE = 'The value of parameter `$afterMethod` must be a non-empty string or a non-empty list of strings. Each value must refer to an existing class method.';
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

    public function testInvalidAfterMethodAttributeOnClassUnknownMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new
        #[AfterMethod('unknownMethod')]
        class($this->benchmarkResults) extends BenchmarkAbstract {};
    }

    public function testInvalidAfterMethodAttributeOnClassEmptyStringMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new
        #[AfterMethod('')]
        class($this->benchmarkResults) extends BenchmarkAbstract {};
    }

    public function testInvalidAfterMethodAttributeOnClassArrayWithUnknownMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new
        #[AfterMethod(['unknownMethod'])]
        class($this->benchmarkResults) extends BenchmarkAbstract {};
    }

    public function testInvalidAfterMethodAttributeOnClassArrayWithEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new
        #[AfterMethod([''])]
        class($this->benchmarkResults) extends BenchmarkAbstract {};
    }

    public function testInvalidAfterMethodAttributeOnMethodUnknownName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new class($this->benchmarkResults) extends BenchmarkAbstract {
            #[Benchmark]
            #[AfterMethod(['unknownMethod'])]
            public function doBenchmark(): void {}
        };
    }

    public function testConfiguredAfterMethodAttributeOnClassOnly(): void
    {
        $class = new
        #[AfterMethod(['foo', 'bar', 'baz'])]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            /** @var list<ReflectionMethod> */
            public readonly array $afterMethodOnClass;

            private function foo(): void {}

            protected function bar(): void {}

            public function baz(): void {}
        };

        self::assertEquals(['foo', 'bar', 'baz'], array_column($class->afterMethodOnClass, 'name'));
    }

    public function testConfiguredAfterMethodAttributeOnClassAndOnMethods(): void
    {
        $class = new
        #[AfterMethod(['baz'])]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            /** @var list<ReflectionMethod> */
            public readonly array $afterMethodOnClass;

            /** @var list<BenchmarkMethod> */
            public readonly array $benchmarkMethods;

            private function foo(): void {}

            protected function bar(): void {}

            public function baz(): void {}

            #[Benchmark]
            #[AfterMethod(['foo', 'bar'])]
            public function doBenchQux(): void {}
        };

        self::assertCount(1, $class->afterMethodOnClass);
        self::assertEquals('baz', $class->afterMethodOnClass[0]->name);

        self::assertCount(1, $class->benchmarkMethods);

        self::assertCount(2, $class->benchmarkMethods[0]->afterReflectionMethod);
        self::assertEquals('foo', $class->benchmarkMethods[0]->afterReflectionMethod[0]->name);
        self::assertEquals('bar', $class->benchmarkMethods[0]->afterReflectionMethod[1]->name);
    }
}
