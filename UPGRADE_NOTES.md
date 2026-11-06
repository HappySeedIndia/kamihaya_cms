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

Drupal 11.4 receives security coverage for a limited window; check the
drupal.org core release cycle for the end-of-life date of the target minor.

Ship the latest patch release of the target minor, not the one that happened
to be current when the work started. Core security advisories arrive as patch
releases, and a release that ships a known-vulnerable core is worse than one
that ships a few days later.

## Core version constraints

**Do not require `drupal/core-recommended` from this distribution.**

`core-recommended` is a metapackage intended for root projects. It requires
Drupal core and its entire vendor tree (Symfony, Twig, Guzzle and others) at
**exact** versions. Requiring it from a distribution propagates those exact pins
to every consuming site, with two consequences:

1. Sites cannot apply a core security release on their own. They must wait for a
   new distribution release.
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
behaves the same as `-W`. On this dependency graph a dependency flag pulls
dozens of contributed modules and drags unrelated vendor libraries across major
versions, none of which core required.

Limiting the *named targets* to `drupal/core-*` does not limit what a dependency
flag updates. There is no safe form of this flag on this project.

### Always snapshot the lock first

```
cp composer.lock composer.lock.bak
```

A test site assembled from this distribution is normally not under version
control, so this is the only rollback mechanism for a Composer operation.

### Updating a contributed module

The command differs by environment, because `genero/kamihaya_cms` is the root
package in the distribution repository but a dependency in a site or
verification checkout.

**In the distribution repository** (where `genero/kamihaya_cms` is the root
package): the root cannot be an update target. Change the constraint in
`composer.json`, then name the module alone, replacing `MODULE` with its
machine name:

```
composer update drupal/MODULE --no-install
```

Because the distribution is the root here, the edited constraint applies
directly — the module-alone update resolves it. (`--no-install` keeps Composer
from expanding `vendor/`, which the distribution checkout does not need.)

**In a site or verification checkout** (where the distribution is a
dependency): name the profile **and** the module, so the profile is re-resolved
and the new constraint takes effect:

```
composer update genero/kamihaya_cms drupal/MODULE
```

In that environment, naming the module alone returns "Nothing to modify",
because the constraint that changed lives in the distribution package rather
than in the root.

### Removing a module

Remove the requirement from the distribution's `composer.json`, then reconcile
the lock. The command depends on the environment:

- **In the distribution repository**: the root cannot be named, so name the
  package you removed (`MODULE`), which drops it from the lock:

  ```
  composer update drupal/MODULE --no-install
  ```

- **In a site or verification checkout**: name the profile, so it is
  re-resolved against the changed requirement:

  ```
  composer update genero/kamihaya_cms
  ```

### Updating core

Core cannot be moved by naming the profile alone: core and its vendor libraries
are fixed in `composer.lock`, and a partial update will not touch packages that
were not named. Name every package explicitly, with **no** dependency flag.

Build the list by reading the requirements rather than by iterating on error
messages. Find the target first — the highest release of the target minor that
`composer show drupal/core-recommended --all` lists, or the release list at
drupal.org/project/drupal/releases — and replace `TARGET` with it below.
**Two commands are needed for core, and both matter:**

```
composer why-not drupal/core-recommended TARGET
composer why-not drupal/core TARGET
```

The first lists every vendor package that `core-recommended` pins, with the
version each one needs. The second lists the contributed modules whose locked
versions do not support the target core, **and** the vendor packages that core
pins directly rather than through `core-recommended`. `twig/twig` is one of
these; reading only the first command misses it. (The second form reports
both because `why-not` lists every installed package whose requirement excludes
the target, and also every requirement of the target that the installed
packages do not satisfy.)

**A site moving to a new distribution release needs a third command**,
`composer why-not genero/kamihaya_cms NEW_VERSION`. It lists the packages whose
locked versions fall outside the new release's constraints even though they
still support the target core — a constraint the distribution narrowed, for
example. The two core forms cannot see those. A core security release within
the same minor leaves the distribution unchanged, so the update guides use two
commands for it and three for a distribution release.

**Give every vendor package an explicit version**, in the form
`vendor/package:~7.4.13`, taken from the `why-not` output. In this repository
the distribution is the root and requires `drupal/core` rather than
`core-recommended`, so nothing pins the vendor tree to exact versions. Core's
own requirements keep the Symfony components it requires directly on 7.4, but
the components it reaches only indirectly are free, and several have an 8.x
series that Drupal 11.4 does not support. On a site whose root keeps
`core-recommended`, that package pins the vendor tree; the explicit version
there serves to make a held-back package fail the dry-run instead of resolving
quietly to an older core.

**Include the root project's `require-dev` packages when they hold Symfony
down.** Drush's dependency tree in particular can pin Symfony to 6.x through
`consolidation/robo`, `consolidation/config` and
`chi-teck/drupal-code-generator`. Their own constraints permit Symfony 7; they
only need to be in the update set. Composer reports this as
`not loaded, conflicts with another require` without naming the culprit — use
`composer why symfony/console` to find it.

Iterating on `fixed to X by a partial update` errors also converges, but it
converges to a *lower* core version: Composer prefers resolving with an older
core over moving a package that was not named, so error-driven iteration can
settle on a core version below the one you intended, with the core packages
left split.

**Verify that all four `drupal/core*` packages land on the same version.** A
split — core at one release with `core-project-message` at another — means the
target list is incomplete. A successful dry-run is not proof that the intended
version was reached. A split is the signal that a directly-pinned vendor package
such as `twig/twig` is missing from the list.

**Read the dry-run for development branches and downgrades.** When the newest
release of a named package needs a dependency that is locked below what it
requires and was not named, Composer does not fail. It chooses an older release
or a development branch that the locked dependency still satisfies, and the
dry-run reports success. Look for any package resolving to a `dev-` branch or an
`x-dev` version, and for any package moving to a lower version than it holds
now. When you find one, name it with an explicit version and name the packages
that version requires as well, then run the dry-run again.

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
impossible to attribute.

This applies to **developing the distribution**: changing constraints in this
repository, and verifying each change in a checkout that tracks the
development branch. It does not apply to a site moving between two release
tags. There the new release's constraints arrive all at once, and core moves
together with every package that blocks it, so the update guides describe that
move as a single operation and rely on the dry-run checks above to catch what
one-change-at-a-time would otherwise isolate.

## Clearing the compiled service container

Clear the container caches **before** `drush cr`, not after it fails:

```
drush sqlq "TRUNCATE cache_container; TRUNCATE cache_bootstrap; TRUNCATE cache_discovery; TRUNCATE cache_config;"
drush cr
```

Do this whenever the profile itself is updated, not only when a module providing
services is removed. A profile update ships changed module code, which can stale
the compiled container even when no module is added or removed — a major module
update and a profile-only code change can both trigger it.

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

| Package | Constraint | Resolves to | Revisit when |
| --- | --- | --- | --- |
| `drupal/field_group_table` | `^1.1@dev` | development branch | A stable release declaring `^11` is published. |
| `drupal/webform_mautic` | `^2.0@dev` | development branch | A stable release declaring `^11` is published. |
| `drupal/cloud` | `^8.0@beta` | beta | An 8.x stable release is published. |
| `drupal/bootstrap_cloud` | `^7.0@beta` | beta | A 7.x stable release is published. |
| `drupal/rigel` | `^8.0@beta` | beta | An 8.x stable release is published. |
| `drupal/youtube` | `^3.0@beta` | beta | A stable 3.x release is published. |
| `drupal/entity_clone` | `^2.1@beta` | beta | A stable 2.x release is published. |
| `drupal/viewsreference` | `^2.0@beta` | beta | A stable 2.x release is published. |
| `drupal/default_content` | `^2.0@alpha` | beta | A stable 2.x release is published. |
| `drupal/flag` | `4.0-beta5` (exact) | beta | A stable 4.x release is published. The exact pin must then be changed by hand; it does not move on its own. |
| `drupal/tb_megamenu` | `^3.0@alpha` | alpha | A stable 3.x release is published. |
| `drupal/advanced_text_formatter` | `^3.0@RC` | release candidate | A stable 3.x release is published. |
| `drupal/block_field` | `^1.0@RC` | release candidate | A stable 1.x release is published. |
| `drupal/webp` | `^1.0@RC` | release candidate | A stable 1.x release is published. |
| `drupal/inline_entity_form` | Not required directly | release candidate | Required by Commerce at RC stability. Keep it in the update guides' list while Commerce resolves it to an RC. |
| `drupal/blazy` | Held at 3.0.x | stable | A 4.x stable release is available. |

`content_access` (`^2.1@RC`) and `shs` (`^2.0@RC`) also carry a flag but
currently resolve to stable releases, so they are not listed.

Prefer stable releases. Adopt an unstable one only when no stable release
supports the target core version, and record the reason.

**Always write the constraint with an `@dev` or `@beta` flag rather than
pinning a branch such as `1.x-dev`.** The flag permits an unstable release; it
does not require one. With `prefer-stable: true`, once a stable release that
satisfies the constraint is published, the next update that names the package
selects it, and no distribution release is needed. The lock does not move by
itself: until an update names the package, the lock keeps the unstable release
it already holds. Pinning a branch keeps every consuming site on
the moving branch forever, which is the same trap as pinning
`core-recommended` to an exact version.

**Stability flags, `minimum-stability` and `prefer-stable` take effect only in
the distribution repository, where the profile is the root package.** Composer
ignores them in a dependency, so on a consuming site the distribution's flags
do nothing. The site's own root must declare `"minimum-stability": "dev"` with
`"prefer-stable": true`, or require each unstable package itself with a flag.
Say so in the prerequisites of every update guide, and keep the list of
unstable packages there complete — including packages the distribution does not
require directly but receives at an unstable release through a dependency.

A consequence of the flag is that the lock does not change when you add it.
`field_group_table` stayed on its stable 1.1.0 until core moved to 11, because
1.1.0 still satisfied `^1.1@dev` on Drupal 10. `Nothing to modify in lock file`
is the expected result, not a failure.

Record in the update guide that unstable releases are outside drupal.org
security advisories, and that a development branch means the exact code a site
runs depends on when the package was last updated: the lock records one commit
of the branch.

## `drupal/ai`

`drupal/ai` (AI Core) is required by the distribution but is **not** enabled by
the install profile, and no distribution feature uses it. It is retained
deliberately so that AI capabilities remain available to consuming sites.

From 3.0 the distribution also ships `drupal/ai_provider_openai` and
`drupal/metatag_ai`. Neither is enabled by the install profile either.

Points for maintainers:

- The `drupal/ai` constraint is `^1.5`, narrowed from the `^1.0` it carried
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
  a matching minor, so provider 1.2.x works with `ai` 1.5.x. Do not assume the
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

Always inspect `drush updatedb:status` in full before running `drush updb`, and
document the two as separate steps in the update guides, with the review in
between. A major core upgrade queues many updates across core and the
contributed modules that moved.

Note that `drush updb --dry-run` does not exist in Drush 13; `updatedb:status`
is the way to inspect pending updates.

If `updb` fails partway, do not retry it. A partially applied update path needs
a decision, not a repetition.

## Modules removed from Drupal core

A core major upgrade can remove modules from core entirely. Drupal 11 removed
`tour`, which moved to a contributed project. A site with it enabled must
uninstall it before the upgrade.

A site that enabled it in the past carries a leftover `system.schema` entry.
That entry is **not harmless**: installing any module afterwards fails with
`The module tour does not exist.`, because Drupal resolves the full module list
during installation. The update guide must tell sites to remove it:

```
drush php:eval "\Drupal::keyValue('system.schema')->delete('tour');"
drush cr
```

If an update for the removed module is queued, its code no longer exists and
`updb` will fail as well. Check with `drush updatedb:status` before running the
updates.

## Nested rendering on Drupal 11

Drupal 11 wraps controller execution in a `\Fiber`. A nested render started
while another render is in progress now leaves an unbalanced frame on the
render context, and the metadata collected below it never bubbles up.

The failure is **silent in production**. With assertions enabled it raises

```
AssertionError: Bubbling failed.
```

from `Renderer::executeInRenderContext()`, but `zend.assertions` is off on production, so nothing is logged at all. What
the visitor gets is a page whose `<head>` still contains
`<css-placeholder>`, `<js-placeholder>` and `<head-placeholder>`: no
stylesheet links, no scripts, an unstyled page. The second request renders
correctly because it comes from cache, so it reads as an intermittent glitch
rather than a defect.

### What triggers it

Calling `Url::toString()` with no arguments inside a render. It goes through
`MetadataBubblingUrlGenerator::bubble()`, which calls `Renderer::render()` to
collect the URL's cacheability, and that pushes the frame.

The contexts where this happens are those that run while a render is already
in progress: `#pre_render` callbacks, `hook_toolbar()`,
`hook_page_attachments()` and preprocess functions.

`HtmlRenderer` works in two phases. `prepare()` builds the page and invokes
`hook_page_attachments()`; `renderResponse()` then renders `html.html.twig`.
The toolbar sits in `page_top`, which is rendered by `html.html.twig`, so its
`#pre_render` — and the `hook_toolbar()` implementations it invokes — run in
the second phase, inside `renderResponse()`. That is where the toolbar case
occurs, not in `prepare()`.

`Url::toString(TRUE)` returns a `GeneratedUrl` without rendering. Where a
nested render really is needed, `renderInIsolation()` runs it in its own
context — it replaced `renderPlain()`, deprecated in Drupal 10.3.

### It usually shows up as an authenticated-only fault

The toolbar is only rendered for authenticated users, and anonymous requests
are served by the internal page cache without reaching the code at all. A
fault in this class therefore affects logged-in users only, on the first
request after a cache clear. That combination is easy to misattribute to
caching or to the hosting environment.

### Finding it

Keep `zend.assertions=1` in the verification environment. The assertion is the
only thing that turns a silent failure into a visible one: with it, the stray
frame raises `AssertionError: Bubbling failed.` with a stack trace instead of
failing silently.

A bare `renderer->render()` in a render hook, or a module that calls
`Url::toString()` from a `#pre_render`, are the usual culprits. Where the
culprit is a contributed module, updating it is the fix.

## Security advisories

Run `composer audit` after a major core upgrade, and before any release.

A core major upgrade moves a great deal at once, and it is easy to finish it
believing the dependency tree is current when it is not. A core upgrade names
only the packages that block the new core; everything else stays at whatever
version the lock held, which can leave contributed modules several releases
behind and carrying published advisories.

Most are reachable without touching a single constraint: the constraint already
permits the fixed release and only the lock is behind.

### Reading the output

Separate three groups:

- **`drupal/*` the distribution declares** — act on these.
- **vendor packages pulled in by contributed modules** — whether they can
  move depends on the contributed module's constraint. If it pins an old
  major, the fix is out of reach until that module updates.
- **the root project's `require-dev`** — static analysis, testing and code
  style tools. These never reach a consuming site.

### Pairs that block each other

When two packages constrain one another, naming them without versions leaves
the pair unresolvable and Composer reports nothing to do. Give both an
explicit target:

```
composer update "drupal/svg_image:^3.2" "enshrined/svg-sanitize:^1.0"
```

A dependency flag would also resolve it, and would also drag unrelated packages
across majors — including Symfony components to an 8.x series against a core
that pins them to `~7.4`. Name the pair with explicit versions instead.

## Checking templates after a base theme or core update

When the base theme or core changes a template, markup that this theme depends
on can stop being emitted without any error. A class is no longer added, an
element is replaced by another, or a class is renamed so that core's own
JavaScript or CSS no longer finds it. The page still renders, nothing is
logged, and the change does not show up in byte counts, element counts or
empty-class counts. Only reading diffs finds it.

After a major update of the base theme or of core:

1. **Diff the base theme's old and new release first.** Templates that the new
   release did not change need no further checking.
2. **For each template the new release removed**, find what this theme's CSS
   and JavaScript relied on from that template's output — the classes,
   elements and attributes it emitted — and check whether anything still
   provides them.
3. **For each template this theme overrides**, diff the override against the
   new upstream version, and separate deliberate customisation (framework
   classes, extra wrappers, this theme's own markup) from drift: places where
   upstream changed a class name, a variable or the markup structure and the
   override did not follow. A variable may be kept only for compatibility, so
   check the preprocess function as well as the template.

**Key CSS and JavaScript on classes, not on element names.** An element name is
not a contract: upstream changes it when the semantics of the markup change. A
selector such as `.card article` stops matching as soon as the wrapper becomes
a `<div>`; a selector keyed on the wrapper's class does not.

## Pre-release checks

A site assembled from a release candidate should report:

- `drush status` — expected core version, successful bootstrap
- `drush core:requirements --severity=2` — no core security coverage errors
- PHPStan — no errors beyond the documented baseline. The baseline for 3.0 is
  4 errors, all `drupal.entityStorageDirectInjection`. Any other error, or a
  change in that count, must be resolved before tagging. Run it with a raised
  memory limit:
  ```
  vendor/bin/phpstan analyse --memory-limit=2G
  ```
  With the default limit of 128M a worker process runs out of memory and the
  analysis stops partway. The result then looks deceptively clean: the table
  output reports "1 error" (the crash itself), and the JSON output reports no
  findings at all. Before reading the count, confirm that the output does not
  contain `Result is incomplete`.
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

Re-measure the baseline whenever the content itself changes. Editing a test
node invalidates its figure, and the resulting difference reads as a
regression when it is not.

These metrics catch a variable that disappears. They do not catch a class that
is renamed or an element that is replaced, because neither changes the counts.
For those, see "Checking templates after a base theme or core update".

### Exercise the authenticated path, properly

Several classes of fault appear only for authenticated users: the toolbar is
not rendered for anonymous visitors, and anonymous requests are served from
the page cache without reaching most code.

Setting the current user and then calling the HTTP kernel does **not**
produce an authenticated request:

```php
\Drupal::currentUser()->setAccount($account);
\Drupal::service('http_kernel')->handle($request);   // still anonymous
```

The authentication middleware re-evaluates the request and resets it. The
check passes and proves nothing.

Use a real session instead: take a one-time login URL from `drush uli`,
fetch it with `curl` to capture the cookie, then request a page with that
cookie. A healthy result is a full page with the expected number of
stylesheet links. A page of roughly two thousand bytes with none is the
signature of a stranded render context frame.

### Deprecations surface only after the upgrade

Static analysis on the old core cannot report deprecations introduced by the
new one. Drupal 11.3 deprecated `theme_get_setting()` in favour of
`ThemeSettingsProvider::getSetting()`; that only becomes visible once core is
on 11.x. Expect the error count to rise after a major core upgrade, and treat
the new entries as the upgrade's output rather than as a regression.
