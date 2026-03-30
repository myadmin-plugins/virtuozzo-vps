---
name: plugin-event-hook
description: Adds a new Symfony event hook to src/Plugin.php in the myadmin-virtuozzo-vps plugin. Handles registering in getHooks(), writing the static handler method with GenericEvent, service type guard, logging, and stopPropagation(). Use when user says 'add hook', 'new event handler', 'listen to event', or adds a handler method to Plugin.php. Do NOT use for modifying template files or adding template actions.
---
# plugin-event-hook

Adds a new Symfony event hook to `src/Plugin.php`, including registration in `getHooks()` and a correctly structured static handler method.

## Critical

- Every handler **must** guard with `in_array($event['type'], [...])` — never process events intended for other VPS types.
- Always call `$event->stopPropagation()` inside the type-guard block (never outside it). Handlers that skip this will process events belonging to other plugins.
- Handler methods have **no declared return type** — do not add `: void` or any return type hint.
- The hook key format is always `self::$module . '.<event-name>'` (e.g., `vps.activate`). Never hard-code `'vps'`.
- Use `$serviceClass = $event->getSubject()` when the subject is an ORM object with `getId()`/`getCustid()`. Use `$serviceInfo = $event->getSubject()` when the subject is an associative array (as in `getQueue`).

## Instructions

1. **Identify the event name and subject type.**
   - Event name comes from the module event system (e.g., `activate`, `deactivate`, `queue`, `restore`).
   - Determine if the subject is a service class (ORM object) or a `$serviceInfo` array. `queue` uses an array; `activate`/`deactivate` use an ORM object.
   - Verify `src/Plugin.php` exists before editing.

2. **Register the hook in `getHooks()`.**
   - Open `src/Plugin.php` and locate the `getHooks()` method (lines 31–39).
   - Add one entry to the returned array following this exact pattern:
     ```php
     self::$module.'.eventname' => [__CLASS__, 'getEventname'],
     ```
   - The method name must be `get` + PascalCase of the event name (e.g., `activate` → `getActivate`).
   - Verify the key is not already present before adding.

3. **Write the handler method.**
   - Add the method immediately after the last existing handler, before the closing `}` of the class.
   - Use this exact boilerplate for an ORM-subject handler:
     ```php
     /**
      * @param \Symfony\Component\EventDispatcher\GenericEvent $event
      */
     public static function getEventname(GenericEvent $event)
     {
         if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
             $serviceClass = $event->getSubject();
             myadmin_log(self::$module, 'info', self::$name.' Eventname', __LINE__, __FILE__, self::$module, $serviceClass->getId(), true, false, $serviceClass->getCustid());
             $event->stopPropagation();
         }
     }
     ```
   - For handlers that also need to record history (like `getDeactivate`), add inside the guard block:
     ```php
     $GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
     ```
   - Verify the method name exactly matches the value registered in `getHooks()` in Step 2.

4. **Update `tests/PluginTest.php`.**
   - Add the new event key to `testGetHooksContainsExpectedKeys()` `$expectedKeys` array.
   - Update `testGetHooksCount()` to reflect the new count.
   - Add a signature test following this pattern:
     ```php
     public function testGetEventnameSignature(): void
     {
         $method = $this->reflection->getMethod('getEventname');
         self::assertTrue($method->isPublic());
         self::assertTrue($method->isStatic());
         $params = $method->getParameters();
         self::assertCount(1, $params);
         self::assertSame('event', $params[0]->getName());
         $type = $params[0]->getType();
         self::assertNotNull($type);
         self::assertSame(GenericEvent::class, $type->getName());
     }
     ```
   - Add a non-matching-type propagation test:
     ```php
     public function testGetEventnameIgnoresNonMatchingType(): void
     {
         $this->setUpGlobalTfStub();
         $subject = new class {
             public function getId(): int { return 1; }
             public function getCustid(): int { return 100; }
         };
         $event = new GenericEvent($subject, ['type' => 0]);
         Plugin::getEventname($event);
         self::assertFalse($event->isPropagationStopped());
     }
     ```
   - Add the new method name to the `$handlers` array in `testEventHandlersReturnType()`.
   - Verify all tests pass: `vendor/bin/phpunit tests/PluginTest.php`

5. **Run tests to confirm no regressions.**
   ```bash
   vendor/bin/phpunit tests/PluginTest.php
   ```
   All tests must pass before considering the task complete.

## Examples

**User says:** "Add an activate hook that logs when a Virtuozzo VPS is activated."

**Actions taken:**

1. In `getHooks()` (in `src/Plugin.php`), add:
   ```php
   self::$module.'.activate' => [__CLASS__, 'getActivate'],
   ```
2. Add the handler method to `src/Plugin.php`:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function getActivate(GenericEvent $event)
   {
       if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
           $serviceClass = $event->getSubject();
           myadmin_log(self::$module, 'info', 'Virtuozzo Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId(), true, false, $serviceClass->getCustid());
           $event->stopPropagation();
       }
   }
   ```
3. In `tests/PluginTest.php`:
   - Add `'vps.activate'` to `testGetHooksContainsExpectedKeys()`.
   - Change count in `testGetHooksCount()` from `3` to `4`.
   - Add `testGetActivateSignature()` and `testGetActivateIgnoresNonMatchingType()` methods.
   - Add `'getActivate'` to the `$handlers` array in `testEventHandlersReturnType()`.
4. Run `vendor/bin/phpunit tests/PluginTest.php` — all tests pass.

**Result:** `getHooks()` returns 4 hooks; `getActivate` is registered and callable; tests confirm correct signature and non-matching-type behavior.

## Common Issues

- **`Call to undefined function get_service_define()`** during tests: The test calls `setUpGlobalTfStub()` in the test method — ensure the stub is installed before calling `Plugin::getYourHandler($event)`.
- **Propagation stopped for wrong type:** You placed `$event->stopPropagation()` outside the `if (in_array(...))` guard. Move it inside the block.
- **Test count mismatch — `testGetHooksCount()` fails with `expected 3, got 4`:** Update the `assertCount` argument in `testGetHooksCount()` to match the new total.
- **`Method getEventname does not exist` in `testGetHooksMethodsExist()`:** The method name in `getHooks()` does not match the declared method name. Verify PascalCase conversion is consistent (`activate` → `getActivate`, not `getactivate`).
- **`testEventHandlersReturnType` fails for new handler:** You added a `: void` return type to the new method. Remove it — existing handlers have no declared return type, and the test asserts `assertNull($returnType)`.
- **`testGetHooksDoesNotContainActivateHook` fails after adding activate:** This test explicitly asserts `vps.activate` is absent. If you are intentionally adding the activate hook, remove or update that test to reflect the new state.
