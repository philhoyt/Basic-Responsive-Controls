# PH Basic Responsive Controls

A WordPress plugin that adds breakpoint-specific font size controls to the block editor. When previewing in Tablet or Mobile mode, a dedicated font size picker replaces the default control in the Typography panel.

On the frontend, scoped CSS is injected inline with the block output using media queries. No separate stylesheet required.

## Features

- Set independent font sizes for tablet (max-width: 782px) and mobile (max-width: 480px).
- Controls live in the Typography panel, visible only in Tablet or Mobile preview mode.
- Supports all theme font size presets and custom CSS values.
- Per-block scoped output — each block gets its own class and style rule.
- Reset to theme default per breakpoint at any time.
- No external JavaScript dependencies.

## Supported Blocks

- `core/heading` — Font Size

## Requirements

- WordPress 6.3+
- PHP 7.4+

## Installation

1. Download the latest `ph-basic-responsive-controls.zip` from the [releases page](https://github.com/philhoyt/ph-basic-responsive-controls/releases).
2. Go to **Plugins > Add New Plugin > Upload Plugin** and upload the zip file.
3. Activate through the Plugins screen.
4. Add a Heading block to any post or page, switch to Tablet or Mobile preview mode, and open the Typography panel in the block inspector.

## How It Works

**Editor:** An HOC wraps `core/heading`'s edit component. In Tablet or Mobile preview mode, it hides the core font size control via JavaScript and renders a replacement picker bound to `tabletFontSize` or `mobileFontSize` block attributes. A live preview is applied to the block element in the editor canvas via inline style.

**Frontend:** A `render_block` filter adds a unique scoping class (`phbrc-{id}`) to the block element and prepends a `<style>` tag containing media query rules. Font size slugs are resolved to CSS custom properties (`var(--wp--preset--font-size--{slug})`); raw values pass through as-is.

## Adding Support for Additional Blocks

**JavaScript** — add an entry to `src/config.js`:
```js
export const RESPONSIVE_BLOCKS = {
    'core/heading': { controls: [ 'fontSize' ] },
    'core/paragraph': { controls: [ 'fontSize' ] }, // example
};
```

**PHP** — add a matching entry to the `$responsive_blocks` array in `includes/render.php`.

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
npm run plugin-zip  # Package
```

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).