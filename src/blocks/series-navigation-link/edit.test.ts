import { ARROW_STYLES, getArrowStyle } from './edit';

jest.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: () => null,
	useBlockProps: () => ( {} ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	PanelBody: () => null,
	SelectControl: () => null,
	TextControl: () => null,
	ToggleControl: () => null,
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

describe( 'series navigation link edit helpers', () => {
	test( 'returns configured arrow styles', () => {
		expect( getArrowStyle( 'arrow' ) ).toEqual( ARROW_STYLES.arrow );
		expect( getArrowStyle( 'chevron' ) ).toEqual( ARROW_STYLES.chevron );
		expect( getArrowStyle( 'none' ) ).toEqual( ARROW_STYLES.none );
	} );

	test( 'falls back to default arrow style', () => {
		expect( getArrowStyle( 'invalid' as never ) ).toEqual(
			ARROW_STYLES.arrow
		);
	} );
} );
