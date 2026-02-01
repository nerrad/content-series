/**
 * Series Taxonomy Filter
 *
 * Higher-order component that extends the default taxonomy term selector
 * for the 'series' taxonomy with additional fields (order, short title).
 *
 * Used with the 'editor.PostTaxonomyType' filter hook.
 */

import type { ComponentType } from 'react';

import SeriesExtensionFields from './series-extension-fields';

interface TermSelectorProps {
	slug: string;
	[ key: string ]: unknown;
}

/**
 * Wraps the taxonomy term selector to add series-specific fields.
 *
 * @param OriginalComponent - The original term selector component
 * @return Wrapped component that adds extension fields for series taxonomy
 */
export function extendSeriesTermSelector(
	OriginalComponent: ComponentType< TermSelectorProps >
): ComponentType< TermSelectorProps > {
	return function SeriesTermSelectorWrapper(
		props: TermSelectorProps
	): JSX.Element {
		// Only extend the series taxonomy
		if ( props.slug !== 'series' ) {
			return <OriginalComponent { ...props } />;
		}

		return (
			<>
				<OriginalComponent { ...props } />
				<SeriesExtensionFields />
			</>
		);
	};
}
