=== Basic Responsive Controls ===
Contributors: philhoyt
Tags: block editor, typography, responsive, font size, gutenberg
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sunset: migrates this plugin's tablet and mobile typography settings to WordPress 7.1's own responsive styles so the plugin can be removed

== Description ==

This plugin is sunset. WordPress 7.1 added responsive style states to every core block support. In the editor, open the Preview dropdown, turn on "Responsive styles", and switch to Tablet or Mobile; the standard Typography panel and the Align text toolbar button then write values for that viewport only. Core uses the same 782px and 480px breakpoints this plugin hardcoded, and themes can change them in theme.json under `settings.viewport`.

Each control this plugin added to the Heading and Paragraph blocks has a core equivalent:

* Font size (tablet, mobile): Typography panel > Font size, with Responsive styles on.
* Line height: Typography panel > Line height.
* Letter spacing: Typography panel > Letter spacing.
* Text alignment: the Align text toolbar button.

No further features will be added. Versions 1.1.0 and later exist to move sites off the plugin.

= What the plugin does on WordPress 7.1 or later =

* Its own tablet and mobile controls are not loaded, so core's responsive controls are not hidden.
* A notice on the Dashboard and Plugins screens counts the posts that still carry this plugin's attributes and offers a Migrate now button.
* The migration rewrites those attributes into core's responsive styles (`style["@tablet"]` and `style["@mobile"]`) in posts, pages, custom post types, synced patterns, templates, and template parts. A core value that is already set is kept. A tablet-only value is also copied to mobile, because the old tablet rule applied to phones too.
* Until a post is migrated, its old values keep rendering on the front end as in 1.0.0.
* When nothing is left to migrate, the notice says the plugin is safe to deactivate.

= What it does on WordPress 6.3 to 7.0 =

Everything works as in 1.0.0. Upgrade WordPress to 7.1, then migrate and deactivate.

= How to migrate =

1. Back up the database. The migration rewrites post content.
2. Update WordPress to 7.1 or later and update this plugin to 1.1.0 or later.
3. Go to Dashboard or Plugins and click "Migrate N posts now" in the notice. Each run stops after about 20 seconds and reports what is left, so a large site may need more than one click.
4. When the notice reports zero posts remaining, deactivate and delete the plugin.

Migration is one-way. Migrated content renders responsively on WordPress 7.1 or later only, and it follows the theme's `settings.viewport` breakpoints if the theme defines its own. The migration needs the `unfiltered_html` capability, so on multisite a super admin has to run it.

== Installation ==

1. Download `basic-responsive-controls.zip` from the [GitHub releases page](https://github.com/philhoyt/Basic-Responsive-Controls/releases).
2. Go to Plugins > Add New Plugin > Upload Plugin and upload the zip.
3. Activate from the Plugins screen.
4. On WordPress 7.1 or later, follow the migration notice on the Dashboard.

Updates are delivered from GitHub releases through Plugin Update Checker.

== Frequently Asked Questions ==

= Do I need this plugin on a new site? =

No. Use the Responsive styles built into WordPress 7.1.

= What happens if I deactivate before migrating? =

The old attributes stay in the post content but nothing renders them, so tablet and mobile overrides disappear from the front end. Reactivate, migrate, then deactivate.

= Which breakpoints did the old plugin use? =

Tablet `max-width: 782px` and mobile `max-width: 480px`. WordPress 7.1 defaults to the same values, written as `width <= 480px` for mobile and `480px < width <= 782px` for tablet.

= How were font sizes output before migration? =

A `<style>` tag prepended to the block, with media query rules scoped to a per-block `phbrc-{id}` class. After migration, WordPress writes the rules itself through the Style Engine.

= Can I still add responsive controls to other blocks? =

Not with this plugin. WordPress 7.1's responsive styles apply to every block that uses core block supports.

== Changelog ==

= 1.1.1 =
* Fixed update checks failing with a missing class: the update checker's Markdown and readme parsers now ship with the plugin.

= 1.1.0 =
* Sunset release. On WordPress 7.1 or later the plugin's tablet and mobile editor controls are no longer loaded.
* Added a one-click migration that rewrites the plugin's attributes into WordPress's responsive style states.
* Added Dashboard and Plugins screen notices that run the migration and report when the plugin is safe to deactivate.
* Added the `ph_brc_legacy_ui_enabled` filter and the `ph_brc_migrated_post` action.
* Rewrote the documentation to cover the sunset and the migration.
* Changed update delivery to GitHub releases.

= 1.0.0 =
* Initial release.
