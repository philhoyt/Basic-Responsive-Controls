# Basic Responsive Controls

> **Sunset.** WordPress 7.1 provides responsive typography natively. Version 1.1.0 is the final feature release: it migrates existing content to core and then gets out of the way.

## Why this plugin is no longer needed

WordPress 7.1 added responsive style states to every core block support. In the editor, open the Preview dropdown, turn on **Responsive styles**, and switch to Tablet or Mobile. The standard Typography panel and the Align text toolbar button then write values for that viewport only, stored as `style["@tablet"]` and `style["@mobile"]` on the block. Core uses the same 782px and 480px breakpoints this plugin hardcoded, and themes can change them in `theme.json` under `settings.viewport`.

Every control this plugin added to the Heading and Paragraph blocks now has a native equivalent:

| This plugin (1.0.0) | WordPress 7.1 |
|---|---|
| Font Size [Tablet] / [Mobile] | Typography > Font size, with Responsive styles on |
| Line Height [Tablet] / [Mobile] | Typography > Line height |
| Letter Spacing [Tablet] / [Mobile] | Typography > Letter spacing |
| Align text [Tablet] / [Mobile] | Align text toolbar button |
| `@media (max-width: 782px)` | `@media (480px < width <= 782px)` |
| `@media (max-width: 480px)` | `@media (width <= 480px)` |

On 7.1 the old controls were worse than redundant: the plugin hid core's Typography panel items and Align text dropdown while previewing Tablet or Mobile, which are exactly the controls core now uses for responsive editing.

Further reading: the [WordPress 7.1 responsive block styles dev note](https://make.wordpress.org/core/2026/08/05/responsive-block-styles-and-configurable-viewports-in-wordpress-7-1/).

## What 1.1.0 does

**On WordPress 7.1 or later**

- The plugin's tablet and mobile editor controls are not loaded, so core's responsive controls are no longer hidden.
- A notice on the Dashboard and Plugins screens counts posts that still carry this plugin's attributes and offers a **Migrate now** button.
- The migration rewrites those attributes into core's responsive style states. It covers posts, pages, custom post types, synced patterns, templates, and template parts, and recurses into nested blocks. Existing core values are never overwritten. A tablet-only value is also copied to mobile because the old tablet rule applied at `max-width: 782px`, which included phones.
- Until a post is migrated, its legacy values keep rendering on the front end exactly as in 1.0.0.
- When nothing is left to migrate, the notice says the plugin is safe to deactivate.

**On WordPress 6.3 to 7.0**

Everything works as in 1.0.0. Upgrade WordPress to 7.1, then migrate and deactivate.

## How to migrate

1. Back up the database. The migration rewrites post content.
2. Update WordPress to 7.1 or later and update this plugin to 1.1.0.
3. Go to Dashboard or Plugins and click **Migrate N posts now** in the notice. Large sites may need more than one click; each run stops after about 20 seconds and reports what is left.
4. When the notice reports zero posts remaining, deactivate and delete the plugin.

Migration is one-way. Migrated content renders responsively only on WordPress 7.1 or later, and it follows the theme's `settings.viewport` breakpoints if the theme defines its own. On multisite the migration must be run by a super admin (it requires the `unfiltered_html` capability so content is written back without filtering).

### Migration mapping

For each of `fontSize`, `lineHeight`, `letterSpacing`, and `textAlign` on `core/heading` and `core/paragraph`:

| Existing core tablet | Existing core mobile | Legacy tablet | Legacy mobile | Result |
|---|---|---|---|---|
| — | — | T | — | `@tablet` = T, `@mobile` = T |
| — | — | T | M | `@tablet` = T, `@mobile` = M |
| — | — | — | M | `@mobile` = M |
| CT | — | T | — | `@tablet` = CT, `@mobile` = T |
| — | CM | T | — | `@tablet` = T, `@mobile` = CM |
| CT | CM | any | any | unchanged |

Font size preset slugs become `var:preset|font-size|{slug}`; raw CSS values are copied verbatim. After conversion, `blockId` and all eight legacy attributes are removed from the block.

## Hooks

- `ph_brc_legacy_ui_enabled` (filter, `bool`) — force the legacy editor UI on or off. Defaults to true on WordPress < 7.1.
- `ph_brc_migrated_post` (action, `int $post_id, int $blocks_changed`) — fires after a post's content was rewritten.

## Requirements

- WordPress 6.3+ (7.1+ for migration)
- PHP 7.4+

## Installation

1. Download `basic-responsive-controls.zip` from the [releases page](https://github.com/philhoyt/Basic-Responsive-Controls/releases).
2. Go to **Plugins > Add New Plugin > Upload Plugin** and upload the zip file.
3. Activate through the Plugins screen.
4. On WordPress 7.1 or later, follow the migration notice on the Dashboard.

## Legacy behaviour (WordPress < 7.1)

The editor wraps the Heading and Paragraph edit components. In Tablet or Mobile preview mode it renders breakpoint-specific font size, line height, and letter spacing controls in the Typography panel and a text alignment picker in the toolbar, applying the values as inline styles on the canvas. On the front end a `render_block` filter adds a scoping class (`phbrc-{id}`) and prepends a `<style>` tag with media query rules; preset slugs resolve to `var(--wp--preset--font-size--{slug})`.

Legacy attributes on each supported block: `blockId`, `tabletFontSize`, `mobileFontSize`, `tabletTextAlign`, `mobileTextAlign`, `tabletLineHeight`, `mobileLineHeight`, `tabletLetterSpacing`, `mobileLetterSpacing`. These stay registered on every WordPress version so unmigrated content is not stripped on re-save.

## Development

```bash
npm install
composer install

npm run build       # Production build
npm run start       # Watch mode
npm run lint:js     # Lint JavaScript
npm run lint:css    # Lint CSS
composer phpcs      # Lint PHP
composer phpcbf     # Auto-fix PHP
composer test       # PHPUnit tests for the migration transform
npm run plugin-zip  # Package for distribution
```

`tests/fixtures/legacy-post-content.html` holds representative 1.0.0 content for manual verification of the migration.

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
