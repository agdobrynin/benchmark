<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\DTO;

use Stringable;

use function extension_loaded;
use function implode;
use function ini_get;
use function intdiv;
use function md5;
use function sprintf;
use function strtolower;

use const PHP_VERSION_ID;

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

    public static function fromCurrentEnv(): self
    {
        $opcacheEnableCli = false;

        if (extension_loaded('Zend OPcache')) {
            $opcacheCli = ini_get('opcache.enable_cli');
            $opcacheEnableCli = false !== $opcacheCli && ('1' === $opcacheCli || 'on' === strtolower($opcacheCli));
        }

        return new self(PHP_VERSION_ID, $opcacheEnableCli);
    }

    public function toHash(): string
    {
        return md5(implode('|', [$this->phpVersionId, $this->opcacheEnableCli]));
    }
}
