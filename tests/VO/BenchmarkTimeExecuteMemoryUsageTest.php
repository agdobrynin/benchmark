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
        self::assertEquals(0, $b->bytesUsage);
        self::assertEquals(0.0, $b->time);
        self::assertEquals(0, $b->numberOfTimes);
        self::assertEquals(0, $b->iterations);
    }

    #[TestWith([
        [
            new TimeExecuteMemoryUsageInIteration(100, 120, 120, 1, 1.2, 2),
            new TimeExecuteMemoryUsageInIteration(120, 150, 159, 1.4, 1.9, 2),
            new TimeExecuteMemoryUsageInIteration(150, 106, 160, 2, 3, 2),
        ],
        30,
        160,
        0.2833,
        2,
        3,
    ])]
    public function testTimeExecuteAndMemory(array $data, int $memUsage, int $memPeakUsage, float $timeExec, int $numOfTimes, int $iters): void
    {
        $b = new BenchmarkTimeExecuteMemoryUsage($data);

        self::assertEquals($memUsage, $b->bytesUsage);
        self::assertEquals($memPeakUsage, $b->bytesPeakUsage);
        self::assertEquals($timeExec, round($b->time, 4));
        self::assertEquals($numOfTimes, $b->numberOfTimes);
        self::assertEquals($iters, $b->iterations);
    }

    public function testListTypeException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('given type "stdClass"');

        new BenchmarkTimeExecuteMemoryUsage([
            new TimeExecuteMemoryUsageInIteration(1, 1, 1, 1, 1, 1),
            'foo' => new stdClass(),
        ]);
    }
}
