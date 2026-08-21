<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\DTO;

final class TimeExecuteMemoryUsageTotal
{
    public function __construct(
        public readonly float $memoryUsageUsage,
        public readonly float $memoryPeakUsage,
        public readonly float $hrTime,
        public readonly float $iterations,
        public readonly int $numberOfTimes,
    ) {}
}
