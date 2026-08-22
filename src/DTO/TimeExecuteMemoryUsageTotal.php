<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\DTO;

final class TimeExecuteMemoryUsageTotal
{
    public function __construct(
        public readonly float $memoryUsage,
        public readonly float $memoryPeakUsage,
        public readonly float $hrTime,
        public readonly int $iterations,
        public readonly int $numberOfTimes,
    ) {}
}
