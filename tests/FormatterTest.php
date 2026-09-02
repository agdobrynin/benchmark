<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests;

use Kaspi\Benchmark\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Formatter::class)]
class FormatterTest extends TestCase
{
    #[TestWith([1, 2, '1 B'])]
    #[TestWith([1_100, 2, '1.07 KB'])]
    #[TestWith([1_048_576, 2, '1 MB'])]
    #[TestWith([2_147_483_648, 2, '2 GB'])]
    public function testFormatBytes(float $bytes, int $prc, string $expectStr): void
    {
        self::assertEquals(
            $expectStr,
            Formatter::formatBytes($bytes, $prc)
        );
    }

    #[TestWith([1_509_799, 4, '1.5098 ms'])]
    #[TestWith([306_999, 4, '0.307 ms'])]
    #[TestWith([9_200, 4, '0.0092 ms'])]
    #[TestWith([99.9999, 2, '100 ns'])]
    #[TestWith([1_000, 4, '1000 ns'])]
    #[TestWith([1_001, 4, '0.001 ms'])]
    #[TestWith([4_008_000_000, 4, '4.008 s'])]
    public function testFormatTimeExecute(float $nanoSec, int $prc, string $expectStr): void
    {
        self::assertEquals(
            $expectStr,
            Formatter::formatTimeExecute($nanoSec, $prc),
        );
    }

    #[TestWith(['getTaggedDefinitions', 'Get tagged definitions'])]
    #[TestWith(['getFileFromS3Storage', 'Get file from s3 storage'])]
    public function testMethodToHuman(string $methodName, string $expectString): void
    {
        self::assertEquals(
            $expectString,
            Formatter::methodToHuman($methodName),
        );
    }

    #[TestWith([
        'title' => 'Foo', 'step' => 10, 'total' => 50, 'sizeTitle' => 10, 'sizeBar' => 10, 'expectedStr' => "\rFoo....... [==>       ] 20%",
    ])]
    #[TestWith([
        'title' => 'Long description', 'step' => 10, 'total' => 50, 'sizeTitle' => 10, 'sizeBar' => 10, 'expectedStr' => "\rLong desc… [==>       ] 20%",
    ])]
    #[TestWith([
        'title' => 'Foo', 'step' => 50, 'total' => 50, 'sizeTitle' => 10, 'sizeBar' => 10, 'expectedStr' => "\rFoo....... [==========] 100%",
    ])]
    public function testProgressBar(string $title, int $step, int $total, int $sizeTitle, int $sizeBar, string $expectedStr): void
    {
        $this->expectOutputString($expectedStr);
        Formatter::progressBar($title, $step, $total, $sizeTitle, $sizeBar);
    }
}
