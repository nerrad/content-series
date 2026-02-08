/**
 * Series Navigation Block - Edit Component
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

import type {
	NavigationBlockAttributes,
	BlockContext,
	SeriesData,
	SeriesPost,
	SeriesOrder,
	WPPost,
} from '../../types';

interface EditProps {
	attributes: NavigationBlockAttributes;
	setAttributes: ( attrs: Partial< NavigationBlockAttributes > ) => void;
	context: BlockContext;
}

interface ArrowStyle {
	prev: string;
	next: string;
}

export const ARROW_STYLES: Record< string, ArrowStyle > = {
	arrow: { prev: '\u2190', next: '\u2192' }, // ← →
	chevron: { prev: '\u2039', next: '\u203A' }, // ‹ ›
	none: { prev: '', next: '' },
};

interface NavigationViewStateArgs {
	isLoading: boolean;
	series?: number[];
	error: string | null;
	prev: SeriesPost | null;
	next: SeriesPost | null;
}

export function getNavigationPosts(
	seriesData: SeriesData | null,
	postId?: number
): {
	prev: SeriesPost | null;
	next: SeriesPost | null;
} {
	if ( ! seriesData || ! seriesData.posts || ! postId ) {
		return { prev: null, next: null };
	}

	const currentIndex = seriesData.posts.findIndex( ( p ) => p.id === postId );

	if ( currentIndex === -1 ) {
		return { prev: null, next: null };
	}

	return {
		prev: currentIndex > 0 ? seriesData.posts[ currentIndex - 1 ] : null,
		next:
			currentIndex < seriesData.posts.length - 1
				? seriesData.posts[ currentIndex + 1 ]
				: null,
	};
}

export function getNavigationViewState( {
	isLoading,
	series,
	error,
	prev,
	next,
}: NavigationViewStateArgs ):
	| 'loading'
	| 'no-series'
	| 'error'
	| 'no-navigation'
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

	if ( ! prev && ! next ) {
		return 'no-navigation';
	}

	return 'ready';
}

export function getArrowStyle(
	arrowStyle: NavigationBlockAttributes['arrowStyle']
): ArrowStyle {
	return ARROW_STYLES[ arrowStyle ?? 'arrow' ] || ARROW_STYLES.arrow;
}

export default function Edit( {
	attributes,
	setAttributes,
	context,
}: EditProps ): JSX.Element {
	const {
		showTitle,
		showSeriesName,
		showPartNumbers,
		prevLabel,
		nextLabel,
		arrowStyle,
	} = attributes;

	const postId = context.postId;
	const [ seriesData, setSeriesData ] = useState< SeriesData | null >( null );
	const [ isLoading, setIsLoading ] = useState< boolean >( true );
	const [ error, setError ] = useState< string | null >( null );

	// Get the post's series
	const { series } = useSelect(
		( select ): { series: number[]; seriesOrder: SeriesOrder } => {
			if ( ! postId ) {
				return { series: [], seriesOrder: {} };
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
				return { series: [], seriesOrder: {} };
			}

			return {
				series: post.series,
				seriesOrder: post.series_order || {},
			};
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
		className: 'wp-block-content-series-navigation',
	} );

	const { prev, next } = getNavigationPosts( seriesData, postId );
	const viewState = getNavigationViewState( {
		isLoading,
		series,
		error,
		prev,
		next,
	} );
	const arrows = getArrowStyle( arrowStyle );

	// Loading state
	if ( viewState === 'loading' ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="leftright"
					label={ __( 'Series Navigation', 'content-series' ) }
				>
					<Spinner />
				</Placeholder>
			</div>
		);
	}

	// No series
	if ( viewState === 'no-series' ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="leftright"
					label={ __( 'Series Navigation', 'content-series' ) }
					instructions={ __(
						'This post is not part of a series. Add it to a series in the sidebar to display navigation.',
						'content-series'
					) }
				/>
			</div>
		);
	}

	// Error state
	if ( viewState === 'error' ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="warning"
					label={ __( 'Series Navigation', 'content-series' ) }
					instructions={ error ?? undefined }
				/>
			</div>
		);
	}

	// No navigation needed (only post in series or not found)
	if ( viewState === 'no-navigation' ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="leftright"
					label={ __( 'Series Navigation', 'content-series' ) }
					instructions={ __(
						'This is the only post in the series, or the post was not found in the series order.',
						'content-series'
					) }
				/>
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display Settings', 'content-series' ) }>
					<ToggleControl
						label={ __( 'Show post title', 'content-series' ) }
						checked={ showTitle ?? true }
						onChange={ ( value: boolean ) =>
							setAttributes( { showTitle: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show series name', 'content-series' ) }
						checked={ showSeriesName ?? false }
						onChange={ ( value: boolean ) =>
							setAttributes( { showSeriesName: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show part numbers', 'content-series' ) }
						checked={ showPartNumbers ?? true }
						onChange={ ( value: boolean ) =>
							setAttributes( { showPartNumbers: value } )
						}
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Arrow style', 'content-series' ) }
						value={ arrowStyle ?? 'arrow' }
						options={ [
							{
								label: __( 'Arrows (← →)', 'content-series' ),
								value: 'arrow',
							},
							{
								label: __( 'Chevrons (‹ ›)', 'content-series' ),
								value: 'chevron',
							},
							{
								label: __( 'None', 'content-series' ),
								value: 'none',
							},
						] }
						onChange={ ( value: string ) =>
							setAttributes( {
								arrowStyle:
									value as NavigationBlockAttributes[ 'arrowStyle' ],
							} )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<PanelBody title={ __( 'Labels', 'content-series' ) }>
					<TextControl
						label={ __( 'Previous label', 'content-series' ) }
						value={ prevLabel ?? 'Previous' }
						onChange={ ( value: string ) =>
							setAttributes( { prevLabel: value } )
						}
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Next label', 'content-series' ) }
						value={ nextLabel ?? 'Next' }
						onChange={ ( value: string ) =>
							setAttributes( { nextLabel: value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<nav { ...blockProps }>
				<div className="wp-block-content-series-navigation__prev">
					{ prev ? (
						<a href={ prev.url }>
							{ arrows.prev && (
								<span className="wp-block-content-series-navigation__arrow">
									{ arrows.prev }
								</span>
							) }
							<span className="wp-block-content-series-navigation__text">
								<span className="wp-block-content-series-navigation__label">
									{ prevLabel ?? 'Previous' }
									{ showPartNumbers &&
										` (Part ${ prev.series_part })` }
								</span>
								{ showTitle && (
									<span className="wp-block-content-series-navigation__title">
										{ prev.title }
									</span>
								) }
							</span>
						</a>
					) : (
						<span className="wp-block-content-series-navigation__placeholder">
							&nbsp;
						</span>
					) }
				</div>

				{ showSeriesName && seriesData && (
					<div className="wp-block-content-series-navigation__series">
						<a href="#">{ seriesData.series.name }</a>
					</div>
				) }

				<div className="wp-block-content-series-navigation__next">
					{ next ? (
						<a href={ next.url }>
							<span className="wp-block-content-series-navigation__text">
								<span className="wp-block-content-series-navigation__label">
									{ nextLabel ?? 'Next' }
									{ showPartNumbers &&
										` (Part ${ next.series_part })` }
								</span>
								{ showTitle && (
									<span className="wp-block-content-series-navigation__title">
										{ next.title }
									</span>
								) }
							</span>
							{ arrows.next && (
								<span className="wp-block-content-series-navigation__arrow">
									{ arrows.next }
								</span>
							) }
						</a>
					) : (
						<span className="wp-block-content-series-navigation__placeholder">
							&nbsp;
						</span>
					) }
				</div>
			</nav>
		</>
	);
}
