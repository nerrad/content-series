declare module "@wordpress/core-data" {
  export namespace BaseEntityRecords {
    export interface Type<C extends Context = "edit">
      extends PostTypeDataViewsFields<C> {}

    export interface Post<C extends Context = "edit">
      extends PostAugmentation<C> {}

    export interface User<C extends Context = "edit">
      extends UserAugmentation<C> {}

    export interface Plugin<C extends Context = "edit">
      extends PluginAugmentation<C> {}

    export interface Theme<C extends Context = "edit">
      extends ThemeAugmentation<C> {}
  }

  export interface TypeVisibility extends PostTypeVisibility {}
}

/**
 * Ensure the type extensions are visible when the underlying BaseEntityRecords
 * namespace is imported directly inside @wordpress/core-data's build types.
 */
declare module "@wordpress/core-data/build-types/entity-types/base-entity-records" {
  export namespace BaseEntityRecords {
    export interface Type<C extends Context = "edit">
      extends PostTypeDataViewsFields<C> {}

    export interface Post<C extends Context = "edit">
      extends PostAugmentation<C> {}

    export interface User<C extends Context = "edit">
      extends UserAugmentation<C> {}

    export interface Plugin<C extends Context = "edit">
      extends PluginAugmentation<C> {}

    export interface Theme<C extends Context = "edit">
      extends ThemeAugmentation<C> {}
  }
}

type IsResolvingArgs =
  | Parameters<typeof metadataIsResolving>[2]
  | ReadonlyArray<unknown>;

type MetadataSelectors = {
  isResolving: (
    selectorName: Parameters<typeof metadataIsResolving>[1],
    args?: IsResolvingArgs
  ) => ReturnType<typeof metadataIsResolving>;
};

type CoreDataSelectFunction = {
  (
    storeNameOrDescriptor: typeof store
  ): CurriedSelectorsOf<typeof store> & MetadataSelectors;
  <S>(storeNameOrDescriptor: S): CurriedSelectorsOf<S>;
};

type MetadataActions = {
  startResolution: typeof startResolution;
  finishResolution: typeof finishResolution;
  invalidateResolution: typeof invalidateResolution;
  invalidateResolutionForStore: typeof invalidateResolutionForStore;
  invalidateResolutionForStoreSelector: typeof invalidateResolutionForStoreSelector;
};

export type WPDataActions = {
  [Action in keyof MetadataActions]: PromisifyActionCreator<
    MetadataActions[Action]
  >;
};

declare module "@wordpress/data" {
  /**
   * Add resolver metadata helpers to the dispatch/useDispatch return type for the core-data store.
   */
  export function dispatch(
    storeNameOrDescriptor: typeof store
  ): DispatchReturn<typeof store> & WPDataActions;
  export function useDispatch(
    storeNameOrDescriptor: typeof store
  ): UseDispatchReturn<typeof store> & WPDataActions;
  export function select(
    storeNameOrDescriptor: typeof store
  ): CurriedSelectorsOf<typeof store> & MetadataSelectors;
  export function useSelect<TResult>(
    mapSelect: (
      select: CoreDataSelectFunction,
      registry?: DataRegistry
    ) => TResult,
    deps?: unknown[]
  ): TResult;
  export function useSelect(
    storeNameOrDescriptor: typeof store
  ): CurriedSelectorsOf<typeof store> & MetadataSelectors;
}
