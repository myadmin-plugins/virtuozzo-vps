<?php

declare(strict_types=1);

namespace Detain\MyAdminVirtuozzo\Tests;

use Detain\MyAdminVirtuozzo\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Test suite for the Detain\MyAdminVirtuozzo\Plugin class.
 *
 * Covers class structure, static properties, hook registration,
 * event handler signatures, and queue template-path logic.
 *
 * @coversDefaultClass \Detain\MyAdminVirtuozzo\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * Reflection instance reused across structural tests.
     *
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    /**
     * Set up the reflection instance before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Clean up global state after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($GLOBALS['tf']);
        parent::tearDown();
    }

    /**
     * Install a minimal $GLOBALS['tf'] stub so that get_service_define()
     * can be called without hitting a real database.  The stub always
     * returns -9999, which will never match a real service type.
     *
     * @return void
     */
    private function setUpGlobalTfStub(): void
    {
        $GLOBALS['tf'] = new class {
            /**
             * @param string $name
             * @return int
             */
            public function get_service_define(string $name): int
            {
                return -9999;
            }
        };
    }

    // ------------------------------------------------------------------
    //  Class structure
    // ------------------------------------------------------------------

    /**
     * Verify that the Plugin class can be instantiated.
     *
     * @covers ::__construct
     * @return void
     */
    public function testPluginCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        self::assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Verify that the class resides in the expected namespace.
     *
     * @return void
     */
    public function testClassNamespace(): void
    {
        self::assertSame(
            'Detain\\MyAdminVirtuozzo',
            $this->reflection->getNamespaceName()
        );
    }

    /**
     * Verify that the class is not abstract or final.
     *
     * @return void
     */
    public function testClassIsConcreteAndNotFinal(): void
    {
        self::assertFalse($this->reflection->isAbstract());
        self::assertFalse($this->reflection->isFinal());
    }

    // ------------------------------------------------------------------
    //  Static properties
    // ------------------------------------------------------------------

    /**
     * Verify the static $name property exists and contains the expected value.
     *
     * @covers ::$name
     * @return void
     */
    public function testStaticNameProperty(): void
    {
        self::assertTrue($this->reflection->hasProperty('name'));
        self::assertTrue($this->reflection->getProperty('name')->isStatic());
        self::assertSame('Virtuozzo VPS', Plugin::$name);
    }

    /**
     * Verify the static $description property exists and is a non-empty string.
     *
     * @covers ::$description
     * @return void
     */
    public function testStaticDescriptionProperty(): void
    {
        self::assertTrue($this->reflection->hasProperty('description'));
        self::assertTrue($this->reflection->getProperty('description')->isStatic());
        self::assertIsString(Plugin::$description);
        self::assertNotEmpty(Plugin::$description);
    }

    /**
     * Verify that the description mentions Virtuozzo.
     *
     * @return void
     */
    public function testDescriptionMentionsVirtuozzo(): void
    {
        self::assertStringContainsString('Virtuozzo', Plugin::$description);
    }

    /**
     * Verify the static $help property exists and is a string.
     *
     * @covers ::$help
     * @return void
     */
    public function testStaticHelpProperty(): void
    {
        self::assertTrue($this->reflection->hasProperty('help'));
        self::assertTrue($this->reflection->getProperty('help')->isStatic());
        self::assertIsString(Plugin::$help);
    }

    /**
     * Verify the static $module property is set to 'vps'.
     *
     * @covers ::$module
     * @return void
     */
    public function testStaticModuleProperty(): void
    {
        self::assertTrue($this->reflection->hasProperty('module'));
        self::assertTrue($this->reflection->getProperty('module')->isStatic());
        self::assertSame('vps', Plugin::$module);
    }

    /**
     * Verify the static $type property is set to 'service'.
     *
     * @covers ::$type
     * @return void
     */
    public function testStaticTypeProperty(): void
    {
        self::assertTrue($this->reflection->hasProperty('type'));
        self::assertTrue($this->reflection->getProperty('type')->isStatic());
        self::assertSame('service', Plugin::$type);
    }

    /**
     * Verify that all five expected static properties are public.
     *
     * @return void
     */
    public function testAllStaticPropertiesArePublic(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expected as $prop) {
            self::assertTrue(
                $this->reflection->getProperty($prop)->isPublic(),
                "Property \${$prop} should be public"
            );
        }
    }

    // ------------------------------------------------------------------
    //  getHooks()
    // ------------------------------------------------------------------

    /**
     * Verify that getHooks() returns an array.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        self::assertIsArray($hooks);
    }

    /**
     * Verify that getHooks() contains exactly the expected event keys.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKeys = [
            'vps.settings',
            'vps.deactivate',
            'vps.queue',
        ];

        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $hooks, "Missing hook key: {$key}");
        }
    }

    /**
     * Verify that getHooks() does NOT contain the commented-out activate hook.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksDoesNotContainActivateHook(): void
    {
        $hooks = Plugin::getHooks();
        self::assertArrayNotHasKey('vps.activate', $hooks);
    }

    /**
     * Verify that every hook value is a callable-style array [class, method].
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $value) {
            self::assertIsArray($value, "Hook '{$key}' value should be an array");
            self::assertCount(2, $value, "Hook '{$key}' should have exactly 2 elements");
            self::assertSame(Plugin::class, $value[0], "Hook '{$key}' class should be Plugin");
            self::assertIsString($value[1], "Hook '{$key}' method name should be a string");
        }
    }

    /**
     * Verify that every method referenced by getHooks() actually exists on the class.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $value) {
            self::assertTrue(
                $this->reflection->hasMethod($value[1]),
                "Method {$value[1]} referenced by hook '{$key}' does not exist on Plugin"
            );
        }
    }

    /**
     * Verify that hook keys are prefixed with the module name.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksKeysArePrefixedWithModule(): void
    {
        $hooks = Plugin::getHooks();
        $prefix = Plugin::$module . '.';
        foreach (array_keys($hooks) as $key) {
            self::assertStringStartsWith(
                $prefix,
                $key,
                "Hook key '{$key}' should start with '{$prefix}'"
            );
        }
    }

    /**
     * Verify the exact number of registered hooks.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksCount(): void
    {
        $hooks = Plugin::getHooks();
        self::assertCount(3, $hooks);
    }

    // ------------------------------------------------------------------
    //  Event-handler method signatures (static analysis)
    // ------------------------------------------------------------------

    /**
     * Verify that getSettings() is a public static method accepting GenericEvent.
     *
     * @covers ::getSettings
     * @return void
     */
    public function testGetSettingsSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        self::assertTrue($method->isPublic());
        self::assertTrue($method->isStatic());

        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify that getDeactivate() is a public static method accepting GenericEvent.
     *
     * @covers ::getDeactivate
     * @return void
     */
    public function testGetDeactivateSignature(): void
    {
        $method = $this->reflection->getMethod('getDeactivate');
        self::assertTrue($method->isPublic());
        self::assertTrue($method->isStatic());

        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify that getActivate() is a public static method accepting GenericEvent.
     *
     * @covers ::getActivate
     * @return void
     */
    public function testGetActivateSignature(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        self::assertTrue($method->isPublic());
        self::assertTrue($method->isStatic());

        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify that getQueue() is a public static method accepting GenericEvent.
     *
     * @covers ::getQueue
     * @return void
     */
    public function testGetQueueSignature(): void
    {
        $method = $this->reflection->getMethod('getQueue');
        self::assertTrue($method->isPublic());
        self::assertTrue($method->isStatic());

        $params = $method->getParameters();
        self::assertCount(1, $params);
        self::assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        self::assertNotNull($type);
        self::assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Verify that all event handlers have void return type or no explicit return type.
     *
     * @return void
     */
    public function testEventHandlersReturnType(): void
    {
        $handlers = ['getSettings', 'getDeactivate', 'getActivate', 'getQueue'];
        foreach ($handlers as $handlerName) {
            $method = $this->reflection->getMethod($handlerName);
            $returnType = $method->getReturnType();
            // These methods have no declared return type in the source
            self::assertNull(
                $returnType,
                "{$handlerName}() should have no declared return type"
            );
        }
    }

    // ------------------------------------------------------------------
    //  getHooks() returns correct method names
    // ------------------------------------------------------------------

    /**
     * Verify that the settings hook points to getSettings.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testSettingsHookPointsToGetSettings(): void
    {
        $hooks = Plugin::getHooks();
        self::assertSame('getSettings', $hooks['vps.settings'][1]);
    }

    /**
     * Verify that the deactivate hook points to getDeactivate.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testDeactivateHookPointsToGetDeactivate(): void
    {
        $hooks = Plugin::getHooks();
        self::assertSame('getDeactivate', $hooks['vps.deactivate'][1]);
    }

    /**
     * Verify that the queue hook points to getQueue.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testQueueHookPointsToGetQueue(): void
    {
        $hooks = Plugin::getHooks();
        self::assertSame('getQueue', $hooks['vps.queue'][1]);
    }

    // ------------------------------------------------------------------
    //  Template directory existence
    // ------------------------------------------------------------------

    /**
     * Verify that the templates directory exists relative to the source.
     *
     * @return void
     */
    public function testTemplatesDirectoryExists(): void
    {
        $templatesDir = dirname((string) (new ReflectionClass(Plugin::class))->getFileName()) . '/../templates';
        self::assertDirectoryExists($templatesDir);
    }

    /**
     * Verify that key shell template files exist in the templates directory.
     *
     * @dataProvider templateFileProvider
     * @param string $filename The template filename to check.
     * @return void
     */
    public function testTemplateFileExists(string $filename): void
    {
        $templatesDir = dirname((string) (new ReflectionClass(Plugin::class))->getFileName()) . '/../templates';
        self::assertFileExists($templatesDir . '/' . $filename);
    }

    /**
     * Data provider for template file existence tests.
     *
     * @return array<string, array{string}>
     */
    public static function templateFileProvider(): array
    {
        return [
            'create template'           => ['create.sh.tpl'],
            'delete template'           => ['delete.sh.tpl'],
            'destroy template'          => ['destroy.sh.tpl'],
            'start template'            => ['start.sh.tpl'],
            'stop template'             => ['stop.sh.tpl'],
            'restart template'          => ['restart.sh.tpl'],
            'enable template'           => ['enable.sh.tpl'],
            'backup template'           => ['backup.sh.tpl'],
            'restore template'          => ['restore.sh.tpl'],
            'add_ip template'           => ['add_ip.sh.tpl'],
            'remove_ip template'        => ['remove_ip.sh.tpl'],
            'change_hostname template'  => ['change_hostname.sh.tpl'],
            'change_root template'      => ['change_root.sh.tpl'],
            'reinstall_os template'     => ['reinstall_os.sh.tpl'],
            'setup_vnc template'        => ['setup_vnc.sh.tpl'],
        ];
    }

    // ------------------------------------------------------------------
    //  Queue handler: non-matching type short-circuits
    // ------------------------------------------------------------------

    /**
     * Verify that getQueue() does not stop propagation when the event type
     * does not match the Virtuozzo service defines.
     *
     * @covers ::getQueue
     * @return void
     */
    public function testGetQueueIgnoresNonMatchingType(): void
    {
        $this->setUpGlobalTfStub();

        $event = new GenericEvent(
            ['server_info' => [], 'action' => 'create', 'vps_hostname' => '', 'vps_id' => 0, 'vps_vzid' => 0, 'vps_custid' => 0],
            ['type' => 0, 'output' => '']
        );

        Plugin::getQueue($event);

        // When the type does not match, propagation should NOT be stopped
        self::assertFalse($event->isPropagationStopped());
    }

    // ------------------------------------------------------------------
    //  Constructor is parameter-less
    // ------------------------------------------------------------------

    /**
     * Verify that the constructor takes no parameters.
     *
     * @covers ::__construct
     * @return void
     */
    public function testConstructorHasNoParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        self::assertNotNull($constructor);
        self::assertCount(0, $constructor->getParameters());
    }

    // ------------------------------------------------------------------
    //  getHooks() return type
    // ------------------------------------------------------------------

    /**
     * Verify that getHooks() has the expected return type declaration or returns an array.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksReturnsArrayValue(): void
    {
        $result = Plugin::getHooks();
        self::assertIsArray($result);
    }

    /**
     * Verify that getHooks() is a public static method.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        self::assertTrue($method->isPublic());
        self::assertTrue($method->isStatic());
    }

    // ------------------------------------------------------------------
    //  Static properties are not null
    // ------------------------------------------------------------------

    /**
     * Verify that no static property is null.
     *
     * @return void
     */
    public function testNoStaticPropertyIsNull(): void
    {
        $props = ['name', 'description', 'help', 'module', 'type'];
        foreach ($props as $prop) {
            $value = $this->reflection->getProperty($prop)->getValue();
            self::assertNotNull($value, "Static property \${$prop} should not be null");
        }
    }

    /**
     * Verify that all static string properties are indeed strings.
     *
     * @return void
     */
    public function testAllStaticPropertiesAreStrings(): void
    {
        $props = ['name', 'description', 'help', 'module', 'type'];
        foreach ($props as $prop) {
            $value = $this->reflection->getProperty($prop)->getValue();
            self::assertIsString($value, "Static property \${$prop} should be a string");
        }
    }

    // ------------------------------------------------------------------
    //  Activate handler: non-matching type short-circuits
    // ------------------------------------------------------------------

    /**
     * Verify that getActivate() does not stop propagation when the event type
     * does not match.
     *
     * @covers ::getActivate
     * @return void
     */
    public function testGetActivateIgnoresNonMatchingType(): void
    {
        $this->setUpGlobalTfStub();

        $subject = new class {
            /**
             * @return int
             */
            public function getId(): int
            {
                return 1;
            }

            /**
             * @return int
             */
            public function getCustid(): int
            {
                return 100;
            }
        };

        $event = new GenericEvent($subject, ['type' => 0]);

        Plugin::getActivate($event);

        self::assertFalse($event->isPropagationStopped());
    }

    /**
     * Verify that getDeactivate() does not stop propagation when the event type
     * does not match.
     *
     * @covers ::getDeactivate
     * @return void
     */
    public function testGetDeactivateIgnoresNonMatchingType(): void
    {
        $this->setUpGlobalTfStub();

        $subject = new class {
            /**
             * @return int
             */
            public function getId(): int
            {
                return 1;
            }

            /**
             * @return int
             */
            public function getCustid(): int
            {
                return 100;
            }
        };

        $event = new GenericEvent($subject, ['type' => 0]);

        Plugin::getDeactivate($event);

        self::assertFalse($event->isPropagationStopped());
    }

    // ------------------------------------------------------------------
    //  Hook callable verification
    // ------------------------------------------------------------------

    /**
     * Verify that each hook in getHooks() references a callable static method.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testAllHooksAreCallable(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $callback) {
            self::assertTrue(
                is_callable($callback),
                "Hook '{$key}' should reference a callable method"
            );
        }
    }

    // ------------------------------------------------------------------
    //  getHooks() is idempotent
    // ------------------------------------------------------------------

    /**
     * Verify that calling getHooks() multiple times returns identical results.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testGetHooksIsIdempotent(): void
    {
        $first = Plugin::getHooks();
        $second = Plugin::getHooks();
        self::assertSame($first, $second);
    }

    // ------------------------------------------------------------------
    //  Module value is used in hook keys
    // ------------------------------------------------------------------

    /**
     * Verify that changing the module property would affect hook key generation
     * by confirming the current keys match the current module value.
     *
     * @covers ::getHooks
     * @return void
     */
    public function testHookKeysMatchModuleProperty(): void
    {
        $hooks = Plugin::getHooks();
        $module = Plugin::$module;
        foreach (array_keys($hooks) as $key) {
            $parts = explode('.', $key, 2);
            self::assertSame($module, $parts[0]);
        }
    }
}
