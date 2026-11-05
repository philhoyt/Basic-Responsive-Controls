=== Basic Responsive Controls ===
Contributors: philhoyt
Tags: block editor, typography, responsive, font size, gutenberg
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sunset. WordPress 7.1 provides responsive typography natively. This final release migrates existing content to core and then gets out of the way.

== Description ==

**This plugin is sunset.** WordPress 7.1 added responsive style states to every core block support. Open the Preview dropdown in the editor, turn on "Responsive styles", switch to Tablet or Mobile, and the standard Typography panel and text alignment toolbar write values for that viewport only. Core uses the same 782px and 480px breakpoints this plugin hardcoded, and themes can change them in theme.json under `settings.viewport`.

Every control this plugin added now has a native equivalent on the Heading and Paragraph blocks:

* Font size (tablet, mobile) → Typography panel > Font size, with Responsive styles on.
* Line height → Typography panel > Line height.
* Letter spacing → Typography panel > Letter spacing.
* Text alignment → Align text toolbar button.

No further features will be added. Version 1.1.0 exists to move sites off the plugin cleanly.

= What 1.1.0 does =

**On WordPress 7.1 or later**

* The plugin's tablet and mobile controls are no longer loaded, so core's own responsive controls are not hidden.
* A notice on the Dashboard and Plugins screens counts the posts that still carry this plugin's attributes and offers a **Migrate now** button.
* The migration rewrites those attributes into WordPress's own responsive styles (`style["@tablet"]` and `style["@mobile"]`), including synced patterns, templates, and template parts. Existing core values are never overwritten. A tablet-only value is also copied to mobile so the page renders exactly as before, because the old tablet rule applied to phones too.
* Until a post is migrated, its legacy values keep rendering on the front end exactly as in 1.0.0.
* When nothing is left to migrate, the notice says the plugin is safe to deactivate.

**On WordPress 6.3 to 7.0**

Everything works as in 1.0.0. Upgrade WordPress to 7.1, then migrate and deactivate.

= How to migrate =

1. Back up the database. The migration rewrites post content.
2. Update WordPress to 7.1 or later and update this plugin to 1.1.0.
3. Go to Dashboard or Plugins and click **Migrate N posts now** in the notice. Large sites may need to click more than once; each run stops after about 20 seconds and reports what is left.
4. When the notice reports zero posts remaining, deactivate and delete the plugin.

Migration is one-way. Migrated content renders responsively only on WordPress 7.1 or later, and it follows the theme's `settings.viewport` breakpoints if the theme defines its own. On multisite the migration must be run by a super admin.

== Installation ==

1. Download `basic-responsive-controls.zip` from the [GitHub releases page](https://github.com/philhoyt/Basic-Responsive-Controls/releases).
2. Go to **Plugins > Add New Plugin > Upload Plugin** and upload the zip file.
3. Activate through the Plugins screen.
4. On WordPress 7.1 or later, follow the migration notice on the Dashboard.

== Frequently Asked Questions ==

= Do I need this plugin on a new site? =

No. Use WordPress 7.1's built-in Responsive styles instead.

= What happens if I deactivate before migrating? =

The legacy attributes stay in the post content but nothing renders them, so tablet and mobile overrides disappear from the front end. Reactivate, migrate, then deactivate.

= Which breakpoints did the old plugin use? =

Tablet `max-width: 782px` and mobile `max-width: 480px`. WordPress 7.1 defaults to the same values, expressed as `width <= 480px` for mobile and `480px < width <= 782px` for tablet.

= How were font sizes output before migration? =

A `<style>` tag prepended to the block with media query rules scoped to a per-block `phbrc-{id}` class. After migration, WordPress emits the rules itself through the Style Engine.

= Can I still add responsive controls to other blocks? =

Not with this plugin. WordPress 7.1's responsive styles already apply to every block that uses core block supports.

== Changelog ==

= 1.1.0 =
* Sunset release. On WordPress 7.1+, the legacy tablet/mobile editor controls are no longer loaded.
* Added a one-click migration that rewrites legacy attributes into WordPress's native responsive style states.
* Added Dashboard and Plugins screen notices to run the migration and confirm when the plugin is safe to deactivate.
* Added `ph_brc_legacy_ui_enabled` filter and `ph_brc_migrated_post` action.
* Documentation rewritten to explain the sunset and migration.

= 1.0.0 =
* Initial release.
