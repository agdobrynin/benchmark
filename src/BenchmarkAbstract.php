<?php

declare(strict_types=1);

namespace Kaspi\Benchmark;

use Generator;
use InvalidArgumentException;
use Kaspi\Benchmark\Attributes\AfterMethod;
use Kaspi\Benchmark\Attributes\BeforeMethod;
use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Iterations;
use Kaspi\Benchmark\Attributes\NumberOfTimes;
use Kaspi\Benchmark\Attributes\Parameters;
use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use TypeError;

use function gc_collect_cycles;
use function gc_enable;
use function get_debug_type;
use function hrtime;
use function is_array;
use function is_callable;
use function is_string;
use function memory_get_peak_usage;
use function memory_get_usage;
use function sprintf;
use function usort;
use function var_export;

/**
 * @phpstan-import-type ParametersType from Parameters
 * @phpstan-import-type ParametersReturnType from Parameters
 */
abstract class BenchmarkAbstract
{
    /**
     * @var BenchmarkMethod[]
     */
    protected readonly array $benchmarkMethods;

    /**
     * @var ReflectionClass<BenchmarkAbstract>
     */
    protected readonly ReflectionClass $reflectionClass;

    /**
     * @var array<non-empty-string, ReflectionMethod>
     */
    protected readonly array $reflectionMethods;

    protected readonly int $iterationsOnClass;

    /**
     * @var list<ReflectionMethod>
     */
    protected readonly array $beforeMethodOnClass;

    /**
     * @var list<ReflectionMethod>
     */
    protected readonly array $afterMethodOnClass;

    /**
     * @var ParametersType
     */
    protected readonly array $parametersOnClass;

    protected readonly int $numberOfTimesOnClass;

    final public function __construct(
        protected readonly BenchmarkResults $benchmarkResults,
        protected readonly bool $showProgressBar = true,
    ) {
        gc_enable();

        $this->reflectionClass = new ReflectionClass($this);

        // Find available methods
        $reflectionMethods = [];

        foreach ($this->reflectionClass->getMethods() as $reflectionMethod) {
            $reflectionMethods[$reflectionMethod->getName()] = $reflectionMethod;
        }

        $this->reflectionMethods = $reflectionMethods;

        /** @var list<ReflectionAttribute<Iterations>> $iterationsOnClassAttributes */
        $iterationsOnClassAttributes = $this->reflectionClass->getAttributes(Iterations::class);

        $this->iterationsOnClass = isset($iterationsOnClassAttributes[0])
            ? $iterationsOnClassAttributes[0]->newInstance()->iterations
            : 1;

        /** @var list<ReflectionAttribute<BeforeMethod>> $beforeMethodOnClassAttributes */
        $beforeMethodOnClassAttributes = $this->reflectionClass->getAttributes(BeforeMethod::class);

        if (isset($beforeMethodOnClassAttributes[0])) {
            $beforeMethodOnClassAttribute = $beforeMethodOnClassAttributes[0]->newInstance();

            $this->beforeMethodOnClass = [...$this->checkAvailableMethod(
                (array) $beforeMethodOnClassAttribute->beforeMethod,
                $beforeMethodOnClassAttribute::class,
                'beforeMethod',
                $this->reflectionClass,
            )];
        } else {
            $this->beforeMethodOnClass = [];
        }

        /** @var list<ReflectionAttribute<AfterMethod>> $afterMethodOnClassAttributes */
        $afterMethodOnClassAttributes = $this->reflectionClass->getAttributes(AfterMethod::class);

        if (isset($afterMethodOnClassAttributes[0])) {
            $afterMethodOnClassAttribute = $afterMethodOnClassAttributes[0]->newInstance();

            $this->afterMethodOnClass = [...$this->checkAvailableMethod(
                (array) $afterMethodOnClassAttribute->afterMethod,
                $afterMethodOnClassAttribute::class,
                'afterMethod',
                $this->reflectionClass,
            )];
        } else {
            $this->afterMethodOnClass = [];
        }

        /** @var list<ReflectionAttribute<Parameters>> $parametersOnClassAttributes */
        $parametersOnClassAttributes = $this->reflectionClass->getAttributes(Parameters::class);

        if (isset($parametersOnClassAttributes[0])) {
            $this->parametersOnClass = $this->buildParameters($parametersOnClassAttributes[0], $this->reflectionClass);
        } else {
            $this->parametersOnClass = [];
        }

        /** @var list<ReflectionAttribute<NumberOfTimes>> $numberOfTimesOnClassAttributes */
        $numberOfTimesOnClassAttributes = $this->reflectionClass->getAttributes(NumberOfTimes::class);

        $this->numberOfTimesOnClass = isset($numberOfTimesOnClassAttributes[0])
            ? $numberOfTimesOnClassAttributes[0]->newInstance()->numberOfTimes
            : 1;

        $benchmarkMethods = [];

        foreach ($this->reflectionMethods as $methodName => $reflectionMethod) {
            // Benchmark method must be declared with modifier public and non-static
            if (!$reflectionMethod->isPublic() || $reflectionMethod->isStatic()) {
                continue;
            }

            $attribute = $reflectionMethod->getAttributes(Benchmark::class)[0] ?? null;

            if (null === $attribute) {
                continue;
            }

            /** @var Benchmark $attributeBenchmark */
            $attributeBenchmark = $attribute->newInstance();
            $description = '' !== $attributeBenchmark->description
                ? $attributeBenchmark->description
                : Formatter::methodToHuman($methodName);

            // Configure benchmark method aka `$reflectionMethod`.

            /** @var list<ReflectionAttribute<Iterations>> $iterationAttributes */
            $iterationAttributes = $reflectionMethod->getAttributes(Iterations::class);

            $iterations = isset($iterationAttributes[0])
                ? $iterationAttributes[0]->newInstance()->iterations
                : $this->iterationsOnClass;

            /** @var list<ReflectionAttribute<BeforeMethod>> $beforeMethodAttributes */
            $beforeMethodAttributes = $reflectionMethod->getAttributes(BeforeMethod::class);

            $beforeMethods = isset($beforeMethodAttributes[0])
                ? [...$this->checkAvailableMethod(
                    (array) $beforeMethodAttributes[0]->newInstance()->beforeMethod,
                    BeforeMethod::class,
                    'beforeMethod',
                    $reflectionMethod,
                )]
                : $this->beforeMethodOnClass;

            /** @var list<ReflectionAttribute<AfterMethod>> $afterMethodAttributes */
            $afterMethodAttributes = $reflectionMethod->getAttributes(AfterMethod::class);

            $afterMethods = isset($afterMethodAttributes[0])
                ? [...$this->checkAvailableMethod(
                    (array) $afterMethodAttributes[0]->newInstance()->afterMethod,
                    AfterMethod::class,
                    'afterMethod',
                    $reflectionMethod,
                )]
                : $this->afterMethodOnClass;

            /** @var list<ReflectionAttribute<Parameters>> $parametersMethodAttributes */
            $parametersMethodAttributes = $reflectionMethod->getAttributes(Parameters::class);

            $parameters = isset($parametersMethodAttributes[0])
                ? $this->buildParameters($parametersMethodAttributes[0], $reflectionMethod)
                : $this->parametersOnClass;

            /** @var list<ReflectionAttribute<NumberOfTimes>> $numberOfTimesMethodAttributes */
            $numberOfTimesMethodAttributes = $reflectionMethod->getAttributes(NumberOfTimes::class);

            $numberOfTimes = isset($numberOfTimesMethodAttributes[0])
                ? $numberOfTimesMethodAttributes[0]->newInstance()->numberOfTimes
                : $this->numberOfTimesOnClass;

            $benchmarkMethods[] = new BenchmarkMethod(
                $description,
                $reflectionMethod,
                $attributeBenchmark->priority,
                $iterations,
                $beforeMethods,
                $afterMethods,
                $parameters,
                $numberOfTimes,
            );
        }

        usort($benchmarkMethods, static function (BenchmarkMethod $a, BenchmarkMethod $b) {
            return $b->priority <=> $a->priority;
        });

        $this->benchmarkMethods = $benchmarkMethods;
    }

    /**
     * @throws ReflectionException
     */
    final public function doBenchmarks(): BenchmarkResults
    {
        $this->benchmarkResults->reset();

        /** @var null|string $benchmarkTitle */
        $benchmarkTitle = null;

        foreach ($this->benchmarkMethods as $benchmarkMethod) {
            $args = $this->benchmarkParameters($benchmarkMethod);

            do {
                foreach ($benchmarkMethod->beforeReflectionMethod as $beforeMethod) {
                    $beforeMethod->invoke($this);
                }

                if ($args->valid()) {
                    $benchmarkArgs = $args->current();
                    $benchmarkDescription = sprintf('%s with parameters name %s', $benchmarkMethod->description, var_export($args->key(), true));
                } else {
                    $benchmarkArgs = [];
                    $benchmarkDescription = $benchmarkMethod->description;
                }

                if ($this->showProgressBar) {
                    echo "\n";
                    $benchmarkTitle = sprintf('[%s] %s', $this->benchmarkResults->groupName, $benchmarkDescription);
                }

                for ($i = 1; $i <= $benchmarkMethod->iterations; ++$i) {
                    if (null !== $benchmarkTitle) {
                        Formatter::progressBar($benchmarkTitle, $i, $benchmarkMethod->iterations, sizeBar: 33);
                    }

                    gc_collect_cycles();

                    $startMemoryUsage = memory_get_usage();
                    $startPeakUsage = memory_get_peak_usage();
                    $startHrTime = hrtime(true);

                    // Execute the target method
                    for ($n = 0; $n < $benchmarkMethod->numberOfTimes; ++$n) {
                        $benchmarkMethod->targetReflectionMethod->invokeArgs($this, $benchmarkArgs);
                    }

                    $timeMemory = new TimeExecuteMemoryUsageIteration(
                        $startMemoryUsage,
                        memory_get_usage(),
                        $startPeakUsage,
                        memory_get_peak_usage(),
                        $startHrTime,
                        hrtime(true),
                        $benchmarkMethod->numberOfTimes,
                    );

                    $this->benchmarkResults->attach(
                        $benchmarkDescription,
                        $timeMemory
                    );
                }

                if ($this->showProgressBar) {
                    echo "\n";
                }

                foreach ($benchmarkMethod->afterReflectionMethod as $afterMethod) {
                    $afterMethod->invoke($this);
                }

                $args->next();
            } while ($args->valid());
        }

        if ($this->showProgressBar) {
            echo "\n";
        }

        return $this->benchmarkResults;
    }

    /**
     * @param list<non-empty-string>                              $methods
     * @param ReflectionClass<BenchmarkAbstract>|ReflectionMethod $on
     *
     * @return Generator<int, ReflectionMethod>
     *
     * @throws InvalidArgumentException
     */
    final protected function checkAvailableMethod(array $methods, string $classAttribute, string $parameterName, ReflectionClass|ReflectionMethod $on): Generator
    {
        foreach ($methods as $method) {
            if (!isset($this->reflectionMethods[$method])) {
                $onName = $on instanceof ReflectionClass
                    ? $on->getName().'::class'
                    : $on->getDeclaringClass()->getName().'::'.$on->getName().'()';

                throw new InvalidArgumentException(
                    sprintf('The attribute `%s` failed validation for the `%s`. The value of the `$%s` parameter must refer to a public class method. Got value %s.', $classAttribute, $onName, $parameterName, var_export($method, true))
                );
            }

            yield $this->reflectionMethods[$method];
        }
    }

    /**
     * @param ReflectionAttribute<Parameters>                     $parameters
     * @param ReflectionClass<BenchmarkAbstract>|ReflectionMethod $on
     *
     * @return ParametersType
     *
     * @throws InvalidArgumentException
     */
    final protected function buildParameters(ReflectionAttribute $parameters, ReflectionClass|ReflectionMethod $on): array
    {
        try {
            return $parameters->newInstance()->parameters;
        } catch (TypeError $error) {
            $onName = $on instanceof ReflectionClass
                ? $on->getName().'::class'
                : $on->getDeclaringClass()->getName().'::'.$on->getName().'()';

            throw new InvalidArgumentException(
                sprintf('The attribute `%s` failed validation for the %s. Reason by: %s', Parameters::class, $onName, $error->getMessage()),
                previous: $error,
            );
        }
    }

    /**
     * @return Generator<non-empty-string, array<int|string, mixed>>
     *
     * @throws InvalidArgumentException
     */
    final protected function benchmarkParameters(BenchmarkMethod $benchMethod): Generator
    {
        if ([] === $benchMethod->parameters) {
            return;
        }

        /** @var array<string, true> $flippedArgsNames */
        $flippedArgsNames = [];

        foreach ($benchMethod->parameters as $parameter) {
            $gotParameters = ($parameter)();

            if (!$gotParameters instanceof Generator && !is_array($gotParameters)) {
                $callableName = '';
                is_callable($parameter, callable_name: $callableName);

                throw new InvalidArgumentException(
                    sprintf('Source parameters %s must be return an array or Generator, got %s.', $callableName, get_debug_type($gotParameters)),
                );
            }

            foreach ($gotParameters as $groupName => $args) {
                if (!is_string($groupName) || '' === $groupName) {
                    $callableName = '';
                    is_callable($parameter, callable_name: $callableName);

                    throw new InvalidArgumentException(
                        sprintf('The parameter group name in the parameter source %s() must be a non-empty string.', $callableName)
                    );
                }

                if (isset($flippedArgsNames[$groupName])) {
                    $callableName = '';
                    is_callable($parameter, callable_name: $callableName);

                    throw new InvalidArgumentException(
                        sprintf('The parameter group name "%s" is not unique in the parameter source %s().', $groupName, $callableName)
                    );
                }

                $flippedArgsNames[$groupName] = true;

                yield $groupName => $args;
            }
        }
    }
}
