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

## 2 — Raise the core constraint in your root composer.json

Composer applies the intersection of your root constraints and the
distribution's, so the distribution's `^11.4` alone cannot raise core if your
root still says `^10`.

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
may simply be unnecessary now.

This distribution removed one of its own core patches for exactly that
reason in this release.

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
`system.schema` produces a warning during `drush updatedb:status`. The
warning is harmless as long as no `tour` update is queued; check with:

```
drush updatedb:status | grep -i tour
```

If a `tour` update *is* queued, its code no longer exists and the update will
fail. Resolve that before continuing.

## 6 — Update with Composer

### Do not use `--with-all-dependencies`

Naming the distribution together with any dependency flag opens every package
it requires. On this dependency graph that pulls in dozens of contributed
modules and can drag unrelated vendor libraries across major versions. Name
packages explicitly instead, with no flag.

### Build the target list from the requirements

Two commands are needed, and both matter:

```
composer why-not drupal/core-recommended 11.4.6
composer why-not drupal/core 11.4.6
```

`core-recommended` pins core's vendor tree to exact versions, so the first
command lists every vendor package that must move, with the version each one
needs. The second lists the contributed modules whose locked versions do not
yet support Drupal 11, **and** the vendor packages that core pins directly
rather than through `core-recommended` — `twig/twig` is one of these and is
easy to miss.

### Pin the vendor versions explicitly

Give each vendor package the version that `why-not` reported, in the form
`vendor/package:~7.4.13`. Without this, Composer may select a newer major
release that Drupal 11.4 does not support: several Symfony components have an
8.x series, while `core-recommended` pins them to `~7.4.x`.

### The command

```
composer update \
  drupal/core drupal/core-recommended \
  drupal/core-composer-scaffold drupal/core-project-message \
  drupal/gin drupal/gin_toolbar \
  <every contributed module why-not listed> \
  drush/drush consolidation/robo consolidation/config \
  chi-teck/drupal-code-generator \
  "twig/twig:~3.28.0" \
  "<every vendor package with its required version>" \
  --dry-run
```

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
| Resolved | 10.6.14 | 11.4.6 |

### Administration theme

`gin` moves from the 3.x release candidates to 5.0, and `gin_toolbar` from
1.x to 3.0. Both declare `^11.2` and cannot be installed on Drupal 10, which
is why they move together with core rather than in a separate step.

### Front-end theme

`bootstrap5` moves from 3.0.x to 4.0.x. The 3.0.x branch is deprecated
upstream. The `cloud`, `bootstrap_cloud` and `rigel` stack moves with it,
because `bootstrap_cloud` constrains `bootstrap5`.

If your site has its own sub-theme of `bootstrap5`, check it against 4.0
before updating. If it uses `stylesheets-remove` in its `.info.yml`, replace
that with `libraries-override`.

### AI capability

`drupal/ai` is raised to 1.4 and the OpenAI provider (`ai_provider_openai`)
and `metatag_ai` are added. None of them is enabled by the install profile.

To use them, enable `key`, `ai`, `ai_provider_openai` and `metatag_ai` in
that order, then configure a provider and an API key. `key` is not declared
as a module dependency by either the provider or `metatag_ai`, so enable it
explicitly. Enabling a provider without configured credentials raises an
exception, which is why none of these is enabled by default.

`metatag_ai` declares `^11` only, so it becomes usable for the first time in
this release.

### Other contributed modules

`fontawesome` moves from 2.26 to 3.0. Its field type, widget and formatter
plugin ids are unchanged, and the settings it stores survive the update. The
CKEditor 4 plugin it used to carry was removed upstream; this distribution
does not use it.

Sixteen further contributed modules move to newer releases that their
existing constraints already permitted.

### Unstable releases in this series

Five packages have no stable release that supports Drupal 11. They are
constrained with `@dev` or `@beta`, which permits an unstable release rather
than requiring one: as soon as a stable release supporting Drupal 11 is
published, it will be preferred automatically, with no change to the
distribution.

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

## Notes

- Blazy remains on 3.0.x. It will move to a 4.x stable release when one is
  available.
- After updating, `drush core:requirements` may report "Module and theme
  update status: Not secure!" if contributed modules have newer releases
  available. The distribution pins the versions it has verified. If you
  update contributed modules independently, avoid `-W` for the reasons given
  in step 6.
- See `UPGRADE_NOTES.md` for ongoing maintenance guidance.
