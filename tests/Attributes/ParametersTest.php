<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\Attributes;

use Kaspi\Benchmark\Attributes\Parameters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @internal
 */
#[CoversClass(Parameters::class)]
class ParametersTest extends TestCase
{
    #[TestWith([['\rand', 'str1']])]
    public function testCallableOnlyInParameters(array $params): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Parameters for the benchmark method must be of a callable type');

        new Parameters($params);
    }

    #[TestWith([['foo' => '\rand', 'bar' => '\log']])]
    #[TestWith(['\rand'])]
    public function testParametersAlwaysAsList(array|string $params): void
    {
        $p = new Parameters($params);

        $this->assertIsList($p->parameters);
    }
}
