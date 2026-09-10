<?php

declare(strict_types=1);

namespace Kaspi\Benchmark;

use InvalidArgumentException;
use Kaspi\Benchmark\VO\BenchmarkTimeExecuteMemoryUsage;

use function array_key_last;
use function array_shift;
use function count;
use function explode;
use function printf;
use function strlen;
use function substr;
use function wordwrap;

final class BenchmarkPrinter
{
    /**
     * @var array<non-empty-string, list<BenchmarkResults>>
     */
    private array $benchmarkResultsCollectionGroupByEnvHash;

    public function attach(BenchmarkResults $benchmarkResults, BenchmarkResults ...$_): self
    {
        $this->benchmarkResultsCollectionGroupByEnvHash[$benchmarkResults->env->toHash()][] = $benchmarkResults;

        foreach ($_ as $__) {
            $this->benchmarkResultsCollectionGroupByEnvHash[$__->env->toHash()][] = $__;
        }

        return $this;
    }

    public function reset(): void
    {
        unset($this->benchmarkResultsCollectionGroupByEnvHash);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function printEachVersion(): void
    {
        $this->collectionIsEmpty();

        $formatResult = "\n| %-38s | %-5s | %-5s | %-11s | %-11s | %-11s |";
        $formatResultMemRealSeparator = "\n| %-38s |%-7s|%-7s+%'-13s+%'-13s+%-13s|";
        $formatTableLineSeparator = "\n+%'-40s+%'-7s+%'-7s+%'-13s+%'-13s+%'-13s+";

        $tableHead = <<< 'TABLEHEAD'

+----------------------------------------+-------+-------+---------------------------+-------------+
| Benchmark description                  | Iter. | Num.  | Memory (max)              | Time        |
|                                        |       | of    +-------------+-------------+ execution   |
|                                        |       | times | Usage code  | Peak code   | per iterate |
|                                        |       |       +-------------+-------------+             |
|                                        |       |       | Usage real  | Peak real   |             |
TABLEHEAD;

        $currentPackageVersion = $currentEnvHash = null;

        foreach ($this->benchmarkResultsCollectionGroupByEnvHash as $envHash => $benchmarkResultsList) {
            if ($currentEnvHash !== $envHash) {
                printf("\n+%'-98s+", '');

                $envLines = explode("\n", wordwrap((string) $benchmarkResultsList[0]->env, 96, cut_long_words: true));
                foreach ($envLines as $envLine) {
                    printf("\n| %-96s |", $envLine);
                }

                printf("\n+%'-98s+", '');
                $currentEnvHash = $benchmarkResultsList[0]->env->toHash();
            }

            foreach ($benchmarkResultsList as $benchmarkResults) {
                if ($currentPackageVersion !== $benchmarkResults->packageVersion) {
                    printf("\n| %-96s |", $benchmarkResults->packageVersion);
                    echo $tableHead;
                    $currentPackageVersion = $benchmarkResults->packageVersion;
                    printf("\n+%'-98s+", '');
                }

                printf("\n| %-96s |", $benchmarkResults->groupName);
                printf($formatTableLineSeparator, '', '', '', '', '', '');

                $timeExecuteMemoryUsingTotalItems = $benchmarkResults->getBenchmarkTimeExecuteMemoryUsageItems();

                foreach ($timeExecuteMemoryUsingTotalItems as $benchmarkDescription => $benchmarkTimeExecuteMemoryUsage) {
                    $description = explode("\n", wordwrap($benchmarkDescription, 38, cut_long_words: true));

                    printf(
                        $formatResult,
                        $description[0],
                        $benchmarkTimeExecuteMemoryUsage->iterations,
                        $benchmarkTimeExecuteMemoryUsage->numberOfTimes,
                        Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesUsage),
                        Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesPeakUsage),
                        Formatter::formatTimeExecute($benchmarkTimeExecuteMemoryUsage->time, 4),
                    );
                    printf($formatResultMemRealSeparator, $description[1] ?? '', '', '', '', '', '');
                    printf(
                        $formatResult,
                        $description[2] ?? '',
                        '',
                        '',
                        Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesUsageReal),
                        Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesPeakUsageReal),
                        '',
                    );

                    for ($i = 3, $c = count($description); $i < $c; ++$i) {
                        printf($formatResult, $description[$i], '', '', '', '', '');
                    }

                    printf($formatTableLineSeparator, '', '', '', '', '', '');
                }
            }
        }

        echo "\n";
    }

    /**
     * @throws InvalidArgumentException
     */
    public function printCompareVersions(): void
    {
        $this->collectionIsEmpty();
        $tableResults = [];
        $mapEnvHashesPrintedVersion = [];

        // collect results group by "benchmark env", "benchmark group name", "benchmark description", "package version".
        foreach ($this->benchmarkResultsCollectionGroupByEnvHash as $envHash => $benchmarkResultsList) {
            foreach ($benchmarkResultsList as $benchmarkResults) {
                $mapEnvHashesPrintedVersion[$envHash] ??= (string) $benchmarkResults->env;
                foreach ($benchmarkResults->getBenchmarkTimeExecuteMemoryUsageItems() as $benchmarkDescription => $benchmarkTimeExecuteMemoryUsage) {
                    $tableResults[$envHash][$benchmarkResults->groupName][$benchmarkDescription][$benchmarkResults->packageVersion] = $benchmarkTimeExecuteMemoryUsage;
                }
            }
        }

        $formatGroup = "\n| %-98s |";
        $formatResult = "\n| %30s | %7s | %-5s | %-5s | %-11s | %-11s | %-11s |";
        $formatResultMemRealSeparator = "\n| %30s |%-9s|%-7s|%-7s+%'-13s+%'-13s+%-13s|";
        $formatDivResult = "\n| %30s +%'-9s+%'-7s+%'-7s+%'-13s+%'-13s+%'-13s+";
        $formatLineDescription = "\n| %30s |%-9s|%-7s|%-7s|%-13s|%-13s|%-13s|";
        $formatLineBound = "\n+%'-32s+%'-9s+%'-7s+%'-7s+%'-13s+%'-13s+%'-13s+";

        $tableHeader = <<< 'TABLEHEAD'

+--------------------------------+---------+-------+-------+---------------------------+-------------+
| Benchmarks group               | Package | Iter. | Num.  | Memory (max)              | Time        |
|  ↘️  Benchmark description     | version |       | of    +-------------+-------------+ execution   |
|                                |         |       | times | Usage code  | Peak code   | per iterate |
|                                |         |       |       +-------------+-------------+             |
|                                |         |       |       | Usage real  | Peak real   |             |
+--------------------------------+---------+-------+-------+-------------+-------------+-------------+
TABLEHEAD;

        $currentEnvHash = null;

        foreach ($tableResults as $envHash => $groupedResults) {
            if ($currentEnvHash !== $envHash) {
                printf("\n+%'-100s+", '');

                $envLines = explode("\n", wordwrap($mapEnvHashesPrintedVersion[$envHash], 98, cut_long_words: true));

                foreach ($envLines as $envLine) {
                    printf("\n| %-98s |", $envLine);
                }

                $currentEnvHash = $envHash;
                echo $tableHeader;
            }

            foreach ($groupedResults as $groupName => $benchmarkNameWithPackageVersions) {
                printf($formatGroup, $groupName);
                printf($formatLineBound, '', '', '', '', '', '', '');

                foreach ($benchmarkNameWithPackageVersions as $benchmarkDescription => $packageVersions) {
                    $descriptionWrap = explode("\n", wordwrap($benchmarkDescription, 30, cut_long_words: true));
                    $lastPackageVersion = array_key_last($packageVersions);

                    /**
                     * @var non-empty-string                $packageVersion
                     * @var BenchmarkTimeExecuteMemoryUsage $benchmarkTimeExecuteMemoryUsage
                     */
                    foreach ($packageVersions as $packageVersion => $benchmarkTimeExecuteMemoryUsage) {
                        $packageVersionPrint = strlen($packageVersion) > 7
                            ? substr($packageVersion, 0, 6).'…'
                            : $packageVersion;
                        $descriptionWrapLine = array_shift($descriptionWrap);

                        printf(
                            $formatResult,
                            $descriptionWrapLine,
                            $packageVersionPrint,
                            $benchmarkTimeExecuteMemoryUsage->iterations,
                            $benchmarkTimeExecuteMemoryUsage->numberOfTimes,
                            Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesUsage),
                            Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesPeakUsage),
                            Formatter::formatTimeExecute($benchmarkTimeExecuteMemoryUsage->time, 4),
                        );

                        $descriptionWrapLine = array_shift($descriptionWrap);
                        printf($formatResultMemRealSeparator, $descriptionWrapLine, '', '', '', '', '', '');

                        $descriptionWrapLine = array_shift($descriptionWrap);
                        printf(
                            $formatResult,
                            $descriptionWrapLine,
                            '',
                            '',
                            '',
                            Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesUsageReal),
                            Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesPeakUsageReal),
                            '',
                        );

                        if ($lastPackageVersion !== $packageVersion) {
                            $descriptionWrapLine = array_shift($descriptionWrap);
                            printf($formatDivResult, $descriptionWrapLine, '', '', '', '', '', '');
                        }
                    }

                    do {
                        $descriptionWrapLine = array_shift($descriptionWrap);
                        if (null === $descriptionWrapLine) {
                            printf($formatLineBound, '', '', '', '', '', '', '');
                        } else {
                            printf($formatLineDescription, $descriptionWrapLine, '', '', '', '', '', '');
                        }
                    } while (null !== $descriptionWrapLine);
                }
            }
        }

        echo "\n";
    }

    /**
     * @throws InvalidArgumentException
     */
    private function collectionIsEmpty(): void
    {
        if (!isset($this->benchmarkResultsCollectionGroupByEnvHash)) {
            throw new InvalidArgumentException('Benchmark results collection is empty.');
        }
    }
}
