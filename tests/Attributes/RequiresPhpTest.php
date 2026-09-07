<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\Attributes;

use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use const PHP_VERSION_ID;

/**
 * @internal
 */
#[CoversClass(RequiresPhp::class)]
class RequiresPhpTest extends TestCase
{
    #[TestWith([
        new RequiresPhp('8.1.0', '<'),
        PHP_VERSION_ID < 80100,
    ])]
    #[TestWith([
        new RequiresPhp('8.1.34'),
        PHP_VERSION_ID === 80134,
    ])]
    #[TestWith([
        new RequiresPhp('8.1', '>='),
        PHP_VERSION_ID >= 80100,
    ])]
    #[TestWith([
        new RequiresPhp('8.5', '>='),
        PHP_VERSION_ID >= 80500,
    ])]
    public function testRequiresPhp(RequiresPhp $attr, bool $expect): void
    {
        self::assertEquals($expect, $attr->isAvailable());
    }

    #[TestWith([''])]
    #[TestWith(['  '])]
    #[TestWith(['more'])]
    public function testInvalidOperator(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid comparison operator');

        new RequiresPhp('8.1', $operator);
    }

    #[TestWith([''])]
    #[TestWith(['  '])]
    public function testInvalidPhpVersion(string $phpVersion): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Php version must be non-empty string');

        new RequiresPhp($phpVersion);
    }
}
