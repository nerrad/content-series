/**
 * Series Post List Block - Edit Component
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

import type { BlockAttributes, BlockContext, SeriesData, WPPost } from '../../types';

interface EditProps {
	attributes: BlockAttributes;
	setAttributes: ( attrs: Partial< BlockAttributes > ) => void;
	context: BlockContext;
}

export default function Edit( { attributes, setAttributes, context }: EditProps ): JSX.Element {
	const {
		showNumbers,
		showShortTitle,
		highlightCurrent,
		showSeriesTitle,
		showSeriesIcon,
	} = attributes;

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
				getEntityRecord: ( kind: string, name: string, id: number ) => WPPost | undefined;
			};

			const post = coreSelectors.getEntityRecord( 'postType', 'post', postId );

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

	// Loading state
	if ( isLoading ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="list-view"
					label={ __( 'Series Post List', 'content-series' ) }
				>
					<Spinner />
				</Placeholder>
			</div>
		);
	}

	// No series
	if ( ! series || series.length === 0 ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="list-view"
					label={ __( 'Series Post List', 'content-series' ) }
					instructions={ __(
						'This post is not part of a series. Add it to a series in the sidebar to display the series post list.',
						'content-series'
					) }
				/>
			</div>
		);
	}

	// Error state
	if ( error ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="warning"
					label={ __( 'Series Post List', 'content-series' ) }
					instructions={ error }
				/>
			</div>
		);
	}

	// No data yet
	if ( ! seriesData ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="list-view"
					label={ __( 'Series Post List', 'content-series' ) }
				>
					<Spinner />
				</Placeholder>
			</div>
		);
	}

	const ListTag = showNumbers ? 'ol' : 'ul';

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display Settings', 'content-series' ) }>
					<ToggleControl
						label={ __( 'Show series title', 'content-series' ) }
						checked={ showSeriesTitle ?? true }
						onChange={ ( value: boolean ) =>
							setAttributes( { showSeriesTitle: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show series icon', 'content-series' ) }
						checked={ showSeriesIcon ?? true }
						onChange={ ( value: boolean ) =>
							setAttributes( { showSeriesIcon: value } )
						}
						__nextHasNoMarginBottom
					/>
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
				{ showSeriesTitle && (
					<div className="wp-block-content-series-post-list__header">
						{ showSeriesIcon && seriesData.series.meta?.series_icon && (
							<img
								src={ seriesData.series.meta.series_icon }
								alt=""
								className="wp-block-content-series-post-list__icon"
							/>
						) }
						<h3 className="wp-block-content-series-post-list__title">
							{ seriesData.series.name }
						</h3>
					</div>
				) }

				<ListTag className="wp-block-content-series-post-list__items">
					{ seriesData.posts.map( ( post ) => {
						const isCurrent = post.id === postId;
						const title = showShortTitle && post.short_title
							? post.short_title
							: post.title;

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
			</div>
		</>
	);
}
