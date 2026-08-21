<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Core;

final class TimeExecuteMemoryUsageIteration
{
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
