<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\VO;

use InvalidArgumentException;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;

use function count;
use function get_debug_type;
use function max;
use function reset;
use function sprintf;

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
     * @param list<TimeExecuteMemoryUsageInIteration> $timeExecuteMemoryUsageInIterations
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $timeExecuteMemoryUsageInIterations)
    {
        $iterations = count($timeExecuteMemoryUsageInIterations);

        if (0 === $iterations) {
            $this->iterations = $this->numberOfTimes = $this->bytesUsage = $this->bytesPeakUsage = 0;
            $this->time = 0.0;

            return;
        }

        $firstResult = reset($timeExecuteMemoryUsageInIterations);
        $this->iterations = $iterations;
        $this->numberOfTimes = $firstResult->numberOfTimes;

        $maxMemoryUsageInIterations = $maxMemoryPeakUsageInIterations = 0;
        $time = 0.0;

        foreach ($timeExecuteMemoryUsageInIterations as $timeExecuteMemoryUsageInIteration) {
            if (!$timeExecuteMemoryUsageInIteration instanceof TimeExecuteMemoryUsageInIteration) {
                throw new InvalidArgumentException(
                    sprintf('The list must consist only of elements of type %s, given type "%s".', TimeExecuteMemoryUsageInIteration::class, get_debug_type($timeExecuteMemoryUsageInIteration))
                );
            }

            $time += ($timeExecuteMemoryUsageInIteration->endTimeInIteration - $timeExecuteMemoryUsageInIteration->startTimeInIteration) / $this->numberOfTimes;
            $maxMemoryUsageInIterations = max($maxMemoryUsageInIterations, $timeExecuteMemoryUsageInIteration->endBytesUsageInIteration - $timeExecuteMemoryUsageInIteration->startBytesUsageInIteration);
            $maxMemoryPeakUsageInIterations = max($maxMemoryPeakUsageInIterations, $timeExecuteMemoryUsageInIteration->bytesPeakUsage);
        }

        $this->time = ($time / $this->iterations);
        $this->bytesUsage = $maxMemoryUsageInIterations;
        $this->bytesPeakUsage = $maxMemoryPeakUsageInIterations;
    }
}
