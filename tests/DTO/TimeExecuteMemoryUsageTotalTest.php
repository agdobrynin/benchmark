<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\DTO;

use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageTotal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TimeExecuteMemoryUsageTotal::class)]
class TimeExecuteMemoryUsageTotalTest extends TestCase
{
    public function testTimeExecuteMemoryUsageTotal(): void
    {
        $src = [
            'memoryUsage' => 10.5,
            'memoryPeakUsage' => 13.4,
            'hrTime' => 100.2,
            'iterations' => 5,
            'numberOfTimes' => 2,
        ];

        $dto = new TimeExecuteMemoryUsageTotal(...$src);

        $this->assertIsFloat($dto->memoryUsage);
        $this->assertEquals(10.5, $dto->memoryUsage);

        $this->assertIsFloat($dto->memoryPeakUsage);
        $this->assertEquals(13.4, $dto->memoryPeakUsage);

        $this->assertIsFloat($dto->hrTime);
        $this->assertEquals(100.2, $dto->hrTime);

        $this->assertIsInt($dto->iterations);
        $this->assertEquals(5, $dto->iterations);

        $this->assertIsInt($dto->numberOfTimes);
        $this->assertEquals(2, $dto->numberOfTimes);
    }
}
