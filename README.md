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

## Installation
```bash
composer require piotrkreft/config
```

## Usage
### Configuration
[example configuration](tests/Fixtures/Resources/config/config.yaml)

:information_source: Variables declared within envs scope take the precedence over global ones.

:information_source: Global variables can be disabled in specific env with the `disable` flag.

### CLI
Validation of entries:
```bash
vendor/bin/pk-config -c config.yaml validate dev
```

Displaying of entries:
```bash
vendor/bin/pk-config -c config.yaml display dev
```

### PHP
```php
use PK\Config\ConfigFactory;

$config = ConfigFactory::create(realpath('config.yaml'));

$config->validate('dev');

$config->fetch('dev');
```

### Symfony Bundle
It's possible to use the component as a Symfony Bundle.
Just make sure you have `symfony/http-kernel` installed and add `PK\Config\PKConfigBundle` to your application Kernel.

If used as such commands will receive `pk:config:` and can be used like:
```bash
bin/console pk:config:validate dev
```

### Adapters
To be able to use a different configuration sources adapters are needed.
By default, package provides:
* aws_ssm (multiple)(`PK\Config\StorageAdapter\{AwsSsm, AwsSsmByPath}`) - for AWS Simple Systems Manager parameters
* local_env (`PK\Config\StorageAdapter\LocalEnv`) - for local environment variables

and each of those is available to be instantiated via component configuration.

If needed a new adapter can be easily created. Just remember to interface it with `PK\Config\StorageAdapterInterface` and to instantiate it.

:information_source: Order of the adapters in each environment is also a priority. If the first adapter provides value, the following will be ignored.

:information_source: If adapter has multiple option assigned it can be configured with multiple different instances. If so each can be referenced in env.adapters like {adapter}.{name} (i.e. aws_ssm.default)

### Testing
```bash
composer test
```

Static checks issues fix
```bash
composer fix
```

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License
The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
