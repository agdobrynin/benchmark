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
class PrintEachVersionTest extends TestCase
{
    #[DataProviderExternal(PrinterDataSet::class, 'benchmarkResults')]
    public function testPrintEachVersion(BenchmarkResults $res, BenchmarkResults ...$_): void
    {
        $printer = new BenchmarkPrinter();
        $printer->attach($res, ...$_);

        $this->expectOutputString('

+--------------------------------------------------------------------------------------------------+
| v1.0.0                                                                                           |
+----------------------------------------+-------+-------+---------------------------+-------------+
| Benchmark description                  | Iter. | Num.  | Memory (max)              | Time        |
|                                        |       | of    +-------------+-------------+ execution   |
|                                        |       | times | Usage code  | Peak code   | per iterate |
|                                        |       |       +-------------+-------------+             |
|                                        |       |       | Usage real  | Peak real   |             |
+--------------------------------------------------------------------------------------------------+
| Foo group                                                                                        |
+----------------------------------------+-------+-------+-------------+-------------+-------------+
| Lorem ipsum dolor sit amet,            | 2     | 2     | 0 B         | 0 B         | 0 ns        |
| consectetur adipiscing elit. Cras      |       |       +-------------+-------------+             |
| porta eleifend ante ut maximus. Sed    |       |       | 0 B         | 0 B         |             |
| eget mi convallis, ultrices orci quis, |       |       |             |             |             |
| aliquet dolor. Donec eget tellus eu    |       |       |             |             |             |
| mauris lacinia finibus.                |       |       |             |             |             |
+----------------------------------------+-------+-------+-------------+-------------+-------------+
| Lorem ipsum dolor sit amet             | 2     | 2     | 0 B         | 0 B         | 0 ns        |
|                                        |       |       +-------------+-------------+             |
|                                        |       |       | 0 B         | 0 B         |             |
+----------------------------------------+-------+-------+-------------+-------------+-------------+

+--------------------------------------------------------------------------------------------------+
| v2.0.x-dev                                                                                       |
+----------------------------------------+-------+-------+---------------------------+-------------+
| Benchmark description                  | Iter. | Num.  | Memory (max)              | Time        |
|                                        |       | of    +-------------+-------------+ execution   |
|                                        |       | times | Usage code  | Peak code   | per iterate |
|                                        |       |       +-------------+-------------+             |
|                                        |       |       | Usage real  | Peak real   |             |
+--------------------------------------------------------------------------------------------------+
| Foo group                                                                                        |
+----------------------------------------+-------+-------+-------------+-------------+-------------+
| Lorem ipsum dolor sit amet,            | 2     | 2     | 0 B         | 0 B         | 0 ns        |
| consectetur adipiscing elit. Cras      |       |       +-------------+-------------+             |
| porta eleifend ante ut maximus. Sed    |       |       | 0 B         | 0 B         |             |
| eget mi convallis, ultrices orci quis, |       |       |             |             |             |
| aliquet dolor. Donec eget tellus eu    |       |       |             |             |             |
| mauris lacinia finibus.                |       |       |             |             |             |
+----------------------------------------+-------+-------+-------------+-------------+-------------+
| Lorem ipsum dolor sit amet             | 2     | 2     | 0 B         | 0 B         | 0 ns        |
|                                        |       |       +-------------+-------------+             |
|                                        |       |       | 0 B         | 0 B         |             |
+----------------------------------------+-------+-------+-------------+-------------+-------------+
');
        $printer->printEachVersion();

        $printer->reset();

        $this->expectException(InvalidArgumentException::class);

        $printer->printEachVersion();
    }
}
