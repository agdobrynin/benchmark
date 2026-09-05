<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\DTO;

use Kaspi\Benchmark\DTO\EnvBenchmark;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use const PHP_VERSION_ID;

/**
 * @internal
 */
#[CoversClass(EnvBenchmark::class)]
class EnvBenchmarkTest extends TestCase
{
    public function testToHash(): void
    {
        $hash = (new EnvBenchmark(PHP_VERSION_ID, true))->toHash();

        self::assertNotEmpty($hash);
    }

    #[TestWith([80100, true, 'PHP runtime: 8.1.0 , OPCache: on'])]
    #[TestWith([80412, false, 'PHP runtime: 8.4.12 , OPCache: off'])]
    public function testToString(int $phpVerId, bool $opcache, string $expect): void
    {
        self::assertEquals($expect, (string) new EnvBenchmark($phpVerId, $opcache));
    }
}
