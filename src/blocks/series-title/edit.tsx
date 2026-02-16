/**
 * Series Title Block - Edit Component
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

import type { SeriesTitleBlockAttributes } from '../../types';

interface EditProps {
	attributes: SeriesTitleBlockAttributes;
	setAttributes: ( attrs: Partial< SeriesTitleBlockAttributes > ) => void;
}

function getHeadingTag( level?: number ): keyof JSX.IntrinsicElements {
	const normalized = Math.max( 1, Math.min( 6, Math.round( level ?? 3 ) ) );
	return `h${ normalized }` as keyof JSX.IntrinsicElements;
}

export default function Edit( {
	attributes,
	setAttributes,
}: EditProps ): JSX.Element {
	const { isLink, level } = attributes;
	const HeadingTag = getHeadingTag( level );
	const blockProps = useBlockProps( {
		className:
			'wp-block-content-series-series-title wp-block-content-series-navigation__series',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Series Title', 'content-series' ) }>
					<SelectControl
						label={ __( 'Heading level', 'content-series' ) }
						value={ String( level ?? 3 ) }
						options={ [ 1, 2, 3, 4, 5, 6 ].map( ( value ) => ( {
							label: `H${ value }`,
							value: String( value ),
						} ) ) }
						onChange={ ( value: string ) =>
							setAttributes( { level: Number( value ) } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __(
							'Link to series archive',
							'content-series'
						) }
						checked={ isLink ?? true }
						onChange={ ( value: boolean ) =>
							setAttributes( { isLink: value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<HeadingTag className="wp-block-content-series-title__heading">
					{ isLink ?? true ? (
						<a>
							{ __( 'Series title', 'content-series' ) }
						</a>
					) : (
						<span>{ __( 'Series title', 'content-series' ) }</span>
					) }
				</HeadingTag>
			</div>
		</>
	);
}
