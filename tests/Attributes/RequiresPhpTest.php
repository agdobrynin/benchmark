<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\Attributes;

use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function version_compare;

use const PHP_VERSION;

/**
 * @internal
 */
#[CoversClass(RequiresPhp::class)]
class RequiresPhpTest extends TestCase
{
    #[TestWith([
        '8.1.0',
        '<',
        'less than 8.1.0',
    ])]
    #[TestWith([
        '8.1.0',
        '<=',
        'less than or equals 8.1.0',
    ])]
    #[TestWith([
        '8.1.34',
        null,
        'equals 8.1.34',
    ])]
    #[TestWith([
        '8.1.33',
        'eq',
        'equals 8.1.33',
    ])]
    #[TestWith([
        '8.1.33',
        '=',
        'equals 8.1.33',
    ])]
    #[TestWith([
        '8.1.33',
        '==',
        'equals 8.1.33',
    ])]
    #[TestWith([
        '8.2.33',
        '>',
        'greater than 8.2.33',
    ])]
    #[TestWith([
        '8.1',
        '>=',
        'greater than or equals 8.1',
    ])]
    #[TestWith([
        '8.0.2',
        '!=',
        'not equals 8.0.2',
    ])]
    #[TestWith([
        '8.3',
        '<>',
        'not equals 8.3',
    ])]
    public function testRequiresPhp(string $version, ?string $operator, string $expectHumanReadable): void
    {
        $attr = new RequiresPhp($version, $operator);
        self::assertEquals($expectHumanReadable, $attr->humanReadable());
        // Test not redefined constant \PHP_VERSION
        self::assertEquals(version_compare(PHP_VERSION, $attr->phpVersion, $attr->operator), $attr->isAvailable());
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
