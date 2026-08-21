<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Core\Attributes;

use Attribute;
use Generator;
use TypeError;
use function array_values;
use function is_callable;
use function sprintf;
use function var_export;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Parameters
{
    /**
     * @var array<callable(): Generator|array>
     */
    public readonly array $parameters;

    /**
     * @param (list<callable(): Generator|array>)|(callable(): Generator|array) $parameters
     */
    public function __construct(array|callable $parameters)
    {
        if (is_callable($parameters)) {
            $this->parameters = [$parameters];

            return;
        }
        foreach ($parameters as $parameter) {
            if (!is_callable($parameter)) {
                throw new TypeError(
                    sprintf('Parameters for the benchmark method must be of a callable type. Got: %s', var_export($parameter, true))
                );
            }
        }

        $this->parameters = array_values($parameters);
    }
}
