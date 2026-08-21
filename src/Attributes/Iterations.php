<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Attributes;

use Attribute;

use function max;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Iterations
{
    public readonly int $iterations;

    /**
     * @param positive-int $iterations
     */
    public function __construct(int $iterations = 1)
    {
        $this->iterations = max($iterations, 1);
    }
}
