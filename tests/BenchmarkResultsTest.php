<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests;

use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageTotal;
use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function round;

/**
 * @internal
 */
#[CoversClass(BenchmarkResults::class)]
#[UsesClass(TimeExecuteMemoryUsageIteration::class)]
#[UsesClass(TimeExecuteMemoryUsageTotal::class)]
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
            new TimeExecuteMemoryUsageIteration(1, 2, 2, 2, 10.22, 10.99, 2)
        );

        self::assertTrue($this->results->getResults()->valid());
        self::assertTrue($this->results->getTimeExecuteMemoryUsingTotalItems()->valid());

        $this->results->reset();

        self::assertFalse($this->results->getResults()->valid());
        self::assertFalse($this->results->getTimeExecuteMemoryUsingTotalItems()->valid());
    }

    public function testAttachOneIteration(): void
    {
        self::assertFalse($this->results->getResults()->valid());

        // do attach item
        $this->results->attachIteration(
            'Bar',
            new TimeExecuteMemoryUsageIteration(1, 2, 2, 2, 10.22, 10.99, 2)
        );
        $this->results->attachIteration(
            'Bar',
            new TimeExecuteMemoryUsageIteration(1, 2, 2, 2, 10.22, 10.99, 2)
        );

        $iterations = $this->results->getResults();

        self::assertTrue($iterations->valid());
        self::assertEquals('Bar', $iterations->key());

        $items = $iterations->current();
        self::assertCount(2, $items);

        self::assertInstanceOf(TimeExecuteMemoryUsageIteration::class, $items[0]);
        self::assertInstanceOf(TimeExecuteMemoryUsageIteration::class, $items[1]);
    }

    public function testAttachAllIterations(): void
    {
        self::assertFalse($this->results->getResults()->valid());

        // do attach items
        $this->results->attachIterations(
            'Bar',
            [
                new TimeExecuteMemoryUsageIteration(1, 2, 2, 2, 10.22, 10.99, 2),
                new TimeExecuteMemoryUsageIteration(1, 2, 2, 2, 10.22, 10.99, 2),
            ]
        );

        self::assertTrue($this->results->getResults()->valid());

        $iterations = $this->results->getResults();

        self::assertEquals('Bar', $iterations->key());

        $items = $iterations->current();

        self::assertCount(2, $items);

        self::assertInstanceOf(TimeExecuteMemoryUsageIteration::class, $items[0]);
        self::assertInstanceOf(TimeExecuteMemoryUsageIteration::class, $items[1]);
    }

    public function testCalculateTotalFromEmptyIterations(): void
    {
        // do attach items
        $this->results->attachIterations('Bar', []);

        $totals = $this->results->getTimeExecuteMemoryUsingTotalItems();

        self::assertFalse($totals->valid());
    }

    public function testCalculateTotals(): void
    {
        // do attach items
        $this->results->attachIterations(
            'Bar',
            [
                new TimeExecuteMemoryUsageIteration(1, 2, 2, 2, 10.22, 10.99, 2),
                new TimeExecuteMemoryUsageIteration(2, 3, 2, 3, 11.99, 12.05, 2),
            ]
        );

        $totalItems = $this->results->getTimeExecuteMemoryUsingTotalItems();

        self::assertTrue($totalItems->valid());
        self::assertEquals('Bar', $totalItems->key());
        $total = $totalItems->current();

        self::assertEquals(2, $total->iterations);
        self::assertEquals(2, $total->numberOfTimes);
        self::assertEquals(2, $total->memoryUsage);
        self::assertEquals(1, $total->memoryPeakUsage);
        self::assertEquals(0.4150, round($total->hrTime, 4));

        // test cached data
        self::assertSame(
            $totalItems->current(),
            $this->results->getTimeExecuteMemoryUsingTotalItems()->current()
        );
    }
}
