<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap.
 *
 * ---------------------------------------------------------------------------------
 * WHY THIS FILE EXISTS
 * ---------------------------------------------------------------------------------
 * `\MyAdmin\App` lives in the MyAdmin core tree, which is not a Composer package, so a
 * standalone checkout of this repo does not have it. `tests/PluginTest.php` calls
 * `App::resetContainer()` in `tearDown()`, which runs after **every** test — so without the
 * class, all 52 tests errored, including the purely structural ones that never touch it.
 *
 * The contract harness already ships a stand-in and aliases it into that name. Calling
 * `installApp()` here rather than the full `Bootstrap::init()` is deliberate: `init()` also
 * defines the plugin's bare constants and calls `register_module()`, neither of which can be
 * undone, and doing that in a shared bootstrap would change the conditions every other test in
 * the process runs under. `installApp()` installs the alias and nothing else.
 *
 * It stands down if something else already owns the name, so running inside a real core
 * checkout keeps using the real class.
 */

require dirname(__DIR__).'/vendor/autoload.php';

\MyAdmin\Plugins\Testing\Bootstrap::installApp();
