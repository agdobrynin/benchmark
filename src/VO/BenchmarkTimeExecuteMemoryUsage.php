<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\VO;

use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;

use function count;
use function end;
use function max;
use function reset;

final class BenchmarkTimeExecuteMemoryUsage
{
    /**
     * Maximum memory usage (in bytes) across all iterations.
     */
    public readonly int $bytesUsage;

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
     * Memory usage increases with each step—this is a memory leak.
     * Comparison of memory usage between the last and first iterations.
     * Memory consumption on the last iteration should be lower than on the first.
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

        $memoryUsageInIterationMax = 0;
        $time = 0.0;

        foreach ($timeExecuteMemoryUsageInIterations as $timeExecuteMemoryUsageInIteration) {
            $time += ($timeExecuteMemoryUsageInIteration->endTimeInIteration - $timeExecuteMemoryUsageInIteration->startTimeInIteration) / $this->numberOfTimes;
            $diffMem = $timeExecuteMemoryUsageInIteration->endBytesUsageInIteration - $timeExecuteMemoryUsageInIteration->startBytesUsageInIteration;
            $memoryUsageInIterationMax = max($diffMem, $memoryUsageInIterationMax);
        }

        $this->time = ($time / $this->iterations);
        // maximum peak memory usage
        $this->bytesPeakUsage = $lastResult->bytesPeakUsage;
        $this->bytesUsage = $memoryUsageInIterationMax;
        // memory leaking
        $firstIterationMemoryUsage = $firstResult->endBytesUsageInIteration - $firstResult->startBytesUsageInIteration;
        $lastIterationMemoryUsage = $lastResult->endBytesUsageInIteration - $lastResult->startBytesUsageInIteration;

        $this->bytesLeaking = $lastIterationMemoryUsage > $firstIterationMemoryUsage
            ? $lastIterationMemoryUsage
            : 0;
    }
}
