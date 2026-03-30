---
name: shell-template
description: Writes or edits `.sh.tpl` Smarty shell templates in `templates/` or `templates/backup/`. Covers Smarty variable interpolation (`{$vps_hostname}`, `{$vps_vzid}`), shell safety, and provirted.phar/prlctl command patterns. Use when user says 'edit template', 'fix shell script', 'update .sh.tpl', or modifies any file under templates/. Do NOT use for PHP changes in src/.
---
# Shell Template

## Critical

- Every action needs **two** files: the main template under `templates/` AND the backup template under `templates/backup/`. Never create one without the other.
- **Always** escape user-supplied variables with `|escapeshellarg`. Missing this is a shell-injection vulnerability.
- `templates/` targets the **provirted** abstraction layer. `templates/backup/` targets **prlctl** directly on backup nodes.
- Never interpolate `$_GET`/`$_POST`-origin data raw — all user params come through `{$param}` which must be piped through `|escapeshellarg`.
- Template filename must exactly match `$serviceInfo['action']` — the filename is how `getQueue()` in `src/Plugin.php` resolves the template.

## Instructions

### Step 1 — Identify the action name

The action name is a lowercase, underscore-separated identifier matching `$serviceInfo['action']`, e.g. `add_ip`, `change_hostname`, `set_slices`. Verify the name matches what is passed in the queue handler before creating files.

### Step 2 — Write the main node template

Main templates call `provirted` with the appropriate subcommand. See `templates/restart.sh.tpl` for a minimal example. Use this VZ ID pattern for every command:

```smarty
{if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if}
```

**Simple action (no user param):**
```smarty
provirted restart {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if};
```

**Action with a user-supplied `$param`:**
```smarty
provirted update --hostname={$param|escapeshellarg} {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if};
```

**Action using `$settings` and `$vps_slices` math:**
```smarty
provirted update --hd={($settings.slice_hd * $vps_slices) + $settings.additional_hd} --ram={$vps_slices * $settings.slice_ram} {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if};
```
Settings in main templates use **dot notation**: `{$settings.slice_hd}`, `{$settings.additional_hd}`, `{$settings.slice_ram}`.

**Action splitting a comma-separated `$param`:**
```smarty
{assign var='param' value=','|explode:$param}provirted change-ip {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if} {$param[0]|escapeshellarg} {$param[1]|escapeshellarg};
```

**Action iterating extra IPs:**
```smarty
{foreach item=$extraIp from=$extraips} --add-ip={$extraIp}{/foreach}
```

Verify the file ends with `;` and contains no raw `$_GET`/`$_POST` references.

### Step 3 — Write the backup node template

Backup templates use `prlctl` directly. Rules differ from main. See `templates/backup/restart.sh.tpl` for a minimal example:

- **Always** start with: `export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";`
- VZ ID is **direct** `{$vps_vzid}` (no conditional needed).
- Settings use **array bracket notation**: `{$settings['slice_hd']}`, `{$settings['additional_hd']}`.
- Variable assignment uses unquoted var name: `{assign var=space value=...}`.

**Simple action:**
```smarty
export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl restart {$vps_vzid};
```

**Action with user param:**
```smarty
export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --hostname {$param|escapeshellarg};
```

**Action with settings math:**
```smarty
{assign var=space value=($settings['slice_hd'] * $vps_slices) + $settings['additional_hd']}
export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --diskspace {$space}G;
```

**Action splitting a comma-separated `$param`:**
```smarty
{assign var='param' value=','|explode:$param}
export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --ipdel {$param[0]|escapeshellarg} --ipadd {$param[1]|escapeshellarg};
```

Verify: file begins with `export PATH=...`, uses `prlctl` not `provirted`, uses `{$vps_vzid}` not the conditional.

### Step 4 — Validate both files exist

After creating/editing, confirm both the main and backup templates are present. For example, after creating a `freeze` action:
```bash
ls templates/freeze.sh.tpl templates/backup/freeze.sh.tpl
```
Both must be present. If adding a new action, also confirm it is reachable from `src/Plugin.php`.

## Examples

**User says:** "Add a new `freeze` action that stops cgroups for a VPS"

**Actions taken:**
1. Create `templates/freeze.sh.tpl` (following the pattern from `templates/restart.sh.tpl`):
```smarty
provirted freeze {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if};
```
2. Create `templates/backup/freeze.sh.tpl` (following the pattern from `templates/backup/restart.sh.tpl`):
```smarty
export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --cpulimit 0;
```

---

**User says:** "Fix `set_slices` to also pass `--cpuunits`"

**Actions taken:**
1. Read `templates/set_slices.sh.tpl` — current content uses provirted with `--cgroups`.
2. Edit to add `--cpuunits={1500 * $vps_slices}` before the VZ ID argument.
3. Read `templates/backup/set_slices.sh.tpl` — uses prlctl.
4. Edit to add `prlctl set {$vps_vzid} --cpuunits {1500 * $vps_slices};` line.

**Result:** Both files updated; `--cpuunits` passed in both code paths.

## Common Issues

**Issue: Template not found / action silently skipped**
- `getQueue()` in `src/Plugin.php` calls `file_exists()` before `$smarty->fetch()` and logs an error via `myadmin_log()` if missing.
- Fix: ensure the filename matches `$serviceInfo['action']` exactly (lowercase, underscores).

**Issue: `|escapeshellarg` breaks numeric VZ IDs in backup templates**
- Backup templates use `{$vps_vzid}` directly — do NOT add `|escapeshellarg` to `{$vps_vzid}` in backup templates (prlctl expects a bare CT ID).
- Only apply `|escapeshellarg` to `{$param}` and user-supplied values.

**Issue: `{$settings.slice_hd}` renders empty in backup template**
- Backup templates receive settings as a PHP array; dot notation fails. Use `{$settings['slice_hd']}` (bracket notation) in `templates/backup/` only.

**Issue: Math expression produces wrong value**
- Smarty operator precedence follows PHP. Wrap sub-expressions in parens: `{($settings.slice_hd * $vps_slices) + $settings.additional_hd}` not `{$settings.slice_hd * $vps_slices + $settings.additional_hd}`.

**Issue: `prlctl: command not found` on main node**
- Main-node templates must use the provirted abstraction layer, not `prlctl`. `prlctl` is only valid in `templates/backup/`.

**Issue: New backup template does nothing**
- Backup templates missing the `export PATH=...` line will fail silently if `/usr/sbin` is not in the shell's PATH. Always include it as the first line.
