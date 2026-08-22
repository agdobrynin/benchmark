<?php

declare(strict_types=1);

namespace Kaspi\Benchmark;

use Kaspi\Benchmark\Attributes\Parameters;
use ReflectionMethod;

/**
 * @internal
 */
/**
 * @phpstan-import-type ParametersType from Parameters
 */
final class BenchmarkMethod
{
    /**
     * @param non-empty-string       $description
     * @param list<ReflectionMethod> $beforeReflectionMethod methods called before the execution of the benchmark method
     * @param list<ReflectionMethod> $afterReflectionMethod  methods called after the execution of the benchmark method
     * @param ParametersType         $parameters             parameters for benchmark method
     */
    public function __construct(
        public readonly string $description,
        public readonly ReflectionMethod $targetReflectionMethod,
        public readonly int $priority = 0,
        public readonly int $iterations = 1,
        public readonly array $beforeReflectionMethod = [],
        public readonly array $afterReflectionMethod = [],
        public readonly array $parameters = [],
        public readonly int $numberOfTimes = 1,
    ) {}
}
