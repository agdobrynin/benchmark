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
    public readonly int $bytesUsage;

    public readonly int $bytesPeakUsage;

    /**
     * @var float average time in nanoseconds per iteration
     */
    public readonly float $time;

    /**
     * @var int number of iterations for benchmarking
     */
    public readonly int $iterations;

    /**
     * @var int number of calls in a single benchmark iteration
     */
    public readonly int $numberOfTimes;

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

        /** @var list<int> $memIteration */
        $memIteration = [];
        $time = 0.0;

        foreach ($timeExecuteMemoryUsageInIterations as $timeExecuteMemoryUsageInIteration) {
            $time += ($timeExecuteMemoryUsageInIteration->endTimeInIteration - $timeExecuteMemoryUsageInIteration->startTimeInIteration) / $this->numberOfTimes;
            $memIteration[] = $timeExecuteMemoryUsageInIteration->endBytesUsageInIteration - $timeExecuteMemoryUsageInIteration->startBytesUsageInIteration;
        }

        $this->time = ($time / $this->iterations);
        // maximum peak memory usage
        $this->bytesPeakUsage = $lastResult->bytesPeakUsage;
        $this->bytesUsage = $lastResult->endBytesUsageInIteration - $firstResult->startBytesUsageInIteration;
        // memory leaking
        $firstMemDiff = reset($memIteration);
        $lastMemDiff = end($memIteration);
        $this->bytesLeaking = max($lastMemDiff - $firstMemDiff, 0);
    }
}
