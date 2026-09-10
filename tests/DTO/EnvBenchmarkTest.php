<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\DTO;

use Kaspi\Benchmark\DTO\EnvBenchmark;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function php_uname;

use const PHP_VERSION_ID;

/**
 * @internal
 */
#[CoversClass(EnvBenchmark::class)]
class EnvBenchmarkTest extends TestCase
{
    public function testToHash(): void
    {
        $hash = (new EnvBenchmark(PHP_VERSION_ID, true, 'Linux WSL2'))->toHash();

        self::assertNotEmpty($hash);
    }

    #[TestWith([80100, true, 'Linux', 'PHP runtime: 8.1.0 , OPCache: on , OS: Linux'])]
    #[TestWith([80412, false, 'Windows', 'PHP runtime: 8.4.12 , OPCache: off , OS: Windows'])]
    public function testToString(int $phpVerId, bool $opcache, string $os, string $expect): void
    {
        self::assertStringContainsString($expect, (string) new EnvBenchmark($phpVerId, $opcache, $os));
    }

    public function testCreateFrom(): void
    {
        $env = EnvBenchmark::fromCurrentEnv();

        self::assertEquals(PHP_VERSION_ID, $env->phpVersionId);
        self::assertStringContainsString(php_uname('s'), $env->operatingSystem);
        self::assertStringContainsString(php_uname('r'), $env->operatingSystem);
        self::assertStringContainsString(php_uname('v'), $env->operatingSystem);
        self::assertStringContainsString(php_uname('m'), $env->operatingSystem);
    }
}
