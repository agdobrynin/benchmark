<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\VO;

use InvalidArgumentException;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\VO\BenchmarkTimeExecuteMemoryUsage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

use function round;

/**
 * @internal
 */
#[CoversClass(BenchmarkTimeExecuteMemoryUsage::class)]
#[UsesClass(TimeExecuteMemoryUsageInIteration::class)]
class BenchmarkTimeExecuteMemoryUsageTest extends TestCase
{
    public function testInputDataIsEmpty(): void
    {
        $b = new BenchmarkTimeExecuteMemoryUsage([]);

        self::assertEquals(0, $b->bytesPeakUsage);
        self::assertEquals(0, $b->bytesPeakUsageReal);
        self::assertEquals(0, $b->bytesUsage);
        self::assertEquals(0, $b->bytesUsageReal);
        self::assertEquals(0.0, $b->time);
        self::assertEquals(0, $b->numberOfTimes);
        self::assertEquals(0, $b->iterations);
    }

    #[TestWith([
        [
            new TimeExecuteMemoryUsageInIteration(100, 1000, 120, 1200, 120, 1200, 1, 1.2, 2),
            new TimeExecuteMemoryUsageInIteration(120, 1200, 150, 1500, 159, 1590, 1.4, 1.9, 2),
            new TimeExecuteMemoryUsageInIteration(150, 1500, 106, 1060, 160, 1600, 2, 3, 2),
        ],
        30,
        300,
        160,
        1600,
        0.2833,
        2,
        3,
    ])]
    public function testTimeExecuteAndMemory(array $data, int $memUsage, int $memUsageReal, int $memPeakUsage, int $memPeakUsageReal, float $timeExec, int $numOfTimes, int $iters): void
    {
        $b = new BenchmarkTimeExecuteMemoryUsage($data);

        self::assertEquals($memUsage, $b->bytesUsage);
        self::assertEquals($memUsageReal, $b->bytesUsageReal);
        self::assertEquals($memPeakUsage, $b->bytesPeakUsage);
        self::assertEquals($memPeakUsageReal, $b->bytesPeakUsageReal);
        self::assertEquals($timeExec, round($b->time, 4));
        self::assertEquals($numOfTimes, $b->numberOfTimes);
        self::assertEquals($iters, $b->iterations);
    }

    public function testListTypeException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('given type "stdClass"');

        new BenchmarkTimeExecuteMemoryUsage([
            new TimeExecuteMemoryUsageInIteration(1, 1, 1, 1, 1, 1, 1, 1, 1),
            'foo' => new stdClass(),
        ]);
    }
}
