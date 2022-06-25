/**
 * Script handles gutenberg block: alby/paywall
 */
 window.addEventListener("DOMContentLoaded", function () {
    ( function ( blocks, blockEditor, element ) {

        var el = element.createElement;
        var BlockControls = blockEditor.BlockControls;
        var useBlockProps = blockEditor.useBlockProps;
     
        blocks.registerBlockType( 'alby/paywall', {
            edit: function ()
            {
                return el(
                    'div',
                    useBlockProps(),
                    el(
                       BlockControls,
                       { key: 'controls' },
                    ),
                    el('hr', {class: "lnp-alby-paywall-widget" })
                );
                
            },
            save: function ()
            {
                return [
                    el(
                       'div', {},
                       el('hr', {class: "lnp-alby-paywall-widget" })
                    ),
                ];
            },
        } );
    })( window.wp.blocks, window.wp.blockEditor, window.wp.element );
});