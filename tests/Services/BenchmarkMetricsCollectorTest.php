<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\Services;

use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\Services\BenchmarkMetricsCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function gc_disable;

/**
 * @internal
 */
#[CoversClass(BenchmarkMetricsCollector::class)]
#[CoversClass(TimeExecuteMemoryUsageInIteration::class)]
class BenchmarkMetricsCollectorTest extends TestCase
{
    public function testGcDisabled(): void
    {
        gc_disable();

        $service = new BenchmarkMetricsCollector(true, 1);
        $service->start();
        $service->end();

        self::assertTrue($service->iterations()->valid());
    }

    public function testExceptionWithoutStartMethod(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('you must call the `'.BenchmarkMetricsCollector::class.'::start()` method.');

        $service = new BenchmarkMetricsCollector(true, 1);
        $service->end();
    }

    public function testEmptyIterations(): void
    {
        $service = new BenchmarkMetricsCollector(true, 1);

        self::assertFalse($service->iterations()->valid());
    }
}
