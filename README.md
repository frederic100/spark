# Spark

Use Spark to initialize a php project from scratch. This prepares you to have some basic tools to practice TDD, etc.

# Install

```bash
git clone git@github.com:frederic100/spark.git
cd spark
./install --profile devlocal
```

# To Contribute to Spark

## Requirements

* docker
* git


## Unit tests

```bash
bin/phpunit
```

Using Test-Driven Development (TDD) principles (thanks to Kent Beck and others), following good practices (thanks to Uncle Bob and others).

## Integration tests
Integration tests are all other test categories except unit tests.

```bash
bin/integration
```

## Acceptation tests
Acceptation tests are specific integration tests to check if the app respects feature specifications. 
Gherkin is the specification language.
Behat is the php Gherkin running tool.

```bash
bin/behat
```


## Manual tests

```bash
./start
```
have a local look at http://127.0.0.1:35080/ in your navigator

```bash
./stop
```

## Quality

Some indicators that seem interesting.

* phpcs PSR12
* phpstan level 9
* coverage >=100%
* infection MSI >=100%

Quick check with:
```bash
./codecheck
```

Check coverage with:
```bash
bin/phpunit --coverage-html var
```
and view 'var/index.html' with your browser

Check infection with:
```bash
bin/infection
```
and view 'var/infection.html' with your browser