/**
 * Series Panel Component
 *
 * Adds a panel to the post editor sidebar for managing series assignments.
 */

import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as noticesStore } from '@wordpress/notices';
import { __, sprintf } from '@wordpress/i18n';
import {
	FormTokenField,
	TextControl,
	PanelRow,
	Spinner,
} from '@wordpress/components';
import { useState, useMemo, useRef, useEffect } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';

import type { SeriesOrder, WPTerm } from '../types';

const TAXONOMY = 'series';

// Stable empty references to avoid new array/object creation on each render
const EMPTY_TERMS: WPTerm[] = [];
const EMPTY_IDS: number[] = [];
const EMPTY_ORDER: SeriesOrder = {};

// Query constants for consistent use in fetch and isResolving checks
const ALL_SERIES_QUERY = {
	per_page: 100,
	orderby: 'name',
	order: 'asc',
} as const;

const SEARCH_QUERY_BASE = {
	per_page: 20,
} as const;

// TokenItem interface from FormTokenField
interface TokenItem {
	value: string;
	status?: 'error' | 'validating' | 'success';
	title?: string;
	isBorderless?: boolean;
}

interface EditorSelectReturn {
	postType: string | undefined;
	currentSeriesIds: number[];
	shortTitle: string;
	seriesOrder: SeriesOrder;
	isSaving: boolean;
	canAssignTerms: boolean;
	canCreateTerms: boolean;
}

interface CoreSelectReturn {
	allSeries: WPTerm[];
	searchResults: WPTerm[];
	currentSeriesTerms: WPTerm[];
	isLoading: boolean;
}

export default function SeriesPanel(): JSX.Element | null {
	const [ search, setSearch ] = useState< string >( '' );
	const [ isCreating, setIsCreating ] = useState< boolean >( false );

	// Track mounted state to prevent state updates after unmount
	const isMountedRef = useRef< boolean >( true );
	useEffect( () => {
		isMountedRef.current = true;
		return () => {
			isMountedRef.current = false;
		};
	}, [] );

	// ALL useDispatch calls first (must be before any conditional returns)
	const { editPost } = useDispatch( editorStore ) as {
		editPost: ( edits: Record< string, unknown > ) => void;
	};

	const { saveEntityRecord } = useDispatch( coreStore ) as {
		saveEntityRecord: (
			kind: string,
			name: string,
			record: Record< string, unknown >
		) => Promise< WPTerm >;
	};

	const { createErrorNotice } = useDispatch( noticesStore ) as {
		createErrorNotice: (
			message: string,
			options?: { type?: string }
		) => void;
	};

	// useDebounce (must be before any conditional returns)
	const debouncedSearch = useDebounce( setSearch, 300 );

	// Get post data
	const {
		postType,
		currentSeriesIds,
		shortTitle,
		seriesOrder,
		isSaving,
		canAssignTerms,
		canCreateTerms,
	} = useSelect( ( select ): EditorSelectReturn => {
		const editorSelectors = select( editorStore ) as {
			getCurrentPostType: () => string | undefined;
			getEditedPostAttribute: ( attr: string ) => unknown;
			getCurrentPost: () => {
				_links?: Record< string, unknown[] >;
			};
			isSavingPost: () => boolean;
		};

		const type = editorSelectors.getCurrentPostType();

		// Only support posts - return stable empty references for non-posts
		if ( type !== 'post' ) {
			return {
				postType: type,
				currentSeriesIds: EMPTY_IDS,
				shortTitle: '',
				seriesOrder: EMPTY_ORDER,
				isSaving: false,
				canAssignTerms: false,
				canCreateTerms: false,
			};
		}

		const post = editorSelectors.getCurrentPost();
		const links = post?._links || {};

		// Check capabilities from post _links
		const hasAssignAction = !! links[ 'wp:action-assign-series' ];
		const hasCreateAction = !! links[ 'wp:action-create-series' ];

		const meta = editorSelectors.getEditedPostAttribute( 'meta' ) as
			| Record< string, unknown >
			| undefined;

		const rawIds = editorSelectors.getEditedPostAttribute( TAXONOMY ) as
			| number[]
			| undefined;

		const rawOrder = editorSelectors.getEditedPostAttribute(
			'series_order'
		) as SeriesOrder | undefined;

		return {
			postType: type,
			currentSeriesIds: rawIds && rawIds.length > 0 ? rawIds : EMPTY_IDS,
			shortTitle: ( meta?._spost_short_title as string ) || '',
			seriesOrder:
				rawOrder && Object.keys( rawOrder ).length > 0
					? rawOrder
					: EMPTY_ORDER,
			isSaving: editorSelectors.isSavingPost(),
			canAssignTerms: hasAssignAction,
			canCreateTerms: hasCreateAction,
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

	// Build search query
	const searchQuery = useMemo(
		() => ( search ? { ...SEARCH_QUERY_BASE, search } : null ),
		[ search ]
	);

	// Get all series and search results (must be before conditional return)
	const { allSeries, searchResults, currentSeriesTerms, isLoading } =
		useSelect(
			( select ): CoreSelectReturn => {
				// Return empty data for non-post types
				if ( postType !== 'post' ) {
					return {
						allSeries: EMPTY_TERMS,
						searchResults: EMPTY_TERMS,
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

				// Get all series for suggestions
				const all =
					coreSelectors.getEntityRecords(
						'taxonomy',
						TAXONOMY,
						ALL_SERIES_QUERY
					) || EMPTY_TERMS;

				// Get search results if searching
				const results = searchQuery
					? coreSelectors.getEntityRecords(
							'taxonomy',
							TAXONOMY,
							searchQuery
					  ) || EMPTY_TERMS
					: EMPTY_TERMS;

				// Get current series terms
				const current = currentSeriesQuery
					? coreSelectors.getEntityRecords(
							'taxonomy',
							TAXONOMY,
							currentSeriesQuery
					  ) || EMPTY_TERMS
					: EMPTY_TERMS;

				// Check loading state using the same query objects
				const isLoadingAll = coreSelectors.isResolving(
					'getEntityRecords',
					[ 'taxonomy', TAXONOMY, ALL_SERIES_QUERY ]
				);

				const isLoadingCurrent =
					currentSeriesQuery &&
					coreSelectors.isResolving( 'getEntityRecords', [
						'taxonomy',
						TAXONOMY,
						currentSeriesQuery,
					] );

				return {
					allSeries: all,
					searchResults: results,
					currentSeriesTerms: current,
					isLoading: isLoadingAll || !! isLoadingCurrent,
				};
			},
			[ postType, searchQuery, currentSeriesQuery ]
		);

	// Build suggestions from all series and search results
	const suggestions = useMemo( (): string[] => {
		const combined = search ? searchResults : allSeries;
		return combined.map( ( term ) => term.name );
	}, [ allSeries, searchResults, search ] );

	// Current values as names
	const currentValues = useMemo( (): string[] => {
		return currentSeriesTerms.map( ( term ) => term.name );
	}, [ currentSeriesTerms ] );

	// Don't render for non-post types (AFTER all hooks)
	if ( postType !== 'post' ) {
		return null;
	}

	// Handle series selection change
	const handleSeriesChange = ( tokens: ( string | TokenItem )[] ): void => {
		// Extract string names from tokens
		const newNames = tokens.map( ( token ) =>
			typeof token === 'string' ? token : token.value
		);

		// Capture current state to avoid stale closures
		const currentAllSeries = allSeries;
		const currentSeriesOrder = seriesOrder;

		// Process series changes asynchronously
		const processChanges = async (): Promise< void > => {
			const termIds: number[] = [];
			const newSeriesOrder: SeriesOrder = { ...currentSeriesOrder };
			const termsToCreate: string[] = [];

			// First pass: identify existing terms and collect terms to create
			for ( const name of newNames ) {
				const existingTerm = currentAllSeries.find(
					( t ) => t.name.toLowerCase() === name.toLowerCase()
				);

				if ( existingTerm ) {
					termIds.push( existingTerm.id );
					if ( ! newSeriesOrder[ existingTerm.id ] ) {
						newSeriesOrder[ existingTerm.id ] = 1;
					}
				} else {
					termsToCreate.push( name );
				}
			}

			// Create new terms in parallel
			if ( termsToCreate.length > 0 ) {
				if ( isMountedRef.current ) {
					setIsCreating( true );
				}

				try {
					const createPromises = termsToCreate.map( ( name ) =>
						saveEntityRecord( 'taxonomy', TAXONOMY, { name } )
					);

					const results = await Promise.allSettled( createPromises );

					results.forEach( ( result, index ) => {
						if (
							result.status === 'fulfilled' &&
							result.value?.id
						) {
							termIds.push( result.value.id );
							newSeriesOrder[ result.value.id ] = 1;
						} else if ( result.status === 'rejected' ) {
							const errorMessage =
								result.reason instanceof Error
									? result.reason.message
									: __( 'Unknown error', 'content-series' );
							const message = sprintf(
								/* translators: %1$s: series name, %2$s: error message */
								__(
									'Failed to create series "%1$s": %2$s',
									'content-series'
								),
								termsToCreate[ index ],
								errorMessage
							);
							createErrorNotice( message, { type: 'snackbar' } );
						}
					} );
				} finally {
					if ( isMountedRef.current ) {
						setIsCreating( false );
					}
				}
			}

			// Remove order for removed series
			Object.keys( newSeriesOrder ).forEach( ( id ) => {
				if ( ! termIds.includes( parseInt( id, 10 ) ) ) {
					delete newSeriesOrder[ parseInt( id, 10 ) ];
				}
			} );

			editPost( {
				[ TAXONOMY ]: termIds,
				series_order: newSeriesOrder,
			} );
		};

		// Fire and forget - errors are handled within processChanges
		void processChanges();
	};

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

	// Show message if user can't assign terms
	if ( ! canAssignTerms ) {
		return (
			<PluginDocumentSettingPanel
				name="content-series"
				title={ __( 'Series', 'content-series' ) }
				className="content-series-panel"
			>
				<p>
					{ __(
						'You do not have permission to assign series.',
						'content-series'
					) }
				</p>
			</PluginDocumentSettingPanel>
		);
	}

	if ( isLoading ) {
		return (
			<PluginDocumentSettingPanel
				name="content-series"
				title={ __( 'Series', 'content-series' ) }
				className="content-series-panel"
			>
				<Spinner />
			</PluginDocumentSettingPanel>
		);
	}

	return (
		<PluginDocumentSettingPanel
			name="content-series"
			title={ __( 'Series', 'content-series' ) }
			className="content-series-panel"
		>
			<PanelRow>
				<div style={ { width: '100%' } }>
					<FormTokenField
						label={ __( 'Series', 'content-series' ) }
						value={ currentValues }
						suggestions={ suggestions }
						onInputChange={ debouncedSearch }
						onChange={ handleSeriesChange }
						__experimentalExpandOnFocus
						__experimentalShowHowTo={ false }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						disabled={ isSaving || isCreating }
						placeholder={
							canCreateTerms
								? __(
										'Search or create series…',
										'content-series'
								  )
								: __( 'Search series…', 'content-series' )
						}
					/>
					{ isCreating && (
						<p className="content-series-panel__creating">
							<Spinner />
							{ __( 'Creating series…', 'content-series' ) }
						</p>
					) }
				</div>
			</PanelRow>

			{ currentSeriesTerms.length > 0 && (
				<>
					<hr />
					<p className="content-series-panel__order-heading">
						<strong>
							{ __( 'Order in Series', 'content-series' ) }
						</strong>
					</p>

					{ currentSeriesTerms.map( ( term ) => (
						<PanelRow key={ term.id }>
							<TextControl
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
						</PanelRow>
					) ) }

					<hr />

					<PanelRow>
						<TextControl
							label={ __(
								'Short Title (optional)',
								'content-series'
							) }
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
					</PanelRow>
				</>
			) }
		</PluginDocumentSettingPanel>
	);
}
