<?php

declare(strict_types=1);

namespace Kaspi\Benchmark;

use Generator;
use JsonException;
use Kaspi\Benchmark\DTO\EnvBenchmark;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use RuntimeException;

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
 * @phpstan-type BenchmarkEnvHashType non-empty-string
 * @phpstan-type BenchmarkEnvType array{
 * phpVersionId: int,
 * opcacheEnableCli: bool,
 * }
 * @phpstan-type TimeExecuteMemoryUsageInIterationType array{
 *     startBytesUsage: int,
 *     startBytesUsageReal: int,
 *     endBytesUsage: int,
 *     endBytesUsageReal: int,
 *     bytesPeakUsage: int,
 *     bytesPeakUsageReal: int,
 *     startTime: float,
 *     endTime: float,
 *     numberOfTimes: int,
 * }
 * @phpstan-type BenchmarkGroupsType array<non-empty-string, array<non-empty-string, non-empty-list<TimeExecuteMemoryUsageInIterationType>>>
 * @phpstan-type FileResultsType array{}|array<BenchmarkEnvHashType, array{
 *          env: BenchmarkEnvType,
 *          packageVersion: array<non-empty-string, BenchmarkGroupsType>
 *      }>
 */
final class BenchmarkResultsFile
{
    private const ENV_SECTION_KEY = 'env';
    private const PACKAGE_VERSION_KEY = 'packageVersion';

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
            $fileResults[$benchmarkResults->env->toHash()][self::ENV_SECTION_KEY] = (array) $benchmarkResults->env;
            $resultsToFile = [];
            $fileResults[$benchmarkResults->env->toHash()][self::PACKAGE_VERSION_KEY] = &$resultsToFile;

            /**
             * @var Generator<TimeExecuteMemoryUsageInIteration> $timeExecuteMemoryUsageInIterationItems
             */
            foreach ($benchmarkResults->getResults() as $benchmarkDescription => $timeExecuteMemoryUsageInIterationItems) {
                $items = [];

                foreach ($timeExecuteMemoryUsageInIterationItems as $timeExecuteMemoryUsageInIteration) {
                    $items[] = (array) $timeExecuteMemoryUsageInIteration;
                }

                $resultsToFile[$benchmarkResults->packageVersion][$benchmarkResults->groupName][$benchmarkDescription] = $items;
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

        foreach ($fileResults as $fileData) {
            [self::ENV_SECTION_KEY => $fileEnv, self::PACKAGE_VERSION_KEY => $filePackageVersions] = $fileData;
            $envBenchmark = new EnvBenchmark(...$fileEnv);
            foreach ($filePackageVersions as $packageVersion => $fileBenchmarkGroups) {
                foreach ($fileBenchmarkGroups as $fileGroupName => $fileBenchmarkResults) {
                    $benchmarkResults = new BenchmarkResults($packageVersion, $fileGroupName, $envBenchmark);

                    foreach ($fileBenchmarkResults as $fileBenchmarkDescription => $fileTimeExecuteMemoryUsageIterations) {
                        $benchmarkResults->attachIterations($fileBenchmarkDescription, $this->buildTimeExecuteMemoryUsageInIteration($fileTimeExecuteMemoryUsageIterations));
                    }

                    yield $benchmarkResults;
                }
            }
        }
    }

    /**
     * @param iterable<TimeExecuteMemoryUsageInIterationType> $fileTimeExecuteMemoryUsageIterations
     *
     * @return Generator<TimeExecuteMemoryUsageInIteration>
     */
    private function buildTimeExecuteMemoryUsageInIteration(iterable $fileTimeExecuteMemoryUsageIterations): Generator
    {
        foreach ($fileTimeExecuteMemoryUsageIterations as $args) {
            yield new TimeExecuteMemoryUsageInIteration(...$args);
        }
    }

    /**
     * @return FileResultsType
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
        } catch (JsonException $e) {
            throw new RuntimeException(
                'Unable to parse the JSON: '.$e->getMessage(),
                previous: $e,
            );
        }

        if (!is_array($resultsFromFile)) {
            return [];
        }

        $results = [];

        foreach ($resultsFromFile as $envHash => $data) {
            if (!is_string($envHash) || '' === $envHash) {
                throw new RuntimeException('Env hash must be a non-empty string.');
            }

            if (!is_array($data) || !isset($data[self::ENV_SECTION_KEY])) {
                throw new RuntimeException(
                    sprintf('Env hash "%s" must contain an "%s" section.', $envHash, self::ENV_SECTION_KEY)
                );
            }

            /** @var BenchmarkEnvType $env */
            $env = $data[self::ENV_SECTION_KEY];

            if (!is_array($env) || 0 === count($env)) {
                throw new RuntimeException(
                    sprintf('The section "%s" defined in env hash "%s" must be a non-empty array with keys corresponding to the public properties of the %s class.', self::ENV_SECTION_KEY, $envHash, EnvBenchmark::class)
                );
            }

            $results[$envHash][self::ENV_SECTION_KEY] = $env;

            if (!isset($data[self::PACKAGE_VERSION_KEY])) {
                throw new RuntimeException(
                    sprintf('The section "%s" not defined in env hash "%s".', self::PACKAGE_VERSION_KEY, $envHash)
                );
            }

            $versions = $data[self::PACKAGE_VERSION_KEY];

            if (!is_array($versions) || 0 === count($versions)) {
                throw new RuntimeException(
                    sprintf('The section "%s" defined in env hash "%s" must be non-empty array.', self::PACKAGE_VERSION_KEY, $envHash)
                );
            }

            foreach ($versions as $version => $groups) {
                if (!is_string($version) || '' === $version) {
                    throw new RuntimeException(
                        sprintf('The package version defined in env hash "%s" must be a non-empty string.', $envHash)
                    );
                }

                if (!is_array($groups) || 0 === count($groups)) {
                    throw new RuntimeException(
                        sprintf('A package of version %s defined in env hash "%s" must contain benchmark groups as a non-empty array.', var_export($version, true), $envHash)
                    );
                }

                foreach ($groups as $groupName => $benchmarkResults) {
                    if (!is_string($groupName) || '' === $groupName) {
                        throw new RuntimeException(
                            sprintf('Package version %s defined in env hash "%s" must contain benchmark groups as a non-empty array where each group name is a non-empty string.', var_export($version, true), $envHash)
                        );
                    }

                    if (!is_array($benchmarkResults) || 0 === count($benchmarkResults)) {
                        throw new RuntimeException(
                            sprintf('Package version %s defined in env hash "%s" with group name %s must contain benchmark results as a non-empty array.', var_export($version, true), $envHash, var_export($groupName, true))
                        );
                    }

                    foreach ($benchmarkResults as $benchmarkDescription => $timeExecuteMemoryUsageIterationsItems) {
                        if (!is_string($benchmarkDescription) || '' === $benchmarkDescription) {
                            throw new RuntimeException(
                                sprintf('Package version %s defined in env hash "%s" with group name %s must contain a benchmark description as a non-empty string.', var_export($version, true), $envHash, var_export($groupName, true))
                            );
                        }

                        if (!is_array($timeExecuteMemoryUsageIterationsItems) || 0 === count($timeExecuteMemoryUsageIterationsItems)) {
                            throw new RuntimeException(
                                sprintf('Package version %s defined in env hash "%s" with group name %s and benchmark %s must contain iteration elements as a non-empty array.', var_export($version, true), $envHash, var_export($groupName, true), var_export($benchmarkDescription, true))
                            );
                        }

                        /**
                         * @var TimeExecuteMemoryUsageInIterationType $timeExecuteMemoryUsageIteration
                         */
                        foreach ($timeExecuteMemoryUsageIterationsItems as $timeExecuteMemoryUsageIteration) {
                            if (!is_array($timeExecuteMemoryUsageIteration) || 0 === count($timeExecuteMemoryUsageIteration)) {
                                throw new RuntimeException(
                                    sprintf('Package version %s defined in env hash "%s" with group name %s and benchmark %s must contain an array of iterations, where each element must be represented as a non-empty array with keys matching the public properties of class %s.', var_export($version, true), $envHash, var_export($groupName, true), var_export($benchmarkDescription, true), TimeExecuteMemoryUsageInIteration::class)
                                );
                            }

                            $results[$envHash][self::PACKAGE_VERSION_KEY][$version][$groupName][$benchmarkDescription][] = $timeExecuteMemoryUsageIteration;
                        }
                    }
                }
            }
        }

        return $results;
    }
}
