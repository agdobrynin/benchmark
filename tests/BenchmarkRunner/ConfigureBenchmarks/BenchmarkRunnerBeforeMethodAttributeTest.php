<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\BeforeMethod;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkRunner::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(BeforeMethod::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(Formatter::class)]
class BenchmarkRunnerBeforeMethodAttributeTest extends TestCase
{
    protected const EXCEPTION_MESSAGE = 'The value of parameter `$beforeMethod` must be a non-empty string or a non-empty list of strings. Each value must refer to an existing class method.';
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

    public function testInvalidBeforeMethodAttributeOnClassUnknownMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[BeforeMethod('unknownMethod')] class() {};
        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testInvalidBeforeMethodAttributeOnClassEmptyStringMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[BeforeMethod('')] class() {};
        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testInvalidBeforeMethodAttributeOnClassArrayWithUnknownMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[BeforeMethod(['unknownMethod'])] class() {};
        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testInvalidBeforeMethodAttributeOnClassArrayWithEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[BeforeMethod([''])] class() {};
        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testInvalidBeforeMethodAttributeOnMethodUnknownName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new class {
            #[Benchmark]
            #[BeforeMethod(['unknownMethod'])]
            public function doBenchmark(): void {}
        };

        new BenchmarkRunner($this->benchmarkResults, $class);
    }

    public function testConfiguredBeforeMethodAttributeOnClassAndOnMethods(): void
    {
        $class = new #[BeforeMethod(['baz'])] class() {
            private function foo(): void {}

            protected function bar(): void {}

            public function baz(): void {}

            #[Benchmark]
            #[BeforeMethod(['foo', 'bar'])]
            public function doBenchQux(): void {}

            #[Benchmark]
            public function doBenchQuz(): void {}
        };

        $runner = new BenchmarkRunner($this->benchmarkResults, $class);

        self::assertCount(2, $runner->benchmarkMethods);

        self::assertCount(2, $runner->benchmarkMethods[0]->beforeReflectionMethod);
        self::assertEquals('foo', $runner->benchmarkMethods[0]->beforeReflectionMethod[0]->name);
        self::assertEquals('bar', $runner->benchmarkMethods[0]->beforeReflectionMethod[1]->name);

        self::assertCount(1, $runner->benchmarkMethods[1]->beforeReflectionMethod);
        self::assertEquals('baz', $runner->benchmarkMethods[1]->beforeReflectionMethod[0]->name);
    }
}
