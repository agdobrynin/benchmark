<?php

declare(strict_types=1);

namespace Kaspi\Benchmark;

use Generator;
use JsonException;
use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;

use function array_map;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use const JSON_BIGINT_AS_STRING;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * @phpstan-type PackageVersionType non-empty-string
 * @phpstan-type BenchmarkGroupNameType non-empty-string
 * @phpstan-type BenchmarkDescriptionType non-empty-string
 * @phpstan-type TimeExecuteMemoryUsageIterationType array{
 *     startMemoryUsage: int,
 *     endMemoryUsage: int,
 *     startMemoryPeakUsage: int,
 *     endMemoryPeakUsage: int,
 *     startHrTime: float,
 *     endHrTime: float,
 *     numberOfTimes: int,
 * }
 */
final class BenchmarkResultsFile
{
    /**
     * @var list<BenchmarkResults>
     */
    private array $attachedBenchmarkResults;

    public function __construct(private readonly string $outputFile) {}

    public function attach(BenchmarkResults $benchmarkResults): self
    {
        $this->attachedBenchmarkResults[] = $benchmarkResults;

        return $this;
    }

    public function reset(): void
    {
        unset($this->attachedBenchmarkResults);
    }

    /**
     * @throws JsonException
     */
    public function save(): self
    {
        if (!isset($this->attachedBenchmarkResults)) {
            file_put_contents($this->outputFile, '{}');

            return $this;
        }

        $fileResults = $this->getArrayFromFile();

        foreach ($this->attachedBenchmarkResults as $benchmarkResults) {
            /**
             * @var list<TimeExecuteMemoryUsageIteration> $timeExecuteMemoryUseIterationItems
             */
            foreach ($benchmarkResults->getResults() as $benchmarkDescription => $timeExecuteMemoryUseIterationItems) {
                $fileResults[$benchmarkResults->packageVersion][$benchmarkResults->groupName][$benchmarkDescription] = array_map(
                    static fn (TimeExecuteMemoryUsageIteration $i): array => (array) $i,
                    $timeExecuteMemoryUseIterationItems
                );
            }
        }

        $json = json_encode($fileResults, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        file_put_contents($this->outputFile, $json);

        return $this;
    }

    /**
     * @return Generator<BenchmarkResults>
     */
    public function read(): Generator
    {
        $fileResults = $this->getArrayFromFile();

        foreach ($fileResults as $packageVersion => $fileBenchmarkGroups) {
            foreach ($fileBenchmarkGroups as $fileGroupName => $fileBenchmarkResults) {
                $benchmarkResults = new BenchmarkResults($packageVersion, $fileGroupName);

                foreach ($fileBenchmarkResults as $fileBenchmarkDescription => $fileTimeExecuteMemoryUsageIterations) {
                    $benchmarkResults->attachIterations(
                        $fileBenchmarkDescription,
                        array_map(static fn (array $i): TimeExecuteMemoryUsageIteration => new TimeExecuteMemoryUsageIteration(...$i), $fileTimeExecuteMemoryUsageIterations)
                    );
                }

                yield $benchmarkResults;
            }
        }
    }

    /**
     * @return array<PackageVersionType, array<BenchmarkGroupNameType, array<BenchmarkDescriptionType, non-empty-list<TimeExecuteMemoryUsageIterationType>>>>
     */
    private function getArrayFromFile(): array
    {
        if (!file_exists($this->outputFile)) {
            return [];
        }

        $content = file_get_contents($this->outputFile);

        if (false === $content) {
            return [];
        }

        try {
            $resultsFromFile = json_decode($content, true, flags: JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException) {
            return [];
        }

        if (!is_array($resultsFromFile)) {
            return [];
        }

        $results = [];

        foreach ($resultsFromFile as $version => $groups) {
            if (!is_string($version) || '' === $version) {
                continue;
            }

            if (!is_array($groups) || 0 === count($groups)) {
                continue;
            }

            foreach ($groups as $groupName => $benchmarkResults) {
                if (!is_string($groupName) || '' === $groupName) {
                    continue;
                }

                if (!is_array($benchmarkResults) || 0 === count($benchmarkResults)) {
                    continue;
                }

                foreach ($benchmarkResults as $benchmarkDescription => $timeExecuteMemoryUsageIterationsItems) {
                    if (!is_string($benchmarkDescription) || '' === $benchmarkDescription) {
                        continue;
                    }

                    if (!is_array($timeExecuteMemoryUsageIterationsItems) || 0 === count($timeExecuteMemoryUsageIterationsItems)) {
                        continue;
                    }

                    /**
                     * @var TimeExecuteMemoryUsageIterationType $timeExecuteMemoryUsageIteration
                     */
                    foreach ($timeExecuteMemoryUsageIterationsItems as $timeExecuteMemoryUsageIteration) {
                        if (!is_array($timeExecuteMemoryUsageIteration)) {
                            continue;
                        }

                        $results[$version][$groupName][$benchmarkDescription][] = $timeExecuteMemoryUsageIteration;
                    }
                }
            }
        }

        return $results;
    }
}
