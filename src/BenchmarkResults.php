<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Core;

use function count;
use function current;
use function max;

final class BenchmarkResults
{
    /**
     * @var array{non-empty-string, list<TimeExecuteMemoryUsageIteration>}
     */
    private array $results = [];

    /**
     * @var array<non-empty-string, TimeExecuteMemoryUsingTotal>
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
     * @param non-empty-string $benchmarkDescription
     */
    public function attach(string $benchmarkDescription, TimeExecuteMemoryUsageIteration $result): void
    {
        $this->results[$benchmarkDescription][] = $result;
        unset($this->timeExecuteMemoryUsingTotalItems);
    }

    /**
     * @param non-empty-string                      $benchmarkDescription
     * @param list<TimeExecuteMemoryUsageIteration> $results
     */
    public function attachResults(string $benchmarkDescription, array $results): void
    {
        $this->results[$benchmarkDescription] = $results;
        unset($this->timeExecuteMemoryUsingTotalItems);
    }

    /**
     * A key of array benchmark description.
     *
     * @return array{non-empty-string, list<TimeExecuteMemoryUsageIteration>}
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * A key of array benchmark description.
     *
     * @return array<non-empty-string, TimeExecuteMemoryUsingTotal>
     */
    public function getTimeExecuteMemoryUsingTotalItems(): array
    {
        if (isset($this->timeExecuteMemoryUsingTotalItems)) {
            return $this->timeExecuteMemoryUsingTotalItems;
        }

        $this->timeExecuteMemoryUsingTotalItems = [];

        /**
         * @var non-empty-string $benchmarkDescription
         * @var list<TimeExecuteMemoryUsageIteration> $benchmarkResults
         */
        foreach ($this->results as $benchmarkDescription => $benchmarkResults) {
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
     * @param non-empty-list<TimeExecuteMemoryUsageIteration> $benchmarkResults
     */
    private function calculateTotal(array $benchmarkResults): false|TimeExecuteMemoryUsingTotal
    {
        $firstItem = current($benchmarkResults);

        if (false === $firstItem) {
            return false;
        }

        $numberOfTimes = $firstItem->numberOfTimes;
        $iterations = count($benchmarkResults);

        if ($iterations === 1) {
            return new TimeExecuteMemoryUsingTotal(
                $firstItem->memoryUsage(),
                $firstItem->memoryPeakUsage(),
                $firstItem->hrTime(),
                $iterations,
                $numberOfTimes,
            );
        }

        /**
         * @var int $sumMemoryAllocated
         * @var int $sumMemoryPeak
         * @var int $sumTime
         */
        $sumMemoryAllocated = $sumMemoryPeak = $sumTime = 0;

        foreach ($benchmarkResults as $benchmarkResult) {
            $sumMemoryAllocated += $benchmarkResult->memoryUsage();
            $sumMemoryPeak += $benchmarkResult->memoryPeakUsage();
            $sumTime += $benchmarkResult->hrTime();
        }

        return new TimeExecuteMemoryUsingTotal(
            $sumMemoryAllocated,
            $sumMemoryPeak,
            $sumTime,
            $iterations,
            $numberOfTimes,
        );
    }
}
