<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Services;

use Kaspi\Benchmark\DTO\EnvBenchmark;

use function extension_loaded;
use function ini_get;
use function strtolower;

use const PHP_VERSION_ID;

final class EnvParams
{
    public static function autoConfigureEnvBenchmark(): EnvBenchmark
    {
        $opcacheExt = extension_loaded('Zend OPcache');
        $opcacheCli = ini_get('opcache.enable_cli');

        if ($opcacheExt
            && false !== $opcacheCli
            && ('1' === $opcacheCli || 'on' === strtolower($opcacheCli))) {
            $opcacheEnableCli = true;
        } else {
            $opcacheEnableCli = false;
        }

        $phpVersionId = PHP_VERSION_ID;

        return new EnvBenchmark($phpVersionId, $opcacheEnableCli);
    }
}
