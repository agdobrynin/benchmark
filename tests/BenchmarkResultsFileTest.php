<?php

declare(strict_types=1);

namespace Kaspi\Benchmark\Tests;

use Generator;
use Kaspi\Benchmark\BenchmarkResults;
use Kaspi\Benchmark\BenchmarkResultsFile;
use Kaspi\Benchmark\DTO\EnvBenchmark;
use Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration;
use Kaspi\Benchmark\Services\EnvParams;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function file_get_contents;
use function file_put_contents;
use function str_replace;

/**
 * @internal
 */
#[CoversClass(BenchmarkResultsFile::class)]
#[CoversClass(TimeExecuteMemoryUsageInIteration::class)]
#[CoversClass(EnvBenchmark::class)]
#[UsesClass(BenchmarkResults::class)]
#[UsesClass(EnvParams::class)]
class BenchmarkResultsFileTest extends TestCase
{
    // ⚠️ need replace `'%hash-env%'` before test
    protected const jsonExist = '{
    "%hash-env%": {
        "env": {
            "phpVersionId": 80100,
            "opcacheEnableCli": false
        },
        "packageVersion": {
            "foo": {
                "bar": {
                    "baz": [
                        {
                            "startBytesUsage": 10,
                            "startBytesUsageReal": 1000,
                            "endBytesUsage": 21,
                            "endBytesUsageReal": 2100,
                            "bytesPeakUsage": 21,
                            "bytesPeakUsageReal": 2100,
                            "startTime": 20.134,
                            "endTime": 22.987,
                            "numberOfTimes": 1
                        }
                    ]
                }
            }
        }
    }
}';
    protected string $envKey;
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
        $env = new EnvBenchmark(80100, false);
        $json = str_replace('%hash-env%', $env->toHash(), self::jsonExist);
        file_put_contents($this->outputFile, $json);

        $file = new BenchmarkResultsFile($this->outputFile);

        // read exist data
        $results = $file->read();
        self::assertTrue($results->valid());

        /** @var BenchmarkResults $resExistGet */
        $resExistGet = $results->current();

        self::assertEquals('foo', $resExistGet->packageVersion);
        self::assertEquals('bar', $resExistGet->groupName);
        self::assertEquals(80100, $resExistGet->env->phpVersionId);
        self::assertFalse($resExistGet->env->opcacheEnableCli);

        $iterations = $resExistGet->getResults();

        self::assertTrue($iterations->valid());

        self::assertEquals('baz', $iterations->key());
        $items = $iterations->current();

        self::assertTrue($items->valid());

        /** @var TimeExecuteMemoryUsageInIteration $current */
        $current = $items->current();
        $key = $items->key();

        self::assertEquals(0, $key);
        self::assertEquals(10, $current->startBytesUsage);
        self::assertEquals(1000, $current->startBytesUsageReal);
        self::assertEquals(21, $current->endBytesUsage);
        self::assertEquals(2100, $current->endBytesUsageReal);
        self::assertEquals(21, $current->bytesPeakUsage);
        self::assertEquals(2100, $current->bytesPeakUsageReal);
        self::assertEquals(20.134, $current->startTime);
        self::assertEquals(22.987, $current->endTime);
        self::assertEquals(1, $current->numberOfTimes);

        $items->next();

        self::assertFalse($items->valid());

        $iterations->next();

        self::assertFalse($iterations->valid());

        $resSet = new BenchmarkResults('foo', 'bar', $env);
        $resSet->attachIterations(
            'baz',
            [
                new TimeExecuteMemoryUsageInIteration(
                    11,
                    1100,
                    20,
                    2000,
                    20,
                    2000,
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

        self::assertEquals(11, $current->startBytesUsage);
        self::assertEquals(1100, $current->startBytesUsageReal);
        self::assertEquals(20, $current->endBytesUsage);
        self::assertEquals(2000, $current->endBytesUsageReal);
        self::assertEquals(20, $current->bytesPeakUsage);
        self::assertEquals(2000, $current->bytesPeakUsageReal);
        self::assertEquals(24.222, $current->startTime);
        self::assertEquals(25.432, $current->endTime);
        self::assertEquals(2, $current->numberOfTimes);
    }

    public function testReset(): void
    {
        $file = new BenchmarkResultsFile($this->outputFile);
        $file->attach(new BenchmarkResults('foo', 'bar', EnvParams::autoConfigureEnvBenchmark()));

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
        yield 'invalid json' => [
            'foo',
            'Unable to parse the JSON',
        ];

        yield 'env hash is empty' => [
            '{
                "": {}
            }',
            'Env hash must be a non-empty string',
        ];

        yield 'env section undefined' => [
            '{
                "hash-env-1234": {}
            }',
            'Env hash "hash-env-1234" must contain an "env" section',
        ];

        yield 'env section invalid' => [
            '{
                "hash-env-1234": {
                    "env": "foo"
                }
            }',
            'The section "env" defined in env hash "hash-env-1234" must be a non-empty array',
        ];

        yield 'packageVersion not defined' => [
            '{
                "hash-env-1234": {
                   "env": {
                      "phpVersionId": 80100,
                      "opcacheEnableCli": false
                   }
                }
            }',
            'The section "packageVersion" not defined in env hash "hash-env-1234"',
        ];

        yield 'package versions is empty array' => [
            '{
                "hash-env-1234": {
                   "env": {
                      "phpVersionId": 80100,
                      "opcacheEnableCli": false
                   },
                   "packageVersion": {}
                }
            }',
            'The section "packageVersion" defined in env hash "hash-env-1234" must be non-empty array',
        ];

        yield 'package version is empty string' => [
            '{
                "hash-env-1234": {
                   "env": {
                      "phpVersionId": 80100,
                      "opcacheEnableCli": false
                   },
                   "packageVersion": {
                       "": {
                            "bar": {
                                "baz": []
                            }
                       }
                   }
                }
            }',
            'The package version defined in env hash "hash-env-1234" must be a non-empty string',
        ];

        yield 'not defined benchmark groups' => [
            '{
                "hash-env-1234": {
                   "env": {
                      "phpVersionId": 80100,
                      "opcacheEnableCli": false
                   },
                   "packageVersion": {
                       "v1.0.0": {}
                   }
                }
            }',
            'A package of version \'v1.0.0\' defined in env hash "hash-env-1234" must contain benchmark groups as a non-empty array',
        ];

        yield 'group name is empty string' => [
            '{
                "hash-env-1234": {
                   "env": {
                      "phpVersionId": 80100,
                      "opcacheEnableCli": false
                   },
                   "packageVersion": {
                       "v1.0.0": {
                            "": {
                                "bar": {
                                    "baz": []
                                }
                            }
                       }
                   }
                }
            }',
            'Package version \'v1.0.0\' defined in env hash "hash-env-1234" must contain benchmark groups as a non-empty array',
        ];

        yield 'benchmark results is empty array' => [
            '{
                "hash-env-1234": {
                   "env": {
                      "phpVersionId": 80100,
                      "opcacheEnableCli": false
                   },
                   "packageVersion": {
                       "v1.0.0": {
                            "foo": {}
                       }
                   }
                }
            }',
            'Package version \'v1.0.0\' defined in env hash "hash-env-1234" with group name \'foo\' must contain benchmark results as a non-empty array',
        ];

        yield 'benchmark description is empty string' => [
            '{
                "hash-env-1234": {
                   "env": {
                      "phpVersionId": 80100,
                      "opcacheEnableCli": false
                   },
                   "packageVersion": {
                       "v1.0.0": {
                            "foo": {
                                "": []
                            }
                       }
                   }
                }
            }',
            'Package version \'v1.0.0\' defined in env hash "hash-env-1234" with group name \'foo\' must contain a benchmark description as a non-empty string',
        ];

        yield 'benchmark results as string' => [
            '{
                "hash-env-1234": {
                   "env": {
                      "phpVersionId": 80100,
                      "opcacheEnableCli": false
                   },
                   "packageVersion": {
                       "v1.0.0": {
                            "foo": {
                                "bar": "baz"
                            }
                       }
                   }
                }
            }',
            'Package version \'v1.0.0\' defined in env hash "hash-env-1234" with group name \'foo\' and benchmark \'bar\' must contain iteration elements as a non-empty array',
        ];

        yield 'Empty benchmark metrics' => [
            '{
                "hash-env-1234": {
                   "env": {
                      "phpVersionId": 80100,
                      "opcacheEnableCli": false
                   },
                   "packageVersion": {
                       "v1.0.0": {
                            "foo": {
                                "bar": []
                            }
                       }
                   }
                }
            }',
            'Package version \'v1.0.0\' defined in env hash "hash-env-1234" with group name \'foo\' and benchmark \'bar\' must contain iteration elements as a non-empty array',
        ];

        yield 'Invalid benchmark metrics' => [
            '{
                "hash-env-1234": {
                   "env": {
                      "phpVersionId": 80100,
                      "opcacheEnableCli": false
                   },
                   "packageVersion": {
                       "v1.0.0": {
                            "foo": {
                                "bar": [
                                    "baz"
                                ]
                            }
                       }
                   }
                }
            }',
            'where each element must be represented as a non-empty array with keys matching the public properties of class Kaspi\Benchmark\DTO\TimeExecuteMemoryUsageInIteration',
        ];
    }
}
