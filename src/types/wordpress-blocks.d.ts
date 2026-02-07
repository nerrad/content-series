declare module '@wordpress/blocks' {
	export interface BlockBindingsSourceConfig<
		TGetFieldsArgs = never,
		TGetValuesArgs = never,
	> {
		name: string;
		label: string;
		usesContext?: string[];
		getFieldsList?: ( args: TGetFieldsArgs ) => unknown[];
		getValues?: ( args: TGetValuesArgs ) => Record< string, unknown >;
	}

	export interface BlockTypeSettings< TProps = never > {
		edit?: React.ComponentType< TProps >;
		save?: () => JSX.Element | null;
		[ key: string ]: unknown;
	}

	export interface BlockVariation<
		TAttributes extends Record< string, unknown > = Record< string, unknown >,
		TInnerBlocks = unknown[],
	> {
		name: string;
		title?: string;
		description?: string;
		category?: string;
		keywords?: string[];
		attributes?: TAttributes;
		isActive?: ( blockAttributes: TAttributes ) => boolean;
		innerBlocks?: TInnerBlocks;
		scope?: string[];
		[ key: string ]: unknown;
	}

	export function registerBlockBindingsSource<
		TGetFieldsArgs = never,
		TGetValuesArgs = never,
	>(
		config: BlockBindingsSourceConfig< TGetFieldsArgs, TGetValuesArgs >
	): void;

	export function registerBlockType< TProps = never >(
		nameOrMetadata:
			| string
			| ( {
					name: string;
			  } & Record< string, unknown > ),
		settings?: BlockTypeSettings< TProps >
	): void;

	export function registerBlockVariation<
		TAttributes extends Record< string, unknown > = Record< string, unknown >,
		TInnerBlocks = unknown[],
	>(
		blockName: string,
		variation: BlockVariation< TAttributes, TInnerBlocks >
	): void;
}
