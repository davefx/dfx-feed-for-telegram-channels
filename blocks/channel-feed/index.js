/**
 * Telegram Channel Feed Block
 */
(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, TextControl, RangeControl } = wp.components;
    const { __ } = wp.i18n;
    const { serverSideRender: ServerSideRender } = wp;

    registerBlockType('dfx-tg-feed/channel-feed', {
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            return [
                wp.element.createElement(InspectorControls, { key: 'inspector' },
                    wp.element.createElement(PanelBody, { title: __('Settings', 'dfx-tg-feed'), initialOpen: true },
                        wp.element.createElement(TextControl, {
                            label: __('Channel Username', 'dfx-tg-feed'),
                            help: __('Enter channel username with @ (e.g., @yourchannel) or channel ID', 'dfx-tg-feed'),
                            value: attributes.channel,
                            onChange: function(value) { setAttributes({ channel: value }); }
                        }),
                        wp.element.createElement(RangeControl, {
                            label: __('Number of Messages', 'dfx-tg-feed'),
                            value: attributes.count,
                            onChange: function(value) { setAttributes({ count: value }); },
                            min: 1,
                            max: 100
                        })
                    )
                ),
                wp.element.createElement('div', blockProps,
                    attributes.channel
                        ? wp.element.createElement(ServerSideRender, {
                            block: 'dfx-tg-feed/channel-feed',
                            attributes: attributes
                        })
                        : wp.element.createElement('div', { style: { padding: '20px', border: '1px dashed #ccc', textAlign: 'center' } },
                            __('Please enter a channel username in the block settings.', 'dfx-tg-feed')
                        )
                )
            ];
        },
        save: function() {
            return null; // Server-side rendering
        }
    });
})(window.wp);
