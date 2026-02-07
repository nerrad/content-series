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
import { useMemo, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import type { SeriesData, SeriesOrder, WPTerm } from '../types';

const TAXONOMY = 'series';

// Stable empty references to avoid new array/object creation on each render
const EMPTY_TERMS: WPTerm[] = [];
const EMPTY_IDS: number[] = [];
const EMPTY_ORDER: SeriesOrder = {};

interface EditorSelectReturn {
	currentSeriesIds: number[];
	hasResolvedSeriesIds: boolean;
	shortTitle: string;
	seriesOrder: SeriesOrder;
	isSaving: boolean;
	postType: string;
	postStatus?: string;
	isNewPost: boolean;
	canAssignTerms: boolean;
}

interface CoreSelectReturn {
	currentSeriesTerms: WPTerm[];
	isLoading: boolean;
}

export default function SeriesExtensionFields(): JSX.Element | null {
	const { editPost } = useDispatch( editorStore ) as {
		editPost: ( edits: Record< string, unknown > ) => void;
	};
	const hasInitializedSeriesRef = useRef( false );
	const previousSeriesIdsRef = useRef< number[] >( [] );
	const pendingSeriesIdsRef = useRef< number[] >( [] );

	// Get post data from editor store
	const {
		currentSeriesIds,
		hasResolvedSeriesIds,
		shortTitle,
		seriesOrder,
		isSaving,
		postType,
		postStatus,
		isNewPost,
		canAssignTerms,
	} = useSelect( ( select ): EditorSelectReturn => {
		const editorSelectors = select( editorStore ) as {
			getEditedPostAttribute: ( attr: string ) => unknown;
			isSavingPost: () => boolean;
			getCurrentPostType: () => string;
			isEditedPostNew?: () => boolean;
			getCurrentPost: () => {
				_links?: Record< string, unknown >;
			};
		};

		const meta = editorSelectors.getEditedPostAttribute( 'meta' ) as
			| Record< string, unknown >
			| undefined;

		const rawIds = editorSelectors.getEditedPostAttribute( TAXONOMY ) as
			| number[]
			| undefined;

		const rawOrder = editorSelectors.getEditedPostAttribute(
			'series_order'
		) as SeriesOrder | undefined;

		const currentPost = editorSelectors.getCurrentPost();
		const hasAssignLink = Boolean(
			currentPost?._links?.[ 'wp:action-assign-series' ]
		);

		return {
			currentSeriesIds: rawIds && rawIds.length > 0 ? rawIds : EMPTY_IDS,
			hasResolvedSeriesIds: rawIds !== undefined,
			shortTitle: ( meta?._spost_short_title as string ) || '',
			seriesOrder:
				rawOrder && Object.keys( rawOrder ).length > 0
					? rawOrder
					: EMPTY_ORDER,
			isSaving: editorSelectors.isSavingPost(),
			postType: editorSelectors.getCurrentPostType(),
			postStatus: editorSelectors.getEditedPostAttribute( 'status' ) as
				| string
				| undefined,
			isNewPost: editorSelectors.isEditedPostNew?.() ?? false,
			canAssignTerms: hasAssignLink,
		};
	}, [] );

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

	const shouldSuggestNextPart =
		isNewPost || postStatus === 'draft' || postStatus === 'auto-draft';

	const getSuggestedOrder = useCallback(
		async ( seriesId: number ): Promise< number > => {
			try {
				const data = await apiFetch< SeriesData >( {
					path: `/content-series/v1/series/${ seriesId }/posts`,
				} );

				if ( ! data.posts || data.posts.length === 0 ) {
					return 1;
				}

				const highestPart = data.posts.reduce(
					( highest, post ) =>
						Math.max( highest, post.series_part || 1 ),
					0
				);

				return highestPart + 1;
			} catch {
				return 1;
			}
		},
		[]
	);

	// Initialize series_order when series terms are newly added to this post.
	useEffect( () => {
		if ( ! hasResolvedSeriesIds ) {
			return;
		}

		if ( ! hasInitializedSeriesRef.current ) {
			previousSeriesIdsRef.current = currentSeriesIds;
			hasInitializedSeriesRef.current = true;
			return;
		}

		const previousSeriesIds = previousSeriesIdsRef.current;
		const addedIds = currentSeriesIds.filter(
			( id ) => ! previousSeriesIds.includes( id )
		);
		const removedIds = previousSeriesIds.filter(
			( id ) => ! currentSeriesIds.includes( id )
		);
		previousSeriesIdsRef.current = currentSeriesIds;

		if ( addedIds.length > 0 || removedIds.length > 0 ) {
			const pendingSet = new Set( pendingSeriesIdsRef.current );

			removedIds.forEach( ( id ) => pendingSet.delete( id ) );
			addedIds.forEach( ( id ) => pendingSet.add( id ) );

			pendingSeriesIdsRef.current = Array.from( pendingSet );
		}

		if ( ! isNewPost && ! postStatus ) {
			return;
		}

		const pendingIds = pendingSeriesIdsRef.current.filter( ( id ) =>
			currentSeriesIds.includes( id )
		);
		if ( pendingIds.length === 0 ) {
			return;
		}

		let isCancelled = false;

		const initializeSeriesOrder = async (): Promise< void > => {
			const newOrder = { ...seriesOrder };
			let hasChanges = false;

			if ( shouldSuggestNextPart ) {
				const suggestions = await Promise.all(
					pendingIds.map( async ( id ) => ( {
						id,
						order: await getSuggestedOrder( id ),
					} ) )
				);

				if ( isCancelled ) {
					return;
				}

				suggestions.forEach( ( { id, order } ) => {
					if ( newOrder[ id ] !== order ) {
						newOrder[ id ] = order;
						hasChanges = true;
					}
				} );
			} else {
				pendingIds.forEach( ( id ) => {
					if ( ! ( id in newOrder ) ) {
						newOrder[ id ] = 1;
						hasChanges = true;
					}
				} );
			}

			if ( hasChanges ) {
				editPost( { series_order: newOrder } );
			}

			pendingSeriesIdsRef.current = pendingSeriesIdsRef.current.filter(
				( id ) => ! pendingIds.includes( id )
			);
		};

		initializeSeriesOrder();

		return () => {
			isCancelled = true;
		};
	}, [
		currentSeriesIds,
		editPost,
		getSuggestedOrder,
		hasResolvedSeriesIds,
		isNewPost,
		postStatus,
		seriesOrder,
		shouldSuggestNextPart,
	] );

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

	// Only render for posts
	if ( postType !== 'post' ) {
		return null;
	}

	// Don't render if user can't assign series terms
	if ( ! canAssignTerms ) {
		return null;
	}

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
