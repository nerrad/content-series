/**
 * Series Navigation Wrapper Block - Edit Component
 */

import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export const NAVIGATION_ALLOWED_BLOCKS: string[] = [
	'content-series/navigation-link',
	'content-series/series-title',
	'content-series/series-icon',
];

export const NAVIGATION_TEMPLATE: Array< unknown[] > = [
	[
		'content-series/navigation-link',
		{
			direction: 'previous',
		},
	],
	[ 'content-series/series-title', {} ],
	[
		'content-series/navigation-link',
		{
			direction: 'next',
		},
	],
];

export function getNavigationTemplate(): Array< unknown[] > {
	// Return a fresh structure so block editor template mutations do not leak.
	return NAVIGATION_TEMPLATE.map( ( block ) => [
		block[ 0 ],
		{ ...( block[ 1 ] as Record< string, unknown > ) },
	] );
}

export default function Edit(): JSX.Element {
	const blockProps = useBlockProps( {
		className: 'wp-block-content-series-navigation',
	} );

	return (
		<nav { ...blockProps }>
			<InnerBlocks
				allowedBlocks={ NAVIGATION_ALLOWED_BLOCKS }
				template={ getNavigationTemplate() }
				templateLock={ false }
				orientation="horizontal"
			/>
		</nav>
	);
}
