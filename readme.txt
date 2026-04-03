=== Basic Responsive Controls ===
Contributors: philhoyt
Tags: block editor, typography, responsive, font size, gutenberg
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Responsive typography controls for the WordPress block editor. Set different font sizes for tablet and mobile breakpoints, per block.

== Description ==

Basic Responsive Controls adds breakpoint-specific font size controls to the block editor. When previewing in Tablet or Mobile mode, a dedicated font size picker replaces the default control in the Typography panel.

On the frontend, scoped CSS is injected inline with the block output using media queries. Responsive font sizes work without any additional stylesheet or configuration.

* Set a font size for tablet (max-width: 782px) and mobile (max-width: 480px) independently.
* Controls appear in the Typography panel, only visible when in Tablet or Mobile preview mode.
* Supports all theme font size presets as well as custom values.
* Scoped per-block — each heading gets its own style rule, no global overrides.
* Reset to theme default per breakpoint at any time.
* No JavaScript dependencies beyond what WordPress already loads.

Supported blocks: Heading (`core/heading`).

== Installation ==

1. Download the latest `basic-responsive-controls.zip` from the [GitHub releases page](https://github.com/philhoyt/Basic-Responsive-Controls/releases).
2. Go to **Plugins > Add New Plugin > Upload Plugin** and upload the zip file.
3. Activate through the Plugins screen.
4. Edit any post or page, add a Heading block, and switch to Tablet or Mobile preview mode in the editor toolbar to see the responsive font size controls.

== Frequently Asked Questions ==

= Which breakpoints are used? =

Tablet: `max-width: 782px`, which matches the WordPress admin breakpoint. Mobile: `max-width: 480px`.

= Does this affect desktop font sizes? =

No. The plugin only outputs styles for tablet and mobile. The desktop font size set via the core Typography control is untouched.

= How are font sizes output on the frontend? =

A `<style>` tag is prepended to the block's HTML output containing scoped media query rules. The block element receives a unique class (`phbrc-{id}`) used to scope the rules to that specific block instance.

= Does it work with theme font size presets? =

Yes. Preset slugs are resolved to CSS custom properties (e.g. `var(--wp--preset--font-size--large)`), so they respect any theme.json overrides. Raw CSS values such as `clamp()` or `rem` values are passed through as-is.

= Can I add responsive controls to other blocks? =

The architecture supports it. Adding a new block requires a code change to `src/config.js` and `includes/render.php`. A UI for managing supported blocks is not included in this version.

== Changelog ==

= 1.0.0 =
* Initial release.