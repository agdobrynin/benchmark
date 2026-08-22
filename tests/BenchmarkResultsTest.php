<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests;

use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageTotal;
use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function round;

/**
 * @internal
 */
#[CoversClass(BenchmarkResults::class)]
#[CoversClass(TimeExecuteMemoryUsageIteration::class)]
#[CoversClass(TimeExecuteMemoryUsageTotal::class)]
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

        $this->assertCount(1, $this->results->getResults());
        $this->assertCount(1, $this->results->getTimeExecuteMemoryUsingTotalItems());

        $this->results->reset();

        $this->assertCount(0, $this->results->getResults());
        $this->assertCount(0, $this->results->getTimeExecuteMemoryUsingTotalItems());
    }

    public function testAttachOneIteration(): void
    {
        $this->assertEquals([], $this->results->getResults());

        // do attach item
        $this->results->attachIteration(
            'Bar',
            new TimeExecuteMemoryUsageIteration(1, 2, 2, 2, 10.22, 10.99, 2)
        );
        $this->results->attachIteration(
            'Bar',
            new TimeExecuteMemoryUsageIteration(1, 2, 2, 2, 10.22, 10.99, 2)
        );

        $this->assertCount(1, $this->results->getResults());

        $iterations = $this->results->getResults()['Bar'];

        $this->assertCount(2, $iterations);

        $this->assertInstanceOf(TimeExecuteMemoryUsageIteration::class, $iterations[0]);
        $this->assertInstanceOf(TimeExecuteMemoryUsageIteration::class, $iterations[1]);
    }

    public function testAttachAllIterations(): void
    {
        $this->assertEquals([], $this->results->getResults());

        // do attach items
        $this->results->attachIterations(
            'Bar',
            [
                new TimeExecuteMemoryUsageIteration(1, 2, 2, 2, 10.22, 10.99, 2),
                new TimeExecuteMemoryUsageIteration(1, 2, 2, 2, 10.22, 10.99, 2),
            ]
        );

        $this->assertCount(1, $this->results->getResults());

        $iterations = $this->results->getResults()['Bar'];

        $this->assertCount(2, $iterations);

        $this->assertInstanceOf(TimeExecuteMemoryUsageIteration::class, $iterations[0]);
        $this->assertInstanceOf(TimeExecuteMemoryUsageIteration::class, $iterations[1]);
    }

    public function testCalculateTotalFromEmptyIterations(): void
    {
        // do attach items
        $this->results->attachIterations('Bar', []);

        $totals = $this->results->getTimeExecuteMemoryUsingTotalItems();

        $this->assertCount(0, $totals);
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

        $this->assertCount(1, $totalItems);

        $total = $totalItems['Bar'];

        $this->assertEquals(2, $total->iterations);
        $this->assertEquals(2, $total->numberOfTimes);
        $this->assertEquals(2, $total->memoryUsage);
        $this->assertEquals(1, $total->memoryPeakUsage);
        $this->assertEquals(0.4150, round($total->hrTime, 4));

        // test cached data
        $this->assertSame(
            $totalItems,
            $this->results->getTimeExecuteMemoryUsingTotalItems()
        );
    }
}
