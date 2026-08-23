<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Attributes;

use Attribute;
use Generator;
use TypeError;

use function array_values;
use function is_callable;
use function sprintf;
use function var_export;

/**
 * @phpstan-type ParametersReturnType Generator<non-empty-string, array<int|string, mixed>>|array<non-empty-string, array<int|string, mixed>>
 * @phpstan-type ParametersType list<callable(): ParametersReturnType>
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Parameters
{
    /**
     * @var ParametersType
     */
    public readonly array $parameters;

    /**
     * @param ParametersType $parameters
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
                    sprintf('Parameters for the benchmark method must be of a callable type or a list of callable types. Got: %s', var_export($parameter, true))
                );
            }
        }

        $this->parameters = array_values($parameters);
    }
}
