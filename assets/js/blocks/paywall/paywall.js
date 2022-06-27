/**
 * Script handles gutenberg block: alby/paywall
 */
 window.addEventListener("DOMContentLoaded", function () {
    ( function ( blocks, blockEditor, element, components, i18n ) {

        var el = element.createElement;
        var BlockControls = blockEditor.BlockControls;
        var TextControl = components.TextControl;
        var __ = i18n.__;
        var useBlockProps = blockEditor.useBlockProps;
     
        blocks.registerBlockType( 'alby/paywall', {
            attributes: {
                amount: {
                  type: 'number',
                },
                text: {
                  type: 'string',
                }
            },
            edit: function ( props )
            {
                const { amount, text } = props.attributes;
                return el(
                    'div',
                    useBlockProps({ className: props.className }),
                    el(
                       BlockControls,
                       { key: 'controls' },
                    ),
                    el(
                      TextControl,
                        {
                          label: __("Amount", "alby"),
                          onChange: (v) => { props.setAttributes( { amount: parseInt(v) } ) },
                          value: amount || 1000,
                        }
                      ),
                    el(
                        TextControl,
                        {
                          label: __("Button Label", "alby"),
                          onChange: (v) => { props.setAttributes( { text: v } ) },
                          value: text || 'Pay now',
                        }
                      ),
                    el('hr', {className: "lnp-alby-paywall-widget" })
                );
                
            },
            save: function ( props )
            {
              let amount = 1000;
              let text = 'Pay now';
              if (props?.attributes?.amount != undefined) {
                amount = props?.attributes?.amount;
              }
              if (props?.attributes?.text != undefined) {
                text = props?.attributes?.text;
              }
              return el(
                'p', {}, `[ln amount=${amount} button_text="${text}"]`
              )
            },
        } );
    })( window.wp.blocks, window.wp.blockEditor, window.wp.element, window.wp.components, window.wp.i18n );
});