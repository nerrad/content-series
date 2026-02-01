/**
 * Series Extension Fields Component
 *
 * Renders additional fields (order in series, short title) below the default
 * taxonomy term selector. Used via the editor.PostTaxonomyType filter.
 */

import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { TextControl, Spinner } from '@wordpress/components';
import { useMemo } from '@wordpress/element';

import type { SeriesOrder, WPTerm } from '../types';

const TAXONOMY = 'series';

// Stable empty references to avoid new array/object creation on each render
const EMPTY_TERMS: WPTerm[] = [];
const EMPTY_IDS: number[] = [];
const EMPTY_ORDER: SeriesOrder = {};

interface EditorSelectReturn {
	currentSeriesIds: number[];
	shortTitle: string;
	seriesOrder: SeriesOrder;
	isSaving: boolean;
}

interface CoreSelectReturn {
	currentSeriesTerms: WPTerm[];
	isLoading: boolean;
}

export default function SeriesExtensionFields(): JSX.Element | null {
	const { editPost } = useDispatch( editorStore ) as {
		editPost: ( edits: Record< string, unknown > ) => void;
	};

	// Get post data from editor store
	const { currentSeriesIds, shortTitle, seriesOrder, isSaving } = useSelect(
		( select ): EditorSelectReturn => {
			const editorSelectors = select( editorStore ) as {
				getEditedPostAttribute: ( attr: string ) => unknown;
				isSavingPost: () => boolean;
			};

			const meta = editorSelectors.getEditedPostAttribute( 'meta' ) as
				| Record< string, unknown >
				| undefined;

			const rawIds = editorSelectors.getEditedPostAttribute(
				TAXONOMY
			) as number[] | undefined;

			const rawOrder = editorSelectors.getEditedPostAttribute(
				'series_order'
			) as SeriesOrder | undefined;

			return {
				currentSeriesIds:
					rawIds && rawIds.length > 0 ? rawIds : EMPTY_IDS,
				shortTitle: ( meta?._spost_short_title as string ) || '',
				seriesOrder:
					rawOrder && Object.keys( rawOrder ).length > 0
						? rawOrder
						: EMPTY_ORDER,
				isSaving: editorSelectors.isSavingPost(),
			};
		},
		[]
	);

	// Build query for current series terms
	const currentSeriesQuery = useMemo(
		() =>
			currentSeriesIds.length > 0
				? { include: currentSeriesIds, per_page: 100 }
				: null,
		[ currentSeriesIds ]
	);

	// Get current series terms from core store
	const { currentSeriesTerms, isLoading } = useSelect(
		( select ): CoreSelectReturn => {
			if ( ! currentSeriesQuery ) {
				return {
					currentSeriesTerms: EMPTY_TERMS,
					isLoading: false,
				};
			}

			const coreSelectors = select( coreStore ) as {
				getEntityRecords: (
					kind: string,
					name: string,
					query?: Record< string, unknown >
				) => WPTerm[] | null;
				isResolving: (
					selectorName: string,
					args: unknown[]
				) => boolean;
			};

			const current =
				coreSelectors.getEntityRecords(
					'taxonomy',
					TAXONOMY,
					currentSeriesQuery
				) || EMPTY_TERMS;

			const loading = coreSelectors.isResolving( 'getEntityRecords', [
				'taxonomy',
				TAXONOMY,
				currentSeriesQuery,
			] );

			return {
				currentSeriesTerms: current,
				isLoading: loading,
			};
		},
		[ currentSeriesQuery ]
	);

	// Handle order change for a specific series
	const handleOrderChange = ( seriesId: number, newOrder: string ): void => {
		const newSeriesOrder: SeriesOrder = {
			...seriesOrder,
			[ seriesId ]: parseInt( newOrder, 10 ) || 1,
		};
		editPost( { series_order: newSeriesOrder } );
	};

	// Handle short title change
	const handleShortTitleChange = ( value: string ): void => {
		editPost( {
			meta: { _spost_short_title: value },
		} );
	};

	// Don't render anything if no series are selected
	if ( currentSeriesIds.length === 0 ) {
		return null;
	}

	if ( isLoading ) {
		return (
			<div className="content-series-extension">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="content-series-extension">
			<div className="content-series-extension__order">
				<p className="content-series-extension__heading">
					<strong>
						{ __( 'Order in Series', 'content-series' ) }
					</strong>
				</p>

				{ currentSeriesTerms.map( ( term ) => (
					<TextControl
						key={ term.id }
						label={ term.name }
						type="number"
						min={ 1 }
						value={ String( seriesOrder[ term.id ] || 1 ) }
						onChange={ ( value: string ) =>
							handleOrderChange( term.id, value )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						disabled={ isSaving }
					/>
				) ) }
			</div>

			<TextControl
				label={ __( 'Short Title (optional)', 'content-series' ) }
				value={ shortTitle }
				onChange={ handleShortTitleChange }
				help={ __(
					'Displayed in series navigation and lists.',
					'content-series'
				) }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				disabled={ isSaving }
			/>
		</div>
	);
}
