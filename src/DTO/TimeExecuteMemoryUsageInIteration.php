<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\DTO;

final class TimeExecuteMemoryUsageInIteration
{
    /**
     * @param int   $startBytesUsage     memory usage value in bytes before an execution iteration allocated to your code
     * @param int   $startBytesUsageReal memory usage value in bytes before an execution iteration allocated from the operating system
     * @param int   $endBytesUsage       memory usage value in bytes after an execution iteration allocated to your code
     * @param int   $endBytesUsageReal   memory usage value in bytes after an execution iteration allocated from the operating system
     * @param int   $bytesPeakUsage      peak memory usage value in bytes a benchmark execution in your code
     * @param int   $bytesPeakUsageReal  peak memory usage value in bytes a benchmark execution allocated from the operating system
     * @param float $startTime           system time in nanoseconds before an execution iteration
     * @param float $endTime             system time in nanoseconds after an execution iteration
     * @param int   $numberOfTimes       number of benchmark runs within a single iteration
     */
    public function __construct(
        public readonly int $startBytesUsage,
        public readonly int $startBytesUsageReal,
        public readonly int $endBytesUsage,
        public readonly int $endBytesUsageReal,
        public readonly int $bytesPeakUsage,
        public readonly int $bytesPeakUsageReal,
        public readonly float $startTime,
        public readonly float $endTime,
        public readonly int $numberOfTimes,
    ) {}
}
