<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class BeforeMethod
{
    /**
     * Methods called before the execution of each benchmark method.
     *
     * @param list<non-empty-string>|non-empty-string $beforeMethod method name or list of method names
     */
    public function __construct(public readonly array|string $beforeMethod) {}
}
