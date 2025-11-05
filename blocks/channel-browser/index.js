/**
 * Telegram Channel Browser Block - Editor Script
 */
(function() {
    const { __ } = wp.i18n;
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, TextControl } = wp.components;
    const { ServerSideRender } = wp.serverSideRender || wp;

    registerBlockType('dfx-tg-feed/channel-browser', {
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            return wp.element.createElement(
                wp.element.Fragment,
                null,
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        { title: __('Settings', 'dfx-tg-feed'), initialOpen: true },
                        wp.element.createElement(TextControl, {
                            label: __('Channel Username', 'dfx-tg-feed'),
                            help: __('Enter channel username with @ (e.g., @yourchannel) or channel ID', 'dfx-tg-feed'),
                            value: attributes.channel,
                            onChange: function(value) { setAttributes({ channel: value }); }
                        })
                    )
                ),
                wp.element.createElement(
                    'div',
                    blockProps,
                    attributes.channel
                        ? wp.element.createElement(ServerSideRender, {
                            block: 'dfx-tg-feed/channel-browser',
                            attributes: attributes
                        })
                        : wp.element.createElement(
                            'div',
                            { className: 'dfx-tg-feed-placeholder', style: { padding: '20px', border: '1px dashed #ccc', textAlign: 'center' } },
                            __('Please enter a channel username in the block settings.', 'dfx-tg-feed')
                        )
                )
            );
        },
        save: function() {
            return null;
        }
    });
})();
