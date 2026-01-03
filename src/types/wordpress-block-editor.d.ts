/* eslint-disable @typescript-eslint/no-explicit-any */
declare module "@wordpress/block-editor" {
  import type { ComponentType, ReactNode } from "react";

  export interface BlockProps {
    className?: string;
    [key: string]: any;
  }

  export function useBlockProps(props?: BlockProps): BlockProps;

  export const InspectorControls: ComponentType<{
    children?: ReactNode;
    group?: string;
  }>;
  export * from "node_modules/@wordpress/block-editor/build-types/components";
  export * from "node_modules/@wordpress/block-editor/build-types/utils";
}
