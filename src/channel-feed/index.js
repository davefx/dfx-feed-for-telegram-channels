import { registerBlockType } from '@wordpress/blocks';
import {
	InspectorControls,
	useBlockProps,
	ColorPalette,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	RangeControl,
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

registerBlockType( 'dfxfftc/channel-feed', {
	title: __( 'Telegram Channel Feed', 'dfx-feed-for-telegram-channels' ),
	description: __(
		'Display recent messages from a Telegram channel',
		'dfx-feed-for-telegram-channels'
	),
	category: 'widgets',
	icon: 'rss',
	attributes: {
		channel: {
			type: 'string',
			default: '',
		},
		count: {
			type: 'number',
			default: 10,
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
			{ label: __( 'Default', 'dfx-feed-for-telegram-channels' ), value: '' },
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
			{ label: __( 'None', 'dfx-feed-for-telegram-channels' ), value: '' },
			{ label: __( 'Solid', 'dfx-feed-for-telegram-channels' ), value: 'solid' },
			{ label: __( 'Dashed', 'dfx-feed-for-telegram-channels' ), value: 'dashed' },
			{ label: __( 'Dotted', 'dfx-feed-for-telegram-channels' ), value: 'dotted' },
			{ label: __( 'Double', 'dfx-feed-for-telegram-channels' ), value: 'double' },
		];

		const shadowPresets = [
			{ label: __( 'None', 'dfx-feed-for-telegram-channels' ), value: '' },
			{
				label: __( 'Small', 'dfx-feed-for-telegram-channels' ),
				value: '0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)',
			},
			{
				label: __( 'Medium', 'dfx-feed-for-telegram-channels' ),
				value: '0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23)',
			},
			{
				label: __( 'Large', 'dfx-feed-for-telegram-channels' ),
				value: '0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23)',
			},
			{
				label: __( 'Extra Large', 'dfx-feed-for-telegram-channels' ),
				value: '0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22)',
			},
			{
				label: __( 'Inset', 'dfx-feed-for-telegram-channels' ),
				value: 'inset 0 2px 4px rgba(0,0,0,0.15)',
			},
			{ label: __( 'Custom', 'dfx-feed-for-telegram-channels' ), value: 'custom' },
		];

		return (
			<>
				<InspectorControls>
					<TabPanel
						className="dfxfftc-tabs"
						activeClass="is-active"
						tabs={ [
							{
								name: 'settings',
								title: __( 'Settings', 'dfx-feed-for-telegram-channels' ),
								className: 'tab-settings',
							},
							{
								name: 'block-styles',
								title: __( 'Block Styles', 'dfx-feed-for-telegram-channels' ),
								className: 'tab-block-styles',
							},
							{
								name: 'message-styles',
								title: __( 'Message Styles', 'dfx-feed-for-telegram-channels' ),
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
												'dfx-feed-for-telegram-channels'
											) }
											initialOpen={ true }
										>
											<TextControl
												label={ __(
													'Channel Username',
													'dfx-feed-for-telegram-channels'
												) }
												help={ __(
													'Enter channel username with @ (e.g., @yourchannel) or channel ID',
													'dfx-feed-for-telegram-channels'
												) }
												value={ attributes.channel }
												onChange={ ( value ) =>
													setAttributes( {
														channel: value,
													} )
												}
											/>
											<RangeControl
												label={ __(
													'Number of Messages',
													'dfx-feed-for-telegram-channels'
												) }
												value={ attributes.count }
												onChange={ ( value ) =>
													setAttributes( {
														count: value,
													} )
												}
												min={ 1 }
												max={ 100 }
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
												'dfx-feed-for-telegram-channels'
											) }
											initialOpen={ true }
										>
											<BaseControl
												id="block-background-color-control"
												label={ __(
													'Background Color',
													'dfx-feed-for-telegram-channels'
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
													'dfx-feed-for-telegram-channels'
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
															'dfx-feed-for-telegram-channels'
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
														id="block-background-color-control"
														label={ __(
															'Border Color',
															'dfx-feed-for-telegram-channels'
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
															'dfx-feed-for-telegram-channels'
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
															'dfx-feed-for-telegram-channels'
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
															'dfx-feed-for-telegram-channels'
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
													'dfx-feed-for-telegram-channels'
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
														'dfx-feed-for-telegram-channels'
													) }
													help={ __(
														'e.g., 0 4px 6px rgba(0,0,0,0.1)',
														'dfx-feed-for-telegram-channels'
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
												'dfx-feed-for-telegram-channels'
											) }
											initialOpen={ true }
										>
											<BaseControl
												id="block-background-color-control"
												label={ __(
													'Background Color',
													'dfx-feed-for-telegram-channels'
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
													'dfx-feed-for-telegram-channels'
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
															'dfx-feed-for-telegram-channels'
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
														id="block-background-color-control"
														label={ __(
															'Border Color',
															'dfx-feed-for-telegram-channels'
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
															'dfx-feed-for-telegram-channels'
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
															'dfx-feed-for-telegram-channels'
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
															'dfx-feed-for-telegram-channels'
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
													'dfx-feed-for-telegram-channels'
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
														'dfx-feed-for-telegram-channels'
													) }
													help={ __(
														'e.g., 0 4px 6px rgba(0,0,0,0.1)',
														'dfx-feed-for-telegram-channels'
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
												'dfx-feed-for-telegram-channels'
											) }
											initialOpen={ false }
										>
											<h3>
												{ __( 'Date', 'dfx-feed-for-telegram-channels' ) }
											</h3>
											<SelectControl
												label={ __(
													'Font Family',
													'dfx-feed-for-telegram-channels'
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
													'dfx-feed-for-telegram-channels'
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
												id="block-background-color-control"
												label={ __(
													'Text Color',
													'dfx-feed-for-telegram-channels'
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
												{ __( 'Author', 'dfx-feed-for-telegram-channels' ) }
											</h3>
											<SelectControl
												label={ __(
													'Font Family',
													'dfx-feed-for-telegram-channels'
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
													'dfx-feed-for-telegram-channels'
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
												id="block-background-color-control"
												label={ __(
													'Text Color',
													'dfx-feed-for-telegram-channels'
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
													'dfx-feed-for-telegram-channels'
												) }
											</h3>
											<SelectControl
												label={ __(
													'Font Family',
													'dfx-feed-for-telegram-channels'
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
													'dfx-feed-for-telegram-channels'
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
												id="block-background-color-control"
												label={ __(
													'Text Color',
													'dfx-feed-for-telegram-channels'
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
							block="dfxfftc/channel-feed"
							attributes={ attributes }
						/>
					) : (
						<div
							className="dfxfftc-placeholder"
							style={ {
								padding: '20px',
								border: '1px dashed #ccc',
								textAlign: 'center',
							} }
						>
							{ __(
								'Please enter a channel username in the block settings.',
								'dfx-feed-for-telegram-channels'
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
