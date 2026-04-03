import { FontSizePicker, useSettings } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';

/**
 * Font size control using the theme's registered font size presets.
 * Appearance matches the core font size control.
 *
 * @param {Object}   props
 * @param {string}   props.label    Breakpoint label shown in the reset button ("Tablet" / "Mobile").
 * @param {string}   props.value    Current font size value (slug or raw CSS).
 * @param {Function} props.onChange Called with the new value when changed, or undefined to reset.
 */
export function FontSizeControl( { label, value, onChange } ) {
	const [ fontSizes ] = useSettings( 'typography.fontSizes' );

	return (
		<div className="ph-brc-font-size-control">
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
