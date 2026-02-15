/**
 * Series Post List Block - Edit Component
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	InnerBlocks,
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

import type {
	BlockAttributes,
	BlockContext,
	SeriesData,
	SeriesPost,
	WPPost,
} from '../../types';

interface EditProps {
	attributes: BlockAttributes;
	setAttributes: ( attrs: Partial< BlockAttributes > ) => void;
	context: BlockContext;
}

interface PostListViewStateArgs {
	isLoading: boolean;
	series?: number[];
	error: string | null;
	seriesData: SeriesData | null;
}

export const POST_LIST_ALLOWED_BLOCKS: string[] = [
	'content-series/series-icon',
	'content-series/series-title',
];

export const POST_LIST_TEMPLATE: Array< unknown[] > = [
	[
		'content-series/series-icon',
		{
			isLink: true,
			size: 60,
		},
	],
	[
		'content-series/series-title',
		{
			isLink: true,
			level: 3,
		},
	],
];

export function getPostListTemplate(): Array< unknown[] > {
	return POST_LIST_TEMPLATE.map( ( block ) => [
		block[ 0 ],
		{ ...( block[ 1 ] as Record< string, unknown > ) },
	] );
}

export function getPostListViewState( {
	isLoading,
	series,
	error,
	seriesData,
}: PostListViewStateArgs ):
	| 'loading'
	| 'no-series'
	| 'error'
	| 'no-data'
	| 'ready' {
	if ( isLoading ) {
		return 'loading';
	}

	if ( ! series || series.length === 0 ) {
		return 'no-series';
	}

	if ( error ) {
		return 'error';
	}

	if ( ! seriesData ) {
		return 'no-data';
	}

	return 'ready';
}

export function getListTag( showNumbers?: boolean ): 'ol' | 'ul' {
	return showNumbers ? 'ol' : 'ul';
}

export function getPostDisplayTitle(
	post: SeriesPost,
	showShortTitle?: boolean
): string {
	if ( showShortTitle && post.short_title ) {
		return post.short_title;
	}

	return post.title;
}

export default function Edit( {
	attributes,
	setAttributes,
	context,
}: EditProps ): JSX.Element {
	const { showNumbers, showShortTitle, highlightCurrent } = attributes;

	const postId = context.postId;
	const [ seriesData, setSeriesData ] = useState< SeriesData | null >( null );
	const [ isLoading, setIsLoading ] = useState< boolean >( true );
	const [ error, setError ] = useState< string | null >( null );

	// Get the post's series
	const { series } = useSelect(
		( select ): { series: number[] } => {
			if ( ! postId ) {
				return { series: [] };
			}

			const coreSelectors = select( coreStore ) as {
				getEntityRecord: (
					kind: string,
					name: string,
					id: number
				) => WPPost | undefined;
			};

			const post = coreSelectors.getEntityRecord(
				'postType',
				'post',
				postId
			);

			if ( ! post || ! post.series || post.series.length === 0 ) {
				return { series: [] };
			}

			return { series: post.series };
		},
		[ postId ]
	);

	// Fetch series posts when we have a series
	useEffect( () => {
		if ( ! series || series.length === 0 ) {
			setIsLoading( false );
			setSeriesData( null );
			return;
		}

		const fetchSeriesData = async (): Promise< void > => {
			setIsLoading( true );
			setError( null );

			try {
				const data = await apiFetch< SeriesData >( {
					path: `/content-series/v1/series/${ series[ 0 ] }/posts`,
				} );
				setSeriesData( data );
			} catch ( err ) {
				setError( ( err as Error ).message );
			}

			setIsLoading( false );
		};

		fetchSeriesData();
	}, [ series ] );

	const blockProps = useBlockProps( {
		className: 'wp-block-content-series-post-list',
	} );
	const viewState = getPostListViewState( {
		isLoading,
		series,
		error,
		seriesData,
	} );

	const ListTag = getListTag( showNumbers );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display Settings', 'content-series' ) }>
					<ToggleControl
						label={ __( 'Show post numbers', 'content-series' ) }
						checked={ showNumbers ?? true }
						onChange={ ( value: boolean ) =>
							setAttributes( { showNumbers: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Use short titles', 'content-series' ) }
						checked={ showShortTitle ?? false }
						onChange={ ( value: boolean ) =>
							setAttributes( { showShortTitle: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __(
							'Highlight current post',
							'content-series'
						) }
						checked={ highlightCurrent ?? true }
						onChange={ ( value: boolean ) =>
							setAttributes( { highlightCurrent: value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="wp-block-content-series-post-list__header">
					<InnerBlocks
						allowedBlocks={ POST_LIST_ALLOWED_BLOCKS }
						template={ getPostListTemplate() }
						templateLock={ false }
						orientation="horizontal"
					/>
				</div>

				{ ( viewState === 'loading' || viewState === 'no-data' ) && (
					<div className="wp-block-content-series-post-list__notice">
						<Spinner />
					</div>
				) }

				{ viewState === 'no-series' && (
					<Notice
						className="wp-block-content-series-post-list__notice"
						status="info"
						isDismissible={ false }
					>
						{ __(
							'This post is not part of a series. Add it to a series to display the post list.',
							'content-series'
						) }
					</Notice>
				) }

				{ viewState === 'error' && (
					<Notice
						className="wp-block-content-series-post-list__notice"
						status="error"
						isDismissible={ false }
					>
						{ error }
					</Notice>
				) }

				{ viewState === 'ready' && seriesData && (
					<ListTag className="wp-block-content-series-post-list__items">
						{ seriesData.posts.map( ( post ) => {
							const isCurrent = post.id === postId;
							const title = getPostDisplayTitle(
								post,
								showShortTitle
							);

							return (
								<li
									key={ post.id }
									className={
										isCurrent && highlightCurrent
											? 'is-current'
											: ''
									}
								>
									{ isCurrent ? (
										<span>{ title }</span>
									) : (
										<a href={ post.url }>{ title }</a>
									) }
								</li>
							);
						} ) }
					</ListTag>
				) }
			</div>
		</>
	);
}
