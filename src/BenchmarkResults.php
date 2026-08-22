<?php

declare(strict_types=1);

namespace Kaspi\Benchmark;

use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageTotal;
use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;

use function count;
use function current;

final class BenchmarkResults
{
    /**
     * @var array<non-empty-string, list<TimeExecuteMemoryUsageIteration>>
     */
    private array $results;

    /**
     * @var array<non-empty-string, TimeExecuteMemoryUsageTotal>
     */
    private array $timeExecuteMemoryUsingTotalItems;

    /**
     * @param non-empty-string $packageVersion
     * @param non-empty-string $groupName
     */
    public function __construct(
        public readonly string $packageVersion,
        public readonly string $groupName,
    ) {}

    /**
     * Attaches a single benchmark iteration to the collection of results.
     *
     * @param non-empty-string $benchmarkDescription
     */
    public function attachIteration(string $benchmarkDescription, TimeExecuteMemoryUsageIteration $iteration): void
    {
        $this->results[$benchmarkDescription][] = $iteration;
        unset($this->timeExecuteMemoryUsingTotalItems);
    }

    /**
     * Attaches the collection of results from all iterations for a single benchmark.
     *
     * @param non-empty-string                      $benchmarkDescription
     * @param list<TimeExecuteMemoryUsageIteration> $iterations
     */
    public function attachIterations(string $benchmarkDescription, array $iterations): void
    {
        $this->results[$benchmarkDescription] = $iterations;
        unset($this->timeExecuteMemoryUsingTotalItems);
    }

    /**
     * A key of array benchmark description.
     *
     * @return array<non-empty-string, list<TimeExecuteMemoryUsageIteration>>
     */
    public function getResults(): array
    {
        return $this->results ?? [];
    }

    /**
     * A key of array benchmark description.
     *
     * @return array<non-empty-string, TimeExecuteMemoryUsageTotal>
     */
    public function getTimeExecuteMemoryUsingTotalItems(): array
    {
        if (isset($this->timeExecuteMemoryUsingTotalItems)) {
            return $this->timeExecuteMemoryUsingTotalItems;
        }

        $this->timeExecuteMemoryUsingTotalItems = [];

        /**
         * @var non-empty-string                      $benchmarkDescription
         * @var list<TimeExecuteMemoryUsageIteration> $benchmarkResults
         */
        foreach ($this->getResults() as $benchmarkDescription => $benchmarkResults) {
            $total = $this->calculateTotal($benchmarkResults);

            if (false !== $total) {
                $this->timeExecuteMemoryUsingTotalItems[$benchmarkDescription] = $total;
            }
        }

        return $this->timeExecuteMemoryUsingTotalItems;
    }

    public function reset(): void
    {
        unset($this->results, $this->timeExecuteMemoryUsingTotalItems);
    }

    /**
     * @param list<TimeExecuteMemoryUsageIteration> $benchmarkResults
     */
    private function calculateTotal(array $benchmarkResults): false|TimeExecuteMemoryUsageTotal
    {
        $firstItem = current($benchmarkResults);

        if (false === $firstItem) {
            return false;
        }

        $numberOfTimes = $firstItem->numberOfTimes;
        $iterations = count($benchmarkResults);

        if (1 === $iterations) {
            return new TimeExecuteMemoryUsageTotal(
                $firstItem->memoryUsage(),
                $firstItem->memoryPeakUsage(),
                $firstItem->hrTime(),
                $iterations,
                $numberOfTimes,
            );
        }

        $sumMemoryAllocated = $sumMemoryPeak = $sumTime = 0;

        foreach ($benchmarkResults as $benchmarkResult) {
            $sumMemoryAllocated += $benchmarkResult->memoryUsage();
            $sumMemoryPeak += $benchmarkResult->memoryPeakUsage();
            $sumTime += $benchmarkResult->hrTime();
        }

        return new TimeExecuteMemoryUsageTotal(
            $sumMemoryAllocated,
            $sumMemoryPeak,
            $sumTime,
            $iterations,
            $numberOfTimes,
        );
    }
}
