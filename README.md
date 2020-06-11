# Config

![Tests](https://github.com/piotrkreft/config/workflows/Tests/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/piotrkreft/config/badge.svg?branch=master)](https://coveralls.io/github/piotrkreft/config?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fpiotrkreft%2Fconfig%2Fmaster)](https://infection.github.io)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/piotrkreft/config/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/piotrkreft/config/?branch=master)

Component for fetching, merging, and validating configuration from various sources.

## Introduction
Whenever you have various environments for multiple purposes: production, staging, test, local and more
the need arises to keep the configuration of those consistent across possibly different platforms like
local environments and some external storage like AWS Simple Systems Manager and not to fail during critical deploy.

This component allows you to keep it tight in one source with a predefined yaml solution.

## Example
```php
use PK\Config\ConfigFactory;

$config = ConfigFactory::create(realpath('/configuration/config.yaml'));

$config->validate('dev');

$config->fetch('dev');
```

[example configuration](tests/Fixtures/Resources/config/config.yaml)

:information_source: Variables declared within envs scope take the precedence over global ones.

:information_source: Global variables can be disabled in specific env with the `disable` flag.

## Adapters
To be able to use different configuration sources adapters are needed.
By default package provides:

* aws_ssm (`PK\Config\StorageAdapter\AwsSsm`) - for AWS Simple Systems Manager parameters
* local_env (`PK\Config\StorageAdapter\LocalEnv`) - for local environment variables

and each of those is available to be instantiated via component configuration.

If needed a new adapter can be easily created. Just remember to interface it with `PK\Config\StorageAdapterInterface` and to instantiate it.

:information_source: Order of the adapters in each environment is also a priority. If the first adapter provides value, the following will be ignored.

## CLI
Validation of entries:
```bash
bin/pk-config -c tests/Fixtures/Resources/config/config.yaml validate dev
```

Displaying of entries:
```bash
bin/pk-config -c tests/Fixtures/Resources/config/config.yaml display dev
```

## Symfony Bundle
It's possible to use the component as a Symfony Bundle. Just make sure you have `symfony/http-kernel` installed.

If used as such commands will receive `pk:config:` (i.e. `pk:config:validate`) prefix.
