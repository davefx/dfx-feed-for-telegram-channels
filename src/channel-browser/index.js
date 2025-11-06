import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TabPanel,
	SelectControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUnitControl as UnitControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalBoxControl as BoxControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.css';

registerBlockType( 'dfx-tg-feed/channel-browser', {
	title: __( 'Telegram Channel Browser', 'dfx-tg-feed' ),
	description: __(
		'Browse and display full message history from a Telegram channel',
		'dfx-tg-feed'
	),
	category: 'widgets',
	icon: 'list-view',
	attributes: {
		channel: {
			type: 'string',
			default: '',
		},
		// Block container styles
		blockBackground: {
			type: 'string',
			default: '',
		},
		blockBorderWidth: {
			type: 'string',
			default: '',
		},
		blockBorderStyle: {
			type: 'string',
			default: '',
		},
		blockBorderColor: {
			type: 'string',
			default: '',
		},
		blockBorderRadius: {
			type: 'string',
			default: '',
		},
		blockPadding: {
			type: 'object',
			default: {},
		},
		blockMargin: {
			type: 'object',
			default: {},
		},
		// Message styles
		messageBackground: {
			type: 'string',
			default: '',
		},
		messageBorderWidth: {
			type: 'string',
			default: '',
		},
		messageBorderStyle: {
			type: 'string',
			default: '',
		},
		messageBorderColor: {
			type: 'string',
			default: '',
		},
		messageBorderRadius: {
			type: 'string',
			default: '',
		},
		messagePadding: {
			type: 'object',
			default: {},
		},
		messageMargin: {
			type: 'object',
			default: {},
		},
		// Typography
		dateFontFamily: {
			type: 'string',
			default: '',
		},
		dateFontSize: {
			type: 'string',
			default: '',
		},
		authorFontFamily: {
			type: 'string',
			default: '',
		},
		authorFontSize: {
			type: 'string',
			default: '',
		},
		textFontFamily: {
			type: 'string',
			default: '',
		},
		textFontSize: {
			type: 'string',
			default: '',
		},
		dateColor: {
			type: 'string',
			default: '',
		},
		authorColor: {
			type: 'string',
			default: '',
		},
		textColor: {
			type: 'string',
			default: '',
		},
	},
	edit: ( props ) => {
		const { attributes, setAttributes } = props;
		// eslint-disable-next-line react-hooks/rules-of-hooks -- WordPress block edit is a valid component function
		const blockProps = useBlockProps();

		const fontFamilies = [
			{ label: __( 'Default', 'dfx-tg-feed' ), value: '' },
			{ label: 'Arial', value: 'Arial, sans-serif' },
			{ label: 'Helvetica', value: 'Helvetica, sans-serif' },
			{ label: 'Times New Roman', value: '"Times New Roman", serif' },
			{ label: 'Georgia', value: 'Georgia, serif' },
			{ label: 'Courier New', value: '"Courier New", monospace' },
			{ label: 'Verdana', value: 'Verdana, sans-serif' },
			{ label: 'Trebuchet MS', value: '"Trebuchet MS", sans-serif' },
			{ label: 'Comic Sans MS', value: '"Comic Sans MS", cursive' },
			{ label: 'Impact', value: 'Impact, sans-serif' },
		];

		const borderStyles = [
			{ label: __( 'None', 'dfx-tg-feed' ), value: '' },
			{ label: __( 'Solid', 'dfx-tg-feed' ), value: 'solid' },
			{ label: __( 'Dashed', 'dfx-tg-feed' ), value: 'dashed' },
			{ label: __( 'Dotted', 'dfx-tg-feed' ), value: 'dotted' },
			{ label: __( 'Double', 'dfx-tg-feed' ), value: 'double' },
		];

		return (
			<>
				<InspectorControls>
					<TabPanel
						className="dfx-tg-feed-tabs"
						activeClass="is-active"
						tabs={ [
							{
								name: 'settings',
								title: __( 'Settings', 'dfx-tg-feed' ),
								className: 'tab-settings',
							},
							{
								name: 'block-styles',
								title: __( 'Block Styles', 'dfx-tg-feed' ),
								className: 'tab-block-styles',
							},
							{
								name: 'message-styles',
								title: __( 'Message Styles', 'dfx-tg-feed' ),
								className: 'tab-message-styles',
							},
						] }
					>
						{ ( tab ) => {
							if ( tab.name === 'settings' ) {
								return (
									<>
										<PanelBody
											title={ __(
												'General Settings',
												'dfx-tg-feed'
											) }
											initialOpen={ true }
										>
											<TextControl
												label={ __(
													'Channel Username',
													'dfx-tg-feed'
												) }
												help={ __(
													'Enter channel username with @ (e.g., @yourchannel) or channel ID',
													'dfx-tg-feed'
												) }
												value={ attributes.channel }
												onChange={ ( value ) =>
													setAttributes( {
														channel: value,
													} )
												}
											/>
										</PanelBody>
									</>
								);
							} else if ( tab.name === 'block-styles' ) {
								return (
									<>
										<PanelBody
											title={ __(
												'Block Container',
												'dfx-tg-feed'
											) }
											initialOpen={ true }
										>
											<TextControl
												label={ __(
													'Background Color',
													'dfx-tg-feed'
												) }
												help={ __(
													'e.g., #ffffff or transparent',
													'dfx-tg-feed'
												) }
												value={
													attributes.blockBackground
												}
												onChange={ ( value ) =>
													setAttributes( {
														blockBackground: value,
													} )
												}
											/>
											<SelectControl
												label={ __(
													'Border Style',
													'dfx-tg-feed'
												) }
												value={
													attributes.blockBorderStyle
												}
												options={ borderStyles }
												onChange={ ( value ) =>
													setAttributes( {
														blockBorderStyle: value,
													} )
												}
											/>
											{ attributes.blockBorderStyle && (
												<>
													<UnitControl
														label={ __(
															'Border Width',
															'dfx-tg-feed'
														) }
														value={
															attributes.blockBorderWidth
														}
														onChange={ ( value ) =>
															setAttributes( {
																blockBorderWidth:
																	value,
															} )
														}
													/>
													<TextControl
														label={ __(
															'Border Color',
															'dfx-tg-feed'
														) }
														help={ __(
															'e.g., #e1e8ed',
															'dfx-tg-feed'
														) }
														value={
															attributes.blockBorderColor
														}
														onChange={ ( value ) =>
															setAttributes( {
																blockBorderColor:
																	value,
															} )
														}
													/>
													<UnitControl
														label={ __(
															'Border Radius',
															'dfx-tg-feed'
														) }
														value={
															attributes.blockBorderRadius
														}
														onChange={ ( value ) =>
															setAttributes( {
																blockBorderRadius:
																	value,
															} )
														}
													/>
												</>
											) }
											{ BoxControl && (
												<>
													<BoxControl
														label={ __(
															'Padding',
															'dfx-tg-feed'
														) }
														values={
															attributes.blockPadding
														}
														onChange={ ( value ) =>
															setAttributes( {
																blockPadding:
																	value,
															} )
														}
													/>
													<BoxControl
														label={ __(
															'Margin',
															'dfx-tg-feed'
														) }
														values={
															attributes.blockMargin
														}
														onChange={ ( value ) =>
															setAttributes( {
																blockMargin:
																	value,
															} )
														}
													/>
												</>
											) }
										</PanelBody>
									</>
								);
							} else if ( tab.name === 'message-styles' ) {
								return (
									<>
										<PanelBody
											title={ __(
												'Message Container',
												'dfx-tg-feed'
											) }
											initialOpen={ true }
										>
											<TextControl
												label={ __(
													'Background Color',
													'dfx-tg-feed'
												) }
												help={ __(
													'e.g., #ffffff',
													'dfx-tg-feed'
												) }
												value={
													attributes.messageBackground
												}
												onChange={ ( value ) =>
													setAttributes( {
														messageBackground:
															value,
													} )
												}
											/>
											<SelectControl
												label={ __(
													'Border Style',
													'dfx-tg-feed'
												) }
												value={
													attributes.messageBorderStyle
												}
												options={ borderStyles }
												onChange={ ( value ) =>
													setAttributes( {
														messageBorderStyle:
															value,
													} )
												}
											/>
											{ attributes.messageBorderStyle && (
												<>
													<UnitControl
														label={ __(
															'Border Width',
															'dfx-tg-feed'
														) }
														value={
															attributes.messageBorderWidth
														}
														onChange={ ( value ) =>
															setAttributes( {
																messageBorderWidth:
																	value,
															} )
														}
													/>
													<TextControl
														label={ __(
															'Border Color',
															'dfx-tg-feed'
														) }
														help={ __(
															'e.g., #e1e8ed',
															'dfx-tg-feed'
														) }
														value={
															attributes.messageBorderColor
														}
														onChange={ ( value ) =>
															setAttributes( {
																messageBorderColor:
																	value,
															} )
														}
													/>
													<UnitControl
														label={ __(
															'Border Radius',
															'dfx-tg-feed'
														) }
														value={
															attributes.messageBorderRadius
														}
														onChange={ ( value ) =>
															setAttributes( {
																messageBorderRadius:
																	value,
															} )
														}
													/>
												</>
											) }
											{ BoxControl && (
												<>
													<BoxControl
														label={ __(
															'Padding',
															'dfx-tg-feed'
														) }
														values={
															attributes.messagePadding
														}
														onChange={ ( value ) =>
															setAttributes( {
																messagePadding:
																	value,
															} )
														}
													/>
													<BoxControl
														label={ __(
															'Margin',
															'dfx-tg-feed'
														) }
														values={
															attributes.messageMargin
														}
														onChange={ ( value ) =>
															setAttributes( {
																messageMargin:
																	value,
															} )
														}
													/>
												</>
											) }
										</PanelBody>
										<PanelBody
											title={ __(
												'Typography',
												'dfx-tg-feed'
											) }
											initialOpen={ false }
										>
											<h3>
												{ __( 'Date', 'dfx-tg-feed' ) }
											</h3>
											<SelectControl
												label={ __(
													'Font Family',
													'dfx-tg-feed'
												) }
												value={
													attributes.dateFontFamily
												}
												options={ fontFamilies }
												onChange={ ( value ) =>
													setAttributes( {
														dateFontFamily: value,
													} )
												}
											/>
											<UnitControl
												label={ __(
													'Font Size',
													'dfx-tg-feed'
												) }
												value={
													attributes.dateFontSize
												}
												onChange={ ( value ) =>
													setAttributes( {
														dateFontSize: value,
													} )
												}
											/>
											<TextControl
												label={ __(
													'Text Color',
													'dfx-tg-feed'
												) }
												help={ __(
													'e.g., #333333',
													'dfx-tg-feed'
												) }
												value={ attributes.dateColor }
												onChange={ ( value ) =>
													setAttributes( {
														dateColor: value,
													} )
												}
											/>
											<hr />
											<h3>
												{ __(
													'Author',
													'dfx-tg-feed'
												) }
											</h3>
											<SelectControl
												label={ __(
													'Font Family',
													'dfx-tg-feed'
												) }
												value={
													attributes.authorFontFamily
												}
												options={ fontFamilies }
												onChange={ ( value ) =>
													setAttributes( {
														authorFontFamily: value,
													} )
												}
											/>
											<UnitControl
												label={ __(
													'Font Size',
													'dfx-tg-feed'
												) }
												value={
													attributes.authorFontSize
												}
												onChange={ ( value ) =>
													setAttributes( {
														authorFontSize: value,
													} )
												}
											/>
											<TextControl
												label={ __(
													'Text Color',
													'dfx-tg-feed'
												) }
												help={ __(
													'e.g., #333333',
													'dfx-tg-feed'
												) }
												value={ attributes.authorColor }
												onChange={ ( value ) =>
													setAttributes( {
														authorColor: value,
													} )
												}
											/>
											<hr />
											<h3>
												{ __(
													'Message Text',
													'dfx-tg-feed'
												) }
											</h3>
											<SelectControl
												label={ __(
													'Font Family',
													'dfx-tg-feed'
												) }
												value={
													attributes.textFontFamily
												}
												options={ fontFamilies }
												onChange={ ( value ) =>
													setAttributes( {
														textFontFamily: value,
													} )
												}
											/>
											<UnitControl
												label={ __(
													'Font Size',
													'dfx-tg-feed'
												) }
												value={
													attributes.textFontSize
												}
												onChange={ ( value ) =>
													setAttributes( {
														textFontSize: value,
													} )
												}
											/>
											<TextControl
												label={ __(
													'Text Color',
													'dfx-tg-feed'
												) }
												help={ __(
													'e.g., #333333',
													'dfx-tg-feed'
												) }
												value={ attributes.textColor }
												onChange={ ( value ) =>
													setAttributes( {
														textColor: value,
													} )
												}
											/>
										</PanelBody>
									</>
								);
							}
						} }
					</TabPanel>
				</InspectorControls>
				<div { ...blockProps }>
					{ attributes.channel ? (
						<ServerSideRender
							block="dfx-tg-feed/channel-browser"
							attributes={ attributes }
						/>
					) : (
						<div
							className="dfx-tg-feed-placeholder"
							style={ {
								padding: '20px',
								border: '1px dashed #ccc',
								textAlign: 'center',
							} }
						>
							{ __(
								'Please enter a channel username in the block settings.',
								'dfx-tg-feed'
							) }
						</div>
					) }
				</div>
			</>
		);
	},
	save: () => {
		return null; // Server-side rendering
	},
} );
