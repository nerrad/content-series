/**
 * Settings Page Component
 */

import { useState, useEffect } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	Spinner,
	Modal,
	TextControl,
	TextareaControl,
	// @ts-expect-error - VStack exists but types may be incomplete
	__experimentalVStack as VStack,
	// @ts-expect-error - HStack exists but types may be incomplete
	__experimentalHStack as HStack,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import type { Series, MediaAttachment } from '../types';

interface SeriesFormData {
	id?: number;
	name: string;
	description: string;
	iconUrl: string;
	iconId: number;
}

export default function SettingsPage(): JSX.Element {
	const [ series, setSeries ] = useState< Series[] >( [] );
	const [ loading, setLoading ] = useState< boolean >( true );
	const [ error, setError ] = useState< string | null >( null );
	const [ editingSeries, setEditingSeries ] = useState< SeriesFormData | null >( null );
	const [ isCreating, setIsCreating ] = useState< boolean >( false );
	const [ isSaving, setIsSaving ] = useState< boolean >( false );
	const [ deleteConfirm, setDeleteConfirm ] = useState< Series | null >( null );

	// Fetch all series
	const fetchSeries = async (): Promise< void > => {
		setLoading( true );
		setError( null );

		try {
			const data = await apiFetch< Series[] >( {
				path: '/wp/v2/series?per_page=100&_embed',
			} );
			setSeries( data );
		} catch ( err ) {
			setError( ( err as Error ).message );
		}

		setLoading( false );
	};

	useEffect( () => {
		fetchSeries();
	}, [] );

	// Create new series
	const handleCreate = async ( data: SeriesFormData ): Promise< void > => {
		setIsSaving( true );

		try {
			const newSeries = await apiFetch< Series >( {
				path: '/wp/v2/series',
				method: 'POST',
				data: {
					name: data.name,
					description: data.description,
					meta: {
						series_icon: data.iconUrl || '',
						series_icon_id: data.iconId || 0,
					},
				},
			} );

			setSeries( ( prev ) => [ ...prev, newSeries ] );
			setEditingSeries( null );
			setIsCreating( false );
		} catch ( err ) {
			setError( ( err as Error ).message );
		}

		setIsSaving( false );
	};

	// Update existing series
	const handleUpdate = async ( id: number, data: SeriesFormData ): Promise< void > => {
		setIsSaving( true );

		try {
			const updated = await apiFetch< Series >( {
				path: `/wp/v2/series/${ id }`,
				method: 'POST',
				data: {
					name: data.name,
					description: data.description,
					meta: {
						series_icon: data.iconUrl || '',
						series_icon_id: data.iconId || 0,
					},
				},
			} );

			setSeries( ( prev ) =>
				prev.map( ( s ) => ( s.id === id ? updated : s ) )
			);
			setEditingSeries( null );
		} catch ( err ) {
			setError( ( err as Error ).message );
		}

		setIsSaving( false );
	};

	// Delete series
	const handleDelete = async ( id: number ): Promise< void > => {
		setIsSaving( true );

		try {
			await apiFetch( {
				path: `/wp/v2/series/${ id }?force=true`,
				method: 'DELETE',
			} );

			setSeries( ( prev ) => prev.filter( ( s ) => s.id !== id ) );
			setDeleteConfirm( null );
		} catch ( err ) {
			setError( ( err as Error ).message );
		}

		setIsSaving( false );
	};

	if ( loading ) {
		return (
			<div className="content-series-settings">
				<h1>{ __( 'Series', 'content-series' ) }</h1>
				<Card>
					<CardBody>
						<Spinner />
						<span style={ { marginLeft: '1rem' } }>
							{ __( 'Loading series...', 'content-series' ) }
						</span>
					</CardBody>
				</Card>
			</div>
		);
	}

	return (
		<div className="content-series-settings">
			<HStack alignment="center" style={ { marginBottom: '1.5rem' } }>
				<h1 style={ { margin: 0 } }>
					{ __( 'Series', 'content-series' ) }
				</h1>
				<Button
					variant="primary"
					onClick={ () => {
						setIsCreating( true );
						setEditingSeries( {
							name: '',
							description: '',
							iconUrl: '',
							iconId: 0,
						} );
					} }
				>
					{ __( 'Add New Series', 'content-series' ) }
				</Button>
			</HStack>

			{ error && (
				<Notice
					status="error"
					onRemove={ () => setError( null ) }
					style={ { marginBottom: '1rem' } }
				>
					{ error }
				</Notice>
			) }

			<Card>
				<CardHeader>
					<h2 style={ { margin: 0 } }>
						{ __( 'All Series', 'content-series' ) }
					</h2>
				</CardHeader>
				<CardBody>
					{ series.length === 0 ? (
						<p>
							{ __(
								'No series found. Create your first series to get started.',
								'content-series'
							) }
						</p>
					) : (
						<table className="wp-list-table widefat striped">
							<thead>
								<tr>
									<th style={ { width: '60px' } }>
										{ __( 'Icon', 'content-series' ) }
									</th>
									<th>{ __( 'Name', 'content-series' ) }</th>
									<th>{ __( 'Slug', 'content-series' ) }</th>
									<th style={ { width: '80px' } }>
										{ __( 'Posts', 'content-series' ) }
									</th>
									<th style={ { width: '150px' } }>
										{ __( 'Actions', 'content-series' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ series.map( ( s ) => (
									<tr key={ s.id }>
										<td>
											{ s.meta?.series_icon ? (
												<img
													src={ s.meta.series_icon }
													alt=""
													style={ {
														width: 40,
														height: 40,
														objectFit: 'cover',
														borderRadius: 4,
													} }
												/>
											) : (
												<span
													style={ { color: '#999' } }
												>
													&mdash;
												</span>
											) }
										</td>
										<td>
											<strong>{ s.name }</strong>
											{ s.description && (
												<p
													style={ {
														margin: '0.25rem 0 0',
														fontSize: '12px',
														color: '#666',
													} }
												>
													{ s.description.length > 100
														? s.description.substring(
																0,
																100
														  ) + '...'
														: s.description }
												</p>
											) }
										</td>
										<td>
											<code>{ s.slug }</code>
										</td>
										<td>{ s.count }</td>
										<td>
											<HStack spacing={ 2 }>
												<Button
													variant="secondary"
													size="small"
													onClick={ () =>
														setEditingSeries( {
															id: s.id,
															name: s.name,
															description:
																s.description ||
																'',
															iconUrl:
																s.meta
																	?.series_icon ||
																'',
															iconId:
																s.meta
																	?.series_icon_id ||
																0,
														} )
													}
												>
													{ __(
														'Edit',
														'content-series'
													) }
												</Button>
												<Button
													variant="tertiary"
													size="small"
													isDestructive
													onClick={ () =>
														setDeleteConfirm( s )
													}
												>
													{ __(
														'Delete',
														'content-series'
													) }
												</Button>
											</HStack>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					) }
				</CardBody>
			</Card>

			{ /* Edit/Create Modal */ }
			{ editingSeries && (
				<SeriesEditModal
					series={ editingSeries }
					isCreating={ isCreating }
					isSaving={ isSaving }
					onClose={ () => {
						setEditingSeries( null );
						setIsCreating( false );
					} }
					onSave={ ( data ) => {
						if ( isCreating ) {
							handleCreate( data );
						} else if ( editingSeries.id ) {
							handleUpdate( editingSeries.id, data );
						}
					} }
				/>
			) }

			{ /* Delete Confirmation Modal */ }
			{ deleteConfirm && (
				<Modal
					title={ __( 'Delete Series', 'content-series' ) }
					onRequestClose={ () => setDeleteConfirm( null ) }
					size="small"
				>
					<p>
						{ __(
							'Are you sure you want to delete this series?',
							'content-series'
						) }
					</p>
					<p>
						<strong>{ deleteConfirm.name }</strong>
					</p>
					{ deleteConfirm.count > 0 && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'This series has posts assigned to it. Posts will not be deleted, but they will no longer be part of this series.',
								'content-series'
							) }
						</Notice>
					) }
					<HStack
						justify="flex-end"
						style={ { marginTop: '1.5rem' } }
					>
						<Button
							variant="tertiary"
							onClick={ () => setDeleteConfirm( null ) }
						>
							{ __( 'Cancel', 'content-series' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							isBusy={ isSaving }
							onClick={ () => handleDelete( deleteConfirm.id ) }
						>
							{ __( 'Delete', 'content-series' ) }
						</Button>
					</HStack>
				</Modal>
			) }
		</div>
	);
}

/**
 * Series Edit Modal Component
 */
interface SeriesEditModalProps {
	series: SeriesFormData;
	isCreating: boolean;
	isSaving: boolean;
	onClose: () => void;
	onSave: ( data: SeriesFormData ) => void;
}

function SeriesEditModal( {
	series,
	isCreating,
	isSaving,
	onClose,
	onSave,
}: SeriesEditModalProps ): JSX.Element {
	const [ name, setName ] = useState< string >( series.name || '' );
	const [ description, setDescription ] = useState< string >(
		series.description || ''
	);
	const [ iconUrl, setIconUrl ] = useState< string >( series.iconUrl || '' );
	const [ iconId, setIconId ] = useState< number >( series.iconId || 0 );

	const handleSave = (): void => {
		if ( ! name.trim() ) {
			return;
		}

		onSave( {
			name: name.trim(),
			description: description.trim(),
			iconUrl,
			iconId,
		} );
	};

	const handleMediaSelect = ( media: MediaAttachment ): void => {
		setIconUrl( media.url );
		setIconId( media.id );
	};

	return (
		<Modal
			title={
				isCreating
					? __( 'Add New Series', 'content-series' )
					: __( 'Edit Series', 'content-series' )
			}
			onRequestClose={ onClose }
			size="medium"
		>
			<VStack spacing={ 4 }>
				<TextControl
					label={ __( 'Name', 'content-series' ) }
					value={ name }
					onChange={ setName }
					__nextHasNoMarginBottom
					required
				/>

				<TextareaControl
					label={ __( 'Description', 'content-series' ) }
					value={ description }
					onChange={ setDescription }
					rows={ 3 }
					__nextHasNoMarginBottom
				/>

				<div>
					<label
						style={ {
							display: 'block',
							marginBottom: '0.5rem',
							fontWeight: 500,
						} }
					>
						{ __( 'Series Icon', 'content-series' ) }
					</label>

					<HStack alignment="flex-start" spacing={ 4 }>
						{ iconUrl && (
							<img
								src={ iconUrl }
								alt=""
								style={ {
									width: 100,
									height: 100,
									objectFit: 'cover',
									borderRadius: 4,
									border: '1px solid #ddd',
								} }
							/>
						) }

						<VStack spacing={ 2 }>
							<MediaUploadCheck>
								<MediaUpload
									onSelect={ handleMediaSelect }
									allowedTypes={ [ 'image' ] }
									render={ ( { open } ) => (
										<Button
											variant="secondary"
											onClick={ open }
										>
											{ iconUrl
												? __(
														'Change Image',
														'content-series'
												  )
												: __(
														'Select Image',
														'content-series'
												  ) }
										</Button>
									) }
								/>
							</MediaUploadCheck>

							{ iconUrl && (
								<Button
									variant="tertiary"
									isDestructive
									onClick={ () => {
										setIconUrl( '' );
										setIconId( 0 );
									} }
								>
									{ __( 'Remove Image', 'content-series' ) }
								</Button>
							) }
						</VStack>
					</HStack>
				</div>
			</VStack>

			<HStack justify="flex-end" style={ { marginTop: '1.5rem' } }>
				<Button variant="tertiary" onClick={ onClose }>
					{ __( 'Cancel', 'content-series' ) }
				</Button>
				<Button
					variant="primary"
					isBusy={ isSaving }
					disabled={ ! name.trim() }
					onClick={ handleSave }
				>
					{ isCreating
						? __( 'Create Series', 'content-series' )
						: __( 'Save Changes', 'content-series' ) }
				</Button>
			</HStack>
		</Modal>
	);
}
