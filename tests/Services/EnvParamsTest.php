<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\Services;

use Kaspi\Benchmark\DTO\EnvBenchmark;
use Kaspi\Benchmark\Services\EnvParams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use const PHP_VERSION_ID;

/**
 * @internal
 */
#[CoversClass(EnvParams::class)]
#[CoversClass(EnvBenchmark::class)]
class EnvParamsTest extends TestCase
{
    public function testEnvParams(): void
    {
        $env = EnvParams::autoConfigureEnvBenchmark();

        self::assertEquals(PHP_VERSION_ID, $env->phpVersionId);
    }
}
