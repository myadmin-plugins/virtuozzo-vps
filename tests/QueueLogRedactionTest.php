<?php

namespace Detain\MyAdminVirtuozzo\Tests;

use Detain\MyAdminVirtuozzo\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * getQueue() logs the rendered queue script; the logged copy must not carry
 * the root password the script was rendered with, while the queued script
 * itself is left alone.
 */
class QueueLogRedactionTest extends TestCase
{
    public function testRenderedCreateScriptIsRedactedInTheLogCopyOnly(): void
    {
        $template = (string) file_get_contents(dirname(__DIR__) . '/templates/create.sh.tpl');
        $this->assertStringContainsString('{$rootpass}', $template);
        $serviceInfo = ['action' => 'create', 'origrootpass' => 'S3cr3t-R00t', 'rootpass' => escapeshellarg('S3cr3t-R00t')];
        $rendered = str_replace('{$rootpass}', $serviceInfo['rootpass'], $template);
        $logged = Plugin::redactQueueOutput($rendered, $serviceInfo);
        $this->assertStringContainsString('S3cr3t-R00t', $rendered);
        $this->assertStringNotContainsString('S3cr3t-R00t', $logged);
        $this->assertStringContainsString('[redacted]', $logged);
    }

    public function testQuotedPasswordsAndStoredColumnsAreRedacted(): void
    {
        $pass = "it's-a-secret";
        $serviceInfo = ['action' => 'reinstall_os', 'origrootpass' => $pass, 'rootpass' => escapeshellarg($pass), 'vps_rootpass' => 'StoredPass1'];
        $output = 'reinstall --password=' . escapeshellarg($pass) . ' raw=' . $pass . ' stored=StoredPass1 vzid=linux123';
        $logged = Plugin::redactQueueOutput($output, $serviceInfo);
        $this->assertStringNotContainsString('secret', $logged);
        $this->assertStringNotContainsString('StoredPass1', $logged);
        $this->assertStringContainsString('vzid=linux123', $logged);
    }

    public function testChangeRootParamIsRedactedButOtherParamsAreNot(): void
    {
        $logged = Plugin::redactQueueOutput('update --password=' . escapeshellarg('NewRoot99') . ' linux1', ['action' => 'change_root', 'param' => 'NewRoot99']);
        $this->assertStringNotContainsString('NewRoot99', $logged);
        $logged = Plugin::redactQueueOutput('change-hostname linux1 host.example.com', ['action' => 'change_hostname', 'param' => 'host.example.com']);
        $this->assertSame('change-hostname linux1 host.example.com', $logged);
    }

    public function testNothingToRedactLeavesTheOutputUnchanged(): void
    {
        $this->assertSame('start linux1', Plugin::redactQueueOutput('start linux1', ['action' => 'start', 'rootpass' => escapeshellarg(''), 'origrootpass' => '']));
    }

    public function testTheQueueLogLineUsesTheRedactedCopy(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__) . '/src/Plugin.php');
        $this->assertStringContainsString("' '.self::redactQueueOutput(\$output, \$serviceInfo), __LINE__", $source);
        $this->assertStringNotContainsString("_name'].' '.\$output, __LINE__", $source);
    }
}
