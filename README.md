# MyAdmin Virtuozzo VPS Plugin

Event-driven MyAdmin plugin for provisioning, lifecycle management, and queue processing of Virtuozzo-based virtual private servers.

[![Build Status](https://github.com/detain/myadmin-virtuozzo-vps/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-virtuozzo-vps/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-virtuozzo-vps/version)](https://packagist.org/packages/detain/myadmin-virtuozzo-vps)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-virtuozzo-vps/downloads)](https://packagist.org/packages/detain/myadmin-virtuozzo-vps)
[![License](https://poser.pugx.org/detain/myadmin-virtuozzo-vps/license)](https://packagist.org/packages/detain/myadmin-virtuozzo-vps)

## Features

- Registers Symfony EventDispatcher hooks for VPS settings, deactivation, and queue processing
- Template-based shell command generation for server operations (create, delete, restart, backup, restore, and more)
- Configurable slice-based pricing for standard and SSD Virtuozzo instances
- Per-location out-of-stock controls for Secaucus NJ, Los Angeles, and TX data centers
- Drop-in MyAdmin plugin architecture via Composer

## Requirements

- PHP 8.2 or later
- ext-soap
- Symfony EventDispatcher 5.x, 6.x, or 7.x

## Installation

```sh
composer require detain/myadmin-virtuozzo-vps
```

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

Licensed under the [LGPL-2.1](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html).
