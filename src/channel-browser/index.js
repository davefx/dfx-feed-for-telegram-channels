import { registerBlockType } from '@wordpress/blocks';
import {
	InspectorControls,
	useBlockProps,
	ColorPalette,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TabPanel,
	SelectControl,
	BaseControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUnitControl as UnitControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalBoxControl as BoxControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.css';

registerBlockType( 'dfxtgfeed/channel-browser', {
	title: __( 'Telegram Channel Browser', 'dfx-telegram-channel-feed' ),
	description: __(
		'Browse and display full message history from a Telegram channel',
		'dfx-telegram-channel-feed'
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
		blockBoxShadow: {
			type: 'string',
			default: '',
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
		messageBoxShadow: {
			type: 'string',
			default: '',
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
			{ label: __( 'Default', 'dfx-telegram-channel-feed' ), value: '' },
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
			{ label: __( 'None', 'dfx-telegram-channel-feed' ), value: '' },
			{ label: __( 'Solid', 'dfx-telegram-channel-feed' ), value: 'solid' },
			{ label: __( 'Dashed', 'dfx-telegram-channel-feed' ), value: 'dashed' },
			{ label: __( 'Dotted', 'dfx-telegram-channel-feed' ), value: 'dotted' },
			{ label: __( 'Double', 'dfx-telegram-channel-feed' ), value: 'double' },
		];

		const shadowPresets = [
			{ label: __( 'None', 'dfx-telegram-channel-feed' ), value: '' },
			{
				label: __( 'Small', 'dfx-telegram-channel-feed' ),
				value: '0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)',
			},
			{
				label: __( 'Medium', 'dfx-telegram-channel-feed' ),
				value: '0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23)',
			},
			{
				label: __( 'Large', 'dfx-telegram-channel-feed' ),
				value: '0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23)',
			},
			{
				label: __( 'Extra Large', 'dfx-telegram-channel-feed' ),
				value: '0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22)',
			},
			{
				label: __( 'Inset', 'dfx-telegram-channel-feed' ),
				value: 'inset 0 2px 4px rgba(0,0,0,0.15)',
			},
			{ label: __( 'Custom', 'dfx-telegram-channel-feed' ), value: 'custom' },
		];

		return (
			<>
				<InspectorControls>
					<TabPanel
						className="dfxtgfeed-tabs"
						activeClass="is-active"
						tabs={ [
							{
								name: 'settings',
								title: __( 'Settings', 'dfx-telegram-channel-feed' ),
								className: 'tab-settings',
							},
							{
								name: 'block-styles',
								title: __( 'Block Styles', 'dfx-telegram-channel-feed' ),
								className: 'tab-block-styles',
							},
							{
								name: 'message-styles',
								title: __( 'Message Styles', 'dfx-telegram-channel-feed' ),
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
												'dfx-telegram-channel-feed'
											) }
											initialOpen={ true }
										>
											<TextControl
												label={ __(
													'Channel Username',
													'dfx-telegram-channel-feed'
												) }
												help={ __(
													'Enter channel username with @ (e.g., @yourchannel) or channel ID',
													'dfx-telegram-channel-feed'
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
												'dfx-telegram-channel-feed'
											) }
											initialOpen={ true }
										>
											<BaseControl
												id="block-background-color"
												label={ __(
													'Background Color',
													'dfx-telegram-channel-feed'
												) }
											>
												<ColorPalette
													value={
														attributes.blockBackground
													}
													onChange={ ( value ) =>
														setAttributes( {
															blockBackground:
																value || '',
														} )
													}
													clearable={ true }
												/>
											</BaseControl>
											<SelectControl
												label={ __(
													'Border Style',
													'dfx-telegram-channel-feed'
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
															'dfx-telegram-channel-feed'
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
													<BaseControl
														id="block-border-color"
														label={ __(
															'Border Color',
															'dfx-telegram-channel-feed'
														) }
													>
														<ColorPalette
															value={
																attributes.blockBorderColor
															}
															onChange={ (
																value
															) =>
																setAttributes( {
																	blockBorderColor:
																		value ||
																		'',
																} )
															}
															clearable={ true }
														/>
													</BaseControl>
													<UnitControl
														label={ __(
															'Border Radius',
															'dfx-telegram-channel-feed'
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
															'dfx-telegram-channel-feed'
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
															'dfx-telegram-channel-feed'
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
											<SelectControl
												label={ __(
													'Box Shadow',
													'dfx-telegram-channel-feed'
												) }
												value={
													shadowPresets.find(
														( preset ) =>
															preset.value ===
																attributes.blockBoxShadow ||
															( attributes.blockBoxShadow &&
																preset.value ===
																	'custom' )
													)?.value || ''
												}
												options={ shadowPresets }
												onChange={ ( value ) => {
													if (
														value === 'custom' &&
														! attributes.blockBoxShadow
													) {
														setAttributes( {
															blockBoxShadow:
																'0 0 10px rgba(0,0,0,0.1)',
														} );
													} else if (
														value !== 'custom'
													) {
														setAttributes( {
															blockBoxShadow:
																value,
														} );
													}
												} }
											/>
											{ ( shadowPresets.find(
												( preset ) =>
													preset.value ===
													attributes.blockBoxShadow
											)?.value === 'custom' ||
												( attributes.blockBoxShadow &&
													! shadowPresets.find(
														( preset ) =>
															preset.value ===
															attributes.blockBoxShadow
													) ) ||
												attributes.blockBoxShadow ) && (
												<TextControl
													label={ __(
														'Custom Shadow (CSS)',
														'dfx-telegram-channel-feed'
													) }
													help={ __(
														'e.g., 0 4px 6px rgba(0,0,0,0.1)',
														'dfx-telegram-channel-feed'
													) }
													value={
														attributes.blockBoxShadow
													}
													onChange={ ( value ) =>
														setAttributes( {
															blockBoxShadow:
																value,
														} )
													}
												/>
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
												'dfx-telegram-channel-feed'
											) }
											initialOpen={ true }
										>
											<BaseControl
												id="message-background-color"
												label={ __(
													'Background Color',
													'dfx-telegram-channel-feed'
												) }
											>
												<ColorPalette
													value={
														attributes.messageBackground
													}
													onChange={ ( value ) =>
														setAttributes( {
															messageBackground:
																value || '',
														} )
													}
													clearable={ true }
												/>
											</BaseControl>
											<SelectControl
												label={ __(
													'Border Style',
													'dfx-telegram-channel-feed'
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
															'dfx-telegram-channel-feed'
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
													<BaseControl
														id="message-border-color"
														label={ __(
															'Border Color',
															'dfx-telegram-channel-feed'
														) }
													>
														<ColorPalette
															value={
																attributes.messageBorderColor
															}
															onChange={ (
																value
															) =>
																setAttributes( {
																	messageBorderColor:
																		value ||
																		'',
																} )
															}
															clearable={ true }
														/>
													</BaseControl>
													<UnitControl
														label={ __(
															'Border Radius',
															'dfx-telegram-channel-feed'
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
															'dfx-telegram-channel-feed'
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
															'dfx-telegram-channel-feed'
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
											<SelectControl
												label={ __(
													'Box Shadow',
													'dfx-telegram-channel-feed'
												) }
												value={
													shadowPresets.find(
														( preset ) =>
															preset.value ===
																attributes.messageBoxShadow ||
															( attributes.messageBoxShadow &&
																preset.value ===
																	'custom' )
													)?.value || ''
												}
												options={ shadowPresets }
												onChange={ ( value ) => {
													if (
														value === 'custom' &&
														! attributes.messageBoxShadow
													) {
														setAttributes( {
															messageBoxShadow:
																'0 0 10px rgba(0,0,0,0.1)',
														} );
													} else if (
														value !== 'custom'
													) {
														setAttributes( {
															messageBoxShadow:
																value,
														} );
													}
												} }
											/>
											{ ( shadowPresets.find(
												( preset ) =>
													preset.value ===
													attributes.messageBoxShadow
											)?.value === 'custom' ||
												( attributes.messageBoxShadow &&
													! shadowPresets.find(
														( preset ) =>
															preset.value ===
															attributes.messageBoxShadow
													) ) ||
												attributes.messageBoxShadow ) && (
												<TextControl
													label={ __(
														'Custom Shadow (CSS)',
														'dfx-telegram-channel-feed'
													) }
													help={ __(
														'e.g., 0 4px 6px rgba(0,0,0,0.1)',
														'dfx-telegram-channel-feed'
													) }
													value={
														attributes.messageBoxShadow
													}
													onChange={ ( value ) =>
														setAttributes( {
															messageBoxShadow:
																value,
														} )
													}
												/>
											) }
										</PanelBody>
										<PanelBody
											title={ __(
												'Typography',
												'dfx-telegram-channel-feed'
											) }
											initialOpen={ false }
										>
											<h3>
												{ __( 'Date', 'dfx-telegram-channel-feed' ) }
											</h3>
											<SelectControl
												label={ __(
													'Font Family',
													'dfx-telegram-channel-feed'
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
													'dfx-telegram-channel-feed'
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
											<BaseControl
												id="date-text-color"
												label={ __(
													'Text Color',
													'dfx-telegram-channel-feed'
												) }
											>
												<ColorPalette
													value={
														attributes.dateColor
													}
													onChange={ ( value ) =>
														setAttributes( {
															dateColor:
																value || '',
														} )
													}
													clearable={ true }
												/>
											</BaseControl>
											<hr />
											<h3>
												{ __( 'Author', 'dfx-telegram-channel-feed' ) }
											</h3>
											<SelectControl
												label={ __(
													'Font Family',
													'dfx-telegram-channel-feed'
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
													'dfx-telegram-channel-feed'
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
											<BaseControl
												id="author-text-color"
												label={ __(
													'Text Color',
													'dfx-telegram-channel-feed'
												) }
											>
												<ColorPalette
													value={
														attributes.authorColor
													}
													onChange={ ( value ) =>
														setAttributes( {
															authorColor:
																value || '',
														} )
													}
													clearable={ true }
												/>
											</BaseControl>
											<hr />
											<h3>
												{ __(
													'Message Text',
													'dfx-telegram-channel-feed'
												) }
											</h3>
											<SelectControl
												label={ __(
													'Font Family',
													'dfx-telegram-channel-feed'
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
													'dfx-telegram-channel-feed'
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
											<BaseControl
												id="text-color"
												label={ __(
													'Text Color',
													'dfx-telegram-channel-feed'
												) }
											>
												<ColorPalette
													value={
														attributes.textColor
													}
													onChange={ ( value ) =>
														setAttributes( {
															textColor:
																value || '',
														} )
													}
													clearable={ true }
												/>
											</BaseControl>
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
							block="dfxtgfeed/channel-browser"
							attributes={ attributes }
						/>
					) : (
						<div
							className="dfxtgfeed-placeholder"
							style={ {
								padding: '20px',
								border: '1px dashed #ccc',
								textAlign: 'center',
							} }
						>
							{ __(
								'Please enter a channel username in the block settings.',
								'dfx-telegram-channel-feed'
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
