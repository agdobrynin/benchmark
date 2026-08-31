<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\DTO;

final class TimeExecuteMemoryUsageInIteration
{
    /**
     * @param int   $startBytesUsageInIteration memory usage value in bytes before an execution iteration
     * @param int   $endBytesUsageInIteration   memory usage value in bytes after an execution iteration
     * @param int   $bytesPeakUsage             peak memory usage value in bytes a benchmark execution
     * @param float $startTimeInIteration       system time in nanoseconds before an execution iteration
     * @param float $endTimeInIteration         system time in nanoseconds after an execution iteration
     * @param int   $numberOfTimes              number of benchmark runs within a single iteration
     */
    public function __construct(
        public readonly int $startBytesUsageInIteration,
        public readonly int $endBytesUsageInIteration,
        public readonly int $bytesPeakUsage,
        public readonly float $startTimeInIteration,
        public readonly float $endTimeInIteration,
        public readonly int $numberOfTimes,
    ) {}
}
