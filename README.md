# Simple benchmarking library
This library allows you to compare the performance of different versions of the  ["kaspi/di-container"](https://github.com/agdobrynin/di-container) package.

### How to use the library
Up-to-date performance comparison results for the ["kaspi/di-container"](https://github.com/agdobrynin/di-container) package using this library can be found in the [kaspi-di-container-bench](https://github.com/agdobrynin/kaspi-di-container-bench) repository.

## Library development

### Testing the library code
Running tests without code coverage analysis:
```shell
composer test
```
Running tests with code coverage analysis:
```shell
composer test-cover
```

### Static analysis of library code
We use the [PHPStan](https://github.com/phpstan/phpstan) package for static analysis:
```shell
composer stat
```

### Code style of library
To bring the code into compliance with standards, we use [friendsofphp/php-cs-fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer),
which is declared in the composer dev dependency:
```shell
composer fixer
``` 

### Using a Docker image with PHP 8.1, 8.2, 8.3, 8.4, 8.5

You can specify the PHP version image in the `.env` file using the `PHP_IMAGE` key.
By default, the container is built using the `php:8.1-cli-alpine` image.

#### Building docker container
```shell
docker-compose build
```
#### Install dependencies via `composer`:
```shell
docker-compose run --rm php composer install
```
🔔 If `make` is installed on the system:
```shell
make install
```
#### Testing the library code
Running tests without code coverage analysis:
```shell
docker-compose run --rm php vendor/bin/phpunit --no-coverage
```
🔔 If `make` is installed on the system:
```shell
make test
```
Running tests with code coverage analysis:
```shell
docker-compose run --rm php vendor/bin/phpunit
```
🔔 If `make` is installed on the system:
```shell
make test-cover
```

#### Static analysis of library code

```shell
docker-compose run --rm php vendor/bin/phpstan
```
🔔 If `make` is installed on the system:
```shell
make stat
```
#### All checks in a single command
If `make` is installed, run the code-style check, static analyzer, and tests:
```shell
make all
```
#### Running tests for all supported PHP versions using Docker images.
If `make` is installed, the `vendor` directory and `composer.lock` file are removed before running the tests;
dependencies are installed, and only then are the tests executed:
```shell
make test-supports-php
```

#### Other
You can work in the shell within a Docker container:
```shell
docker-compose run --rm php sh
```
