/**
 * Series Icon Block - Edit Component
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

import type { SeriesIconBlockAttributes } from '../../types';

interface EditProps {
	attributes: SeriesIconBlockAttributes;
	setAttributes: ( attrs: Partial< SeriesIconBlockAttributes > ) => void;
}

export default function Edit( {
	attributes,
	setAttributes,
}: EditProps ): JSX.Element {
	const { isLink, size, alt } = attributes;
	const resolvedSize = Math.max( 16, Math.min( 256, Number( size ?? 40 ) ) );
	const blockProps = useBlockProps( {
		className:
			'wp-block-content-series-series-icon wp-block-content-series-navigation__series-icon',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Series Icon', 'content-series' ) }>
					<TextControl
						label={ __( 'Icon size (px)', 'content-series' ) }
						type="number"
						min={ 16 }
						max={ 256 }
						value={ String( resolvedSize ) }
						onChange={ ( value: string ) =>
							setAttributes( { size: Number( value ) || 40 } )
						}
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __( 'Alt text', 'content-series' ) }
						value={ alt ?? '' }
						onChange={ ( value: string ) =>
							setAttributes( { alt: value } )
						}
						help={ __(
							'Leave empty to use a generated description.',
							'content-series'
						) }
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
				{ isLink ?? true ? (
					<a>
						<span
							className="wp-block-content-series-series-icon__image wp-block-content-series-navigation__icon wp-block-content-series-navigation__icon--placeholder"
							style={ {
								width: `${ resolvedSize }px`,
								height: `${ resolvedSize }px`,
							} }
						/>
					</a>
				) : (
					<span
						className="wp-block-content-series-series-icon__image wp-block-content-series-navigation__icon wp-block-content-series-navigation__icon--placeholder"
						style={ {
							width: `${ resolvedSize }px`,
							height: `${ resolvedSize }px`,
						} }
					/>
				) }
			</div>
		</>
	);
}
