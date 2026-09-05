<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\DTO;

use function implode;
use function md5;

final class EnvBenchmark
{
    public function __construct(public readonly int $phpVersionId, public readonly bool $opcacheEnableCli) {}

    public function toHash(): string
    {
        return md5(implode('|', [$this->phpVersionId, $this->opcacheEnableCli]));
    }
}
