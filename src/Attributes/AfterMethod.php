<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class AfterMethod
{
    /**
     * Methods called after the execution of each benchmark method.
     *
     * @param list<non-empty-string>|non-empty-string $afterMethod method name or list of method names
     */
    public function __construct(public readonly array|string $afterMethod) {}
}
