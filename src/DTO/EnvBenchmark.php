<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\DTO;

use Stringable;

use function extension_loaded;
use function implode;
use function ini_get;
use function intdiv;
use function md5;
use function php_uname;
use function sprintf;
use function strtolower;

use const PHP_VERSION_ID;

final class EnvBenchmark implements Stringable
{
    /**
     * @param non-empty-string $operatingSystem
     */
    public function __construct(
        public readonly int $phpVersionId,
        public readonly bool $opcacheEnableCli,
        public readonly string $operatingSystem,
    ) {}

    public function __toString(): string
    {
        $opCache = $this->opcacheEnableCli ? 'on' : 'off';
        $major = intdiv($this->phpVersionId, 10_000);
        $minor = intdiv($this->phpVersionId % 10_000, 100);
        $release = $this->phpVersionId % 100;

        return sprintf('PHP runtime: %d.%d.%d , OPCache: %s , OS: %s', $major, $minor, $release, $opCache, $this->operatingSystem);
    }

    public static function fromCurrentEnv(): self
    {
        $opcacheEnableCli = false;

        if (extension_loaded('Zend OPcache')) {
            $opcacheCli = ini_get('opcache.enable_cli');
            $opcacheEnableCli = false !== $opcacheCli && ('1' === $opcacheCli || 'on' === strtolower($opcacheCli));
        }

        $operatingSystem = sprintf('%s %s %s %s', php_uname('s'), php_uname('r'), php_uname('v'), php_uname('m'));

        return new self(PHP_VERSION_ID, $opcacheEnableCli, $operatingSystem);
    }

    /**
     * @return non-empty-string
     */
    public function toHash(): string
    {
        return md5(implode('|', [$this->phpVersionId, $this->opcacheEnableCli, $this->operatingSystem]));
    }
}
