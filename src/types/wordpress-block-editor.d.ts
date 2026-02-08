declare module '@wordpress/block-editor' {
	import type { ComponentType, ReactNode } from 'react';

	export interface BlockProps {
		className?: string;
		[ key: string ]: unknown;
	}

	export function useBlockProps( props?: BlockProps ): BlockProps;

	export const InnerBlocks: ComponentType< {
		allowedBlocks?: string[];
		template?: unknown[];
		templateLock?: false | 'all' | 'insert';
		orientation?: 'horizontal' | 'vertical';
	} > & {
		Content: ComponentType;
	};

	export const InspectorControls: ComponentType< {
		children?: ReactNode;
		group?: string;
	} >;
	export * from 'node_modules/@wordpress/block-editor/build-types/components';
	export * from 'node_modules/@wordpress/block-editor/build-types/utils';
}
