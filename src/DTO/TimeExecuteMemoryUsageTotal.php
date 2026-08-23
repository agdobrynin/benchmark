<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\DTO;

final class TimeExecuteMemoryUsageTotal
{
    /**
     * @param float $memoryUsage     memory usage in bytes
     * @param float $memoryPeakUsage memory peak usage in bytes
     * @param float $hrTime          execute time in nanoseconds
     * @param int   $iterations      number of benchmark runs
     * @param int   $numberOfTimes   number of benchmark runs within a single iteration
     */
    public function __construct(
        public readonly float $memoryUsage,
        public readonly float $memoryPeakUsage,
        public readonly float $hrTime,
        public readonly int $iterations,
        public readonly int $numberOfTimes,
    ) {}
}
