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

    blocks.registerBlockType( 'woochat-pro/whatsapp-button', {
        edit: function ( props ) {
            var attrs = props.attributes;
            var label = attrs.text && attrs.text.length ? attrs.text : __( 'Chat on WhatsApp', 'woochat-pro' );

            return [
                el(
                    InspectorControls,
                    { key: 'inspector' },
                    el(
                        PanelBody,
                        { title: __( 'Button', 'woochat-pro' ), initialOpen: true },
                        el( TextControl, {
                            label: __( 'Phone number', 'woochat-pro' ),
                            value: attrs.phone || '',
                            onChange: function ( v ) { props.setAttributes( { phone: v } ); },
                            help: __( 'Include the country code, e.g. +14155550100. Leave blank to open WhatsApp without a destination.', 'woochat-pro' )
                        } ),
                        el( TextControl, {
                            label: __( 'Button text', 'woochat-pro' ),
                            value: attrs.text || '',
                            onChange: function ( v ) { props.setAttributes( { text: v } ); }
                        } ),
                        el( TextareaControl, {
                            label: __( 'Preset message', 'woochat-pro' ),
                            value: attrs.message || '',
                            onChange: function ( v ) { props.setAttributes( { message: v } ); },
                            help: __( 'Shown as the prefilled WhatsApp message body.', 'woochat-pro' )
                        } )
                    )
                ),
                el(
                    'div',
                    { key: 'preview', className: 'wcwp-whatsapp-button-block' },
                    el(
                        'a',
                        {
                            className: 'wcwp-whatsapp-button',
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
