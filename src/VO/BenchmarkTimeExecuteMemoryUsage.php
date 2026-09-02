<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\VO;

use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;

use function count;
use function end;
use function reset;

final class BenchmarkTimeExecuteMemoryUsage
{
    /**
     * Maximum memory usage (in bytes) across all iterations.
     */
    public readonly int $bytesUsage;

    /**
     * Peak memory consumption (in bytes) during benchmark execution.
     */
    public readonly int $bytesPeakUsage;

    /**
     * Average time per iteration in nanoseconds.
     */
    public readonly float $time;

    /**
     * Number of iterations for the benchmark.
     */
    public readonly int $iterations;

    /**
     * Number of calls in a single benchmark iteration.
     */
    public readonly int $numberOfTimes;

    /**
     * Memory usage increases with each step — this is a memory leak.
     */
    public readonly int $bytesLeaking;

    /**
     * @param list<TimeExecuteMemoryUsageInIteration> $timeExecuteMemoryUsageInIterations
     */
    public function __construct(array $timeExecuteMemoryUsageInIterations)
    {
        $iterations = count($timeExecuteMemoryUsageInIterations);

        if (0 === $iterations) {
            $this->iterations = $this->numberOfTimes = $this->bytesUsage = $this->bytesPeakUsage = $this->bytesLeaking = 0;
            $this->time = 0.0;

            return;
        }

        $firstResult = reset($timeExecuteMemoryUsageInIterations);
        $lastResult = end($timeExecuteMemoryUsageInIterations);

        $this->iterations = $iterations;
        $this->numberOfTimes = $firstResult->numberOfTimes;
        // maximum peak memory usage
        $this->bytesPeakUsage = $lastResult->bytesPeakUsage;

        $time = 0.0;
        // Total memory usage for iterations excluding the first iteration.
        $memoryUsageSum = 0;
        $maxMemoryUsageInIterations = 0;

        foreach ($timeExecuteMemoryUsageInIterations as $i => $iValue) {
            $time += ($iValue->endTimeInIteration - $iValue->startTimeInIteration) / $this->numberOfTimes;
            $diffMemIteration = $iValue->endBytesUsageInIteration - $iValue->startBytesUsageInIteration;

            if ($i > 0) {
                $memoryUsageSum += $diffMemIteration;
            }

            if ($maxMemoryUsageInIterations < $diffMemIteration) {
                $maxMemoryUsageInIterations = $diffMemIteration;
            }
        }

        $this->time = ($time / $this->iterations);
        $this->bytesUsage = $maxMemoryUsageInIterations;

        /*
         * Memory leaking.
         *
         * If the maximum amount of memory is allocated during the first iteration,
         * and more memory is allocated during subsequent iterations than was initially allocated,
         * then this is a memory leak.
         */
        $this->bytesLeaking = ($firstResult->endBytesUsageInIteration - $firstResult->startBytesUsageInIteration) < $memoryUsageSum
            ? $memoryUsageSum
            : 0;
    }
}
