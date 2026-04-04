// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalLetterSpacingControl as WPLetterSpacingControl } from '@wordpress/block-editor';
import { useRef, useLayoutEffect } from '@wordpress/element';

/**
 * Letter spacing control using WordPress's built-in LetterSpacingControl.
 * Appearance matches the core letter spacing control.
 *
 * LetterSpacingControl hardcodes "Letter spacing" as its label with no prop
 * to override it, so we use a ref + useLayoutEffect to rewrite the text node
 * synchronously after each render — no visible flash.
 *
 * @param {Object}   props
 * @param {string}   props.label    Breakpoint label e.g. "Tablet" → "Letter Spacing [Tablet]".
 * @param {string}   props.value    Current letter spacing value (e.g. "0.1em").
 * @param {Function} props.onChange Called with the new value when changed, or undefined to reset.
 */
export function LetterSpacingControl( { label, value, onChange } ) {
	const wrapperRef = useRef();

	useLayoutEffect( () => {
		if ( ! wrapperRef.current || ! label ) {
			return;
		}
		const labelEl = wrapperRef.current.querySelector(
			'.components-base-control__label'
		);
		if ( labelEl ) {
			labelEl.textContent = `Letter Spacing [${ label }]`;
		}
	}, [ label ] );

	return (
		<div ref={ wrapperRef } className="ph-brc-letter-spacing-control">
			<WPLetterSpacingControl
				value={ value }
				onChange={ onChange }
				__unstableInputWidth="100%"
			/>
		</div>
	);
}
