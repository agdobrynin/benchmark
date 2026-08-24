<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkAbstract\ConfigureBenchmarks;

use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\BeforeMethod;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\BenchmarkAbstract;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * @internal
 */
#[CoversClass(BenchmarkAbstract::class)]
#[CoversClass(BeforeMethod::class)]
#[CoversClass(BenchmarkResults::class)]
#[CoversClass(Benchmark::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(Formatter::class)]
class BenchmarkAbstractBeforeMethodAttributeTest extends TestCase
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

        new
        #[BeforeMethod('unknownMethod')]
        class($this->benchmarkResults) extends BenchmarkAbstract {};
    }

    public function testInvalidBeforeMethodAttributeOnClassEmptyStringMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new
        #[BeforeMethod('')]
        class($this->benchmarkResults) extends BenchmarkAbstract {};
    }

    public function testInvalidBeforeMethodAttributeOnClassArrayWithUnknownMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new
        #[BeforeMethod(['unknownMethod'])]
        class($this->benchmarkResults) extends BenchmarkAbstract {};
    }

    public function testInvalidBeforeMethodAttributeOnClassArrayWithEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new
        #[BeforeMethod([''])]
        class($this->benchmarkResults) extends BenchmarkAbstract {};
    }

    public function testInvalidBeforeMethodAttributeOnMethodUnknownName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);

        new class($this->benchmarkResults) extends BenchmarkAbstract {
            #[Benchmark]
            #[BeforeMethod(['unknownMethod'])]
            public function doBenchmark(): void {}
        };
    }

    public function testConfiguredBeforeMethodAttributeOnClassAndOnMethods(): void
    {
        $class = new
        #[BeforeMethod(['baz'])]
        class($this->benchmarkResults) extends BenchmarkAbstract {
            /** @var list<ReflectionMethod> */
            public readonly array $beforeMethodOnClass;

            /** @var list<BenchmarkMethod> */
            public readonly array $benchmarkMethods;

            private function foo(): void {}

            protected function bar(): void {}

            public function baz(): void {}

            #[Benchmark]
            #[BeforeMethod(['foo', 'bar'])]
            public function doBenchQux(): void {}
        };

        self::assertCount(1, $class->beforeMethodOnClass);
        self::assertEquals('baz', $class->beforeMethodOnClass[0]->name);

        self::assertCount(1, $class->benchmarkMethods);

        self::assertCount(2, $class->benchmarkMethods[0]->beforeReflectionMethod);
        self::assertEquals('foo', $class->benchmarkMethods[0]->beforeReflectionMethod[0]->name);
        self::assertEquals('bar', $class->benchmarkMethods[0]->beforeReflectionMethod[1]->name);
    }
}
