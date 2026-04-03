import { useEffect } from '@wordpress/element';

/**
 * Ensures blockId is set to clientId on mount. Returns the resolved blockId.
 *
 * @param {Object} props
 * @param {string} props.blockId      Current blockId attribute value.
 * @param {string} props.clientId     Block's clientId from the editor.
 * @param {Function} props.setAttributes Block's setAttributes function.
 * @return {string} Resolved blockId.
 */
export function useBlockId( { blockId, clientId, setAttributes } ) {
	useEffect( () => {
		if ( ! blockId ) {
			setAttributes( { blockId: clientId } );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- intentionally mount-only

	return blockId || clientId;
}
