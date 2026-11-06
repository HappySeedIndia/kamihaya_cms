# Kamihaya CMS 3.0 Update Guide

This guide describes how to update a site running Kamihaya CMS 2.x to
Kamihaya CMS 3.0, which moves the distribution to **Drupal 11**.

> Read this guide fully before starting. Perform the steps in order on a
> staging copy first, then on production.
>
> Steps 1 to 5 are preparation you must complete **before** running any
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
- **Stability settings in your root `composer.json`.** Several packages this
  release requires have no stable release yet (see "Unstable releases in this
  series" below). Composer reads stability flags such as `@dev` and `@beta`,
  and the `minimum-stability` setting, **from the root `composer.json` only**;
  the distribution's own settings have no effect on your site. Your root must
  either declare

  ```json
  "minimum-stability": "dev",
  "prefer-stable": true,
  ```

  or require each of those packages itself with a stability flag, for example
  `"drupal/field_group_table": "^1.1@dev"`. Without one of these, the update
  fails to resolve. `prefer-stable` is what makes Composer choose a stable
  release whenever one satisfies the constraint.
- **Patching enabled in your root `composer.json`.** The distribution ships
  patches for Drupal core and several contributed modules, applied by
  `cweagans/composer-patches` 1.x. That plugin applies patches declared by a
  dependency only when the root allows the plugin and enables patching:

  ```json
  "config": {
      "allow-plugins": {
          "cweagans/composer-patches": true
      }
  },
  "extra": {
      "enable-patching": true
  }
  ```

  Without `enable-patching`, the distribution's patches are skipped without an
  error and the site runs unpatched code.
- **Run commands from your project root.** Every `composer` and `drush` command
  in this guide is run from the directory that holds your root
  `composer.json`.
- **A full database and files backup.** This update runs a large number of
  database updates. Several are not reversible without a backup.

## What changed in 3.0

### Core

| | 2.x | 3.0 |
| --- | --- | --- |
| Constraint | `^10.6` | `^11.4` |
| Resolved | 10.6.x | 11.4.10 or later in the 11.4 series |

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

AI Core 1.5 requires five further modules, which are therefore shipped as
well:

- `ai_content_suggestions`
- `ai_logging`
- `ai_translate`
- `ai_validations`
- `field_widget_actions`

`ai_validations` in turn requires `field_validation`, so that module is shipped
too.

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

Several packages a 3.0 site receives resolve to a release that is not stable.
The distribution constrains them with a stability flag such as `@dev` or
`@beta`, which permits an unstable release rather than requiring one. Your
lock does not move on its own when a stable release is published: it keeps the
unstable release until you run an update that names the package. At that
point, with `prefer-stable` set in your root (see Prerequisites), Composer
selects the stable release if it satisfies the constraint, with no change to
the distribution. `flag` is the exception: the distribution pins it to one
exact beta release, so an update does not move it either.

Five are new to this release. The others were already unstable in 2.x and are
listed for completeness.

| Package | State | Since |
| --- | --- | --- |
| `field_group_table` | development branch | 3.0 |
| `webform_mautic` | development branch | 3.0 |
| `cloud` | beta | 3.0 |
| `bootstrap_cloud` | beta | 3.0 |
| `rigel` | beta | 3.0 |
| `youtube` | beta | 2.0 |
| `entity_clone` | beta | 1.x |
| `viewsreference` | beta | 1.x |
| `default_content` | beta | 1.x |
| `flag` | beta, exact version | 1.x |
| `tb_megamenu` | alpha | 1.x |
| `advanced_text_formatter` | release candidate | 1.x |
| `block_field` | release candidate | 1.x |
| `webp` | release candidate | 1.x |
| `inline_entity_form` | release candidate, required by Commerce | — |

`inline_entity_form` is not required by the distribution directly. Commerce
requires it, and its flag, like every flag, takes effect only in your root —
which is one more reason to prefer the `minimum-stability` setting over
listing packages one by one.

Two consequences are worth knowing. Releases that are not stable —
development, alpha, beta and release candidate — are not covered by drupal.org
security advisories. And a development release is a moving branch: your lock
records one commit of it, so the exact code your site runs depends on when the
package was last updated.

## Step 1 — Allow the symfony/runtime Composer plugin

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

## Step 2 — Raise the constraints in your root composer.json

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

## Step 3 — Review your core patches

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

## Step 4 — Check Drush and its dependencies

Drush's own dependency tree can hold Symfony at 6.x. Drupal 11 needs Symfony
7.4, and Composer cannot move a package that is not named in a partial
update, so the resolution fails with messages of the form:

```
symfony/console ... not loaded, conflicts with another require
```

Find the packages that hold Symfony back by reading the requirements:

```
composer why symfony/console
```

Every package listed there whose locked version does not accept Symfony 7.4
must be named in the update command in Step 6. If
the resolution error names another Symfony component, run `composer why` for
that component as well.

On a typical site the list includes `drush/drush`, `consolidation/robo`,
`consolidation/config` and `chi-teck/drupal-code-generator` — but these are
examples, not the list. Tools in your root `require-dev` — static analysis,
testing, code generation — appear in the same output and belong in the update
set too. Use what `composer why` reports for your site.

## Step 5 — Uninstall the tour module if it is enabled

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

## Step 6 — Update with Composer

### Before you start

Take a restore point immediately before the first Composer command, after the
module changes in Step 5:

```
drush sql:dump --result-file="$HOME/backup-before-3.0.sql" --gzip
cp composer.json "$HOME/composer.json.before-3.0"
cp composer.lock "$HOME/composer.lock.before-3.0"
```

Back up the files directory as well, if your general backup does not already
cover it. To roll back, copy the two Composer files back, run
`composer install`, and import the dump.

Capture the pages you will compare against in Step 8: the front page and one
page of each content type, both as an anonymous visitor and logged in. Keep a
screenshot and the page source of each. For the anonymous view, the source can
be saved with the command below, replacing `YOUR_SITE` with your site's host
name:

```
curl -s https://YOUR_SITE/ -o "$HOME/before-3.0-front.html"
```

### Do not use `--with-all-dependencies`

Naming the distribution together with any dependency flag opens every package
it requires. On this dependency graph that pulls in dozens of contributed
modules and can drag unrelated vendor libraries across major versions. Name
packages explicitly instead, with no flag.

### Why this is a single operation

Core, the distribution and every package that blocks them move in one Composer
operation. Once your root requires `^3.0`, the distribution needs Drupal 11,
and Drupal 11 cannot be installed while any locked package still excludes it,
so the change cannot be split into one module at a time. The one-change-per-step
rule in `UPGRADE_NOTES.md` applies to developing the distribution, not to a site
moving between releases; here, the dry-run checks below take its place.

### Build the target list from the requirements

Read the requirements rather than iterating on error messages.

First find the target core release: the latest release in the 11.4 series.

```
composer show drupal/core-recommended --all
```

The `versions` line lists every published release; take the highest `11.4.`
release. To print just that version:

```
composer show drupal/core-recommended --all | grep -oE '11\.4\.[0-9]+' | sort -V | tail -1
```

Development versions such as `11.4.x-dev` do not match the pattern, so only the
latest stable release is printed. The release list at
drupal.org/project/drupal/releases shows the same.
In the commands below, replace `TARGET` with that version, for example
`11.4.10`.

Three commands are needed:

```
composer why-not genero/kamihaya_cms 3.0.0
composer why-not drupal/core-recommended TARGET
composer why-not drupal/core TARGET
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

Each command also lists packages that are already in the command below — the
four `drupal/core*` packages and the distribution itself. Skip those.

Name every package these commands list, together with what the target release
of each requires. To see what a release requires, run the command below,
replacing `PACKAGE` and `VERSION`; `--available` makes Composer read the
release from the package repository rather than from what is installed:

```
composer show PACKAGE VERSION --available
```

A required package that is already in your lock at a version the new release
does not accept must be named, or Composer holds it where it is and the update
fails. A required package that is not yet in your lock is installed without
being named; naming it does no harm.

### Give the vendor packages their versions

Give each vendor package that the two core `why-not` commands reported the
version they reported, in the form `"vendor/package:~7.4.13"`.

On a site whose root requires `drupal/core-recommended` — the normal case, see
Step 2b — that package already pins the vendor tree to exact versions, so the
version argument does not change what is installed. What it changes is how a
mistake shows up. A version given on the command line is a requirement: if
something holds the package below it, the dry-run fails and names the
conflict, instead of resolving quietly to an older core. If your root requires
`drupal/core` without `core-recommended`, nothing pins the Symfony components
that core requires only indirectly, and some of them have an 8.x series that
Drupal 11.4 does not support; the version argument is then also what keeps
them on 7.4.

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
  NAMED_PACKAGES \
  VERSIONED_PACKAGES \
  --dry-run
```

Replace the two placeholders with the packages collected above, separated by
spaces. **Name each package once, in one of the two places:**

- `VERSIONED_PACKAGES` — each package you give a version, quoted, for example
  `"twig/twig:^3.28"`: the vendor packages the two core `why-not` commands
  reported, and any package you pin after reading the dry-run (see "Watch for
  development branches and downgrades" below).
- `NAMED_PACKAGES` — every other package, by name alone: the packages the
  `why-not` commands listed, the packages their target releases require that
  are already in your lock, and the packages `composer why` listed in Step 4.

Every name comes from the `why-not` and `composer why` output for your site, so
the list differs from site to site.

Review the dry-run before executing. Two checks matter most:

- **All four `drupal/core*` packages must land on the same version.** If they
  split — core on one release and `core-project-message` on another — the
  target list is incomplete. Composer resolves successfully with an older
  core rather than move a package you did not name, so a clean dry-run is not
  proof that you reached the version you intended.
- **The Symfony components that core pins stay on 7.4.** That is every
  `symfony/*` package `drupal/core-recommended` pins, and those `drupal/core`
  requires directly. None of them may land on 8.x. Symfony packages outside
  that set can legitimately resolve to 8.x — `symfony/cache`, which reaches the
  site through AI Core's dependencies, is one — and that is expected.

If the dry-run fails to resolve, do not add the packages the error names one at
a time. Go back to the three `why-not` commands and to `composer why` from
Step 4, confirm that every package they listed is named together with what its
target release requires, and rebuild the list. If the error blames "another
require" without naming it, `composer why PACKAGE` shows which locked package
is holding the constraint down; that package belongs in the list.

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
  dry-run fails instead of downgrading. Run
  `composer show drupal/ai VERSION --available` for the 1.5 release you are
  targeting, name every package it requires that is already in your lock, and
  run the dry-run again.

Then run the command without `--dry-run`.

## Step 7 — Rebuild and run database updates

`drush cr` will fail before the database updates run, with an error such as:

```
SQLSTATE[42S22]: Unknown column 'alias' in 'INSERT INTO': INSERT INTO "router" ...
```

This is expected. The code is Drupal 11 while the database is still on the
Drupal 10 schema, and `system_update_11201` is what adds that column. Do not
try to fix it; run the updates.

First clear the container caches and list the queued updates:

```
drush sqlq "TRUNCATE cache_container; TRUNCATE cache_bootstrap; TRUNCATE cache_discovery; TRUNCATE cache_config;"
drush updatedb:status
```

**Review the list before going further.** A 10.6 to 11.4 update queues many
updates across core and the contributed modules that moved. Check that every
entry belongs to a module present on your site; an entry for `tour` means
Step 5 is incomplete. Proceed only when the list is what you expect:

```
drush updb -y
drush cr
```

`drush cr` succeeding afterwards is the confirmation that the schema has
caught up with the code.

If `updb` fails partway, do not re-run it. A partially applied update path
needs a decision, not a retry — restore the backup and investigate.

## Step 8 — Verify

- `drush status` reports Drupal 11.4.x and a successful bootstrap.
- `drush core:requirements --severity=2` reports no core update or security
  coverage errors.
- The front page and a page of each content type render, and the page
  container structure is unchanged. Compare each page against the capture
  taken in Step 6 — the markup as well as the screenshot if your theme
  extends `bootstrap5`.
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
update does not move packages you do not name. Find the target release as in
Step 6 — the highest `11.4.` release that `composer show
drupal/core-recommended --all` lists — and read **both** `why-not` forms,
replacing `TARGET` with that version:

```
composer why-not drupal/core-recommended TARGET
composer why-not drupal/core TARGET
```

`core-recommended` pins core's vendor tree, so the first lists the vendor
packages that must move, with the version each needs. Core also pins some
vendor packages directly — `twig/twig` is one — and those appear only in the
second.

Two commands are enough here, where Step 6 needs three. The third form,
`why-not genero/kamihaya_cms`, finds packages that fall outside a *new*
distribution release's constraints; a core security release leaves the
distribution where it is, so there is nothing for it to find.

### The command

Name the four core packages together, with the vendor packages `why-not`
listed, and **no** dependency flag:

```
composer update \
  drupal/core drupal/core-recommended \
  drupal/core-composer-scaffold drupal/core-project-message \
  VERSIONED_PACKAGES \
  --dry-run
```

Replace `VERSIONED_PACKAGES` with each vendor package `why-not` reported,
quoted with the version it reported, for example `"symfony/console:~7.4.13"`,
separated by spaces.

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

List the queued database updates first:

```
drush updatedb:status
```

A patch-level security release usually queues few or no database updates.
Review the list, and proceed only when it is what you expect:

```
drush updb -y
drush cr
drush status --field=drupal-version
```

Run `updb` even when the list is empty. Confirm the resolved core version with
`drush status`.

### Knowing an update is needed

- `composer audit` reports published advisories affecting your locked packages.
  Run it periodically, not only after an upgrade.
- `drush core:requirements --severity=2` reports when core security coverage is
  missing or an update is due.
- Core security releases are announced at drupal.org/security, on a regular
  schedule.

## Notes

- Blazy remains on 3.0.x. It will move to a 4.x stable release when one is
  available.
- After updating, `drush core:requirements` may report "Module and theme
  update status: Not secure!" if contributed modules have newer releases
  available. The distribution pins the versions it has verified. If you
  update contributed modules independently, avoid `-W` for the reasons given
  in Step 6.
- See `UPGRADE_NOTES.md` for ongoing maintenance guidance.
