<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Attributes;

use Attribute;
use InvalidArgumentException;

use function implode;
use function in_array;
use function rtrim;
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
    public readonly string $phpVersion;
    public readonly string $operator;

    /**
     * @param non-empty-string      $phpVersion php version
     * @param null|non-empty-string $operator   comparison operator @see https://www.php.net/manual/en/function.version-compare.php
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $phpVersion, ?string $operator = null)
    {
        $this->phpVersion = trim($phpVersion);

        if ('' === $this->phpVersion) {
            throw new InvalidArgumentException('Php version must be non-empty string');
        }

        if (null === $operator) {
            $this->operator = '=';

            return;
        }

        $this->operator = trim($operator);

        $requiredOperators = ['<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne'];

        if (!in_array($this->operator, $requiredOperators, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid comparison operator %s. Support operators: %s', var_export($this->operator, true), implode(', ', $requiredOperators))
            );
        }
    }

    public function humanReadable(): string
    {
        $condition = match ($this->operator) {
            '<', 'lt' => 'less than',
            '<=', 'le' => 'less than or equals',
            '>', 'gt' => 'greater than',
            '>=', 'ge' => 'greater than or equals',
            '!=', '<>', 'ne' => 'not equals',
            default => 'equals',
        };

        return rtrim(sprintf('%s %s', $condition, $this->phpVersion));
    }

    public function isAvailable(): bool
    {
        return version_compare(PHP_VERSION, $this->phpVersion, $this->operator);
    }
}
