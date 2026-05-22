/**
 * WhatsApp Button block.
 *
 * Server-rendered (PHP render_callback in includes/blocks.php). The editor
 * gives the admin three controls — phone, text, preset message — and a
 * static preview anchor. No JSX so the plugin can ship without a build step.
 */
( function ( blocks, element, blockEditor, components, i18n ) {
    var el = element.createElement;
    var __ = i18n.__;

    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody         = components.PanelBody;
    var TextControl       = components.TextControl;
    var TextareaControl   = components.TextareaControl;

    blocks.registerBlockType( 'zignites-chat/whatsapp-button', {
        edit: function ( props ) {
            var attrs = props.attributes;
            var label = attrs.text && attrs.text.length ? attrs.text : __( 'Chat on WhatsApp', 'zignites-chat' );

            return [
                el(
                    InspectorControls,
                    { key: 'inspector' },
                    el(
                        PanelBody,
                        { title: __( 'Button', 'zignites-chat' ), initialOpen: true },
                        el( TextControl, {
                            label: __( 'Phone number', 'zignites-chat' ),
                            value: attrs.phone || '',
                            onChange: function ( v ) { props.setAttributes( { phone: v } ); },
                            help: __( 'Include the country code, e.g. +14155550100. Leave blank to open WhatsApp without a destination.', 'zignites-chat' )
                        } ),
                        el( TextControl, {
                            label: __( 'Button text', 'zignites-chat' ),
                            value: attrs.text || '',
                            onChange: function ( v ) { props.setAttributes( { text: v } ); }
                        } ),
                        el( TextareaControl, {
                            label: __( 'Preset message', 'zignites-chat' ),
                            value: attrs.message || '',
                            onChange: function ( v ) { props.setAttributes( { message: v } ); },
                            help: __( 'Shown as the prefilled WhatsApp message body.', 'zignites-chat' )
                        } )
                    )
                ),
                el(
                    'div',
                    { key: 'preview', className: 'zignites-chat-whatsapp-button-block' },
                    el(
                        'a',
                        {
                            className: 'zignites-chat-whatsapp-button',
                            // No real href in the editor — the live anchor is rendered server-side.
                            onClick: function ( e ) { e.preventDefault(); }
                        },
                        label
                    )
                )
            ];
        },
        save: function () {
            return null;
        }
    } );
}( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n ) );
