<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\Services;

use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\Services\TimeMemoryService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TimeMemoryService::class)]
#[CoversClass(TimeExecuteMemoryUsageInIteration::class)]
class TimeMemoryServiceTest extends TestCase
{
    public function testTimeMemoryInIterationWithGc(): void
    {
        $item = ($service = new TimeMemoryService(true, 2))->create();

        self::assertIsInt($service->getCollectedCyclesBefore());
        self::assertIsInt($service->getCollectedCyclesAfter());

        self::assertEquals(2, $item->numberOfTimes);
    }

    public function testTimeMemoryInIterationWithoutGc(): void
    {
        $item = ($service = new TimeMemoryService(false, 1))->create();

        self::assertNull($service->getCollectedCyclesBefore());
        self::assertNull($service->getCollectedCyclesAfter());

        self::assertEquals(1, $item->numberOfTimes);
    }
}
