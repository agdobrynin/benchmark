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
     * Maximum memory usage (in bytes) across all iterations allocated to your code.
     */
    public readonly int $bytesUsage;

    /**
     * Maximum memory usage (in bytes) across all iterations allocated from the operating system.
     */
    public readonly int $bytesUsageReal;

    /**
     * Peak memory consumption (in bytes) during benchmark execution allocated to your code.
     */
    public readonly int $bytesPeakUsage;

    /**
     * Peak memory consumption (in bytes) during benchmark execution allocated from the operating system.
     */
    public readonly int $bytesPeakUsageReal;

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
            $this->iterations = $this->numberOfTimes = $this->bytesUsage = $this->bytesUsageReal = $this->bytesPeakUsage = $this->bytesPeakUsageReal = 0;
            $this->time = 0.0;

            return;
        }

        $firstResult = reset($timeExecuteMemoryUsageInIterations);
        $this->iterations = $iterations;
        $this->numberOfTimes = $firstResult->numberOfTimes;

        $maxMemoryUsageInIterations = $maxMemoryUsageRealInIterations = $maxMemoryPeakUsageInIterations = $maxMemoryPeakUsageRealInIterations = 0;
        $time = 0.0;

        foreach ($timeExecuteMemoryUsageInIterations as $timeExecuteMemoryUsageInIteration) {
            if (!$timeExecuteMemoryUsageInIteration instanceof TimeExecuteMemoryUsageInIteration) {
                throw new InvalidArgumentException(
                    sprintf('The list must consist only of elements of type %s, given type "%s".', TimeExecuteMemoryUsageInIteration::class, get_debug_type($timeExecuteMemoryUsageInIteration))
                );
            }

            $time += ($timeExecuteMemoryUsageInIteration->endTime - $timeExecuteMemoryUsageInIteration->startTime) / $this->numberOfTimes;
            $maxMemoryUsageInIterations = max($maxMemoryUsageInIterations, $timeExecuteMemoryUsageInIteration->endBytesUsage - $timeExecuteMemoryUsageInIteration->startBytesUsage);
            $maxMemoryUsageRealInIterations = max($maxMemoryUsageRealInIterations, $timeExecuteMemoryUsageInIteration->endBytesUsageReal - $timeExecuteMemoryUsageInIteration->startBytesUsageReal);
            $maxMemoryPeakUsageInIterations = max($maxMemoryPeakUsageInIterations, $timeExecuteMemoryUsageInIteration->bytesPeakUsage);
            $maxMemoryPeakUsageRealInIterations = max($maxMemoryPeakUsageRealInIterations, $timeExecuteMemoryUsageInIteration->bytesPeakUsageReal);
        }

        $this->time = ($time / $this->iterations);
        $this->bytesUsage = $maxMemoryUsageInIterations;
        $this->bytesUsageReal = $maxMemoryUsageRealInIterations;
        $this->bytesPeakUsage = $maxMemoryPeakUsageInIterations;
        $this->bytesPeakUsageReal = $maxMemoryPeakUsageRealInIterations;
    }
}
