<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\DTO;

final class BenchmarkGroup
{
    /**
     * @param non-empty-string      $name
     * @param list<BenchmarkMethod> $benchmarkMethods
     * @param object                $benchmarkObject  benchmark-class object
     */
    public function __construct(
        public readonly string $name,
        public readonly array $benchmarkMethods,
        public readonly object $benchmarkObject,
    ) {}
}
