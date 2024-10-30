const copperx_settings = window.wc.wcSettings.getSetting( 'copperx_data', {} );
const copperx_ariaLabel = window.wp.htmlEntities.decodeEntities( copperx_settings.title ) || window.wp.i18n.__( 'Copperx', 'copperx' );
const CopperxLabel = () => {
    const title = window.wp.htmlEntities.decodeEntities( copperx_settings.title ) || window.wp.i18n.__( 'Copperx', 'copperx' );
    const iconsHtml = window.wp.htmlEntities.decodeEntities(copperx_settings.icons_html || '');

    return [
        window.wp.element.createElement('span', null, title),
        window.wp.element.createElement('span', { dangerouslySetInnerHTML: { __html: iconsHtml } })
    ];
}

const CopperxContent = () => {
    return window.wp.htmlEntities.decodeEntities( copperx_settings.description || '' );
};

const Copperx_Block_Gateway = {
    name: 'copperx',
    label: Object( window.wp.element.createElement )( CopperxLabel, null ),
    content: Object( window.wp.element.createElement )( CopperxContent, null ),
    edit: Object( window.wp.element.createElement )( CopperxContent, null ),
    canMakePayment: () => true,
    ariaLabel: copperx_ariaLabel,
    supports: {
        features: copperx_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Copperx_Block_Gateway );
