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

+--------------------------------------------------------------------------------------------------------+
| v1.0.0                                                                                                 |
+--------------------------------+-------+-------+-----------------------------------------+-------------+
| Benchmark description          |       | Num.  | Memory                                  | Time        |
|                                | Iter. | of    +-------------+-------------+-------------+ execution   |
|                                |       | times |  Usage      | Peak        | Leak        | per iterate |
+--------------------------------------------------------------------------------------------------------+
| Foo group                                                                                              |
+--------------------------------+-------+-------+-------------+-------------+-------------+-------------+
| Lorem ipsum dolor sit amet,    | 2     | 2     | 170 B       | 270 B       | 170 B       | 7.39 ns     |
| consectetur adipiscing elit.   |       |       |             |             |             |             |
| Cras porta eleifend ante ut    |       |       |             |             |             |             |
| maximus.                       |       |       |             |             |             |             |
+--------------------------------+-------+-------+-------------+-------------+-------------+-------------+
| Lorem ipsum dolor sit amet     | 1     | 2     | 10 B        | 0 B         | 10 B        | 2.17 ns     |
+--------------------------------+-------+-------+-------------+-------------+-------------+-------------+

+--------------------------------------------------------------------------------------------------------+
| v2.0.x-dev                                                                                             |
+--------------------------------+-------+-------+-----------------------------------------+-------------+
| Benchmark description          |       | Num.  | Memory                                  | Time        |
|                                | Iter. | of    +-------------+-------------+-------------+ execution   |
|                                |       | times |  Usage      | Peak        | Leak        | per iterate |
+--------------------------------------------------------------------------------------------------------+
| Foo group                                                                                              |
+--------------------------------+-------+-------+-------------+-------------+-------------+-------------+
| Lorem ipsum dolor sit amet,    | 2     | 2     | 100 B       | 0 B         | 100 B       | 6.18 ns     |
| consectetur adipiscing elit.   |       |       |             |             |             |             |
| Cras porta eleifend ante ut    |       |       |             |             |             |             |
| maximus.                       |       |       |             |             |             |             |
+--------------------------------+-------+-------+-------------+-------------+-------------+-------------+
| Lorem ipsum dolor sit amet     | 1     | 2     | 0 B         | 0 B         | 0 B         | 1.955 ns    |
+--------------------------------+-------+-------+-------------+-------------+-------------+-------------+
');
        $printer->printEachVersion();

        $printer->reset();

        $this->expectException(InvalidArgumentException::class);

        $printer->printEachVersion();
    }
}
