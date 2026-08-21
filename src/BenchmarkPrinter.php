<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Core;

use function array_key_last;
use function array_shift;
use function count;
use function explode;
use function printf;
use function wordwrap;

final class BenchmarkPrinter
{
    /**
     * @var list<BenchmarkResults>
     */
    private array $benchmarkResultsCollection;
    public function attach(BenchmarkResults $benchmarkResults): self
    {
        $this->benchmarkResultsCollection[] = $benchmarkResults;

        return $this;
    }

    public function reset(): void
    {
        unset($this->benchmarkResultsCollection);
    }

    public function printEachVersion(): void
    {
        $this->collectionIsEmpty();

        $currentPackageVersion = null;
        $formatResult = "\n| %-38s | %-5s | %-5s | %-11s | %-11s | %-11s |";
        $formatTableLineSeparator = "\n+%'-40s+%'-7s+%'-7s+%'-13s+%'-13s+%'-13s+";

        $tableHead = <<< TABLEHEAD

+----------------------------------------+-------+-------+---------------------------+-------------+
| Benchmark description                  |       | Num.  |         Memory            |    Time     |
|                                        | Iter. | of    +-------------+-------------+  execution  |
|                                        |       | times |  Allocated  |    Peak     |             |
TABLEHEAD;

        foreach ($this->benchmarkResultsCollection as $benchmarkResult) {
            if ($currentPackageVersion !== $benchmarkResult->packageVersion) {
                printf("\n\n+%'-98s+", '');
                printf("\n| %-96s |", $benchmarkResult->packageVersion);
                print $tableHead;
                $currentPackageVersion = $benchmarkResult->packageVersion;
                printf("\n+%'-98s+", '');
            }

            printf("\n| %-96s |", $benchmarkResult->groupName);
            printf($formatTableLineSeparator, '', '', '', '', '', '');

            $timeExecuteMemoryUsingTotalItems =  $benchmarkResult->getTimeExecuteMemoryUsingTotalItems();


            foreach ($timeExecuteMemoryUsingTotalItems as $benchmarkDescription => $timeExecuteMemoryUsingTotal) {
                $description = explode("\n", wordwrap($benchmarkDescription, 38, cut_long_words: true));

                printf(
                    $formatResult,
                    $description[0],
                    $timeExecuteMemoryUsingTotal->iterations,
                    $timeExecuteMemoryUsingTotal->numberOfTimes,
                    Formatter::formatBytes($timeExecuteMemoryUsingTotal->memoryUsageUsage, 4),
                    Formatter::formatBytes($timeExecuteMemoryUsingTotal->memoryPeakUsage, 4),
                    Formatter::formatTimeExecute($timeExecuteMemoryUsingTotal->hrTime, 4),
                );

                for ($i = 1, $c = count($description); $i < $c; $i++) {
                    printf($formatResult, $description[$i], '', '', '', '', '');
                }

                printf($formatTableLineSeparator, '', '', '', '', '', '');
            }
        }

        print "\n";
    }

    public function printCompareVersions(): void
    {
        $this->collectionIsEmpty();
        $tableResults = [];

        // collect results group by "benchmark group name", "benchmark description", "package version".
        foreach ($this->benchmarkResultsCollection as $benchmarkResult) {
            foreach ($benchmarkResult->getTimeExecuteMemoryUsingTotalItems() as $benchmarkDescription => $timeExecuteMemoryUsingTotal) {
                $tableResults[$benchmarkResult->groupName][$benchmarkDescription][$benchmarkResult->packageVersion] = $timeExecuteMemoryUsingTotal;
            }
        }

        $formatGroup = "\n| %-98s |";
        $formatResult = "\n| %30s | %7s | %-5s | %-5s | %-11s | %-11s | %-11s |";
        $formatDivResult = "\n| %30s +%'-9s+%'-7s+%'-7s+%'-13s+%'-13s+%'-13s+";
        $formatLineDescription = "\n| %30s |%-9s|%-7s|%-7s|%-13s|%-13s|%-13s|";
        $formatLineBound = "\n+%'-32s+%'-9s+%'-7s+%'-7s+%'-13s+%'-13s+%'-13s+";

        print <<< TABLEHEAD
+--------------------------------+---------+-------+-------+---------------------------+-------------+
| Benchmarks group               | Package | Iter. |  Num. |         Memory            |    Time     |
|  ↘️  Benchmark description     | version |       |   of  +-------------+-------------+  execution  |
|                                |         |       | times |  Allocated  |    Peak     |             |
TABLEHEAD;

        printf($formatLineBound, '', '', '', '', '', '', '');

        foreach ($tableResults as $groupName => $benchmarkResults) {
            printf($formatGroup, $groupName);
            printf($formatLineBound, '', '', '', '', '', '', '');

            foreach ($benchmarkResults as $benchmarkDescription => $packageVersions) {
                $descriptionWrap = explode("\n", wordwrap($benchmarkDescription, 30, cut_long_words: true));
                $lastPackageVersion = array_key_last($packageVersions);
                /**
                 * @var string $packageVersion
                 * @var TimeExecuteMemoryUsingTotal $timeExecMemTotal
                 */
                foreach ($packageVersions as $packageVersion => $timeExecMemTotal) {
                    $packageVersionPrint = \strlen($packageVersion) > 7
                        ? substr($packageVersion, 0, 6).'…'
                        : $packageVersion;
                    $descriptionWrapLine = array_shift($descriptionWrap);

                    printf(
                        $formatResult,
                        $descriptionWrapLine,
                        $packageVersionPrint,
                        $timeExecMemTotal->iterations,
                        $timeExecMemTotal->numberOfTimes,
                        Formatter::formatBytes($timeExecMemTotal->memoryUsageUsage, 4),
                        Formatter::formatBytes($timeExecMemTotal->memoryPeakUsage, 4),
                        Formatter::formatTimeExecute($timeExecMemTotal->hrTime, 4),
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

        print "\n";
    }

    private function collectionIsEmpty(): void
    {
        if (!isset($this->benchmarkResultsCollection)) {
            print "Benchmark results collection is empty.\n";

            exit(1);
        }
    }
}
