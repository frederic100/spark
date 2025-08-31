# Spark

Use Spark to bootstrap a PHP project from scratch. It sets up the essentials for practicing TDD and maintaining high code quality.

## Install

```bash
git clone git@github.com:frederic100/spark.git
cd spark
./install --profile devlocal
```

## Contributing

### Requirements

* docker
* git

### Add a .env.local file

To install locally or on a development server, be careful with the following environment variables:
* DATA_PATH: path where data is stored; must be inside the project (default: ./data)
* DATA_PATH_STORE: path for backups; generally outside the project (default: ../data/spark)
* REMOVE_DATABASE_WHEN_INSTALL: remove database during install (default: false)
* BUILD_WHEN_INSTALL: build application during install (default: false)
* DOCKER_DEV: run development-specific containers (default: false)
* DOCKER_PHP_BUILT_IMAGE: application prebuilt Docker image
* OPTIONAL_VOLUME: mount a local volume for localhost development (default: empty)
* LOCALDEV_WORKING_DIR: working directory useful for development (default: undefined)
* URL_API: override the base URL used by internal API clients (default: empty)
* PULL_POLICY: policy for pulling the PHP built image on start (default: missing)

Typical local development .env.local:

```
DATA_PATH=./data
REMOVE_DATABASE_WHEN_INSTALL=true
BUILD_WHEN_INSTALL=true
DOCKER_DEV=true
OPTIONAL_VOLUME=.:/var/spark
LOCALDEV_WORKING_DIR=true
URL_API=http://nginx
PULL_POLICY=never
```

Typical server development .env.local:
```
DATA_PATH=../data
REMOVE_DATABASE_WHEN_INSTALL=true
DOCKER_DEV=true
DOCKER_PHP_BUILT_IMAGE=gitlab.logipro.com:5050/logipro-fr/captain-learning/captain-learning/captain-learning-php-dev:latest
URL_API=https://dev.your-app.tld
```

For production and pre-production, a .env.local MUST NOT exist because default variables target the production environment.
However, this project includes a frontend that calls the API, so in pre-production you need a .env.local to override `URL_API`.

Typical pre-production .env.local:
```
URL_API=https://preprod.your-app.tld
```


## Tests

### Unit tests

```bash
bin/phpunit
```

We use Test-Driven Development (TDD) principles and good practices.

### Integration tests
Integration tests are all test categories other than unit tests.

```bash
bin/integration
```

### Acceptance tests
Acceptance tests are integration tests that verify the application against feature specifications.
Gherkin is the specification language; Behat is the PHP runner.

```bash
bin/behat
```


## Manual tests

```bash
./start
```
Then open http://127.0.0.1:35080/ in your browser.

```bash
./stop
```

## Quality

Some indicators we aim for:

* phpcs PSR12
* phpstan level 10
* coverage >=100%
* infection MSI >=100%

Quick check:
```bash
./codecheck
```

Check coverage:
```bash
bin/phpunit --coverage-html var
```
Then open `var/index.html` in your browser.

Check infection:
```bash
bin/infection
```
Then open `var/infection.html` in your browser.