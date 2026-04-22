# Basic Responsive Controls

A WordPress plugin that adds breakpoint-specific typography controls to the block editor. When previewing in Tablet or Mobile mode, dedicated controls appear for font size, text alignment, line height, and letter spacing — independently set per breakpoint.

On the frontend, scoped inline CSS is injected with the block output using media queries. No separate stylesheet required.

## Features

- **Font size** — independent values for tablet (max-width: 782px) and mobile (max-width: 480px). Supports all theme font size presets and custom CSS values (`clamp()`, `rem`, `em`, `px`, etc.).
- **Text alignment** — per-breakpoint alignment (left, center, right, justify) via a toolbar control, visible only in Tablet or Mobile preview mode.
- **Line height** — per-breakpoint line height using WordPress's native line height control.
- **Letter spacing** — per-breakpoint letter spacing using WordPress's letter spacing control.
- **Reset controls** — reset any individual breakpoint value, or reset all responsive settings at once from Desktop mode.
- **Live editor preview** — changes are immediately reflected in the editor canvas.
- **Per-block scoped output** — each block gets its own class and style rule; no global stylesheet.
- No external JavaScript dependencies.

## Supported Blocks

| Block | Font Size | Text Align | Line Height | Letter Spacing |
|---|---|---|---|---|
| `core/heading` | ✓ | ✓ | ✓ | ✓ |
| `core/paragraph` | ✓ | ✓ | ✓ | ✓ |

## Requirements

- WordPress 6.3+
- PHP 7.4+

## Installation

1. Download the latest `basic-responsive-controls.zip` from the [releases page](https://github.com/philhoyt/Basic-Responsive-Controls/releases).
2. Go to **Plugins > Add New Plugin > Upload Plugin** and upload the zip file.
3. Activate through the Plugins screen.
4. Add a Heading or Paragraph block, switch to Tablet or Mobile preview mode, and open the Typography panel in the block inspector.

## How It Works

**Editor:** A Higher-Order Component wraps supported block edit components. In Tablet or Mobile preview mode it renders breakpoint-specific controls for font size, line height, and letter spacing in the Typography panel, and a text alignment picker in the block toolbar. Changes are applied as inline styles to the block element in the editor canvas for immediate preview.

**Frontend:** A `render_block` filter adds a unique scoping class (`phbrc-{id}`) to the block's outermost element and prepends a `<style>` tag containing media query rules. Font size presets are resolved to CSS custom properties (`var(--wp--preset--font-size--{slug})`); raw values pass through as-is. Mobile rules are output after tablet rules to ensure correct cascade override behavior.

## Block Attributes

Each supported block gains the following attributes:

| Attribute | Type | Description |
|---|---|---|
| `blockId` | string | Unique ID used for the scoping CSS class |
| `tabletFontSize` | string | Font size value or preset slug at ≤782px |
| `mobileFontSize` | string | Font size value or preset slug at ≤480px |
| `tabletTextAlign` | string | Text alignment at ≤782px |
| `mobileTextAlign` | string | Text alignment at ≤480px |
| `tabletLineHeight` | string | Line height value at ≤782px |
| `mobileLineHeight` | string | Line height value at ≤480px |
| `tabletLetterSpacing` | string | Letter spacing value at ≤782px |
| `mobileLetterSpacing` | string | Letter spacing value at ≤480px |

## Adding Support for Additional Blocks

**JavaScript** — add an entry to `src/config.js`:
```js
export const RESPONSIVE_BLOCKS = {
    'core/heading': { controls: [ 'fontSize' ] },
    'core/paragraph': { controls: [ 'fontSize' ] },
    'core/list-item': { controls: [ 'fontSize' ] }, // example
};
```

**PHP** — add a matching entry to the `$responsive_blocks` array in `includes/render.php`:
```php
$responsive_blocks = [ 'core/heading', 'core/paragraph', 'core/list-item' ];
```

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
npm run plugin-zip  # Package for distribution
```

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
