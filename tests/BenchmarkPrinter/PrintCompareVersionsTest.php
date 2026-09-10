<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkPrinter;

use InvalidArgumentException;
use Kaspi\Benchmark\BenchmarkPrinter;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\EnvBenchmark;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\Formatter;
use Kaspi\Benchmark\VO\BenchmarkTimeExecuteMemoryUsage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BenchmarkPrinter::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(TimeExecuteMemoryUsageInIteration::class)]
#[UsesClass(Formatter::class)]
#[UsesClass(BenchmarkTimeExecuteMemoryUsage::class)]
#[UsesClass(EnvBenchmark::class)]
class PrintCompareVersionsTest extends TestCase
{
    #[DataProviderExternal(PrinterDataSet::class, 'benchmarkResults')]
    public function testPrintEachVersion(BenchmarkResults $res, BenchmarkResults ...$_): void
    {
        $printer = new BenchmarkPrinter();
        $printer->attach($res, ...$_);

        $this->expectOutputString('
+----------------------------------------------------------------------------------------------------+
| PHP runtime: 8.1.0 , OPCache: off , OS: Linux 6.18.33.2-microsoft-standard-WSL2 #1 SMP             |
| PREEMPT_DYNAMIC Thu Jun 18 21:54:43 UTC 2026 x86_64                                                |
+--------------------------------+---------+-------+-------+---------------------------+-------------+
| Benchmarks group               | Package | Iter. | Num.  | Memory (max)              | Time exec.  |
|  ↘️  Benchmark description     | version |       | of    +-------------+-------------+-------------+
|                                |         |       | times | Usage code  | Peak code   | Avg iterate |
|                                |         |       |       +-------------+-------------+-------------+
|                                |         |       |       | Usage real  | Peak real   | Max iterate |
+--------------------------------+---------+-------+-------+-------------+-------------+-------------+
| Foo group                                                                                          |
+--------------------------------+---------+-------+-------+-------------+-------------+-------------+
|    Lorem ipsum dolor sit amet, |  v1.0.0 | 2     | 2     | 0 B         | 0 B         | 0 ns        |
|   consectetur adipiscing elit. |         |       |       +-------------+-------------+-------------+
|    Cras porta eleifend ante ut |         |       |       | 0 B         | 0 B         | 0 ns        |
|           maximus. Sed eget mi +---------+-------+-------+-------------+-------------+-------------+
| convallis, ultrices orci quis, | v2.0.x… | 2     | 2     | 0 B         | 0 B         | 0 ns        |
|      aliquet dolor. Donec eget |         |       |       +-------------+-------------+-------------+
|       tellus eu mauris lacinia |         |       |       | 0 B         | 0 B         | 0 ns        |
|                       finibus. |         |       |       |             |             |             |
+--------------------------------+---------+-------+-------+-------------+-------------+-------------+
|     Lorem ipsum dolor sit amet |  v1.0.0 | 2     | 2     | 0 B         | 0 B         | 0 ns        |
|                                |         |       |       +-------------+-------------+-------------+
|                                |         |       |       | 0 B         | 0 B         | 0 ns        |
|                                +---------+-------+-------+-------------+-------------+-------------+
|                                | v2.0.x… | 2     | 2     | 0 B         | 0 B         | 0 ns        |
|                                |         |       |       +-------------+-------------+-------------+
|                                |         |       |       | 0 B         | 0 B         | 0 ns        |
+--------------------------------+---------+-------+-------+-------------+-------------+-------------+
');
        $printer->printCompareVersions();

        $printer->reset();

        $this->expectException(InvalidArgumentException::class);

        $printer->printCompareVersions();
    }
}
