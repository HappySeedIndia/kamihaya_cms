# Maintenance and Upgrade Notes

Guidance for maintainers of the Kamihaya CMS distribution. Site administrators
updating an existing site should read `UPDATE_2.0.md` or `UPDATE_3.0.md`
instead, depending on the target release.

## Release series

| Series | Drupal core | Status |
| --- | --- | --- |
| 1.x | 10.4 | Superseded. Pins sites to an exact core release; see below. |
| 2.x | 10.6 | Maintenance only. |
| 3.x | 11.4 | Current. |

Drupal 10 reaches end of life on 9 December 2026, and 10.6 is its final minor
release. The 2.x series therefore has a limited support window by design and
exists to give sites a supported Drupal 10 position while they prepare for
Drupal 11.

Drupal 11.4 receives security coverage until June 2027.

## Core version constraints

**Do not require `drupal/core-recommended` from this distribution.**

`core-recommended` is a metapackage intended for root projects. It requires
Drupal core and its entire vendor tree (Symfony, Twig, Guzzle and others) at
**exact** versions. Requiring it from a distribution propagates those exact pins
to every consuming site, with two consequences:

1. Sites cannot apply a core security release on their own. They must wait for a
   new distribution release. During the 1.x series this left sites on core
   10.4.10 after that release had reached end of life.
2. The distribution takes on responsibility for a vendor tree it does not
   manage or test.

Require `drupal/core` with a range constraint instead. Managing
`core-recommended` and `core-composer-scaffold` belongs to each site's root
`composer.json`.

Keep the constraint narrow enough to express what has actually been verified.
The 2.x series uses `^10.6` rather than a wider range: a constraint that admits
a core version the distribution has not been tested against will ship that
version to any site whose root allows it.

## Composer update discipline

### Never use `-W` or `-w`

Not for contrib, and not for core. Naming `genero/kamihaya_cms` together with
any dependency flag opens **every package this distribution requires** for
update.

Contributed modules are direct dependencies of the profile, so lowercase `-w`
behaves the same as `-W`. Observed effects on this dependency graph: a module
removal run with `-W` moved 55 packages, and a core minor update run with `-W`
moved 23 contributed modules together with `dompdf/dompdf` 2.0 to 3.1,
`phpoffice/phpspreadsheet` 2.4 to 5.9 and `sabberworm/php-css-parser` 8.9 to
9.4, none of which core required.

Limiting the *named targets* to `drupal/core-*` does not limit what a dependency
flag updates. There is no safe form of this flag on this project.

### Always snapshot the lock first

```
cp composer.lock composer.lock.bak
```

A test site assembled from this distribution is normally not under version
control, so this is the only rollback mechanism for a Composer operation.

### Updating a contributed module

Name the profile **and** the module:

```
composer update genero/kamihaya_cms drupal/<name>
```

Version constraints live in this distribution's `composer.json`, so the profile
must be re-resolved for a new constraint to take effect. Naming the module alone
returns "Nothing to modify".

### Removing a module

Name the profile alone:

```
composer update genero/kamihaya_cms
```

### Updating core

Core cannot be moved by naming the profile alone: core and its vendor libraries
are fixed in `composer.lock`, and a partial update will not touch packages that
were not named. Name every package explicitly, with **no** dependency flag.

Build the list by reading the requirements rather than by iterating on error
messages. **Two commands are needed, and both matter:**

```
composer why-not drupal/core-recommended <target-version>
composer why-not drupal/core <target-version>
```

The first lists every vendor package that `core-recommended` pins, with the
version each one needs. The second lists the contributed modules whose locked
versions do not support the target core, **and** the vendor packages that core
pins directly rather than through `core-recommended`. `twig/twig` is one of
these; reading only the first command misses it.

**Give every vendor package an explicit version**, in the form
`vendor/package:~7.4.13`, taken from the `why-not` output. This distribution
requires `drupal/core` rather than `core-recommended`, so nothing pins the
vendor tree here and Composer is free to take a newer major of any component
that has one. Several Symfony components have an 8.x series while core pins
them to `~7.4.x`.

**Include the root project's `require-dev` packages when they hold Symfony
down.** Drush's dependency tree in particular can pin Symfony to 6.x through
`consolidation/robo`, `consolidation/config` and
`chi-teck/drupal-code-generator`. Their own constraints permit Symfony 7; they
only need to be in the update set. Composer reports this as
`not loaded, conflicts with another require` without naming the culprit — use
`composer why symfony/console` to find it.

Iterating on `fixed to X by a partial update` errors also converges, but it
converges to a *lower* core version: Composer prefers resolving with an older
core over moving a package that was not named. On this project that approach
capped at 10.6.8 when 10.6.14 was the target, and left the core packages split.

**Verify that all four `drupal/core*` packages land on the same version.** A
split — core at 11.4.4 with `core-project-message` at 11.4.6 — means the target
list is incomplete. A successful dry-run is not proof that the intended version
was reached. On the 11.4 upgrade this split was the signal that `twig/twig` was
missing from the list.

### Blockers are not limited to enabled modules

Composer resolves against `composer.json`, not `core.extension`. A module that
is disabled on every site still blocks a core upgrade if its locked version
declares an incompatible core requirement. Never build a blocker list from
`drush pm:list --status=enabled`; build it from `composer why-not drupal/core`.

Note also that a package may need no constraint change at all. If the existing
constraint already permits a version that supports the target core, naming the
package in the update command is enough — the lock was simply holding an older
release.

### One change per step

Apply and verify one change at a time. Batching contrib updates makes failures
impossible to attribute. Every contrib update in the 2.x series was applied and
verified individually.

## Clearing the compiled service container

Clear the container caches **before** `drush cr`, not after it fails:

```
drush sqlq "TRUNCATE cache_container; TRUNCATE cache_bootstrap; TRUNCATE cache_discovery; TRUNCATE cache_config;"
drush cr
```

Do this whenever the profile itself is updated, not only when a module providing
services is removed. A profile update ships changed module code, which can stale
the compiled container even when no module is added or removed. Observed with
webform 6.3, with Commerce 3, and with a profile update that changed only the
distribution's own module code.

If a service class has been removed, `drush cr` fails with `Class "..." not
found` because rebuilding requires booting with the old container. Clearing the
container caches directly avoids the failure.

## Removing modules from the distribution

Do not write update hooks that uninstall removed modules. Document a manual
pre-update uninstall step in the release's update guide instead. The reason is a
design decision: the distribution should not carry code that references modules
it no longer ships.

Distinguish two cases in the documentation:

- **Modules listed in the profile's install list.** Enabled on every consuming
  site. Document an unconditional uninstall.
- **Modules required in `composer.json` but not in the install list.** Normally
  disabled, but a site may have enabled one manually. Document a conditional
  check followed by an uninstall.

## Unstable and held releases

| Package | Constraint | Revisit when |
| --- | --- | --- |
| `drupal/field_group_table` | `^1.1@dev` | A stable release declaring `^11` is published. |
| `drupal/webform_mautic` | `^2.0@dev` | A stable release declaring `^11` is published. |
| `drupal/cloud` | `^8.0@beta` | An 8.x stable release is published. |
| `drupal/bootstrap_cloud` | `^7.0@beta` | A 7.x stable release is published. |
| `drupal/rigel` | `^8.0@beta` | An 8.x stable release is published. |
| `drupal/youtube` | `^3.0@beta` | A stable 3.x release is published. |
| `drupal/blazy` | Held at 3.0.x | A 4.x stable release is available. |
| `drupal/entity_clone` | Beta | A stable release is published. |
| `drupal/viewsreference` | Beta | A stable release is published. |

Prefer stable releases. Adopt an unstable one only when no stable release
supports the target core version, and record the reason.

**Always write the constraint with an `@dev` or `@beta` flag rather than
pinning a branch such as `1.x-dev`.** The flag permits an unstable release; it
does not require one. With `prefer-stable: true` the moment a stable release
appears that satisfies the constraint, it is preferred automatically and no
distribution release is needed. Pinning a branch keeps every consuming site on
the moving branch forever, which is the same trap as pinning
`core-recommended` to an exact version.

A consequence of the flag is that the lock does not change when you add it.
`field_group_table` stayed on its stable 1.1.0 until core moved to 11, because
1.1.0 still satisfied `^1.1@dev` on Drupal 10. `Nothing to modify in lock file`
is the expected result, not a failure.

Record in the update guide that unstable releases are outside drupal.org
security advisories, and that a development branch means the exact code a site
receives depends on when it installs.

## `drupal/ai`

`drupal/ai` (AI Core) is required by the distribution but is **not** enabled by
the install profile, and no distribution feature uses it. It is retained
deliberately so that AI capabilities remain available to consuming sites.

From 3.0 the distribution also ships `drupal/ai_provider_openai` and
`drupal/metatag_ai`. Neither is enabled by the install profile either.

Points for maintainers:

- The `drupal/ai` constraint is `^1.4`, narrowed from the `^1.0` it carried
  through the 2.x series. `^1.0` was wider than anything verified, and once
  core reached 10.6 it allowed the resolver to jump several minor versions at
  once. Keep the constraint at the series that is actually tested.
- `drupal/key` arrives transitively through `drupal/ai` and `drupal/encrypt`.
  Do not declare it in the distribution's `composer.json`; re-declaring a
  transitive dependency is not Composer practice. Note however that **neither
  `ai_provider_openai` nor `metatag_ai` declares `key` as a module
  dependency**, so a site enabling them must enable `key` explicitly. Say so in
  the update guide.
- `ai_provider_openai` requires `drupal/ai` with a caret range (`^1.2.0`), not
  a matching minor, so provider 1.2.x works with `ai` 1.4.x. Do not assume the
  provider's minor must track the AI module's minor.
- `metatag_ai` declares `drupal/ai: ^1.3@beta`. The `@beta` flag permits a beta
  rather than requiring one, so a stable `ai` release satisfies it.
- `metatag_ai` declares `core_version_requirement: ^11` only. It could not be
  enabled at all before 3.0.

`drupal/openai` was a separate project from a different ecosystem, with no
stable release ever published. It was removed in 2.0. It is not the provider
for `drupal/ai`; that is `drupal/ai_provider_openai`. Do not confuse the two.

## Filename casing

PSR-4 autoloading resolves a class name directly to a filename. A filename that
differs from its declared class name only in casing therefore loads without
complaint on a case-insensitive filesystem (the macOS and Windows defaults) and
fails with `Class not found` on the case-sensitive filesystems used by typical
Linux servers.

Neither PHPStan nor the standard PHPCS sniff set detects this, so it must be
checked explicitly. Two such files existed in 1.x and were corrected in 2.0.
Audit before tagging a release:

```
for f in $(find modules themes -path "*/src/*" -name "*.php"); do
  base=$(basename "$f" .php)
  decl=$(grep -m1 -oE '^(final |abstract )*(class|interface|trait|enum) [A-Za-z0-9_]+' "$f" \
         | awk '{print $NF}')
  if [ -n "$decl" ] && [ "$base" != "$decl" ]; then
    echo "$f : file=$base declared=$decl"
  fi
done
```

Note that `git status` does not report a case-only rename while
`core.ignorecase` is `true`, which is the default on case-insensitive
filesystems. Use `git diff-index --cached --name-status HEAD` to confirm such a
change is staged, and set `core.ignorecase false` only for the duration of the
commit.

## Patches

Patches to `drupal/core` are the most common cause of a failed major upgrade,
and they fail for two different reasons:

- the surrounding code moved, so the patch no longer applies; or
- **the fix was committed to core**, so there is nothing left to add and the
  patch reports a context mismatch.

The second case reads exactly like the first. Before rerolling anything, read
the issue and compare the patch against the current core source: the patch may
simply be obsolete. One core patch carried through 1.x and 2.x was removed in
3.0 for precisely that reason — Drupal 11 implements the fix natively, and more
thoroughly than the patch did.

With `composer-exit-on-patch-failure` enabled, one failing patch aborts the
whole install and leaves `composer.lock` updated while `vendor/` is not. That
state does not boot. Review the patch list before a major core upgrade rather
than discovering it mid-install.

Prefer local patch files under `patches/` over links to drupal.org. A remote
patch can disappear, and its content is not under this project's control.

## Composer plugins

Drupal 11 uses `symfony/runtime`, which ships a Composer plugin. Composer
blocks plugins that are not listed in `config.allow-plugins` and aborts the
install when it meets one.

`--dry-run` does not execute plugins, so this class of failure only appears on
the real run. A dry-run that resolves cleanly is not evidence that the install
will complete.

This affects every consuming site, since no 1.x or 2.x root `composer.json`
lists `symfony/runtime`. It belongs in the update guide as a prerequisite, not
as troubleshooting.

## The database update step

`drush cr` fails between the Composer update and `drush updb` on a major core
upgrade, with an error such as `Unknown column 'alias' in 'INSERT INTO'`. The
code is on the new major while the database still has the old schema, and the
update that adds the column has not run yet. This is expected; run `updb`.

Always inspect `drush updatedb:status` in full before running `drush updb`. A
10.6 to 11.4 upgrade queues around forty-four updates.

Note that `drush updb --dry-run` does not exist in Drush 13; `updatedb:status`
is the way to inspect pending updates.

If `updb` fails partway, do not retry it. A partially applied update path needs
a decision, not a repetition.

## Modules removed from Drupal core

A core major upgrade can remove modules from core entirely. Drupal 11 removed
`tour`, which moved to a contributed project. A site with it enabled must
uninstall it before the upgrade; a site that enabled it in the past carries a
leftover `system.schema` entry that produces a warning.

The warning is harmless as long as no update for the removed module is queued.
If one is, its code no longer exists and `updb` will fail. Check with
`drush updatedb:status` before running the updates.

## Pre-release checks

A site assembled from a release candidate should report:

- `drush status` — expected core version, successful bootstrap
- `drush core:requirements --severity=2` — no core security coverage errors
- PHPStan — no errors
- PHPCS — no errors or warnings
- A real request through the kernel, not only CLI checks:
  ```
  drush php:eval "\$r = \Drupal::service('http_kernel')->handle(\Symfony\Component\HttpFoundation\Request::create('/')); print \$r->getStatusCode();"
  ```

PHPStan resolves class names case-insensitively and does not execute code, so it
cannot substitute for the runtime checks above.

### Compare rendered markup across an upgrade

Some breakage is silent. A theme variable that the base theme stops supplying
renders as an empty string in Twig: no exception, no log entry, and the layout
quietly collapses. Static analysis cannot see Twig variables or form array
keys, so a clean PHPStan result does not cover them.

Capture rendered markup metrics **before** an upgrade and compare afterwards.
Counting the wrapper classes the theme emits, and counting empty `class=""`
attributes, is enough to catch a missing variable:

```
drush php:eval "\$r = \Drupal::service('http_kernel')->handle(\Symfony\Component\HttpFoundation\Request::create('/')); \$h=\$r->getContent(); print 'status='.\$r->getStatusCode().' container='.preg_match_all('/<div class=\"container[^\"]*\"/', \$h).' emptyclass='.preg_match_all('/<div class=\"\"/', \$h).' bytes='.strlen(\$h).PHP_EOL;"
```

Run it against the front page and against a node of each content type whose
template differs. A test site with no content cannot exercise node templates at
all, so create one node per relevant type before starting.

### Deprecations surface only after the upgrade

Static analysis on the old core cannot report deprecations introduced by the
new one. Drupal 11.3 deprecated `theme_get_setting()` in favour of
`ThemeSettingsProvider::getSetting()`; that only becomes visible once core is
on 11.x. Expect the error count to rise after a major core upgrade, and treat
the new entries as the upgrade's output rather than as a regression.
