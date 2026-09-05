<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\DTO;

use Stringable;

use function implode;
use function intdiv;
use function md5;
use function sprintf;

final class EnvBenchmark implements Stringable
{
    public function __construct(public readonly int $phpVersionId, public readonly bool $opcacheEnableCli) {}

    public function __toString(): string
    {
        $opCache = $this->opcacheEnableCli ? 'on' : 'off';
        $major = intdiv($this->phpVersionId, 10_000);
        $minor = intdiv($this->phpVersionId % 10_000, 100);
        $release = $this->phpVersionId % 100;

        return sprintf('PHP runtime: %d.%d.%d , OPCache: %s', $major, $minor, $release, $opCache);
    }

    public function toHash(): string
    {
        return md5(implode('|', [$this->phpVersionId, $this->opcacheEnableCli]));
    }
}
