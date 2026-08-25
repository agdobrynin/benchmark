<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\AfterMethod;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function array_column;

/**
 * @internal
 */
#[CoversClass(BenchmarkRunner::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(AfterMethod::class)]
#[CoversClass(BenchmarkMethod::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(Formatter::class)]
class BenchmarkRunnerAfterMethodAttributeTest extends TestCase
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

        $class = new #[AfterMethod('unknownMethod')] class() {};
        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testInvalidAfterMethodAttributeOnClassEmptyStringMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[AfterMethod('')] class() {};
        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testInvalidAfterMethodAttributeOnClassArrayWithUnknownMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[AfterMethod(['unknownMethod'])] class() {};
        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testInvalidAfterMethodAttributeOnClassArrayWithEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[AfterMethod([''])] class {};
        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testInvalidAfterMethodAttributeOnMethodUnknownName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new class {
            #[Benchmark]
            #[AfterMethod(['unknownMethod'])]
            public function doBenchmark(): void {}
        };

        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testConfiguredAfterMethodAttributeOnClassOnly(): void
    {
        $class = new #[AfterMethod(['foo', 'bar', 'baz'])] class() {
            private function foo(): void {}

            protected function bar(): void {}

            public function baz(): void {}

            #[Benchmark]
            public function doBench(): void {}
        };

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(1, $runner->benchmarkMethods);
        self::assertEquals(['foo', 'bar', 'baz'], array_column($runner->benchmarkMethods[0]->afterReflectionMethod, 'name'));
        self::assertCount(0, $runner->benchmarkMethods[0]->beforeReflectionMethod);
    }

    public function testConfiguredAfterMethodAttributeOnClassAndOnMethods(): void
    {
        $class = new #[AfterMethod(['baz'])] class() {
            private function foo(): void {}

            protected function bar(): void {}

            public function baz(): void {}

            #[Benchmark]
            #[AfterMethod(['foo', 'bar'])]
            public function doBenchQux(): void {}
        };

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(1, $runner->benchmarkMethods);
        $methods = array_column($runner->benchmarkMethods[0]->afterReflectionMethod, 'name');
        self::assertEquals(['foo', 'bar'], $methods);
        self::assertCount(0, $runner->benchmarkMethods[0]->beforeReflectionMethod);
    }
}
