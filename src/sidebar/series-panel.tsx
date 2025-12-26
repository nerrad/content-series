/**
 * Series Panel Component
 *
 * Adds a panel to the post editor sidebar for managing series assignments.
 */

import { PluginDocumentSettingPanel } from "@wordpress/editor";
import { useSelect, useDispatch } from "@wordpress/data";
import { store as coreStore } from "@wordpress/core-data";
import { store as editorStore } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";
import {
  FormTokenField,
  TextControl,
  PanelRow,
  Spinner,
} from "@wordpress/components";
import { useState, useMemo } from "@wordpress/element";
import { useDebounce } from "@wordpress/compose";

import type { SeriesOrder, WPTerm } from "../types";

const TAXONOMY = "series";

interface EditorSelectReturn {
  postType: string | undefined;
  postId: number | undefined;
  currentSeriesIds: number[];
  shortTitle: string;
  seriesOrder: SeriesOrder;
  isSaving: boolean;
}

interface CoreSelectReturn {
  allSeries: WPTerm[];
  searchResults: WPTerm[];
  currentSeriesTerms: WPTerm[];
  isLoading: boolean;
}

export default function SeriesPanel(): JSX.Element | null {
  const [search, setSearch] = useState<string>("");
  const [isCreating, setIsCreating] = useState<boolean>(false);

  // Get post data
  const {
    postType,
    postId,
    currentSeriesIds,
    shortTitle,
    seriesOrder,
    isSaving,
  } = useSelect((select): EditorSelectReturn => {
    const editorSelectors = select(editorStore) as {
      getCurrentPostType: () => string | undefined;
      getCurrentPostId: () => number | undefined;
      getEditedPostAttribute: (attr: string) => unknown;
      isSavingPost: () => boolean;
    };

    const type = editorSelectors.getCurrentPostType();

    // Only support posts
    if (type !== "post") {
      return {
        postType: type,
        postId: undefined,
        currentSeriesIds: [],
        shortTitle: "",
        seriesOrder: {},
        isSaving: false,
      };
    }

    const meta = editorSelectors.getEditedPostAttribute("meta") as
      | Record<string, unknown>
      | undefined;

    return {
      postType: type,
      postId: editorSelectors.getCurrentPostId(),
      currentSeriesIds:
        (editorSelectors.getEditedPostAttribute(TAXONOMY) as number[]) || [],
      shortTitle: (meta?._spost_short_title as string) || "",
      seriesOrder:
        (editorSelectors.getEditedPostAttribute(
          "series_order"
        ) as SeriesOrder) || {},
      isSaving: editorSelectors.isSavingPost(),
    };
  }, []);

  // Don't render for non-post types
  if (postType !== "post") {
    return null;
  }

  const { editPost } = useDispatch(editorStore) as {
    editPost: (edits: Record<string, unknown>) => void;
  };

  const { saveEntityRecord } = useDispatch(coreStore) as {
    saveEntityRecord: (
      kind: string,
      name: string,
      record: Record<string, unknown>
    ) => Promise<WPTerm>;
  };

  // Search for series
  const debouncedSearch = useDebounce(setSearch, 300);

  // Get all series and search results
  const { allSeries, searchResults, currentSeriesTerms, isLoading } = useSelect(
    (select): CoreSelectReturn => {
      const coreSelectors = select(coreStore) as {
        getEntityRecords: (
          kind: string,
          name: string,
          query?: Record<string, unknown>
        ) => WPTerm[] | null;
        isResolving: (selectorName: string, args: unknown[]) => boolean;
      };

      // Get all series for suggestions
      const all =
        coreSelectors.getEntityRecords("taxonomy", TAXONOMY, {
          per_page: 100,
          orderby: "name",
          order: "asc",
        }) || [];

      // Get search results if searching
      const results = search
        ? coreSelectors.getEntityRecords("taxonomy", TAXONOMY, {
            search,
            per_page: 20,
          }) || []
        : [];

      // Get current series terms
      const current =
        currentSeriesIds.length > 0
          ? coreSelectors.getEntityRecords("taxonomy", TAXONOMY, {
              include: currentSeriesIds,
              per_page: 100,
            }) || []
          : [];

      return {
        allSeries: all,
        searchResults: results,
        currentSeriesTerms: current,
        isLoading:
          coreSelectors.isResolving("getEntityRecords", [
            "taxonomy",
            TAXONOMY,
            { per_page: 100 },
          ]) ||
          (currentSeriesIds.length > 0 &&
            coreSelectors.isResolving("getEntityRecords", [
              "taxonomy",
              TAXONOMY,
              { include: currentSeriesIds },
            ])),
      };
    },
    [search, currentSeriesIds]
  );

  // Build suggestions from all series and search results
  const suggestions = useMemo((): string[] => {
    const combined = search ? searchResults : allSeries;
    return combined.map((term) => term.name);
  }, [allSeries, searchResults, search]);

  // Current values as names
  const currentValues = useMemo((): string[] => {
    return currentSeriesTerms.map((term) => term.name);
  }, [currentSeriesTerms]);

  // Handle series selection change
  const handleSeriesChange = async (newNames: string[]): Promise<void> => {
    const termIds: number[] = [];
    const newSeriesOrder: SeriesOrder = { ...seriesOrder };

    for (const name of newNames) {
      // Find existing term
      let term = allSeries.find(
        (t) => t.name.toLowerCase() === name.toLowerCase()
      );

      if (!term) {
        // Create new term
        setIsCreating(true);
        try {
          term = await saveEntityRecord("taxonomy", TAXONOMY, {
            name,
          });
        } catch (error) {
          console.error("Failed to create series:", error);
          continue;
        }
        setIsCreating(false);
      }

      if (term && term.id) {
        termIds.push(term.id);

        // Set default order if new
        if (!newSeriesOrder[term.id]) {
          newSeriesOrder[term.id] = 1;
        }
      }
    }

    // Remove order for removed series
    Object.keys(newSeriesOrder).forEach((id) => {
      if (!termIds.includes(parseInt(id, 10))) {
        delete newSeriesOrder[parseInt(id, 10)];
      }
    });

    editPost({
      [TAXONOMY]: termIds,
      series_order: newSeriesOrder,
    });
  };

  // Handle order change for a specific series
  const handleOrderChange = (seriesId: number, newOrder: string): void => {
    const newSeriesOrder: SeriesOrder = {
      ...seriesOrder,
      [seriesId]: parseInt(newOrder, 10) || 1,
    };
    editPost({ series_order: newSeriesOrder });
  };

  // Handle short title change
  const handleShortTitleChange = (value: string): void => {
    editPost({
      meta: { _spost_short_title: value },
    });
  };

  if (isLoading) {
    return (
      <PluginDocumentSettingPanel
        name="content-series"
        title={__("Series", "content-series")}
        className="content-series-panel"
      >
        <Spinner />
      </PluginDocumentSettingPanel>
    );
  }

  return (
    <PluginDocumentSettingPanel
      name="content-series"
      title={__("Series", "content-series")}
      className="content-series-panel"
    >
      <PanelRow>
        <div style={{ width: "100%" }}>
          <FormTokenField
            label={__("Series", "content-series")}
            value={currentValues}
            suggestions={suggestions}
            onInputChange={debouncedSearch}
            onChange={handleSeriesChange}
            __experimentalExpandOnFocus
            __experimentalShowHowTo={false}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            disabled={isSaving || isCreating}
            placeholder={__("Search or create series...", "content-series")}
          />
          {isCreating && (
            <p className="content-series-panel__creating">
              <Spinner />
              {__("Creating series...", "content-series")}
            </p>
          )}
        </div>
      </PanelRow>

      {currentSeriesTerms.length > 0 && (
        <>
          <hr />
          <p className="content-series-panel__order-heading">
            <strong>{__("Order in Series", "content-series")}</strong>
          </p>

          {currentSeriesTerms.map((term) => (
            <PanelRow key={term.id}>
              <TextControl
                label={term.name}
                type="number"
                min={1}
                value={String(seriesOrder[term.id] || 1)}
                onChange={(value: string) => handleOrderChange(term.id, value)}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
                disabled={isSaving}
              />
            </PanelRow>
          ))}

          <hr />

          <PanelRow>
            <TextControl
              label={__("Short Title (optional)", "content-series")}
              value={shortTitle}
              onChange={handleShortTitleChange}
              help={__(
                "Displayed in series navigation and lists.",
                "content-series"
              )}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
              disabled={isSaving}
            />
          </PanelRow>
        </>
      )}
    </PluginDocumentSettingPanel>
  );
}
