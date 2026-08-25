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
use Kaspi\Benchmark\DTO\BenchmarkMethod;
use Kaspi\Benchmark\VO\TimeExecuteMemoryUsageIteration;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use TypeError;

use function gc_collect_cycles;
use function gc_enable;
use function get_debug_type;
use function hrtime;
use function is_array;
use function is_callable;
use function is_int;
use function is_string;
use function memory_get_peak_usage;
use function memory_get_usage;
use function sprintf;
use function usort;
use function var_export;

/**
 * @template T of object
 *
 * @phpstan-import-type ParametersType from Parameters
 * @phpstan-import-type ParametersReturnType from Parameters
 */
final class BenchmarkRunner
{
    /**
     * @var list<BenchmarkMethod>
     */
    public readonly array $benchmarkMethods;

    /** @var class-string */
    private readonly string $benchmarkClassFCQN;

    /**
     * @var array<non-empty-string, ReflectionMethod>
     */
    private readonly array $reflectionMethods;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly BenchmarkResults $benchmarkResults,
        private readonly object $benchmarkClass,
        private readonly bool $showProgressBar = true,
    ) {
        gc_enable();

        /** @var ReflectionClass<T> $reflectionClass */
        $reflectionClass = new ReflectionClass($benchmarkClass);
        $this->benchmarkClassFCQN = $reflectionClass->getName();

        // Find available methods
        $reflectionMethods = [];

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $reflectionMethods[$reflectionMethod->getName()] = $reflectionMethod;
        }

        $this->reflectionMethods = $reflectionMethods;

        /** @var list<ReflectionAttribute<Iterations>> $iterationsOnClassAttributes */
        $iterationsOnClassAttributes = $reflectionClass->getAttributes(Iterations::class);

        /** @var positive-int $iterationsOnClass */
        $iterationsOnClass = isset($iterationsOnClassAttributes[0])
            ? $iterationsOnClassAttributes[0]->newInstance()->iterations
            : 1;

        /** @var list<ReflectionAttribute<BeforeMethod>> $beforeMethodOnClassAttributes */
        $beforeMethodOnClassAttributes = $reflectionClass->getAttributes(BeforeMethod::class);

        if (isset($beforeMethodOnClassAttributes[0])) {
            $beforeMethodOnClassAttribute = $beforeMethodOnClassAttributes[0]->newInstance();

            /** @var list<ReflectionMethod> $beforeMethodOnClass */
            $beforeMethodOnClass = [...$this->checkAvailableMethod(
                (array) $beforeMethodOnClassAttribute->beforeMethod,
                $beforeMethodOnClassAttribute::class,
                'beforeMethod',
                $reflectionClass,
            )];
        } else {
            $beforeMethodOnClass = [];
        }

        /** @var list<ReflectionAttribute<AfterMethod>> $afterMethodOnClassAttributes */
        $afterMethodOnClassAttributes = $reflectionClass->getAttributes(AfterMethod::class);

        if (isset($afterMethodOnClassAttributes[0])) {
            $afterMethodOnClassAttribute = $afterMethodOnClassAttributes[0]->newInstance();

            /** @var list<ReflectionMethod> $afterMethodOnClass */
            $afterMethodOnClass = [...$this->checkAvailableMethod(
                (array) $afterMethodOnClassAttribute->afterMethod,
                $afterMethodOnClassAttribute::class,
                'afterMethod',
                $reflectionClass,
            )];
        } else {
            $afterMethodOnClass = [];
        }

        /** @var list<ReflectionAttribute<Parameters>> $parametersOnClassAttributes */
        $parametersOnClassAttributes = $reflectionClass->getAttributes(Parameters::class);

        /** @var ParametersType $parametersOnClass */
        $parametersOnClass = isset($parametersOnClassAttributes[0])
            ? $this->buildParameters($parametersOnClassAttributes[0], $reflectionClass)
            : [];

        /** @var list<ReflectionAttribute<NumberOfTimes>> $numberOfTimesOnClassAttributes */
        $numberOfTimesOnClassAttributes = $reflectionClass->getAttributes(NumberOfTimes::class);

        /** @var positive-int $numberOfTimesOnClass */
        $numberOfTimesOnClass = isset($numberOfTimesOnClassAttributes[0])
            ? $numberOfTimesOnClassAttributes[0]->newInstance()->numberOfTimes
            : 1;

        /** @var array<string, BenchmarkMethod> $benchmarkMethods */
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
                : $iterationsOnClass;

            /** @var list<ReflectionAttribute<BeforeMethod>> $beforeMethodAttributes */
            $beforeMethodAttributes = $reflectionMethod->getAttributes(BeforeMethod::class);

            $beforeMethods = isset($beforeMethodAttributes[0])
                ? [...$this->checkAvailableMethod(
                    (array) $beforeMethodAttributes[0]->newInstance()->beforeMethod,
                    BeforeMethod::class,
                    'beforeMethod',
                    $reflectionMethod,
                )]
                : $beforeMethodOnClass;

            /** @var list<ReflectionAttribute<AfterMethod>> $afterMethodAttributes */
            $afterMethodAttributes = $reflectionMethod->getAttributes(AfterMethod::class);

            $afterMethods = isset($afterMethodAttributes[0])
                ? [...$this->checkAvailableMethod(
                    (array) $afterMethodAttributes[0]->newInstance()->afterMethod,
                    AfterMethod::class,
                    'afterMethod',
                    $reflectionMethod,
                )]
                : $afterMethodOnClass;

            /** @var list<ReflectionAttribute<Parameters>> $parametersMethodAttributes */
            $parametersMethodAttributes = $reflectionMethod->getAttributes(Parameters::class);

            $parameters = isset($parametersMethodAttributes[0])
                ? $this->buildParameters($parametersMethodAttributes[0], $reflectionMethod)
                : $parametersOnClass;

            /** @var list<ReflectionAttribute<NumberOfTimes>> $numberOfTimesMethodAttributes */
            $numberOfTimesMethodAttributes = $reflectionMethod->getAttributes(NumberOfTimes::class);

            $numberOfTimes = isset($numberOfTimesMethodAttributes[0])
                ? $numberOfTimesMethodAttributes[0]->newInstance()->numberOfTimes
                : $numberOfTimesOnClass;

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
     * @throws RuntimeException    no benchmark methods were found in class
     */
    public function doBenchmarks(): BenchmarkResults
    {
        if ([] === $this->benchmarkMethods) {
            throw new RuntimeException(
                sprintf('Benchmark methods not found in the class %s. Use the PHP attribute %s to configure benchmark methods.', $this->benchmarkClassFCQN, Benchmark::class)
            );
        }

        $this->benchmarkResults->reset();

        /** @var null|string $benchmarkTitle */
        $benchmarkTitle = null;

        foreach ($this->benchmarkMethods as $benchmarkMethod) {
            $args = $this->benchmarkParameters($benchmarkMethod);

            do {
                foreach ($benchmarkMethod->beforeReflectionMethod as $beforeMethod) {
                    $beforeMethod->invoke($this->benchmarkClass);
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
                        $benchmarkMethod->targetReflectionMethod->invokeArgs($this->benchmarkClass, $benchmarkArgs);
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

                    $this->benchmarkResults->attachIteration(
                        $benchmarkDescription,
                        $timeMemory
                    );
                }

                if ($this->showProgressBar) {
                    echo "\n";
                }

                foreach ($benchmarkMethod->afterReflectionMethod as $afterMethod) {
                    $afterMethod->invoke($this->benchmarkClass);
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
     * @param list<non-empty-string>              $methods
     * @param ReflectionClass<T>|ReflectionMethod $on
     *
     * @return Generator<int, ReflectionMethod>
     *
     * @throws InvalidArgumentException
     */
    private function checkAvailableMethod(array $methods, string $classAttribute, string $parameterName, ReflectionClass|ReflectionMethod $on): Generator
    {
        foreach ($methods as $method) {
            if (!is_string($method) || !isset($this->reflectionMethods[$method])) {
                $onName = $on instanceof ReflectionClass
                    ? $on->getName().'::class'
                    : $on->getDeclaringClass()->getName().'::'.$on->getName().'()';

                throw new InvalidArgumentException(
                    sprintf('Attribute `%s` failed validation for `%s`. The value of parameter `$%s` must be a non-empty string or a non-empty list of strings. Each value must refer to an existing class method. Value received: %s.', $classAttribute, $onName, $parameterName, var_export($method, true))
                );
            }

            yield $this->reflectionMethods[$method];
        }
    }

    /**
     * @param ReflectionAttribute<Parameters>     $parameters
     * @param ReflectionClass<T>|ReflectionMethod $on
     *
     * @return ParametersType
     *
     * @throws InvalidArgumentException
     */
    private function buildParameters(ReflectionAttribute $parameters, ReflectionClass|ReflectionMethod $on): array
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
    private function benchmarkParameters(BenchmarkMethod $benchMethod): Generator
    {
        if ([] === $benchMethod->parameters) {
            return;
        }

        /** @var array<string, true> $flippedArgsNames */
        $flippedArgsNames = [];

        /** @var callable(): ParametersReturnType $parameter */
        foreach ($benchMethod->parameters as $parameter) {
            $gotParameters = ($parameter)();

            if (!$gotParameters instanceof Generator && !is_array($gotParameters)) {
                throw new InvalidArgumentException(
                    sprintf('Source parameters %s() must be return an array or Generator, got %s.', $this->callableName($parameter), get_debug_type($gotParameters)),
                );
            }

            foreach ($gotParameters as $groupName => $args) {
                $normalizedGroupName = is_int($groupName)
                    ? 'Set #'.$groupName
                    : $groupName;

                if (!is_string($normalizedGroupName) || '' === $normalizedGroupName) {
                    throw new InvalidArgumentException(
                        sprintf('The parameter group name in the parameter source %s() must be a non-empty string or integer.', $this->callableName($parameter))
                    );
                }

                if (!is_array($args)) {
                    throw new InvalidArgumentException(
                        sprintf('The parameter group named %s in the parameter source %s() must return an array containing the parameters.', var_export($normalizedGroupName, true), $this->callableName($parameter))
                    );
                }

                if (isset($flippedArgsNames[$normalizedGroupName])) {
                    throw new InvalidArgumentException(
                        sprintf('The parameter group named "%s" is not unique in the parameter source %s().', $normalizedGroupName, $this->callableName($parameter))
                    );
                }

                $flippedArgsNames[$normalizedGroupName] = true;

                yield $normalizedGroupName => $args;
            }
        }
    }

    private function callableName(mixed $callable): string
    {
        $callableName = '';
        is_callable($callable, true, $callableName);

        return $callableName;
    }
}
