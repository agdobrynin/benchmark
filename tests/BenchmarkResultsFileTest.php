<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests;

use Generator;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkResultsFile;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function file_get_contents;
use function file_put_contents;

/**
 * @internal
 */
#[CoversClass(BenchmarkResultsFile::class)]
#[CoversClass(TimeExecuteMemoryUsageInIteration::class)]
#[UsesClass(BenchmarkResults::class)]
class BenchmarkResultsFileTest extends TestCase
{
    protected const jsonExist = '{
    "foo": {
        "bar": {
            "baz": [
                {
                    "startBytesUsageInIteration": 10,
                    "endBytesUsageInIteration": 21,
                    "bytesPeakUsage": 21,
                    "startTimeInIteration": 20.134,
                    "endTimeInIteration": 22.987,
                    "numberOfTimes": 1
                }
            ]
        }
    }
}';
    protected string $outputFile;

    protected function setUp(): void
    {
        parent::setUp();
        vfsStream::setup();
        $this->outputFile = vfsStream::url('root/output.json');
    }

    public function testSaveEmptyResults(): void
    {
        $file = new BenchmarkResultsFile($this->outputFile);

        self::assertFalse($file->read()->valid());

        $file->save();

        self::assertEquals('{}', file_get_contents($this->outputFile));
    }

    public function testReadExistFileWithPermissionDeny(): void
    {
        $outputFile = vfsStream::newFile('res.json', '0222');
        vfsStream::setup()->addChild($outputFile);

        $file = new BenchmarkResultsFile($outputFile->url());

        self::assertFalse($file->read()->valid());
    }

    public function testReadExistFileWrongJsonFormat(): void
    {
        $outputFile = vfsStream::newFile('res.json');
        $outputFile->setContent('{aaa}');
        vfsStream::setup()->addChild($outputFile);

        $file = new BenchmarkResultsFile($outputFile->url());

        self::assertFalse($file->read()->valid());
    }

    public function testReadExistFileWhenSourceJsonIsNotArray(): void
    {
        $outputFile = vfsStream::newFile('res.json');
        $outputFile->setContent('"foo"');
        vfsStream::setup()->addChild($outputFile);

        $file = new BenchmarkResultsFile($outputFile->url());

        self::assertFalse($file->read()->valid());
    }

    public function testSaveResultsWithReplaceBenchmark(): void
    {
        file_put_contents($this->outputFile, self::jsonExist);

        $file = new BenchmarkResultsFile($this->outputFile);

        // read exist data
        $results = $file->read();
        self::assertTrue($results->valid());

        /** @var BenchmarkResults $resExistGet */
        $resExistGet = $results->current();

        self::assertEquals('foo', $resExistGet->packageVersion);
        self::assertEquals('bar', $resExistGet->groupName);
        $iterations = $resExistGet->getResults();

        self::assertTrue($iterations->valid());

        self::assertEquals('baz', $iterations->key());
        $items = $iterations->current();

        self::assertTrue($items->valid());

        $current = $items->current();
        $key = $items->key();

        self::assertEquals(0, $key);
        self::assertEquals(10, $current->startBytesUsageInIteration);
        self::assertEquals(21, $current->endBytesUsageInIteration);
        self::assertEquals(21, $current->bytesPeakUsage);
        self::assertEquals(20.134, $current->startTimeInIteration);
        self::assertEquals(22.987, $current->endTimeInIteration);
        self::assertEquals(1, $current->numberOfTimes);

        $items->next();

        self::assertFalse($items->valid());

        $iterations->next();

        self::assertFalse($iterations->valid());

        $resSet = new BenchmarkResults('foo', 'bar');
        $resSet->attachIterations(
            'baz',
            [
                new TimeExecuteMemoryUsageInIteration(
                    11,
                    20,
                    20,
                    24.222,
                    25.432,
                    2
                ),
            ]
        );
        $file->attach($resSet);
        $file->save();

        $results = $file->read();
        self::assertTrue($results->valid());

        /** @var BenchmarkResults $resGetUpdated */
        $resGetUpdated = $results->current();

        $results->next();

        self::assertFalse($results->valid());

        self::assertEquals('foo', $resGetUpdated->packageVersion);
        self::assertEquals('bar', $resGetUpdated->groupName);

        $iterations = $resGetUpdated->getResults();

        self::assertEquals('baz', $iterations->key());

        $items = $iterations->current();
        self::assertTrue($items->valid());

        /** @var TimeExecuteMemoryUsageInIteration $current */
        $current = $items->current();
        $key = $items->key();

        self::assertEquals(0, $key);

        self::assertEquals(11, $current->startBytesUsageInIteration);
        self::assertEquals(20, $current->endBytesUsageInIteration);
        self::assertEquals(20, $current->bytesPeakUsage);
        self::assertEquals(24.222, $current->startTimeInIteration);
        self::assertEquals(25.432, $current->endTimeInIteration);
        self::assertEquals(2, $current->numberOfTimes);
    }

    public function testReset(): void
    {
        $file = new BenchmarkResultsFile($this->outputFile);
        $file->attach(new BenchmarkResults('foo', 'bar'));

        self::assertTrue($file->getAttached()->valid());

        $file->reset();

        self::assertFalse($file->getAttached()->valid());
    }

    #[DataProvider('dataProviderForValidator')]
    public function testValidatorInMethodGetArrayFromFile(string $jsonContent, string $expectMessage): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectMessage);

        file_put_contents($this->outputFile, $jsonContent);

        self::assertFalse((new BenchmarkResultsFile($this->outputFile))->read()->valid());
    }

    public static function dataProviderForValidator(): Generator
    {
        yield 'package version is empty string' => [
            '{
                "": {
                    "bar": {
                        "baz": []
                    }
                }
            }',
            'The package version must be a non-empty string',
        ];

        yield 'not defined package groups' => [
            '{
                "foo": {
                }
            }',
            'must contain benchmark groups as a non-empty array',
        ];

        yield 'group name is empty string' => [
            '{
                "foo": {
                    "": {
                        "baz": []
                    }
                }
            }',
            'each group name is a non-empty string',
        ];

        yield 'benchmark results is string' => [
            '{
                "foo": {
                    "bar": "qux"
                }
            }',
            'must contain benchmark results as a non-empty array',
        ];

        yield 'benchmark description is empty string' => [
            '{
                "foo": {
                    "bar": {
                        "": []
                    }
                }
            }',
            'must contain a benchmark description as a non-empty string',
        ];

        yield 'benchmark iterations is string' => [
            '{
                "foo": {
                    "bar": {
                        "baz": "qux"
                    }
                }
            }',
            'must contain iteration elements as a non-empty array',
        ];

        yield 'benchmark iteration items is string' => [
            '{
                "foo": {
                    "bar": {
                        "baz": "qux"
                    }
                }
            }',
            'must contain iteration elements as a non-empty array',
        ];

        yield 'benchmark iteration wrong structure' => [
            '{
                "foo": {
                    "bar": {
                        "baz": [
                            "qux"
                        ]
                    }
                }
            }',
            'each element must be represented as a non-empty array',
        ];
    }
}
