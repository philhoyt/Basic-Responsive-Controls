import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import { useEffect, useRef } from '@wordpress/element';

import { RESPONSIVE_BLOCKS } from './config';
import { useDeviceType } from './hooks/useDeviceType';
import { useBlockId } from './hooks/useBlockId';
import { FontSizeControl } from './components/FontSizeControl';

import './editor.css';

/**
 * Resolve a font size value against theme presets.
 * Returns var(--wp--preset--font-size--{slug}) for known slugs,
 * or the raw value as-is for custom CSS values.
 *
 * @param {string} value     Font size slug or raw CSS value.
 * @param {Array}  fontSizes Theme font size presets from useSettings.
 * @return {string} Resolved CSS value.
 */
function resolveFontSize( value, fontSizes = [] ) {
	const match = fontSizes.find( ( preset ) => preset.slug === value );
	if ( match ) {
		return `var(--wp--preset--font-size--${ match.slug })`;
	}
	return value;
}

/**
 * Register responsive attributes on each block defined in RESPONSIVE_BLOCKS.
 */
addFilter(
	'blocks.registerBlockType',
	'ph/responsive-controls-attributes',
	( settings, name ) => {
		if ( ! RESPONSIVE_BLOCKS[ name ] ) {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				blockId: {
					type: 'string',
					default: '',
				},
				tabletFontSize: {
					type: 'string',
					default: '',
				},
				mobileFontSize: {
					type: 'string',
					default: '',
				},
			},
		};
	}
);

/**
 * Inner component for withResponsiveControls.
 *
 * Extracted from the HOC so all hooks are called unconditionally,
 * satisfying the Rules of Hooks. The HOC wrapper guards the block name
 * check before rendering this component.
 *
 * @param {Object}   props           Block edit props plus the wrapped BlockEdit component.
 * @param {Function} props.BlockEdit The original BlockEdit component.
 */
function ResponsiveControlsEdit( { BlockEdit, ...props } ) {
	const { attributes, setAttributes, clientId } = props;
	const { blockId, tabletFontSize, mobileFontSize } = attributes;

	const deviceType = useDeviceType();
	const resolvedBlockId = useBlockId( {
		blockId,
		clientId,
		setAttributes,
	} );

	// useSettings returns stable references via useSelect — the array reference
	// only changes when theme font size settings actually change.
	const [ fontSizes ] = useSettings( 'typography.fontSizes' );

	// Stores the block element's original inline font-size before we override
	// it, so we can restore it exactly when switching back to Desktop.
	const originalFontSizeRef = useRef( null );

	// Hide core Typography panel items in tablet/mobile via JS.
	// CSS-based hiding (.is-tablet-preview selector) does not work in WP 7.0
	// because .is-tablet-preview is on the canvas iframe container, not an
	// ancestor of the inspector sidebar in the outer document.
	//
	// A MutationObserver re-applies hiding whenever WordPress remounts the
	// ToolsPanelItem elements (which clears our inline styles), fixing the
	// intermittent flash where items briefly reappear after a device switch.
	// The observer only fires applyHiding when ToolsPanelItem nodes are actually
	// added or removed, avoiding unnecessary work on unrelated DOM mutations.
	useEffect( () => {
		const isResponsive = deviceType !== 'Desktop';

		const applyHiding = () => {
			document
				.querySelectorAll(
					'.typography-block-support-panel .components-tools-panel-item'
				)
				.forEach( ( el ) => {
					el.style.display = isResponsive ? 'none' : '';
				} );
		};

		applyHiding();

		const panel = document.querySelector(
			'.typography-block-support-panel'
		);
		if ( ! panel ) {
			return;
		}

		const observer = new MutationObserver( ( mutations ) => {
			const relevant = mutations.some( ( m ) =>
				[ ...m.addedNodes, ...m.removedNodes ].some( ( n ) =>
					n.classList?.contains( 'components-tools-panel-item' )
				)
			);
			if ( relevant ) {
				applyHiding();
			}
		} );
		observer.observe( panel, { childList: true, subtree: true } );

		return () => {
			observer.disconnect();
			document
				.querySelectorAll(
					'.typography-block-support-panel .components-tools-panel-item'
				)
				.forEach( ( el ) => el.style.removeProperty( 'display' ) );
		};
	}, [ deviceType ] );

	// Apply/clear inline font size on the block element in the editor canvas.
	// The block canvas runs inside an iframe as of WordPress 6.3.
	//
	// The editor applies the desktop font size as a React-controlled inline
	// style. Calling removeProperty() clears it but React never re-applies it
	// because from React's perspective nothing changed. Instead we save the
	// original value in a ref before our first override and restore it exactly
	// when switching back to Desktop.
	useEffect( () => {
		if ( ! resolvedBlockId ) {
			return;
		}

		let fontSize = null;

		if ( deviceType === 'Tablet' && tabletFontSize ) {
			fontSize = resolveFontSize( tabletFontSize, fontSizes );
		} else if ( deviceType === 'Mobile' && mobileFontSize ) {
			fontSize = resolveFontSize( mobileFontSize, fontSizes );
		}

		// Locate the block element — first try the iframe canvas, then
		// fall back to the outer document for non-iframe editor setups.
		// NOTE: 'iframe[name="editor-canvas"]' is the WordPress 6.3+ selector.
		const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
		const doc = iframe?.contentDocument ?? document;
		const blockEl = doc.querySelector( `[data-block="${ clientId }"]` );

		if ( ! blockEl ) {
			return;
		}

		if ( fontSize ) {
			// Capture the desktop inline style before our first override.
			if ( originalFontSizeRef.current === null ) {
				originalFontSizeRef.current = blockEl.style.fontSize;
			}
			blockEl.style.fontSize = fontSize;
		} else if ( originalFontSizeRef.current !== null ) {
			// Restore what React had set rather than just removing the property.
			blockEl.style.fontSize = originalFontSizeRef.current;
			originalFontSizeRef.current = null;
		} else {
			blockEl.style.removeProperty( 'font-size' );
		}
	}, [
		clientId,
		deviceType,
		fontSizes,
		mobileFontSize,
		resolvedBlockId,
		tabletFontSize,
	] );

	return (
		<>
			<BlockEdit { ...props } />
			<InspectorControls group="typography">
				{ deviceType === 'Tablet' && (
					<FontSizeControl
						label="Tablet"
						value={ tabletFontSize }
						onChange={ ( value ) =>
							setAttributes( { tabletFontSize: value ?? '' } )
						}
					/>
				) }
				{ deviceType === 'Mobile' && (
					<FontSizeControl
						label="Mobile"
						value={ mobileFontSize }
						onChange={ ( value ) =>
							setAttributes( { mobileFontSize: value ?? '' } )
						}
					/>
				) }
			</InspectorControls>
		</>
	);
}

/**
 * HOC that injects responsive typography controls into the block inspector
 * and applies live preview styles in the editor canvas.
 *
 * This wrapper only checks the block name and delegates to ResponsiveControlsEdit
 * so that all hooks are always called unconditionally (Rules of Hooks).
 */
const withResponsiveControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( ! RESPONSIVE_BLOCKS[ props.name ] ) {
			return <BlockEdit { ...props } />;
		}
		return <ResponsiveControlsEdit BlockEdit={ BlockEdit } { ...props } />;
	};
}, 'withResponsiveControls' );

addFilter(
	'editor.BlockEdit',
	'ph/responsive-controls',
	withResponsiveControls
);
