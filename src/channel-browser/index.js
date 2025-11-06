import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('dfx-tg-feed/channel-browser', {
    title: __('Telegram Channel Browser', 'dfx-tg-feed'),
    description: __('Browse and display full message history from a Telegram channel', 'dfx-tg-feed'),
    category: 'widgets',
    icon: 'list-view',
    attributes: {
        channel: {
            type: 'string',
            default: ''
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
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    {attributes.channel ? (
                        <ServerSideRender
                            block="dfx-tg-feed/channel-browser"
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
