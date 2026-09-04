<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\BenchmarkPrinter;

use InvalidArgumentException;
use Kaspi\Benchmark\BenchmarkPrinter;
use Kaspi\Benchmark\BenchmarkResults;
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
class PrintCompareVersionsTest extends TestCase
{
    #[DataProviderExternal(PrinterDataSet::class, 'benchmarkResults')]
    public function testPrintEachVersion(BenchmarkResults $res, BenchmarkResults ...$_): void
    {
        $printer = new BenchmarkPrinter();
        $printer->attach($res, ...$_);

        $this->expectOutputString('
+--------------------------------+---------+-------+-------+---------------------------+-------------+
| Benchmarks group               | Package | Iter. | Num.  | Memory (max)              | Time        |
|  ↘️  Benchmark description     | version |       | of    +-------------+-------------+ execution   |
|                                |         |       | times | Usage code  | Peak code   | per iterate |
|                                |         |       |       +-------------+-------------+             |
|                                |         |       |       | Usage real  | Peak real   |             |
+--------------------------------+---------+-------+-------+-------------+-------------+-------------+
| Foo group                                                                                          |
+--------------------------------+---------+-------+-------+-------------+-------------+-------------+
|    Lorem ipsum dolor sit amet, |  v1.0.0 | 2     | 2     | 170 B       | 270 B       | 7.39 ns     |
|   consectetur adipiscing elit. |         |       |       +-------------+-------------+             |
|    Cras porta eleifend ante ut |         |       |       | 1.66 KB     | 2.64 KB     |             |
|           maximus. Sed eget mi +---------+-------+-------+-------------+-------------+-------------+
| convallis, ultrices orci quis, | v2.0.x… | 2     | 2     | 100 B       | 0 B         | 6.18 ns     |
|      aliquet dolor. Donec eget |         |       |       +-------------+-------------+             |
|       tellus eu mauris lacinia |         |       |       | 1000 B      | 0 B         |             |
|                       finibus. |         |       |       |             |             |             |
+--------------------------------+---------+-------+-------+-------------+-------------+-------------+
|     Lorem ipsum dolor sit amet |  v1.0.0 | 2     | 2     | 16 B        | 0 B         | 2.395 ns    |
|                                |         |       |       +-------------+-------------+             |
|                                |         |       |       | 160 B       | 0 B         |             |
|                                +---------+-------+-------+-------------+-------------+-------------+
|                                | v2.0.x… | 2     | 2     | 40 B        | 0 B         | 1.705 ns    |
|                                |         |       |       +-------------+-------------+             |
|                                |         |       |       | 400 B       | 0 B         |             |
+--------------------------------+---------+-------+-------+-------------+-------------+-------------+
');
        $printer->printCompareVersions();

        $printer->reset();

        $this->expectException(InvalidArgumentException::class);

        $printer->printCompareVersions();
    }
}
