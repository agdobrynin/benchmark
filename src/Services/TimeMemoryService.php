<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Services;

use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;

use function gc_collect_cycles;
use function gc_enable;
use function gc_enabled;
use function hrtime;
use function memory_get_peak_usage;
use function memory_get_usage;

final class TimeMemoryService
{
    private readonly float $startTime;
    private readonly int $startMemoryUsage;
    private readonly int $startMemoryUsageReal;

    private ?int $collectedCyclesBefore = null;
    private ?int $collectedCyclesAfter = null;

    public function __construct(
        private readonly bool $runGarbageCollector,
        private readonly int $numberOfTimes,
    ) {
        if ($runGarbageCollector) {
            if (!gc_enabled()) {
                gc_enable();
            }

            $this->collectedCyclesBefore = gc_collect_cycles();
        }

        $this->startTime = hrtime(true);
        $this->startMemoryUsage = memory_get_usage();
        $this->startMemoryUsageReal = memory_get_usage(true);
    }

    public function create(): TimeExecuteMemoryUsageInIteration
    {
        $endHrTime = hrtime(true);

        if ($this->runGarbageCollector) {
            $this->collectedCyclesAfter = gc_collect_cycles();
        }

        $endMemoryUsage = memory_get_usage();
        $endMemoryUsageReal = memory_get_usage(true);
        $memoryPeakUsage = memory_get_peak_usage();
        $memoryPeakUsageReal = memory_get_peak_usage(true);

        return new TimeExecuteMemoryUsageInIteration(
            startBytesUsage: $this->startMemoryUsage,
            startBytesUsageReal: $this->startMemoryUsageReal,
            endBytesUsage: $endMemoryUsage,
            endBytesUsageReal: $endMemoryUsageReal,
            bytesPeakUsage: $memoryPeakUsage,
            bytesPeakUsageReal: $memoryPeakUsageReal,
            startTime: $this->startTime,
            endTime: $endHrTime,
            numberOfTimes: $this->numberOfTimes,
        );
    }

    public function getCollectedCyclesBefore(): ?int
    {
        return $this->collectedCyclesBefore;
    }

    public function getCollectedCyclesAfter(): ?int
    {
        return $this->collectedCyclesAfter;
    }
}
