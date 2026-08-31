<?php

declare(strict_types=1);

namespace Kaspi\Benchmark;

use Generator;
use JsonException;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use RuntimeException;

use function array_map;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;
use function var_export;

use const JSON_BIGINT_AS_STRING;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * @phpstan-type PackageVersionType non-empty-string
 * @phpstan-type BenchmarkGroupNameType non-empty-string
 * @phpstan-type BenchmarkDescriptionType non-empty-string
 * @phpstan-type TimeExecuteMemoryUsageInIterationType array{
 *     startBytesUsageInIteration: int,
 *     endBytesUsageInIteration: int,
 *     bytesPeakUsage: int,
 *     startTimeInIteration: float,
 *     endTimeInIteration: float,
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

    /**
     * @return Generator<BenchmarkResults>
     */
    public function getAttached(): Generator
    {
        yield from $this->attachedBenchmarkResults ?? [];
    }

    public function reset(): void
    {
        unset($this->attachedBenchmarkResults);
    }

    /**
     * @throws JsonException|RuntimeException
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
             * @var Generator<TimeExecuteMemoryUsageInIteration> $timeExecuteMemoryUsageInIterationItems
             */
            foreach ($benchmarkResults->getResults() as $benchmarkDescription => $timeExecuteMemoryUsageInIterationItems) {
                $items = [];

                foreach ($timeExecuteMemoryUsageInIterationItems as $timeExecuteMemoryUsageInIteration) {
                    $items[] = (array) $timeExecuteMemoryUsageInIteration;
                }

                $fileResults[$benchmarkResults->packageVersion][$benchmarkResults->groupName][$benchmarkDescription] = $items;
                unset($items);
            }
        }

        $json = json_encode($fileResults, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        file_put_contents($this->outputFile, $json);

        return $this;
    }

    /**
     * @return Generator<BenchmarkResults>
     *
     * @throws RuntimeException
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
                        array_map(static fn (array $args): TimeExecuteMemoryUsageInIteration => new TimeExecuteMemoryUsageInIteration(...$args), $fileTimeExecuteMemoryUsageIterations)
                    );
                }

                yield $benchmarkResults;
            }
        }
    }

    /**
     * @return array<PackageVersionType, array<BenchmarkGroupNameType, array<BenchmarkDescriptionType, non-empty-list<TimeExecuteMemoryUsageInIterationType>>>>
     *
     * @throws RuntimeException
     */
    private function getArrayFromFile(): array
    {
        if (!file_exists($this->outputFile)) {
            return [];
        }

        $content = @file_get_contents($this->outputFile);

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
                throw new RuntimeException('The package version must be a non-empty string.');
            }

            if (!is_array($groups) || 0 === count($groups)) {
                throw new RuntimeException(
                    sprintf('A package of version %s must contain benchmark groups as a non-empty array.', var_export($version, true))
                );
            }

            foreach ($groups as $groupName => $benchmarkResults) {
                if (!is_string($groupName) || '' === $groupName) {
                    throw new RuntimeException(
                        sprintf('Package version %s must contain benchmark groups as a non-empty array where each group name is a non-empty string.', var_export($version, true))
                    );
                }

                if (!is_array($benchmarkResults) || 0 === count($benchmarkResults)) {
                    throw new RuntimeException(
                        sprintf('A package of version %s with group name %s must contain benchmark results as a non-empty array.', var_export($version, true), var_export($groupName, true))
                    );
                }

                foreach ($benchmarkResults as $benchmarkDescription => $timeExecuteMemoryUsageIterationsItems) {
                    if (!is_string($benchmarkDescription) || '' === $benchmarkDescription) {
                        throw new RuntimeException(
                            sprintf('A package of version %s with group name %s must contain a benchmark description as a non-empty string.', var_export($version, true), var_export($groupName, true))
                        );
                    }

                    if (!is_array($timeExecuteMemoryUsageIterationsItems) || 0 === count($timeExecuteMemoryUsageIterationsItems)) {
                        throw new RuntimeException(
                            sprintf('Benchmark %s in package version %s and group named %s must contain iteration elements as a non-empty array.', var_export($benchmarkDescription, true), var_export($version, true), var_export($groupName, true))
                        );
                    }

                    /**
                     * @var TimeExecuteMemoryUsageInIterationType $timeExecuteMemoryUsageIteration
                     */
                    foreach ($timeExecuteMemoryUsageIterationsItems as $timeExecuteMemoryUsageIteration) {
                        if (!is_array($timeExecuteMemoryUsageIteration)) {
                            throw new RuntimeException(
                                sprintf('The benchmark %s in package version %s and group named %s must contain an array of iterations, where each element must be represented as a non-empty array with keys matching the public properties of class %s.', var_export($benchmarkDescription, true), var_export($version, true), var_export($groupName, true), TimeExecuteMemoryUsageInIteration::class)
                            );
                        }

                        $results[$version][$groupName][$benchmarkDescription][] = $timeExecuteMemoryUsageIteration;
                    }
                }
            }
        }

        return $results;
    }
}
