/* eslint-disable @typescript-eslint/no-explicit-any */
declare module "@wordpress/blocks" {
  export interface BlockBindingsSourceConfig {
    name: string;
    label: string;
    usesContext?: string[];
    getFieldsList?: (args: any) => any[];
    getValues?: (args: any) => Record<string, any>;
  }

  export interface BlockTypeSettings {
    edit?: React.ComponentType<any>;
    save?: () => JSX.Element | null;
    [key: string]: any;
  }

  export function registerBlockBindingsSource(
    config: BlockBindingsSourceConfig
  ): void;

  export function registerBlockType(
    nameOrMetadata: string | { name: string; [key: string]: any },
    settings?: BlockTypeSettings
  ): void;
}
