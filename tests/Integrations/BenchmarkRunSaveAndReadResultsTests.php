<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests\Integrations;

use Kaspi\Benchmark\Attributes\Benchmark;
use Kaspi\Benchmark\Attributes\Group;
use Kaspi\Benchmark\Attributes\Iterations;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkResultsFile;
use Kaspi\Benchmark\BenchmarkRunner;
use Kaspi\Benchmark\DTO\EnvBenchmark;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
class BenchmarkRunSaveAndReadResultsTests extends TestCase
{
    public function testSaveResultsAndReadResults(): void
    {
        $classOne = new #[Iterations(8)] #[Group('Class One')] class {
            #[Benchmark]
            public function doBenchOne(): void {}
        };

        $classTwo = new #[Group('Class Two')] class {
            #[Benchmark]
            #[Iterations(3)]
            public function doBenchOne(): void {}

            #[Benchmark]
            #[Iterations(5)]
            public function doBenchTwo(): void {}
        };

        vfsStream::setup('var');
        $resultsFile = vfsStream::url('var/results.json');
        $env = new EnvBenchmark(PHP_VERSION_ID, false, 'linux');

        $runner = new BenchmarkRunner('v1.x-dev', $env, $classOne, $classTwo);
        $runner->showProgressBar(false);

        $benchResultsFileSaver = new BenchmarkResultsFile($resultsFile);

        foreach ($runner->doBenchmarks() as $result) {
            $benchResultsFileSaver->attach($result);
        }

        $benchResultsFileSaver->save();

        $benchResultsFileReader = new BenchmarkResultsFile($resultsFile);

        $results = $benchResultsFileReader->read();

        /** @var BenchmarkResults $res1 */
        $res1 = $results->current();

        self::assertEquals('Class One', $res1->groupName);

        $itemsRes1 = $res1->getBenchmarkTimeExecuteMemoryUsageItems();
        self::assertEquals('Do bench one', $itemsRes1->key());
        self::assertEquals(8, $itemsRes1->current()->iterations);

        $itemsRes1->next();

        self::assertFalse($itemsRes1->valid());

        $results->next();

        /** @var BenchmarkResults $res2 */
        $res2 = $results->current();

        self::assertEquals('Class Two', $res2->groupName);

        $itemsRes2 = $res2->getBenchmarkTimeExecuteMemoryUsageItems();
        self::assertEquals('Do bench one', $itemsRes2->key());
        self::assertEquals(3, $itemsRes2->current()->iterations);

        $itemsRes2->next();

        self::assertEquals('Do bench two', $itemsRes2->key());
        self::assertEquals(5, $itemsRes2->current()->iterations);

        $itemsRes2->next();

        self::assertFalse($itemsRes2->valid());
    }
}
