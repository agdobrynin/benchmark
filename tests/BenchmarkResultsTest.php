<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests;

use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\VO\BenchmarkTimeExecuteMemoryUsage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function round;

/**
 * @internal
 */
#[CoversClass(BenchmarkResults::class)]
#[CoversClass(BenchmarkTimeExecuteMemoryUsage::class)]
#[UsesClass(TimeExecuteMemoryUsageInIteration::class)]
class BenchmarkResultsTest extends TestCase
{
    protected BenchmarkResults $results;

    protected function setUp(): void
    {
        parent::setUp();
        $this->results = new BenchmarkResults('0.0.1', 'Foo');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->results);
    }

    public function testReset(): void
    {
        $this->results->attachIteration(
            'Bar',
            new TimeExecuteMemoryUsageInIteration(1, 2, 2, 10.22, 10.99, 2)
        );

        self::assertTrue($this->results->getResults()->valid());
        self::assertTrue($this->results->getBenchmarkTimeExecuteMemoryUsageItems()->valid());

        $this->results->reset();

        self::assertFalse($this->results->getResults()->valid());
        self::assertFalse($this->results->getBenchmarkTimeExecuteMemoryUsageItems()->valid());
    }

    public function testAttachOneIteration(): void
    {
        self::assertFalse($this->results->getResults()->valid());

        // do attach item
        $this->results->attachIteration(
            'Bar',
            new TimeExecuteMemoryUsageInIteration(1, 2, 2, 10.22, 10.99, 2)
        );
        $this->results->attachIteration(
            'Bar',
            new TimeExecuteMemoryUsageInIteration(1, 2, 2, 10.22, 10.99, 2)
        );

        $iterations = $this->results->getResults();

        self::assertTrue($iterations->valid());
        self::assertEquals('Bar', $iterations->key());

        $items = $iterations->current();

        self::assertTrue($items->valid());

        $item = $items->current();
        $key = $items->key();

        self::assertInstanceOf(TimeExecuteMemoryUsageInIteration::class, $item);
        self::assertEquals(0, $key);

        $items->next();

        $item = $items->current();
        $key = $items->key();

        self::assertInstanceOf(TimeExecuteMemoryUsageInIteration::class, $item);
        self::assertEquals(1, $key);

        $items->next();

        self::assertFalse($items->valid());
    }

    public function testAttachAllIterations(): void
    {
        self::assertFalse($this->results->getResults()->valid());

        // do attach items
        $this->results->attachIterations(
            'Bar',
            [
                new TimeExecuteMemoryUsageInIteration(1, 2, 2, 10.22, 10.99, 2),
                new TimeExecuteMemoryUsageInIteration(1, 2, 2, 10.22, 10.99, 2),
            ]
        );

        self::assertTrue($this->results->getResults()->valid());

        $iterations = $this->results->getResults();

        self::assertEquals('Bar', $iterations->key());

        $items = $iterations->current();

        self::assertTrue($items->valid());

        self::assertInstanceOf(TimeExecuteMemoryUsageInIteration::class, $items->current());
        self::assertEquals(0, $items->key());
        $items->next();
        self::assertInstanceOf(TimeExecuteMemoryUsageInIteration::class, $items->current());
        self::assertEquals(1, $items->key());
        $items->next();
        self::assertFalse($items->valid());
    }

    public function testCalculateTotalFromEmptyIterations(): void
    {
        // do attach items
        $this->results->attachIterations('Bar', []);

        $totals = $this->results->getBenchmarkTimeExecuteMemoryUsageItems();

        self::assertFalse($totals->valid());
    }

    public function testCalculateTotals(): void
    {
        // do attach items
        $this->results->attachIterations(
            'Bar',
            [
                new TimeExecuteMemoryUsageInIteration(1, 2, 2, 10.22, 10.99, 2),
                new TimeExecuteMemoryUsageInIteration(2, 3, 3, 11.99, 12.05, 2),
            ]
        );

        $totalItems = $this->results->getBenchmarkTimeExecuteMemoryUsageItems();

        self::assertTrue($totalItems->valid());
        self::assertEquals('Bar', $totalItems->key());

        /** @var BenchmarkTimeExecuteMemoryUsage $total */
        $total = $totalItems->current();

        self::assertEquals(2, $total->iterations);
        self::assertEquals(2, $total->numberOfTimes);
        self::assertEquals(2, $total->bytesUsage);
        self::assertEquals(3, $total->bytesPeakUsage);
        self::assertEquals(0.2075, round($total->time, 4));

        // test cached data
        self::assertSame(
            $totalItems->current(),
            $this->results->getBenchmarkTimeExecuteMemoryUsageItems()->current()
        );
    }
}
