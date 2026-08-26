<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\BeforeMethod;
use Kaspi\Benchmark\Attributes\Benchmark;
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
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(BeforeMethod::class)]
#[UsesClass(BenchmarkGroup::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(Formatter::class)]
class BenchmarkRunnerBeforeMethodAttributeTest extends TestCase
{
    protected const EXCEPTION_MESSAGE = 'The value of parameter `$beforeMethod` must be a non-empty string or a non-empty list of strings. Each value must refer to an existing class method.';

    public function testInvalidBeforeMethodAttributeOnClassUnknownMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[BeforeMethod('unknownMethod')] class() {};
        new BenchmarkRunner('foo', $class);
    }

    public function testInvalidBeforeMethodAttributeOnClassEmptyStringMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[BeforeMethod('')] class() {};
        new BenchmarkRunner('foo', $class);
    }

    public function testInvalidBeforeMethodAttributeOnClassArrayWithUnknownMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[BeforeMethod(['unknownMethod'])] class() {};
        new BenchmarkRunner('foo', $class);
    }

    public function testInvalidBeforeMethodAttributeOnClassArrayWithEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        $class = new #[BeforeMethod([''])] class() {};
        new BenchmarkRunner('foo', $class);
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

        new BenchmarkRunner('foo', $class);
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

        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);
        self::assertCount(2, $runner->benchmarkGroups[0]->benchmarkMethods);

        $methods = $runner->benchmarkGroups[0]->benchmarkMethods;

        self::assertCount(2, $methods[0]->beforeReflectionMethod);
        self::assertEquals('foo', $methods[0]->beforeReflectionMethod[0]->name);
        self::assertEquals('bar', $methods[0]->beforeReflectionMethod[1]->name);

        $groups2 = $runner->benchmarkGroups[1];

        self::assertCount(1, $methods[1]->beforeReflectionMethod);
        self::assertEquals('baz', $methods[1]->beforeReflectionMethod[0]->name);
    }
}
