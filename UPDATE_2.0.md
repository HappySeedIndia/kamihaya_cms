# Kamihaya CMS 2.0 Update Guide

This guide describes how to update a site running Kamihaya CMS 1.x to
Kamihaya CMS 2.0. It covers one-time migration steps that are intentionally
**not** automated inside the distribution (for example, uninstalling modules
that this release removes), so that the distribution code stays clean and does
not reference removed modules.

> Read this guide fully before starting. Perform the steps in order on a
> staging copy first, then on production.

## What this release does

Kamihaya CMS 2.0 moves the distribution to **Drupal core 10.6**, updates
several contributed modules across major versions, and removes modules that are
no longer used.

Drupal 10.4 and 10.5 have reached end of life and no longer receive security
fixes. Drupal 10.6 is the final Drupal 10 minor release and is supported until
December 2026.

This release also fixes a defect that prevented the distribution from pinning
your site to a single core release. See "Core version constraints" below.

## What this release does not do

**2.0 does not move your site to Drupal 11.** The core constraint in this
release is `^10.6`, which resolves within the Drupal 10.6 series only.

Note that Drupal 10 reaches end of life on 9 December 2026, and 10.6 is its
final minor release. Plan accordingly.

## Prerequisites

- **Drupal core 10.3.0 or later.** Sites on 10.2 or earlier must first update
  to 10.3+ before applying this release.
- **PHP 8.1 or later.** Drupal core 10.6 declares `php: >=8.1.0`. PHP 8.3 or
  later is recommended.
- Composer 2.x.
- Drush 13. The commands in this guide were verified against Drush 13.3.3.
  Note that `drush updb --dry-run` does not exist in Drush 13; use
  `drush updatedb:status` to inspect pending updates.
- **A full database and files backup.** Several steps run update hooks that
  modify data and are not reversible without a backup.

## Summary of changes in 2.0

### Core

| Package | 1.x | 2.0 |
| --- | --- | --- |
| Constraint | `drupal/core-recommended` pinned to an exact version | `drupal/core: ^10.6` |
| Resolved version | 10.4.10 | 10.6.14 or later in the 10.6 series |

### Contributed modules updated

| Module | From | To | Notes |
| --- | --- | --- | --- |
| commerce | 2.x | 3.x | Major upgrade. Modifies order data. |
| webform | 6.2 | 6.3 | Service classes removed. |
| xmlsitemap | 8.x-1.x | 2.0 | Major upgrade. |
| youtube | 2.x | 3.0.0-beta1 | Beta; no stable release is available yet. |

### Modules removed

| Module | Reason | Action required |
| --- | --- | --- |
| ckeditor, colorbutton, panelbutton | CKEditor 4 reached end of life upstream and is unused: all text formats already use CKEditor 5. | Uninstall before updating. See Step 2. |
| open_ai_metadata | Not maintained for current Drupal versions. Never enabled by the install profile and not used by any Kamihaya CMS feature. | Uninstall only if you enabled it manually. See Step 2. |
| openai | No stable release has ever been published. Never enabled by the install profile. Superseded by the AI Core (`drupal/ai`) ecosystem, which is retained. | Uninstall only if you enabled it manually. See Step 2. |

The CKEditor 4 plugin JavaScript libraries under `web/libraries/` are removed by
Composer. They are libraries, not modules, so they require no uninstall step.

### Other fixes

- Two files in `kamihaya_cms_contentserv_api` had filenames that did not match
  the class names they declared. PSR-4 autoloading resolves a class name
  directly to a filename, so these classes could not be loaded on a
  case-sensitive filesystem, which is the normal case on Linux servers. This
  module is not in the install profile's module list, so it only affects sites
  that enabled it. If yours did, and you saw a class-not-found error from the
  `contentserv.api` service, this release fixes it.

## Core version constraints

In 1.x, the distribution required `drupal/core-recommended` at an **exact**
version. `core-recommended` is a metapackage intended for root projects: it
pins Drupal core and its entire vendor tree (Symfony, Twig, Guzzle and others)
to exact versions. Requiring it from a distribution meant every site running
Kamihaya CMS was locked to one specific core release and could not take even a
core patch release without waiting for a new distribution release.

From 2.0, the distribution requires `drupal/core` with a range constraint
instead. Managing `core-recommended` and the Composer scaffold remains the
responsibility of your site's own root `composer.json`, which is where they
belong. **You can now apply core security releases within the 10.6 series
yourself, without waiting for a Kamihaya CMS release.**

## Step 1 — Back up

```
drush sql:dump --result-file=../backup-before-2.0.sql --gzip
```

Also back up the files directory. Do not proceed without a restorable backup:
Step 6 writes to order data.

## Step 2 — Uninstall removed modules (before updating code)

The modules removed in this release must be uninstalled **before** Composer
deletes their code. Otherwise they remain enabled in `core.extension` while
their code is gone, and the site fails to boot.

### CKEditor 4 (required)

```
drush pmu ckeditor colorbutton panelbutton
drush cr
```

`colorbutton` depends on `panelbutton`, which depends on `ckeditor`.
Uninstalling `colorbutton` allows the others to be removed.

### Unused AI modules (only if you enabled them)

These modules were shipped as dependencies of the install profile but were never
enabled by it and are not used by any Kamihaya CMS feature. Check whether your
site enabled either of them manually:

```
drush pm:list --status=enabled --format=list | grep -E "open_ai_metadata|^openai"
```

If either is listed, uninstall it before updating:

```
drush pmu open_ai_metadata openai
drush cr
```

If the command returns nothing, no action is needed.

> These uninstall steps are intentionally manual rather than update hooks. The
> distribution does not carry code that references removed modules.

## Step 3 — Update your root composer.json

Two constraints in your site's own root `composer.json` need attention before
you run anything. **Composer applies the intersection of the root and
distribution constraints, so a change inside the distribution has no effect if
your root is narrower.**

### 3a — The distribution constraint (required)

This is the step that actually pulls in 2.0. Without it, `composer update` will
report that there is nothing to do, no matter how many times you run it.

Find the line that requires the distribution and raise it to the 2.x series:

```json
"genero/kamihaya_cms": "^2.0"
```

Check what your root currently declares before editing:

```
composer show --self 2>/dev/null | head -5
grep -n "kamihaya_cms" composer.json
```

Three cases:

- **A range in the 1.x series**, such as `"^1.0"` or `"^1.65"` — replace it with
  `"^2.0"`. A `^1` constraint will never resolve to 2.0.0, because a major
  version bump is outside the range by definition.
- **An exact version**, such as `"1.65.0"` — replace it with `"^2.0"`. Consider
  keeping the range form afterwards, so that you can take 2.0.x patch releases
  without editing `composer.json` again.
- **A development branch reference**, such as `"dev-master"` or `"1.x-dev"` —
  switch to `"^2.0"`. Development references track a moving branch and do not
  correspond to a release; they should not be used on production sites.

If your root declares a `repositories` entry for the distribution rather than
resolving it from a Composer endpoint, the entry itself does not change. Only
the version constraint does.

### 3b — The core constraint

If your root project is based on `drupal/recommended-project` and declares:

```json
"drupal/core-recommended": "^10",
"drupal/core-composer-scaffold": "^10",
```

then no change is required: `^10` already allows 10.6.

If your root pins an exact core version, for example `"10.4.10"`, relax it to a
range:

```json
"drupal/core-recommended": "^10.6",
"drupal/core-composer-scaffold": "^10.6",
```

`core-recommended` stays in your root. From 2.0 the distribution no longer
requires it, precisely so that your root remains in control of core and its
vendor tree. Do not remove it from the root.

### 3c — Confirm before proceeding

```
composer validate --no-check-publish
```

Do not run `composer update` yet. Read Step 4 first: the form of the update
command matters more than anything else in this guide.

## Step 4 — Update with Composer

This is the step where most upgrade problems originate. Read this section before
running anything.

### Do not use `--with-all-dependencies`

Adding `-W` (or `-w`) while naming `genero/kamihaya_cms` opens **every package
the distribution requires** for update, not only the packages you intended to
move. It can update contributed modules that core did not require, and pull
unrelated vendor libraries across major version boundaries. Major vendor jumps
of this kind can break custom code and are very difficult to attribute
afterwards.

If you use `-W`, always inspect `--dry-run` output first and look specifically
for major version changes in non-Drupal packages.

### Why this is a single operation

Moving from a 1.x release to 2.0.0 cannot be staged module by module. Once your
root constraint on `genero/kamihaya_cms` is `^2.0` (Step 3a), Composer resolves
the distribution to 2.0.0, which requires `drupal/core` at `^10.6`. Your
`composer.lock` still holds core at the exact version 1.x pinned, and a partial
update does not move packages you have not named. A command that names the
distribution and one contributed module therefore fails, reporting the four
core packages as fixed to their current version by a partial update.

So core moves in the same operation as everything else, together with every
contributed module whose locked 1.x version does not satisfy 2.0.0's
constraints. Updating modules one at a time is only possible when a site tracks
a development branch of the distribution, where constraints change one at a
time. Between release tags it is not.

### Build the target list

Take a `composer.lock` snapshot first, so that you can roll back:

```
cp composer.lock composer.lock.bak
```

Read the requirements rather than iterating on error messages. Three commands
are needed:

```
composer why-not genero/kamihaya_cms 2.0.0
composer why-not drupal/core-recommended <latest 10.6.x>
composer why-not drupal/core <latest 10.6.x>
```

- The first lists the locked packages that block the distribution: core, and the
  contributed modules that must move.
- The second lists the vendor packages `core-recommended` pins, with the version
  each one needs.
- The third lists vendor packages that core pins directly rather than through
  `core-recommended`. Reading only the second misses them.

### The command

Name the distribution, the four core packages, the contributed modules that
must move, and each vendor package with the version `why-not` reported, in the
form `vendor/package:<version>`. Use **no** dependency flag:

```
composer update \
  genero/kamihaya_cms \
  drupal/core drupal/core-recommended \
  drupal/core-composer-scaffold drupal/core-project-message \
  drupal/commerce drupal/webform drupal/xmlsitemap drupal/youtube \
  "<each vendor package with its required version>" \
  --dry-run
```

The four contributed modules are the ones listed under "Contributed modules
updated" above. Add any other module the first `why-not` reports for your site.
The modules removed in this release do not need to be named: once the
distribution no longer requires them, Composer removes them in the same
operation. That is why Step 2 must come first.

**Check the dry-run before executing.**

- All four `drupal/core*` packages must land on the **same** version. If they
  split, the target list is incomplete. Composer resolves successfully with an
  older core rather than move a package you did not name, so a successful
  dry-run is not by itself proof that you reached the version you wanted.
- No package should resolve to a development branch, and none should move to a
  lower version than it holds now. Either means a requirement was not named:
  name that package with an explicit version, together with what that version
  requires, and run the dry-run again.
- The packages removed should be the ones listed under "Modules removed", and
  no others.

If the target core release does not appear as a candidate — for example, if
Composer reports it as unavailable, or as affected by a security advisory that
does not apply to it — the local package metadata may be stale. Clear the cache
and run the dry-run again:

```
composer clear-cache
```

Then run the command without `--dry-run`. Continue with Step 5 **before** running
`drush cr`. Step 6 runs the database updates for every module at once,
including the Commerce order balance backfill described in Step 7.

## Step 5 — Clear the compiled service container

Do this **before** `drush cr`, not after it fails.

When core or a module that provides services changes, the compiled service
container held in cache becomes stale. If a service class has been removed,
`drush cr` itself fails with `Class "..." not found`, because rebuilding the
cache requires booting with the old container. Clearing the container caches
directly avoids this:

```
drush sqlq "TRUNCATE cache_container; TRUNCATE cache_bootstrap; TRUNCATE cache_discovery; TRUNCATE cache_config;"
drush cr
```

Run this after the Composer update and before the first `drush cr`, not only
when a failure occurs. It is inexpensive and it prevents a failure mode that is
confusing to diagnose.

## Step 6 — Run database updates

```
drush updb
drush cr
```

Because Step 4 moved core and the contributed modules in one operation, this
single run applies all of their update hooks. Inspect them first with
`drush updatedb:status`. Updating core from 10.4.10 runs `system_update_10600`;
a site starting from a different 10.x release may see more. The Commerce update
hooks write to order data — see the Commerce notes below.

## Step 7 — Contributed module notes

### webform (6.2 to 6.3)

6.3 removed some service classes, including `WebformAddonsManager`. A stale
compiled container makes `drush cr` fail with `Class "..." not found`. Clear the
container caches as described in Step 5 before rebuilding.

Deprecated Webform sub-modules were moved to the `webform_deprecated` project.
This distribution uses only `webform` and `webform_ui`, so no action is needed
unless your site enabled deprecated sub-modules such as `webform_shortcuts`.

### xmlsitemap (8.x-1.x to 2.0)

A major update. Run database updates to apply configuration changes, then
regenerate the sitemap and confirm `/sitemap.xml` is served.

### youtube (2.x to 3.0.0-beta1)

Field type and formatter machine names are unchanged, so existing youtube fields
keep working. This is a beta release, adopted because no stable release is
available. It defines no update hooks, so no database update is needed for this
module.

### commerce (2.x to 3.x)

A major upgrade, and the only step that modifies order data.

- **Back up the database first.** In Commerce 3 the order balance became a base
  field, and update hooks (`commerce_order_update_82xx`) backfill the balance
  for all existing orders. This is a data write and is not reversible without a
  backup.
- Clear the container caches before running `drush updb`, as Commerce 3 removes
  deprecated service classes:
  ```
  drush sqlq "TRUNCATE cache_container; TRUNCATE cache_bootstrap; TRUNCATE cache_discovery; TRUNCATE cache_config;"
  drush cr
  drush updb
  ```
- No Commerce extension modules are used by this distribution, so the Composer
  upgrade resolves cleanly, with no `commerce_base` or `commerce_cart_api`
  blockers. If your site added Commerce extension modules of its own, check
  their Commerce 3 compatibility before starting.
- Behaviour change: the "Add payment" form is now a single page. To keep the
  previous two-step form, set the following in `settings.php`:
  ```php
  $settings['commerce_payment_use_legacy_add_payment_form'] = TRUE;
  ```
- The distribution's own Commerce code (product fields and the Contentserv
  product sync) uses only stable base Commerce APIs and required no changes for
  Commerce 3.

## Step 8 — Verify

- `drush status` reports Drupal 10.6.x and a successful bootstrap.
- `drush core:requirements --severity=2` no longer reports
  "Drupal core security coverage: Coverage has ended".
- Content editing: open a node edit form and confirm CKEditor 5 loads, including
  the Bootstrap grid (`ckeditor_bs_grid`) in Full HTML.
- Forms: confirm webforms render and submit.
- Sitemap: regenerate and confirm `/sitemap.xml` is served.
- Commerce: confirm order pages load and the order balance displays correctly.
- Menus, media, and any site-specific features.
- Check the log for errors: `drush watchdog:show`.

## Applying a core security release between distribution releases

Drupal publishes core security fixes as patch releases of the current minor.
From 2.0 this distribution requires `drupal/core` with a range constraint
(`^10.6`) rather than pinning `core-recommended`, so your site can take such a
release as soon as it appears. **No new distribution release is needed, and none
will be issued for a core-only security fix** — the responsibility and the
control sit with your root `composer.json`.

### The constraint does not change

`^10.6` already permits every 10.6.x patch, so a fix published as a later
10.6.x release is within range. A constraint only changes to move to a new
minor, never for a patch within the minor you are on.

### Build the target list

Core and its vendor libraries are fixed in `composer.lock`, and a partial
update does not move packages you do not name. Read **both** `why-not` forms to
derive the list:

```
composer why-not drupal/core-recommended <latest 10.6.x>
composer why-not drupal/core <latest 10.6.x>
```

`core-recommended` pins core's vendor tree, so the first lists the vendor
packages that must move, with the version each needs. Core also pins some
vendor packages directly — `twig/twig` is one — and those appear only in the
second. Give each vendor package the version `why-not` reported, in the form
`vendor/package:<version>`.

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

- `drupal/ai` (AI Core) is retained deliberately, so that AI capabilities remain
  available to sites built on this distribution. It is not enabled by the
  install profile. To use it, install an AI provider module and enable both.
- Blazy is held at 3.0.x. It will move to a 4.x stable release when one is
  available.
- After updating, `drush core:requirements` may report
  "Module and theme update status: Not secure!" if contributed modules have newer
  releases available. The distribution pins the versions it has verified. If you
  update contributed modules independently, avoid `-W` for the reasons given in
  Step 4.
- See `UPGRADE_NOTES.md` for ongoing maintenance guidance.
