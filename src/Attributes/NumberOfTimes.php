<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Attributes;

use Attribute;

use function max;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class NumberOfTimes
{
    public readonly int $numberOfTimes;

    /**
     * @param positive-int $numberOfTimes
     */
    public function __construct(int $numberOfTimes = 1)
    {
        $this->numberOfTimes = max($numberOfTimes, 1);
    }
}
