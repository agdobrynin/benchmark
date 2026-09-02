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
     * @var list<BenchmarkResults>
     */
    private array $benchmarkResultsCollection;

    public function attach(BenchmarkResults $benchmarkResults, BenchmarkResults ...$_): self
    {
        $this->benchmarkResultsCollection[] = $benchmarkResults;

        foreach ($_ as $__) {
            $this->benchmarkResultsCollection[] = $__;
        }

        return $this;
    }

    public function reset(): void
    {
        unset($this->benchmarkResultsCollection);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function printEachVersion(): void
    {
        $this->collectionIsEmpty();

        $currentPackageVersion = null;
        $formatResult = "\n| %-38s | %-5s | %-5s | %-11s | %-11s | %-11s |";
        $formatTableLineSeparator = "\n+%'-40s+%'-7s+%'-7s+%'-13s+%'-13s+%'-13s+";

        $tableHead = <<< 'TABLEHEAD'

+----------------------------------------+-------+-------+---------------------------+-------------+
| Benchmark description                  | Iter. | Num.  | Memory                    | Time        |
|                                        |       | of    +-------------+-------------+ execution   |
|                                        |       | times | Usage       | Peak usage  | per iterate |
TABLEHEAD;

        foreach ($this->benchmarkResultsCollection as $benchmarkResult) {
            if ($currentPackageVersion !== $benchmarkResult->packageVersion) {
                printf("\n\n+%'-98s+", '');
                printf("\n| %-96s |", $benchmarkResult->packageVersion);
                echo $tableHead;
                $currentPackageVersion = $benchmarkResult->packageVersion;
                printf("\n+%'-98s+", '');
            }

            printf("\n| %-96s |", $benchmarkResult->groupName);
            printf($formatTableLineSeparator, '', '', '', '', '', '');

            $timeExecuteMemoryUsingTotalItems = $benchmarkResult->getBenchmarkTimeExecuteMemoryUsageItems();

            foreach ($timeExecuteMemoryUsingTotalItems as $benchmarkDescription => $benchmarkTimeExecuteMemoryUsage) {
                $description = explode("\n", wordwrap($benchmarkDescription, 38, cut_long_words: true));

                printf(
                    $formatResult,
                    $description[0],
                    $benchmarkTimeExecuteMemoryUsage->iterations,
                    $benchmarkTimeExecuteMemoryUsage->numberOfTimes,
                    Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesUsage, 4),
                    Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesPeakUsage, 4),
                    Formatter::formatTimeExecute($benchmarkTimeExecuteMemoryUsage->time, 4),
                );

                for ($i = 1, $c = count($description); $i < $c; ++$i) {
                    printf($formatResult, $description[$i], '', '', '', '', '');
                }

                printf($formatTableLineSeparator, '', '', '', '', '', '');
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

        // collect results group by "benchmark group name", "benchmark description", "package version".
        foreach ($this->benchmarkResultsCollection as $benchmarkResult) {
            foreach ($benchmarkResult->getBenchmarkTimeExecuteMemoryUsageItems() as $benchmarkDescription => $benchmarkTimeExecuteMemoryUsing) {
                $tableResults[$benchmarkResult->groupName][$benchmarkDescription][$benchmarkResult->packageVersion] = $benchmarkTimeExecuteMemoryUsing;
            }
        }

        $formatGroup = "\n| %-98s |";
        $formatResult = "\n| %30s | %7s | %-5s | %-5s | %-11s | %-11s | %-11s |";
        $formatDivResult = "\n| %30s +%'-9s+%'-7s+%'-7s+%'-13s+%'-13s+%'-13s+";
        $formatLineDescription = "\n| %30s |%-9s|%-7s|%-7s|%-13s|%-13s|%-13s|";
        $formatLineBound = "\n+%'-32s+%'-9s+%'-7s+%'-7s+%'-13s+%'-13s+%'-13s+";

        echo <<< 'TABLEHEAD'

+--------------------------------+---------+-------+-------+---------------------------+-------------+
| Benchmarks group               | Package | Iter. | Num.  | Memory                    | Time        |
|  ↘️  Benchmark description     | version |       | of    +-------------+-------------+ execution   |
|                                |         |       | times | Usage       | Peak usage  | per iterate |
TABLEHEAD;

        printf($formatLineBound, '', '', '', '', '', '', '');

        foreach ($tableResults as $groupName => $benchmarkResults) {
            printf($formatGroup, $groupName);
            printf($formatLineBound, '', '', '', '', '', '', '');

            foreach ($benchmarkResults as $benchmarkDescription => $packageVersions) {
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
                        Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesUsage, 4),
                        Formatter::formatBytes($benchmarkTimeExecuteMemoryUsage->bytesPeakUsage, 4),
                        Formatter::formatTimeExecute($benchmarkTimeExecuteMemoryUsage->time, 4),
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

        echo "\n";
    }

    /**
     * @throws InvalidArgumentException
     */
    private function collectionIsEmpty(): void
    {
        if (!isset($this->benchmarkResultsCollection)) {
            throw new InvalidArgumentException('Benchmark results collection is empty.');
        }
    }
}
