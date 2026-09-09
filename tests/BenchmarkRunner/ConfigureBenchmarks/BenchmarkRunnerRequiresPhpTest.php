<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkRunner\ConfigureBenchmarks;

use Generator;
use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\RequiresPhp;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\BenchmarkGroup;
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkRunner::class)]
#[CoversClass(BenchmarkMethod::class)]
#[CoversClass(RequiresPhp::class)]
#[UsesClass(BenchmarkGroup::class)]
#[UsesClass(Benchmark::class)]
#[UsesClass(Formatter::class)]
class BenchmarkRunnerRequiresPhpTest extends TestCase
{
    #[DataProvider('invalidAttributeProvider')]
    public function testInvalidAttribute(object $class, string $expectExceptionMessageMatchs): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($expectExceptionMessageMatchs);

        new BenchmarkRunner('foo', $class);
    }

    public static function invalidAttributeProvider(): Generator
    {
        yield [
            new #[RequiresPhp('')] class {},
            '/The attribute `.+RequiresPhp` failed validation for the class@anonymous.+Php version must be non-empty string/',
        ];

        yield [
            new #[RequiresPhp('8.1', 'more than')] class {},
            '/The attribute `.+RequiresPhp` failed validation for the class@anonymous.+Invalid comparison operator \'more than\'/',
        ];

        yield [
            new class {
                #[Benchmark]
                #[RequiresPhp('')]
                public function doNothing(): void {}
            },
            '/The attribute `.+RequiresPhp` failed validation for the class@anonymous.+::doNothing\(\).+Php version must be non-empty string/',
        ];

        yield [
            new class {
                #[Benchmark]
                #[RequiresPhp('8.1', 'more than')]
                public function doNothing(): void {}
            },
            '/The attribute `.+RequiresPhp` failed validation for the class@anonymous.+::doNothing\(\).+Reason by\: Invalid comparison operator \'more than\'/',
        ];
    }

    public function testRequiresPhpNotSet(): void
    {
        $class = new class {
            #[Benchmark]
            public function doNothing(): void {}
        };

        $runner = new BenchmarkRunner('foo', $class);

        self::assertNull($runner->benchmarkGroups[0]->benchmarkMethods[0]->requiresPhp);
    }

    #[DataProvider('successAttributeProvider')]
    public function testSuccessAttribute(object $class, string $humanReadable): void
    {
        $runner = new BenchmarkRunner('foo', $class);

        self::assertCount(1, $runner->benchmarkGroups);

        $methods = $runner->benchmarkGroups[0]->benchmarkMethods;

        self::assertCount(1, $methods);

        self::assertEquals($humanReadable, $methods[0]->requiresPhp->humanReadable());
    }

    public static function successAttributeProvider(): Generator
    {
        yield [
            new #[RequiresPhp('8.1')] class {
                #[Benchmark]
                public function doNothing(): void {}
            },
            'equals 8.1',
        ];

        yield [
            new class {
                #[Benchmark]
                #[RequiresPhp('8.4', '>=')]
                public function doNothing(): void {}
            },
            'greater than or equals 8.4',
        ];
    }
}
