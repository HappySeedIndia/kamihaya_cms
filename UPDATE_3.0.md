# Kamihaya CMS 3.0 Update Guide

This guide describes how to update a site running Kamihaya CMS 2.x to
Kamihaya CMS 3.0, which moves the distribution to **Drupal 11**.

> Read this guide fully before starting. Perform the steps in order on a
> staging copy first, then on production.
>
> Sections 1 to 4 are preparation you must complete **before** running any
> Composer command. Skipping them leaves the update half-applied.

## What this release does

Kamihaya CMS 3.0 moves the distribution from Drupal core 10.6 to **Drupal
11.4**, and updates the contributed modules and themes that Drupal 11
requires.

Drupal 10 reaches end of life on 9 December 2026. After that date the 2.x
series receives no further security coverage, because Drupal 10.6 is the
final Drupal 10 minor release.

## Prerequisites

- **Kamihaya CMS 2.x.** Updating directly from 1.x is technically possible —
  see "Updating directly from 1.x" below — but it is not the path this
  release was verified against.
- **Drupal core 10.3.0 or later.** Drupal 11 removed every update path older
  than that. In practice, updating from 10.6 is the tested route.
- **PHP 8.3 or later.** Drupal 11 requires it. Check with `php -v` and also
  with `drush status --field=php-version`, since the CLI and the web server
  do not always use the same binary.
- Composer 2.x.
- Drush 13.
- **A full database and files backup.** This update runs more than forty
  database updates. Several are not reversible without a backup.

## 1 — Allow the symfony/runtime Composer plugin

Drupal 11 uses `symfony/runtime`, which ships a Composer plugin. Composer
blocks plugins that are not explicitly allowed, and aborts mid-install when
it meets one. The result is a `composer.lock` updated to Drupal 11 while
`vendor/` still holds Drupal 10 — a state that will not boot.

Add the entry to your site's own root `composer.json`:

```json
"config": {
    "allow-plugins": {
        "symfony/runtime": true,
        ...
    }
}
```

This must be done before the update, not after the failure.

## 2 — Raise the constraints in your root composer.json

Composer applies the intersection of your root constraints and the
distribution's, so a change inside the distribution has no effect if your root
is narrower. Two constraints in your root `composer.json` need attention before
you run anything.

### 2a — The distribution constraint (required)

This is the step that actually pulls in 3.0. Without it, `composer update`
reports that there is nothing to do, no matter how many times you run it.

Check what your root currently declares, then raise the line that requires the
distribution to the 3.x series:

```
grep -n "kamihaya_cms" composer.json
```

```json
"genero/kamihaya_cms": "^3.0"
```

A `^2` constraint will never resolve to 3.0.0 — a major version bump is outside
the range by definition — so it must be raised explicitly. If your root pins an
exact version or a development branch, replace it with `"^3.0"`. If your root
declares a `repositories` entry for the distribution, that entry does not
change; only the version constraint does.

### 2b — The core constraint

The distribution requires `drupal/core` at `^11.4`, but Composer applies the
intersection with your root, so the distribution's constraint alone cannot
raise core if your root still says `^10`.

```json
"drupal/core-recommended": "^11",
"drupal/core-composer-scaffold": "^11",
```

`core-recommended` stays in your root. The distribution deliberately does not
require it, so that your root keeps control of core and its vendor tree.

## 3 — Review your core patches

If your site applies patches to `drupal/core`, check each one against Drupal
11 before starting. Patches written for Drupal 10 fail for two different
reasons, and both abort the whole install when
`composer-exit-on-patch-failure` is enabled:

- the surrounding code moved, so the patch no longer applies; or
- **the fix was committed to Drupal core**, so there is nothing left to add
  and the patch reports a context mismatch.

The second case is easy to misread as a broken patch. Read the issue and
compare against the Drupal 11 source before rerolling anything — the patch
may simply be unnecessary now and can be dropped rather than rerolled. After a
major core upgrade, expect at least one long-standing core patch to fall into
this category.

## 4 — Check Drush and its dependencies

Drush's own dependency tree can hold Symfony at 6.x. Drupal 11 needs Symfony
7.4, and Composer cannot move a package that is not named in a partial
update, so the resolution fails with messages of the form:

```
symfony/console ... not loaded, conflicts with another require
```

The packages responsible are usually `consolidation/robo`,
`consolidation/config` and `chi-teck/drupal-code-generator`. Their own
constraints already permit Symfony 7; they simply need to be in the update
set. Include `drush/drush` and those three in the update command in step 6.

If your root `require-dev` holds other tools that depend on Symfony — static
analysis, testing, code generation — include them as well. Use
`composer why symfony/console` to find them.

## 5 — Uninstall the tour module if it is enabled

`tour` was removed from Drupal core in Drupal 11 and moved to a contributed
project. If your site has it enabled, uninstall it first:

```
drush pm:list --status=enabled --format=list | grep -x tour
drush pmu tour
```

If it was enabled at some point and later removed, a leftover entry in
`system.schema` remains. That entry is not harmless: **installing any module
afterwards fails** with

```
The module tour does not exist.
```

because Drupal resolves the full module list during installation. Remove the
entry:

```
drush php:eval "\Drupal::keyValue('system.schema')->delete('tour');"
drush cr
```

Also check whether a `tour` update is queued. If one is, its code no longer
exists and `updb` will fail:

```
drush updatedb:status | grep -i tour
```

## 6 — Update with Composer

### Do not use `--with-all-dependencies`

Naming the distribution together with any dependency flag opens every package
it requires. On this dependency graph that pulls in dozens of contributed
modules and can drag unrelated vendor libraries across major versions. Name
packages explicitly instead, with no flag.

### Build the target list from the requirements

Read the requirements rather than iterating on error messages. Three commands
are needed:

```
composer why-not genero/kamihaya_cms 3.0.0
composer why-not drupal/core-recommended <latest 11.4.x>
composer why-not drupal/core <latest 11.4.x>
```

- The first lists the locked packages whose versions do not satisfy 3.0's
  constraints. It finds what the other two miss: a package whose locked
  release still supports Drupal 11, so it does not block core, but falls
  outside the range 3.0 requires. On a site coming from 2.0, `drupal/ai` is in
  this position: 2.0 allowed any 1.x release, and 3.0 requires the 1.5 series.
- The second lists the vendor packages `core-recommended` pins, with the version
  each one needs.
- The third lists the contributed modules whose locked versions do not yet
  support Drupal 11, **and** the vendor packages that core pins directly rather
  than through `core-recommended`. `twig/twig` is one of these. Reading only the
  second misses them.

Name every package these commands list, together with what the target release
of each requires. To see what a release requires:

```
composer show <package> <version> --available
```

A required package that is already in your lock at a version the new release
does not accept must be named, or Composer holds it where it is and the update
fails. A required package that is not yet in your lock is installed without
being named; naming it does no harm.

### Pin the vendor versions explicitly

Give each vendor package the version that `why-not` reported, in the form
`vendor/package:~7.4.13`. Without this, Composer may select a newer major
release that Drupal 11.4 does not support: several Symfony components have an
8.x series, while `core-recommended` pins them to `~7.4.x`.

### The command

Name `genero/kamihaya_cms` as well, so the 3.0 distribution — and the
constraints it ships — is pulled in alongside core. Updating core without it
leaves the distribution on 2.x, whose `^10.6` core constraint conflicts with
Drupal 11 and fails to resolve.

```
composer update \
  genero/kamihaya_cms \
  drupal/core drupal/core-recommended \
  drupal/core-composer-scaffold drupal/core-project-message \
  <each package the three why-not commands listed> \
  <each package those releases require that is already in your lock> \
  drush/drush consolidation/robo consolidation/config \
  chi-teck/drupal-code-generator \
  "<each vendor package with its required version>" \
  --dry-run
```

The Drush packages are the ones described in Step 4. The rest come from the
`why-not` output for your site, so the list differs from site to site.

Review the dry-run before executing. Two checks matter most:

- **All four `drupal/core*` packages must land on the same version.** If they
  split — core on one release and `core-project-message` on another — the
  target list is incomplete. Composer resolves successfully with an older
  core rather than move a package you did not name, so a clean dry-run is not
  proof that you reached the version you intended.
- **No Symfony package should land on 8.x.**

If the dry-run fails to resolve, read the error and add only the packages it
names. If the error blames "another require" without naming it, use
`composer why <package>` to find which locked package is holding the
constraint down.

### Watch for development branches and downgrades

A dry-run can succeed and still put a package somewhere you did not intend. When
a package's newest release needs a dependency that is locked below what it
requires, and that dependency is not named, Composer does not fail: it chooses
an older release, or a development branch, that the locked dependency still
satisfies.

Read the dry-run for:

- any package resolving to a `dev-` branch or an `x-dev` version, and
- any package moving to a **lower** version than it holds now.

When you see either, name that package with an explicit version, name what that
version requires as well, and run the dry-run again.

Two packages to check in this release:

- **`drupal/commerce`.** Commerce 3.3.10 and later require `commerceguys/intl`
  `^2.1.0`. If your lock holds `commerceguys/intl` below that and you do not
  name it, Commerce resolves to the 3.0.x development branch rather than
  failing. Name both, for example
  `"drupal/commerce:^3.3.10" "commerceguys/intl:^2.1"`.
- **`drupal/ai`.** The distribution requires `^1.5`, so `drupal/ai` cannot fall
  back to the 1.4 series. If the 1.5 series cannot be resolved — because
  `drupal/ai` or a package it requires is held at its locked version — the
  dry-run fails instead of downgrading. Read the error, name the package it
  reports together with what that release requires, and run the dry-run again.
  `composer show drupal/ai <version> --available` lists what a release
  requires.

Then run the command without `--dry-run`.

## 7 — Rebuild and run database updates

`drush cr` will fail before the database updates run, with an error such as:

```
SQLSTATE[42S22]: Unknown column 'alias' in 'INSERT INTO': INSERT INTO "router" ...
```

This is expected. The code is Drupal 11 while the database is still on the
Drupal 10 schema, and `system_update_11201` is what adds that column. Do not
try to fix it; run the updates.

```
drush sqlq "TRUNCATE cache_container; TRUNCATE cache_bootstrap; TRUNCATE cache_discovery; TRUNCATE cache_config;"
drush updatedb:status
drush updb -y
drush cr
```

Review `drush updatedb:status` before running `updb`. A 10.6 to 11.4 update
queues around forty-four updates, spanning `system`, `views`, `locale`,
`help`, `block`, `block_content`, `ckeditor5`, `content_moderation`, `field`,
`file`, `media`, `node`, `path_alias` and `update`.

`drush cr` succeeding afterwards is the confirmation that the schema has
caught up with the code.

If `updb` fails partway, do not re-run it. A partially applied update path
needs a decision, not a retry — restore the backup and investigate.

## What changed in 3.0

### Core

| | 2.x | 3.0 |
| --- | --- | --- |
| Constraint | `^10.6` | `^11.4` |
| Resolved | 10.6.14 | 11.4.10 or later in the 11.4 series |

### Administration theme

`gin` moves from the 3.x release candidates to 5.0, and `gin_toolbar` from
1.x to 3.0. Both declare `^11.2` and cannot be installed on Drupal 10, which
is why they move together with core rather than in a separate step.

### Front-end theme

`bootstrap5` moves from 3.0.x to 4.0.x. The 3.0.x branch is deprecated
upstream. The `cloud`, `bootstrap_cloud` and `rigel` stack moves with it,
because `bootstrap_cloud` constrains `bootstrap5`.

### AI capability

`drupal/ai` (AI Core) is constrained at `^1.5` in this release. 2.0 allowed any
1.x release, so a site updating from 2.0 must update `drupal/ai` to the 1.5
series as part of the upgrade, unless its lock already holds a 1.5 release. This
applies even if the module is not enabled. The 1.2 and 1.4 releases still
support Drupal 11, so they do not block core; the first `why-not` in Step 6 is
what lists them.

AI Core 1.5 requires six further modules, which are therefore shipped as well:

- `ai_content_suggestions`
- `ai_logging`
- `ai_translate`
- `ai_validations`
- `field_widget_actions`
- `field_validation`

The OpenAI provider (`ai_provider_openai`) and `metatag_ai` are also shipped.
**None of these modules is enabled by the install profile**, so nothing changes
at runtime unless you enable them yourself.

To use the AI features, enable `key`, `ai`, `ai_provider_openai` and
`metatag_ai` in that order, then configure a provider and an API key. `key` is
not declared as a module dependency by either the provider or `metatag_ai`, so
enable it explicitly. Enabling a provider without configured credentials raises
an exception, which is why none of these is enabled by default.

`metatag_ai` declares `core_version_requirement: ^11`, so it becomes usable for
the first time in this release.

### Other contributed modules

`fontawesome` moves from 2.26 to 3.0. Its field type, widget and formatter
plugin ids are unchanged, and the settings it stores survive the update. The
CKEditor 4 plugin it used to carry was removed upstream; this distribution
does not use it.

A number of further contributed modules move to newer releases that their
existing constraints already permitted.

### Unstable releases in this series

Several packages have no stable release that supports Drupal 11. They are
constrained with `@dev` or `@beta`, which permits an unstable release rather
than requiring one: as soon as a stable release supporting Drupal 11 is
published, it will be preferred automatically, with no change to the
distribution. Five are new to this release; `youtube` has carried a beta
constraint since 2.0 and is listed here for completeness.

| Package | State |
| --- | --- |
| `field_group_table` | development branch |
| `webform_mautic` | development branch |
| `cloud` | beta |
| `bootstrap_cloud` | beta |
| `rigel` | beta |
| `youtube` | beta (unchanged since 2.0) |

Two consequences are worth knowing. Development and beta releases are not
covered by drupal.org security advisories. And a development release is a
moving branch, so the exact code your site receives depends on when you
install.

## 8 — Verify

- `drush status` reports Drupal 11.4.x and a successful bootstrap.
- `drush core:requirements --severity=2` reports no core update or security
  coverage errors.
- The front page and a page of each content type render, and the page
  container structure is unchanged. If your theme extends `bootstrap5`,
  compare the rendered markup against a capture taken before the update.
- Content editing: open a node edit form, confirm CKEditor 5 loads.
- Any field groups on your form and view displays still show their groups.
- The administration interface loads with Gin.
- Forms, sitemap and Commerce pages behave as before.
- `drush watchdog:show` reports no new errors.
- **Log in and load a page immediately after clearing caches.** Several
  Drupal 11 faults appear only for authenticated users, and only on the first
  request after a cache clear. The symptom is a page with no CSS at all: view
  the source and check that `<head>` contains stylesheet links rather than
  `<css-placeholder>` and `<js-placeholder>`. See "If pages render without
  CSS" below.

## If pages render without CSS

A page whose `<head>` still contains `<css-placeholder>`, `<js-placeholder>`
and `<head-placeholder>` has lost its asset metadata during rendering. No
stylesheet is loaded and the page appears unstyled. Nothing is logged.

It affects **authenticated users only**, and only the **first request after a
cache clear** — a second request is served from cache and looks correct, so
it reads as an intermittent glitch.

The cause is a nested render started while another render is in progress.
Drupal 11 wraps controller execution in a Fiber, and a nested render now
strands a frame on the render context so that the assets collected below it
never reach the response. The usual trigger is `Url::toString()` called with
no arguments inside a `#pre_render` callback, `hook_page_attachments()` or
`hook_toolbar()`.

If your site has custom modules, check them for this pattern.
`Url::toString(TRUE)` returns a `GeneratedUrl` without rendering;
`renderInIsolation()` is the right call where a nested render is genuinely
needed.

To confirm the cause, set `zend.assertions=1` on a copy of the site. The
failure then raises `AssertionError: Bubbling failed.` instead of failing
silently, and the stack trace identifies the code responsible.

If your site or its custom modules contain this pattern, expect to fix it the
same way. A contributed module may also carry it, in which case updating that
module is the fix.

## Updating directly from 1.x

Drupal core permits it: a site on core 10.4 can reach 11.4 in one step,
because the update path requires only 10.3 or later and 11.4 accepts sites
coming from 10.4.

The distribution does not prevent it either. But going 1.x to 3.0 in a single
Composer operation applies every change from both releases at once —
Commerce 2 to 3, the CKEditor 4 removal, the module removals, two theme major
upgrades and the core major upgrade. If anything goes wrong there is no way
to tell which change caused it.

Updating to 2.0 first, verifying, and then updating to 3.0 is the recommended
route. `UPDATE_2.0.md` covers the first half.

## Known deprecation warnings

Drupal 11.3 deprecated `theme_get_setting()` in favour of
`ThemeSettingsProvider::getSetting()`. Themes that call it continue to work —
the function is not removed until Drupal 13 — but static analysis will report
it. If your site has a custom theme that reads theme settings, expect to see
these warnings and plan the replacement.

## After updating

Run `composer audit` once the update is complete. A major core upgrade moves
the packages that block it and leaves everything else where the lock had it,
so a site can finish the upgrade with contributed modules several releases
behind and carrying published advisories.

Most are reachable without changing any constraint — the constraint already
permits the fixed release and only the lock is behind.

## Applying a core security release between distribution releases

Drupal publishes core security fixes as patch releases of the current minor.
This distribution requires `drupal/core` with a range constraint (`^11.4`)
rather than pinning `core-recommended`, so your site can take such a release as
soon as it appears. **No new distribution release is needed, and none will be
issued for a core-only security fix** — the responsibility and the control sit
with your root `composer.json`.

### The constraint does not change

`^11.4` already permits every 11.4.x patch, so a fix published as a later
11.4.x release is within range. A constraint only changes to move to a new
minor, never for a patch within the minor you are on.

### Build the target list

Core and its vendor libraries are fixed in `composer.lock`, and a partial
update does not move packages you do not name. Read **both** `why-not` forms to
derive the list:

```
composer why-not drupal/core-recommended <latest 11.4.x>
composer why-not drupal/core <latest 11.4.x>
```

`core-recommended` pins core's vendor tree, so the first lists the vendor
packages that must move, with the version each needs. Core also pins some
vendor packages directly — `twig/twig` is one — and those appear only in the
second. Give each vendor package the version `why-not` reported, in the form
`vendor/package:~7.4.13`.

### The command

Name the four core packages together, with any vendor packages `why-not`
listed, and **no** dependency flag:

```
composer update \
  drupal/core drupal/core-recommended \
  drupal/core-composer-scaffold drupal/core-project-message \
  "<each vendor package with its required version>" \
  --dry-run
```

Review the dry-run, then run it again without `--dry-run`. Two checks:

- **All four `drupal/core*` packages must land on the same version.** If they
  split — one on the new release, another left behind — the vendor list is
  incomplete. Composer resolves successfully with an older core rather than
  move a package you did not name, so a clean dry-run does not prove the
  intended version was reached.
- **Do not add `-W` / `--with-all-dependencies`.** It opens every package in
  the dependency graph and can drag unrelated libraries across major versions.
  Name only what must move.

### Afterwards

```
drush updatedb:status
drush updb -y
drush cr
drush status --field=drupal-version
```

A patch-level security release usually queues few or no database updates, but
run `updatedb:status` and `updb` regardless. Confirm the resolved core version
with `drush status`.

### Knowing an update is needed

- `composer audit` reports published advisories affecting your locked packages.
  Run it periodically, not only after an upgrade.
- `drush core:requirements --severity=2` reports when core security coverage is
  missing or an update is due.
- Core security releases are announced at drupal.org/security; the Drupal
  security team publishes them on a published schedule.

## Notes

- Blazy remains on 3.0.x. It will move to a 4.x stable release when one is
  available.
- After updating, `drush core:requirements` may report "Module and theme
  update status: Not secure!" if contributed modules have newer releases
  available. The distribution pins the versions it has verified. If you
  update contributed modules independently, avoid `-W` for the reasons given
  in step 6.
- See `UPGRADE_NOTES.md` for ongoing maintenance guidance.
