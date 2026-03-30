---
name: add-vps-action
description: Adds a new Virtuozzo VPS action by creating matching templates/{action}.sh.tpl and templates/backup/{action}.sh.tpl Smarty shell templates. Use when user says 'add action', 'new vps command', 'create template for', 'support new action', or needs a new $serviceInfo['action'] value handled in getQueue(). Do NOT use for modifying Plugin.php hook registration, adding new service types, or changing settings.
---
# add-vps-action

## Critical

- **Both files are required**: every action MUST have a matching file in both `templates/` and `templates/backup/`. `getQueue()` in `src/Plugin.php` checks `file_exists()` on the action template before fetching — if missing it logs an error and produces no output. The backup path is used when the VPS is routed to a backup node and uses `prlctl` commands on backup nodes.
- **Never skip the backup template**: all existing actions in `templates/` have a counterpart in `templates/backup/`. Omitting it breaks backup-node queue processing silently.
- **Action name = filename stem**: the value of `$serviceInfo['action']` must exactly match the filename (e.g. action `restart` → `templates/restart.sh.tpl`). Use `snake_case`.
- **No PHP changes required** for adding a new action — `getQueue()` in `src/Plugin.php` dynamically fetches any template file by action name.

## Instructions

### Step 1 — Identify the action name and required Smarty variables

Confirm the action name (snake_case) and which `$serviceInfo` keys the shell commands need. All keys of `$serviceInfo` are auto-assigned to Smarty via `$smarty->assign($serviceInfo)` in `src/Plugin.php`. Common variables:

| Smarty var | Source |
|---|---|
| `{$vps_id}` | numeric VPS ID |
| `{$vps_vzid}` | container ID (use `"0"` check) |
| `{$vps_hostname}` | hostname |
| `{$param}` | single action parameter |
| `{$settings.slice_hd}` | HD per slice (GB) |
| `{$settings.slice_ram}` | RAM per slice (MB) |

Verify variables exist by checking a similar existing template (e.g. `templates/restart.sh.tpl`) before proceeding.

### Step 2 — Create the main node template

This template runs on the primary node via the provirted abstraction layer. See `templates/restart.sh.tpl` for a complete single-line example. The core VZ ID conditional pattern used in every main template:

```smarty
{if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if}
```

If the action takes a parameter:
```smarty
--{flag}={$param|escapeshellarg} {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if};
```

Rules:
- Always use `{$vps_vzid|escapeshellarg}` (not bare `{$vps_vzid}`) except inside the `== "0"` branch where the fallback `{$vps_id}` is used.
- Always use `{$param|escapeshellarg}` for user-supplied parameters.
- End every shell line with `;`.
- Single-line templates are the norm for simple actions (see `templates/restart.sh.tpl`, `templates/stop.sh.tpl`, `templates/add_ip.sh.tpl`).

Verify the file is created at exactly `templates/{your-action-name}.sh.tpl` before proceeding.

### Step 3 — Create the backup node template

This template runs on backup nodes using `prlctl` directly. See `templates/backup/restart.sh.tpl` for a complete example. Rules differ from main:

```smarty
export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl {prlctl-subcommand} {$vps_vzid};
```

If the action sets a value:
```smarty
export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --{flag}:{$param|escapeshellarg};
```

Rules:
- Always include the `export PATH=...` line first (see `templates/backup/restart.sh.tpl`, `templates/backup/stop.sh.tpl`).
- Use `{$vps_vzid}` directly (not the `== "0"` conditional) — backup nodes always have a vzid.
- Mirror the semantics of the primary template using equivalent `prlctl` commands.

Verify the file is created at exactly `templates/backup/{your-action-name}.sh.tpl` before proceeding.

### Step 4 — Verify both templates exist

Verify both the main template and backup template files exist for the new action:

```bash
ls templates/restart.sh.tpl templates/backup/restart.sh.tpl
```

(Replace `restart` with your action name.) Both must be present. Then do a quick syntax check — confirm all Smarty variable references are closed (`{$var}` not `$var`), `|escapeshellarg` is applied to user input, and no unmatched `{if}`/`{/if}` blocks exist.

### Step 5 — Cross-check the full action list stays in sync

Confirm `templates/` and `templates/backup/` have identical file sets:
```bash
diff <(ls templates/*.sh.tpl | xargs -n1 basename) <(ls templates/backup/*.sh.tpl | xargs -n1 basename)
```

No diff = both directories are in sync. If there is a diff, create the missing file.

## Examples

**User says:** "Add a `snapshot` action that snapshots a VPS"

**Actions taken:**

1. Action name: `snapshot` — create `templates/snapshot.sh.tpl` and `templates/backup/snapshot.sh.tpl`.
2. Create `templates/snapshot.sh.tpl` (following the pattern from `templates/restart.sh.tpl`):
   ```smarty
   {* snapshot subcommand targeting the container *}
   {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if};
   ```
3. Create `templates/backup/snapshot.sh.tpl` (following the pattern from `templates/backup/restart.sh.tpl`):
   ```smarty
   export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
   prlctl snapshot {$vps_vzid};
   ```
4. Verify: `ls templates/snapshot.sh.tpl templates/backup/snapshot.sh.tpl` → both present.
5. Run diff check → no difference.

**Result:** When `$serviceInfo['action'] === 'snapshot'`, `getQueue()` in `src/Plugin.php` will find and render the template, appending the generated shell command to `$event['output']`.

---

**User says:** "Add a `set_password` action with a `$param` for the new password"

`templates/set_password.sh.tpl` (following the with-param pattern):
```smarty
--password={$param|escapeshellarg} {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if};
```

`templates/backup/set_password.sh.tpl`:
```smarty
export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --userpasswd root:{$param|escapeshellarg};
```

## Common Issues

**"Call {action} for VPS ... Does not Exist"** logged by `myadmin_log` and no output generated:
- The action template file is missing or the filename doesn't exactly match the `$serviceInfo['action']` value.
- Fix: verify the file exists and the action string matches exactly (case-sensitive, snake_case). Check `src/Plugin.php` to confirm the action string.

**Backup node produces no output / silent failure:**
- The backup template file is missing.
- Fix: create the backup template. Run the diff check in Step 5 to catch any other missing backup templates.

**Shell injection / command runs with unescaped input:**
- A Smarty var derived from user input was rendered without `|escapeshellarg`.
- Fix: add `|escapeshellarg` to every user-supplied variable except `{$vps_id}` (which is always a validated integer) and `{$vps_vzid}` inside the `== "0"` branch fallback.

**Smarty parse error on template fetch:**
- Unmatched `{if}`/`{/if}`, unclosed `{foreach}`, or a bare `$var` without Smarty braces.
- Fix: ensure every Smarty tag uses `{...}` delimiters and all block tags are closed.

**`diff` shows extra files in `templates/` with no backup counterpart:**
- A prior action was added to `templates/` but its backup template file was never created.
- Fix: create the missing backup template using Step 3 patterns.
