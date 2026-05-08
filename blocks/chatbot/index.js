/**
 * WooChat Chatbot block.
 *
 * Server-rendered (PHP render_callback in includes/blocks.php), so save()
 * returns null. The editor preview is a static placeholder card — calling
 * the live shortcode renderer would require ServerSideRender, which adds
 * REST + iframe overhead that is not worth it for a "chatbot lives in the
 * footer" placeholder.
 */
( function ( blocks, element, i18n ) {
    var el = element.createElement;
    var __ = i18n.__;

    blocks.registerBlockType( 'woochat-pro/chatbot', {
        edit: function () {
            return el(
                'div',
                { className: 'wcwp-block-placeholder' },
                el(
                    'span',
                    { className: 'wcwp-block-placeholder-icon', 'aria-hidden': 'true' },
                    '💬'
                ),
                el(
                    'div',
                    null,
                    el( 'strong', null, __( 'WooChat Chatbot', 'woochat-pro' ) ),
                    el(
                        'p',
                        { className: 'wcwp-block-placeholder-help' },
                        __( 'The floating chatbot widget will render on the published page when the chatbot is enabled and a Pro license is active.', 'woochat-pro' )
                    )
                )
            );
        },
        save: function () {
            return null;
        }
    } );
}( window.wp.blocks, window.wp.element, window.wp.i18n ) );
