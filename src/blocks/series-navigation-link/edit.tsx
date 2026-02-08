/**
 * Series Navigation Link Block - Edit Component
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
} from '@wordpress/components';

import type { NavigationLinkBlockAttributes } from '../../types';

interface EditProps {
	attributes: NavigationLinkBlockAttributes;
	setAttributes: ( attrs: Partial< NavigationLinkBlockAttributes > ) => void;
}

interface ArrowStyle {
	prev: string;
	next: string;
}

export const ARROW_STYLES: Record< string, ArrowStyle > = {
	arrow: { prev: '\u2190', next: '\u2192' },
	chevron: { prev: '\u2039', next: '\u203A' },
	none: { prev: '', next: '' },
};

export function getArrowStyle(
	arrowStyle: NavigationLinkBlockAttributes[ 'arrowStyle' ]
): ArrowStyle {
	return ARROW_STYLES[ arrowStyle ?? 'arrow' ] || ARROW_STYLES.arrow;
}

export default function Edit( {
	attributes,
	setAttributes,
}: EditProps ): JSX.Element {
	const {
		direction = 'previous',
		showTitle,
		showPartNumbers,
		label,
		arrowStyle,
	} = attributes;
	const isPrevious = direction === 'previous';
	const arrows = getArrowStyle( arrowStyle );
	let resolvedLabel = label ?? '';

	if ( ! resolvedLabel.trim() ) {
		resolvedLabel = isPrevious
			? __( 'Previous', 'content-series' )
			: __( 'Next', 'content-series' );
	}
	const blockProps = useBlockProps( {
		className: isPrevious
			? 'wp-block-content-series-navigation__prev'
			: 'wp-block-content-series-navigation__next',
	} );
	const samplePart = isPrevious ? 2 : 4;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Navigation Link', 'content-series' ) }>
					<SelectControl
						label={ __( 'Direction', 'content-series' ) }
						value={ direction }
						options={ [
							{
								label: __( 'Previous', 'content-series' ),
								value: 'previous',
							},
							{
								label: __( 'Next', 'content-series' ),
								value: 'next',
							},
						] }
						onChange={ ( value: string ) =>
							setAttributes( {
								direction:
									value as NavigationLinkBlockAttributes[ 'direction' ],
							} )
						}
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Label', 'content-series' ) }
						value={ label ?? '' }
						onChange={ ( value: string ) =>
							setAttributes( { label: value } )
						}
						placeholder={
							isPrevious
								? __( 'Previous', 'content-series' )
								: __( 'Next', 'content-series' )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show post title', 'content-series' ) }
						checked={ showTitle ?? true }
						onChange={ ( value: boolean ) =>
							setAttributes( { showTitle: value } )
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
									value as NavigationLinkBlockAttributes[ 'arrowStyle' ],
							} )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<a href="https://example.com">
					{ isPrevious && arrows.prev && (
						<span className="wp-block-content-series-navigation__arrow">
							{ arrows.prev }
						</span>
					) }
					<span className="wp-block-content-series-navigation__text">
						<span className="wp-block-content-series-navigation__label">
							{ resolvedLabel }
							{ ( showPartNumbers ?? true ) &&
								` (Part ${ samplePart })` }
						</span>
						{ ( showTitle ?? true ) && (
							<span className="wp-block-content-series-navigation__title">
								{ isPrevious
									? __(
											'Previous post title',
											'content-series'
									  )
									: __(
											'Next post title',
											'content-series'
									  ) }
							</span>
						) }
					</span>
					{ ! isPrevious && arrows.next && (
						<span className="wp-block-content-series-navigation__arrow">
							{ arrows.next }
						</span>
					) }
				</a>
			</div>
		</>
	);
}
