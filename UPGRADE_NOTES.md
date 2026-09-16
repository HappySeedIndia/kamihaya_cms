# Maintenance and Upgrade Notes

Guidance for maintainers of the Kamihaya CMS distribution. Site administrators
updating an existing site should read `UPDATE_2.0.md` instead.

## Release series

| Series | Drupal core | Status |
| --- | --- | --- |
| 1.x | 10.4 | Superseded. Pins sites to an exact core release; see below. |
| 2.x | 10.6 | Current. |

Drupal 10 reaches end of life on 9 December 2026, and 10.6 is its final minor
release. The 2.x series therefore has a limited support window by design.

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
messages:

```
composer why-not drupal/core-recommended <target-version>
```

`core-recommended` pins core's whole vendor tree, so this single command lists
every vendor package that must move, with the version required. Name all of
them plus the four `drupal/core*` packages.

Iterating on `fixed to X by a partial update` errors also converges, but it
converges to a *lower* core version: Composer prefers resolving with an older
core over moving a package that was not named. On this project that approach
capped at 10.6.8 when 10.6.14 was the target, and left the core packages split.

**Verify that all four `drupal/core*` packages land on the same version.** A
split — core at 10.6.8 with `core-composer-scaffold` at 10.6.14 — means the
target list is incomplete. A successful dry-run is not proof that the intended
version was reached.

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

| Package | State | Revisit when |
| --- | --- | --- |
| `drupal/youtube` | `^3.0@beta` (3.0.0-beta1) | A stable 3.x release is published. The constraint tracks beta releases automatically. |
| `drupal/blazy` | Held at 3.0.x | A 4.x stable release is available. |
| `drupal/entity_clone` | Beta | A stable release is published. |
| `drupal/viewsreference` | Beta | A stable release is published. |
| `drupal/inline_entity_form` | Was RC in 1.x, stable available | Already resolved. |

Prefer stable releases. Adopt a beta only when no stable release supports the
target core version, and record the reason.

## `drupal/ai`

`drupal/ai` (AI Core) is required by the distribution but is **not** enabled by
the install profile, and no distribution feature uses it. It is retained
deliberately so that AI capabilities remain available to consuming sites.

Two consequences for maintainers:

- The constraint is currently `^1.0`, which is wider than what has been
  verified. During the core 10.6 work this caused the resolver to propose
  jumping 1.2.16 to 1.4.5 whenever a dependency flag was used. Tighten it to the
  series actually intended once a provider module is added.
- `drupal/key` arrives transitively through `drupal/ai` (and `drupal/encrypt`).
  Do not declare it in the distribution's `composer.json`; re-declaring a
  transitive dependency is not Composer practice.

`drupal/openai` was a separate project from a different ecosystem, with no
stable release ever published. It is not the provider for `drupal/ai`; that is
`drupal/ai_provider_openai`. Do not confuse the two.

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
