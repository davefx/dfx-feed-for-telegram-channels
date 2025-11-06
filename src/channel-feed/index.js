import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('dfx-tg-feed/channel-feed', {
    title: __('Telegram Channel Feed', 'dfx-tg-feed'),
    description: __('Display recent messages from a Telegram channel', 'dfx-tg-feed'),
    category: 'widgets',
    icon: 'rss',
    attributes: {
        channel: {
            type: 'string',
            default: ''
        },
        count: {
            type: 'number',
            default: 10
        }
    },
    edit: (props) => {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Settings', 'dfx-tg-feed')} initialOpen={true}>
                        <TextControl
                            label={__('Channel Username', 'dfx-tg-feed')}
                            help={__('Enter channel username with @ (e.g., @yourchannel) or channel ID', 'dfx-tg-feed')}
                            value={attributes.channel}
                            onChange={(value) => setAttributes({ channel: value })}
                        />
                        <RangeControl
                            label={__('Number of Messages', 'dfx-tg-feed')}
                            value={attributes.count}
                            onChange={(value) => setAttributes({ count: value })}
                            min={1}
                            max={100}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    {attributes.channel ? (
                        <ServerSideRender
                            block="dfx-tg-feed/channel-feed"
                            attributes={attributes}
                        />
                    ) : (
                        <div className="dfx-tg-feed-placeholder" style={{ padding: '20px', border: '1px dashed #ccc', textAlign: 'center' }}>
                            {__('Please enter a channel username in the block settings.', 'dfx-tg-feed')}
                        </div>
                    )}
                </div>
            </>
        );
    },
    save: () => {
        return null; // Server-side rendering
    }
});
