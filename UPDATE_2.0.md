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

Drupal 11 support is delivered in Kamihaya CMS 3.0. Because Drupal 10 reaches
end of life on 9 December 2026, plan the update to 3.0 accordingly; 2.0 is a
bridge release, not a destination.

## Prerequisites

- **Drupal core 10.3.0 or later.** Sites on 10.2 or earlier must first update
  to 10.3+ before applying this release.
- **PHP 8.1 or later.** Drupal core 10.6 declares `php: >=8.1.0`. PHP 8.3 or
  later is recommended, because Kamihaya CMS 3.0 (Drupal 11) will require it.
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
| youtube | 2.x | 3.0.0-beta1 | Beta, pending a stable Drupal 11 release. |

### Modules removed

| Module | Reason | Action required |
| --- | --- | --- |
| ckeditor, colorbutton, panelbutton | CKEditor 4 is not available on Drupal 11. All text formats already use CKEditor 5. | Uninstall before updating. See Step 2. |
| open_ai_metadata | No Drupal 11 compatible release. Never enabled by the install profile and not used by any Kamihaya CMS feature. | Uninstall only if you enabled it manually. See Step 2. |
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
move. During development of this release, that behaviour was measured: `-W`
moved 23 contributed modules that core did not require, and pulled unrelated
vendor libraries across major version boundaries, including
`phpoffice/phpspreadsheet` 2.4 to 5.9, `dompdf/dompdf` 2.0 to 3.1 and
`sabberworm/php-css-parser` 8.9 to 9.4. Major vendor jumps of this kind can
break custom code and are very difficult to attribute afterwards.

If you use `-W`, always inspect `--dry-run` output first and look specifically
for major version changes in non-Drupal packages.

### Recommended: staged update

The distribution was verified by applying these changes one at a time, checking
the site after each one. Following the same order is the lowest-risk path,
and it means that if something breaks you know which change caused it.

Take a `composer.lock` snapshot before each stage so that you can roll back:

```
cp composer.lock composer.lock.bak
```

Apply the stages in this order. In each command, naming the distribution
together with the target module is required: the version constraints live in the
distribution's `composer.json`, so Composer cannot move a module without also
re-resolving the distribution.

```
composer update genero/kamihaya_cms drupal/xmlsitemap
drush cr && drush updb

composer update genero/kamihaya_cms drupal/youtube
drush cr

composer update genero/kamihaya_cms drupal/webform
drush cr && drush updb

composer update genero/kamihaya_cms drupal/commerce
drush cr && drush updb

composer update genero/kamihaya_cms
drush cr && drush updb
```

The youtube stage has no `drush updb`: the module defines no update hooks at
all. xmlsitemap, webform and commerce all do, and commerce's include the order
balance backfill described below.

Use `drush updatedb:status` before each `drush updb` if you want to see what is
pending. `drush updb --dry-run` does not exist in Drush 13.

The final command applies the module removals. See the notes in Step 7 for the
container cache handling that webform and Commerce require.

Then update core, as described below.

### Updating core

Core cannot be moved by naming the distribution alone, because core and its
vendor libraries are pinned in your `composer.lock` and a partial update will
not touch packages you have not named. Naming them all explicitly, with **no
dependency flag**, moves exactly what is needed and nothing else.

To build the list of packages to name, read the requirements directly rather
than guessing:

```
composer why-not drupal/core-recommended 10.6.14
```

`core-recommended` pins core's whole vendor tree to exact versions, so this
single command lists every vendor package that must move, together with the
version required. Name all of them, plus the four core packages, with no flag:

```
composer update \
  drupal/core drupal/core-recommended \
  drupal/core-composer-scaffold drupal/core-project-message \
  <every package listed by why-not>
```

For reference, updating from core 10.4.10 to 10.6.14 during development required
these 21 vendor packages to be named alongside the core packages:

```
asm89/stack-cors  guzzlehttp/guzzle  guzzlehttp/promises  guzzlehttp/psr7
masterminds/html5  mck89/peast  pear/archive_tar  twig/twig
symfony/deprecation-contracts  symfony/event-dispatcher-contracts
symfony/http-foundation  symfony/routing  symfony/service-contracts
symfony/translation-contracts  symfony/polyfill-ctype
symfony/polyfill-iconv  symfony/polyfill-intl-grapheme
symfony/polyfill-intl-idn  symfony/polyfill-intl-normalizer
symfony/polyfill-mbstring  symfony/polyfill-php83
```

Your list will differ, because it depends on the versions currently in your
`composer.lock`. Use `why-not` against the core version you are targeting.

**Check the dry-run before executing.** All four `drupal/core*` packages must
land on the **same** version. If they split — for example core at 10.6.8 while
`core-composer-scaffold` is at 10.6.14 — your target list is incomplete.
Composer will resolve successfully with an older core rather than move a package
you did not name, so a successful dry-run is not by itself proof that you
reached the version you wanted.

### Alternative: single update

If you accept the risk described above, the whole release can be applied in one
operation:

```
cp composer.lock composer.lock.bak
composer update genero/kamihaya_cms --with-all-dependencies --dry-run
```

Review the output carefully. Look for:

- any non-Drupal package crossing a major version boundary
- contributed modules moving that you did not expect

Only then run the command without `--dry-run`.

This path was **not** used to verify the release. The staged path was.

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

Run this after every stage of the update, not only when a failure occurs. It is
inexpensive and it prevents a failure mode that is confusing to diagnose.

## Step 6 — Run database updates

```
drush updb
drush cr
```

Review the update hooks that run. Updating core from 10.4.10 to 10.6.14 runs
`system_update_10600` only; a site starting from a different 10.x release may
see more. The Commerce update hooks write to order data — see the Commerce notes
below.

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
keep working. This is a beta release, adopted because a stable Drupal 11
compatible release is not yet available.

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

## Deferred to Kamihaya CMS 3.0

The following are intentionally not part of 2.0:

- **Drupal 11.** Core stays in the 10.6 series in this release.
- **bootstrap5 3.x to 4.x**, together with the decision on the `cloud`,
  `bootstrap_cloud` and `rigel` modules, which currently constrain bootstrap5.
- **Metatag AI.** `open_ai_metadata` is removed in 2.0 without a replacement,
  because it was never enabled. Metatag AI 2.x, together with an AI provider
  module, is planned for 3.0.
- **Font Awesome consolidation.** The distribution currently loads Font Awesome
  through more than one path. This has no functional impact and is deferred.

## Notes

- `drupal/ai` (AI Core) is retained deliberately, so that AI capabilities remain
  available to sites built on this distribution. It is not enabled by the
  install profile. To use it, install an AI provider module and enable both.
- Blazy is held at 3.0.x, which declares Drupal 11 compatibility. It will move
  to a 4.x stable release when one is available.
- After updating, `drush core:requirements` may report
  "Module and theme update status: Not secure!" if contributed modules have newer
  releases available. The distribution pins the versions it has verified. If you
  update contributed modules independently, avoid `-W` for the reasons given in
  Step 4.
- See `UPGRADE_NOTES.md` for ongoing maintenance guidance.
