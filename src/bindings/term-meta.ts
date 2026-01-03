/**
 * Block Bindings Source for Series Term Meta
 *
 * Registers a block bindings source that allows binding block attributes
 * to series term meta values (like series_icon).
 */

import { __ } from '@wordpress/i18n';
import { registerBlockBindingsSource } from '@wordpress/blocks';
import { store as coreDataStore } from '@wordpress/core-data';

import type { WPTerm } from '../types';

interface BindingArgs {
	key: string;
}

interface Binding {
	args?: BindingArgs;
}

interface Bindings {
	[ attributeName: string ]: Binding;
}

interface BlockContext {
	termId?: number;
	taxonomy?: string;
}

interface GetValuesArgs {
	bindings: Bindings;
	context: BlockContext;
	select: typeof import('@wordpress/data').select;
}

interface GetFieldsListArgs {
	context: BlockContext;
	select: typeof import('@wordpress/data').select;
}

interface FieldDefinition {
	label: string;
	args: BindingArgs;
	type: string;
}

/**
 * Available fields for the binding UI.
 */
const seriesMetaFields: FieldDefinition[] = [
	{
		label: __( 'Series Icon', 'content-series' ),
		args: { key: 'series_icon' },
		type: 'string',
	},
	{
		label: __( 'Series Icon ID', 'content-series' ),
		args: { key: 'series_icon_id' },
		type: 'string',
	},
];

/**
 * Register the block bindings source for series term meta.
 */
export function registerSeriesTermMetaBindings(): void {
	registerBlockBindingsSource( {
		name: 'content-series/term-meta',
		label: __( 'Series Term Meta', 'content-series' ),
		usesContext: [ 'termId', 'taxonomy' ],

		getFieldsList( { context }: GetFieldsListArgs ): FieldDefinition[] {
			// Only show fields when we have term context
			if ( ! context || ( ! context.termId && ! context.taxonomy ) ) {
				return [];
			}
			return seriesMetaFields;
		},

		getValues( { bindings, context, select }: GetValuesArgs ): Record< string, string > {
			const values: Record< string, string > = {};
			const termId = context.termId;
			const taxonomy = context.taxonomy || 'series';

			if ( ! termId ) {
				return values;
			}

			// Use the core data store to get the term entity record
			const term = select( coreDataStore ).getEntityRecord(
				'taxonomy',
				taxonomy,
				termId
			) as WPTerm | undefined;

			for ( const [ attributeName, binding ] of Object.entries( bindings ) ) {
				const key = binding.args?.key;
				if ( ! key ) {
					values[ attributeName ] = '';
					continue;
				}

				// Get the meta value from the term record
				if ( term?.meta?.[ key ] ) {
					values[ attributeName ] = String( term.meta[ key ] );
				} else {
					values[ attributeName ] = '';
				}
			}

			return values;
		},
	} );
}

// Auto-register when the script loads
registerSeriesTermMetaBindings();
