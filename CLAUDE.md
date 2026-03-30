# MyAdmin Virtuozzo VPS Plugin

Composer plugin providing event-driven provisioning, lifecycle, and queue processing for Virtuozzo VPS within the MyAdmin ecosystem.

## Commands

```bash
composer install                        # install deps
vendor/bin/phpunit                      # run all tests
vendor/bin/phpunit tests/PluginTest.php # run plugin tests
```

## Architecture

- **Entry**: `src/Plugin.php` — all logic lives here; registers and handles Symfony events
- **Hooks**: `getHooks()` returns `vps.settings`, `vps.deactivate`, `vps.queue` mappings
- **Templates**: `templates/*.sh.tpl` — Smarty templates rendered by `getQueue()` via `TFSmarty`
- **Backup templates**: `templates/backup/*.sh.tpl` — mirrors main templates for backup-node routing
- **Tests**: `tests/PluginTest.php` · config `phpunit.xml.dist` · bootstrap `vendor/autoload.php`
- **Namespace**: `Detain\MyAdminVirtuozzo\` → `src/` · test namespace `Detain\MyAdminVirtuozzo\Tests\`
- **CI/CD**: `.github/` contains GitHub Actions workflows for automated testing and deployment pipelines
- **IDE**: `.idea/` contains PhpStorm project configuration including `inspectionProfiles/`, `deployment.xml`, and `encodings.xml`

## Key Patterns

### Hook Handler Signature
```php
public static function getQueue(GenericEvent $event)
{
    if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
        $serviceInfo = $event->getSubject();
        $settings = get_module_settings(self::$module);  // self::$module = 'vps'
        // render template via TFSmarty, append to $event['output']
        $event->stopPropagation();
    }
}
```

### Template Rendering in getQueue()
```php
$smarty = new \TFSmarty();
$smarty->assign($serviceInfo);
$output = $smarty->fetch(__DIR__.'/../templates/'.$serviceInfo['action'].'.sh.tpl');
$event['output'] = $event['output'] . $output;
```
- Template filename = `$serviceInfo['action']` + `.sh.tpl`
- Always check `file_exists()` before fetching; log error via `myadmin_log()` if missing
- All `$serviceInfo` keys are available as Smarty vars (`{$vps_hostname}`, `{$vps_vzid}`, etc.)

### Logging
```php
myadmin_log(self::$module, 'info', 'message', __LINE__, __FILE__, self::$module, $serviceClass->getId(), true, false, $serviceClass->getCustid());
```

### Settings Registration (getSettings)
- `$settings->add_text_setting()` for cost fields (e.g. `vps_slice_virtuozzo_cost`)
- `$settings->add_select_master()` for server selection per location
- `$settings->add_dropdown_setting()` for out-of-stock toggles per datacenter
- Always `$settings->setTarget('module')` at start, `$settings->setTarget('global')` at end

## Template Actions

Existing actions in `templates/`: `add_ip`, `backup`, `block_smtp`, `change_hostname`, `change_ip`, `change_root`, `create`, `delete`, `destroy`, `disable_quota`, `enable`, `enable_quota`, `ensure_addon_ip`, `install_cpanel`, `reinstall_os`, `remove_ip`, `restart`, `restore`, `set_slices`, `setup_vnc`, `start`, `stop`, `update_hdsize`

Each action in `templates/` must have a matching file in `templates/backup/` for backup-node routing.

## Service Types

- `get_service_define('VIRTUOZZO')` — standard Virtuozzo instances
- `get_service_define('SSD_VIRTUOZZO')` — SSD-backed Virtuozzo instances
- All handlers must check both types via `in_array()`

## Coding Conventions

- Tabs for indentation (see `.scrutinizer.yml`)
- `myadmin_log()` for all significant state changes
- `$GLOBALS['tf']->history->add()` for deactivation history entries
- Commit messages: lowercase, descriptive (`add resize action`, `fix backup template`)
- Run `caliber refresh` before commits; stage any modified doc files

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
