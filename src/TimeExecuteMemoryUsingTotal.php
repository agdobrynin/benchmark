<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Core;

final class TimeExecuteMemoryUsingTotal
{
    public function __construct(
        public readonly float $memoryUsageUsage,
        public readonly float $memoryPeakUsage,
        public readonly float $hrTime,
        public readonly float $iterations,
        public readonly int $numberOfTimes,
    ) {}
}
