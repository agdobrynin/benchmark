<?php

declare(strict_types=1);

namespace Kaspi\Benchmark;

use Generator;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\VO\BenchmarkTimeExecuteMemoryUsage;

final class BenchmarkResults
{
    /**
     * @var array<non-empty-string, list<TimeExecuteMemoryUsageInIteration>>
     */
    private array $results;

    /**
     * @var array<non-empty-string, BenchmarkTimeExecuteMemoryUsage>
     */
    private array $benchmarkTimeExecuteMemoryUsageItems;

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
    public function attachIteration(string $benchmarkDescription, TimeExecuteMemoryUsageInIteration $iteration): void
    {
        $this->results[$benchmarkDescription][] = $iteration;
        unset($this->benchmarkTimeExecuteMemoryUsageItems);
    }

    /**
     * Attaches the collection of results from all iterations for a single benchmark.
     *
     * @param non-empty-string                        $benchmarkDescription
     * @param list<TimeExecuteMemoryUsageInIteration> $iterations
     */
    public function attachIterations(string $benchmarkDescription, array $iterations): void
    {
        $this->results[$benchmarkDescription] = $iterations;
        unset($this->benchmarkTimeExecuteMemoryUsageItems);
    }

    /**
     * Generator key - benchmark description.
     *
     * @return Generator<non-empty-string, Generator<non-negative-int, TimeExecuteMemoryUsageInIteration>>
     */
    public function getResults(): Generator
    {
        foreach ($this->results ?? [] as $benchmarkDescription => $iterations) {
            yield $benchmarkDescription => (static fn () => yield from $iterations)();
        }
    }

    /**
     * A key of array benchmark description.
     *
     * @return Generator<non-empty-string, BenchmarkTimeExecuteMemoryUsage>
     */
    public function getBenchmarkTimeExecuteMemoryUsageItems(): Generator
    {
        if (isset($this->benchmarkTimeExecuteMemoryUsageItems)) {
            yield from $this->benchmarkTimeExecuteMemoryUsageItems;
        }

        $this->benchmarkTimeExecuteMemoryUsageItems = [];

        /**
         * @var non-empty-string                        $benchmarkDescription
         * @var list<TimeExecuteMemoryUsageInIteration> $benchmarkResults
         */
        foreach ($this->results ?? [] as $benchmarkDescription => $benchmarkResults) {
            $total = new BenchmarkTimeExecuteMemoryUsage($benchmarkResults);

            if (0 < $total->iterations) {
                $this->benchmarkTimeExecuteMemoryUsageItems[$benchmarkDescription] = $total;
            }

            unset($benchmarkResults);
        }

        yield from $this->benchmarkTimeExecuteMemoryUsageItems;
    }

    public function reset(): void
    {
        unset($this->results, $this->benchmarkTimeExecuteMemoryUsageItems);
    }
}
