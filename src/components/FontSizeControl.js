import { FontSizePicker, useSettings } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { useRef, useLayoutEffect } from '@wordpress/element';

/**
 * Font size control using the theme's registered font size presets.
 * Appearance matches the core font size control.
 *
 * FontSizePicker hardcodes "Font size" as its internal label with no prop to
 * override it, so we use a ref + useLayoutEffect to rewrite the text node
 * synchronously after each render — no visible flash.
 *
 * @param {Object}   props
 * @param {string}   props.label    Breakpoint label e.g. "Tablet" → "Font Size [Tablet]".
 * @param {string}   props.value    Current font size value (slug or raw CSS).
 * @param {Function} props.onChange Called with the new value when changed, or undefined to reset.
 */
export function FontSizeControl( { label, value, onChange } ) {
	const [ fontSizes ] = useSettings( 'typography.fontSizes' );
	const wrapperRef = useRef();

	useLayoutEffect( () => {
		if ( ! wrapperRef.current || ! label ) return;
		const labelEl = wrapperRef.current.querySelector(
			'.components-font-size-picker__header .components-base-control__label'
		);
		if ( labelEl ) {
			labelEl.textContent = `Font Size [${ label }]`;
		}
	} );

	return (
		<div ref={ wrapperRef } className="ph-brc-font-size-control">
			<FontSizePicker
				fontSizes={ fontSizes }
				value={ value }
				onChange={ onChange }
			/>
			{ value && (
				<Button
					variant="link"
					isDestructive
					onClick={ () => onChange( undefined ) }
				>
					{ label ? `Reset ${ label } to default` : 'Reset to default' }
				</Button>
			) }
		</div>
	);
}
