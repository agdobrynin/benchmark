<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\VO;

final class TimeExecuteMemoryUsageIteration
{
    /**
     * @param int   $startMemoryUsage     memory usage value in bytes before an execution iteration
     * @param int   $endMemoryUsage       memory usage value in bytes after an execution iteration
     * @param int   $startMemoryPeakUsage peak memory usage value in bytes before an execution iteration
     * @param int   $endMemoryPeakUsage   peak memory usage value in bytes after an execution iteration
     * @param float $startHrTime          system time in nanoseconds before an execution iteration
     * @param float $endHrTime            system time in nanoseconds after an execution iteration
     * @param int   $numberOfTimes        number of benchmark runs within a single iteration
     */
    public function __construct(
        public readonly int $startMemoryUsage,
        public readonly int $endMemoryUsage,
        public readonly int $startMemoryPeakUsage,
        public readonly int $endMemoryPeakUsage,
        public readonly float $startHrTime,
        public readonly float $endHrTime,
        public readonly int $numberOfTimes,
    ) {}

    public function memoryUsage(): int
    {
        return $this->endMemoryUsage - $this->startMemoryUsage;
    }

    public function memoryPeakUsage(): int
    {
        return $this->endMemoryPeakUsage - $this->startMemoryPeakUsage;
    }

    public function hrTime(): float
    {
        return ($this->endHrTime - $this->startHrTime) / $this->numberOfTimes;
    }
}
