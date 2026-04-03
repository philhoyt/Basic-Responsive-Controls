import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Returns the current device preview type: 'Desktop' | 'Tablet' | 'Mobile'.
 *
 * Uses optional chaining on getDeviceType() for compatibility with WP < 6.5
 * where the selector may not exist on the core/editor store.
 */
export function useDeviceType() {
	return useSelect(
		( select ) => select( editorStore ).getDeviceType?.() ?? 'Desktop'
	);
}
