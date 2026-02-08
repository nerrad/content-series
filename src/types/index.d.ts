/**
 * Type definitions for Content Series plugin.
 */

/// <reference path="./wordpress-core-data.d.ts" />
/// <reference path="./wordpress-components.d.ts" />
/// <reference path="./wordpress-blocks.d.ts" />
/// <reference path="./wordpress-block-editor.d.ts" />
/// <reference path="./wordpress-hooks.d.ts" />

export interface Series {
	id: number;
	name: string;
	slug: string;
	description: string;
	count: number;
	link?: string;
	meta?: {
		series_icon?: string;
		series_icon_id?: number;
	};
}

export interface SeriesPost {
	id: number;
	title: string;
	short_title?: string;
	url: string;
	series_part: number;
	status: string;
	date: string;
}

export interface SeriesData {
	series: Series;
	posts: SeriesPost[];
}

export interface SeriesOrder {
	[ seriesId: number ]: number;
}

export interface BlockAttributes {
	showNumbers?: boolean;
	showShortTitle?: boolean;
	highlightCurrent?: boolean;
	showSeriesTitle?: boolean;
	showSeriesIcon?: boolean;
	context?: string;
}

export interface NavigationLinkBlockAttributes {
	direction?: 'previous' | 'next';
	showTitle?: boolean;
	showPartNumbers?: boolean;
	label?: string;
	arrowStyle?: 'arrow' | 'chevron' | 'none';
}

export interface SeriesTitleBlockAttributes {
	isLink?: boolean;
	level?: number;
}

export interface SeriesIconBlockAttributes {
	isLink?: boolean;
	size?: number;
	alt?: string;
}

export interface BlockContext {
	postId?: number;
	postType?: string;
}

export interface ContentSeriesData {
	taxonomy: string;
	partKey: string;
	shortTitleKey: string;
}

export interface ContentSeriesAdmin {
	restUrl: string;
	wpRestUrl: string;
	nonce: string;
	taxonomyUrl: string;
	adminUrl: string;
	pluginVersion: string;
}

// Extend Window interface for global variables
declare global {
	interface Window {
		contentSeriesData?: ContentSeriesData;
		contentSeriesAdmin?: ContentSeriesAdmin;
	}
}

// WordPress types that might be missing
export interface WPPost {
	id: number;
	series?: number[];
	series_order?: SeriesOrder;
	meta?: {
		_spost_short_title?: string;
		[ key: string ]: unknown;
	};
}

export interface WPTerm {
	id: number;
	name: string;
	slug: string;
	description?: string;
	count?: number;
	meta?: Record< string, unknown >;
}

export interface MediaAttachment {
	id: number;
	url: string;
	title?: string;
	alt?: string;
}
