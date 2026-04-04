import { LineHeightControl as WPLineHeightControl } from '@wordpress/block-editor';
import { useRef, useLayoutEffect } from '@wordpress/element';

/**
 * Line height control using WordPress's built-in LineHeightControl.
 * Appearance matches the core line height control.
 *
 * LineHeightControl hardcodes "Line height" as its label with no prop to
 * override it, so we use a ref + useLayoutEffect to rewrite the text node
 * synchronously after each render — no visible flash.
 *
 * @param {Object}   props
 * @param {string}   props.label    Breakpoint label e.g. "Tablet" → "Line Height [Tablet]".
 * @param {string}   props.value    Current line height value.
 * @param {Function} props.onChange Called with the new value when changed, or undefined to reset.
 */
export function LineHeightControl( { label, value, onChange } ) {
	const wrapperRef = useRef();

	useLayoutEffect( () => {
		if ( ! wrapperRef.current || ! label ) {
			return;
		}
		const labelEl = wrapperRef.current.querySelector(
			'.components-base-control__label'
		);
		if ( labelEl ) {
			labelEl.textContent = `Line Height [${ label }]`;
		}
	}, [ label ] );

	return (
		<div ref={ wrapperRef } className="ph-brc-line-height-control">
			<WPLineHeightControl
				value={ value }
				onChange={ onChange }
				__unstableInputWidth="100%"
			/>
		</div>
	);
}
