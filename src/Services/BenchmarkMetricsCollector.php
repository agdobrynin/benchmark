<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Services;

use Generator;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use RuntimeException;

use function gc_collect_cycles;
use function gc_enable;
use function gc_enabled;
use function hrtime;
use function memory_get_peak_usage;
use function memory_get_usage;
use function sprintf;

final class BenchmarkMetricsCollector
{
    /** @var list<TimeExecuteMemoryUsageInIteration> */
    private array $iterations;

    private float $startTime;

    private int $startMemoryUsage;

    private int $startMemoryUsageReal;

    public function __construct(
        private readonly bool $runGarbageCollector,
        private readonly int $numberOfTimes,
    ) {
        if ($runGarbageCollector && !gc_enabled()) {
            gc_enable();
        }
    }

    public function start(): void
    {
        $this->startTime = hrtime(true);
        unset($this->iterations);

        if ($this->runGarbageCollector) {
            gc_collect_cycles();
        }

        $this->startMemoryUsage = memory_get_usage();
        $this->startMemoryUsageReal = memory_get_usage(true);
    }

    /**
     * @throws RuntimeException
     */
    public function end(): void
    {
        if (!isset($this->startTime, $this->startMemoryUsage, $this->startMemoryUsageReal)) {
            throw new RuntimeException(
                sprintf('Before calling the `%s()` method, you must call the `%s::start()` method.', __METHOD__, __CLASS__)
            );
        }

        $endHrTime = hrtime(true);

        if ($this->runGarbageCollector) {
            gc_collect_cycles();
        }

        $endMemoryUsage = memory_get_usage();
        $endMemoryUsageReal = memory_get_usage(true);
        $memoryPeakUsage = memory_get_peak_usage();
        $memoryPeakUsageReal = memory_get_peak_usage(true);

        $this->iterations[] = new TimeExecuteMemoryUsageInIteration(
            startBytesUsage: $this->startMemoryUsage,
            startBytesUsageReal: $this->startMemoryUsageReal,
            endBytesUsage: $endMemoryUsage,
            endBytesUsageReal: $endMemoryUsageReal,
            bytesPeakUsage: $memoryPeakUsage,
            bytesPeakUsageReal: $memoryPeakUsageReal,
            startTime: $this->startTime,
            endTime: $endHrTime,
            numberOfTimes: $this->numberOfTimes,
        );
    }

    /**
     * @return Generator<TimeExecuteMemoryUsageInIteration>
     */
    public function iterations(): Generator
    {
        yield from $this->iterations ?? [];
    }
}
