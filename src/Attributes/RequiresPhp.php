<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Attributes;

use Attribute;
use InvalidArgumentException;

use function implode;
use function in_array;
use function sprintf;
use function trim;
use function var_export;
use function version_compare;

use const PHP_VERSION;

/**
 * This attribute uses the built-in `\version_compare()` function.
 *
 * @see https://www.php.net/manual/en/function.version-compare.php
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class RequiresPhp
{
    /**
     * @param non-empty-string      $phpVersion php version
     * @param null|non-empty-string $operator   comparison operator @see https://www.php.net/manual/en/function.version-compare.php
     *
     * @throws InvalidArgumentException
     */
    public function __construct(private readonly string $phpVersion, private readonly ?string $operator = null)
    {
        if ('' === trim($phpVersion)) {
            throw new InvalidArgumentException('Php version must be non-empty string');
        }

        $requiredOperators = ['<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne'];

        if (null !== $this->operator && !in_array($operator, $requiredOperators, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid comparison operator %s. Support operators: %s', var_export($operator, true), implode(', ', $requiredOperators))
            );
        }
    }

    public function isAvailable(): bool
    {
        $comparison = version_compare(PHP_VERSION, $this->phpVersion, $this->operator);

        if (null === $this->operator) {
            return 0 === $comparison;
        }

        return $comparison;
    }
}
