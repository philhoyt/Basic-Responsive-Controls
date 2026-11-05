# Basic Responsive Controls

[![CI](https://github.com/philhoyt/Basic-Responsive-Controls/actions/workflows/ci.yml/badge.svg)](https://github.com/philhoyt/Basic-Responsive-Controls/actions/workflows/ci.yml)

A sunset WordPress plugin: it migrates the tablet and mobile typography settings it used to add to the Heading and Paragraph blocks into WordPress 7.1's own responsive styles, so it can then be deactivated.

## Requirements

- WordPress 6.3 or later; 7.1 or later for the migration
- PHP 7.4 or later

## Installation

1. Download `basic-responsive-controls.zip` from the [releases page](https://github.com/philhoyt/Basic-Responsive-Controls/releases).
2. Go to Plugins > Add New Plugin > Upload Plugin and upload the zip.
3. Activate from the Plugins screen.
4. On WordPress 7.1 or later, follow the migration notice on the Dashboard.

Updates are offered from GitHub releases through Plugin Update Checker. Only releases with a `.zip` asset are offered.

## Usage

### Why the plugin is sunset

WordPress 7.1 added responsive style states to every core block support. In the editor, open the Preview dropdown, turn on Responsive styles, and switch to Tablet or Mobile. The standard Typography panel and the Align text toolbar button then write values for that viewport only, stored as `style["@tablet"]` and `style["@mobile"]` on the block. Core uses the same 782px and 480px breakpoints this plugin hardcoded, and themes can change them in `theme.json` under `settings.viewport`.

| This plugin (1.0.0) | WordPress 7.1 |
|---|---|
| Font Size [Tablet] / [Mobile] | Typography > Font size, with Responsive styles on |
| Line Height [Tablet] / [Mobile] | Typography > Line height |
| Letter Spacing [Tablet] / [Mobile] | Typography > Letter spacing |
| Align text [Tablet] / [Mobile] | Align text toolbar button |
| `@media (max-width: 782px)` | `@media (480px < width <= 782px)` |
| `@media (max-width: 480px)` | `@media (width <= 480px)` |

On 7.1 the 1.0.0 controls also got in the way: while previewing Tablet or Mobile the plugin hid core's Typography panel items and Align text dropdown, which are the controls core now uses for responsive editing.

Reference: the [WordPress 7.1 responsive block styles dev note](https://make.wordpress.org/core/2026/08/05/responsive-block-styles-and-configurable-viewports-in-wordpress-7-1/).

### On WordPress 7.1 or later

- The plugin's tablet and mobile editor controls are not loaded.
- A notice on the Dashboard and Plugins screens counts posts that still carry the plugin's attributes and offers a Migrate now button.
- The migration rewrites those attributes into core's responsive style states. It covers posts, pages, custom post types, synced patterns, templates, and template parts, and recurses into nested blocks. A core value that is already set is kept. A tablet-only value is also copied to mobile, because the old tablet rule applied at `max-width: 782px`, which included phones.
- Until a post is migrated, its old values keep rendering on the front end as in 1.0.0.
- When nothing is left to migrate, the notice says the plugin is safe to deactivate.

### On WordPress 6.3 to 7.0

Everything works as in 1.0.0. Upgrade WordPress to 7.1, then migrate and deactivate.

### How to migrate

1. Back up the database. The migration rewrites post content.
2. Update WordPress to 7.1 or later and this plugin to 1.1.0 or later.
3. Go to Dashboard or Plugins and click "Migrate N posts now" in the notice. Each run stops after about 20 seconds and reports what is left, so a large site may need more than one click.
4. When the notice reports zero posts remaining, deactivate and delete the plugin.

Migration is one-way. Migrated content renders responsively on WordPress 7.1 or later only, and it follows the theme's `settings.viewport` breakpoints if the theme defines its own. The migration handler requires `manage_options` and `unfiltered_html`, so content is written back without KSES filtering; on multisite that means a super admin has to run it.

### Migration mapping

For each of `fontSize`, `lineHeight`, `letterSpacing`, and `textAlign` on `core/heading` and `core/paragraph` (a blank cell means not set):

| Existing core tablet | Existing core mobile | Legacy tablet | Legacy mobile | Result |
|---|---|---|---|---|
| | | T | | `@tablet` = T, `@mobile` = T |
| | | T | M | `@tablet` = T, `@mobile` = M |
| | | | M | `@mobile` = M |
| CT | | T | | `@tablet` = CT, `@mobile` = T |
| | CM | T | | `@tablet` = T, `@mobile` = CM |
| CT | CM | any | any | unchanged |

Font size preset slugs become `var:preset|font-size|{slug}`; raw CSS values are copied as they are. After conversion, `blockId` and all eight legacy attributes are removed from the block.

### Hooks

- `ph_brc_legacy_ui_enabled` (filter, `bool`): force the legacy editor UI on or off. Defaults to true on WordPress below 7.1.
- `ph_brc_migrated_post` (action, `int $post_id, int $blocks_changed`): fires after a post's content has been rewritten.

### Legacy behaviour (WordPress below 7.1)

The editor wraps the Heading and Paragraph edit components. In Tablet or Mobile preview mode it renders breakpoint-specific font size, line height, and letter spacing controls in the Typography panel and a text alignment picker in the toolbar, and applies the values as inline styles on the canvas. On the front end a `render_block` filter adds a scoping class (`phbrc-{id}`) and prepends a `<style>` tag with media query rules; preset slugs resolve to `var(--wp--preset--font-size--{slug})`.

Legacy attributes on each supported block: `blockId`, `tabletFontSize`, `mobileFontSize`, `tabletTextAlign`, `mobileTextAlign`, `tabletLineHeight`, `mobileLineHeight`, `tabletLetterSpacing`, `mobileLetterSpacing`. They stay registered on every WordPress version so unmigrated content is not stripped on re-save.

## Development

```bash
npm install
composer install    # also copies Plugin Update Checker into lib/ via the copy-puc script
```

```bash
npm start           # webpack watch
npm run build       # production build into build/
npm run lint:js
npm run lint:css
composer phpcs      # PHP lint, WordPress Coding Standards
composer phpcbf     # PHP auto-fix
composer test       # PHPUnit, tests/MigrateBlockTest.php (migration transform, no WordPress install needed)
npm run plugin-zip  # builds basic-responsive-controls.zip
```

There are no JavaScript tests. `tests/fixtures/legacy-post-content.html` holds 1.0.0 content for checking the migration by hand. CI (`.github/workflows/ci.yml`) runs `composer audit`, phpcs, and PHPUnit on PHP 8.2 through 8.4, then lints and builds the JavaScript on Node 22; PHPUnit 11 and the `composer.lock` platform pin need PHP 8.2, so CI does not cover the 7.4 to 8.1 range the plugin header allows.

## Releases

Bump the version in `basic-responsive-controls.php`, `package.json`, and the `Stable tag` in `readme.txt`, add the changelog entry to `readme.txt`, then push a lowercase `v` tag:

```bash
git tag v1.1.1 && git push origin v1.1.1
```

The release workflow (`.github/workflows/release.yml`) runs `npm ci`, builds, runs `npm run plugin-zip`, pulls the matching `= X.Y.Z =` section out of `readme.txt` as the release notes, and attaches `basic-responsive-controls.zip` and `readme.txt` to the GitHub release. The tag must use a lowercase `v`: Plugin Update Checker strips the prefix case-sensitively, so a `V1.2.0` tag is never offered as an update.

## Limitations

- No new features. The plugin exists to migrate content and then be removed.
- Migration is one-way and rewrites post content; back up first.
- Migrated content is responsive only on WordPress 7.1 or later.
- The migration needs `unfiltered_html`, which on multisite only super admins have.
- Only `core/heading` and `core/paragraph` were ever supported.

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
