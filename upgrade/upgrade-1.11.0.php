<?php
/**
 * 1.11.0 — "Karta podarunkowa" placements tab.
 *
 * The gift-card "buy" link used to live in the giftcard module's
 * displayLeftColumn block, which Hummingbird no longer renders. This module
 * now offers three configurable homes for it (main menu, footer block,
 * floating pill), so it needs the displayFooter hook and sensible defaults.
 *
 * All placements start disabled: config does not travel between environments,
 * so the shop enables what it wants per environment in the BO tab.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_11_0($module)
{
    $ok = $module->registerHook('displayFooter');

    foreach ([
        'HBE_GIFTCARD_MENU_ENABLED'   => 0,
        'HBE_GIFTCARD_FOOTER_ENABLED' => 0,
        'HBE_GIFTCARD_FLOAT_ENABLED'  => 0,
        'HBE_GIFTCARD_FLOAT_POSITION' => 'right',
        'HBE_GIFTCARD_MENU_LABEL'     => 'Karta podarunkowa',
        'HBE_GIFTCARD_FOOTER_LABEL'   => 'Karta podarunkowa',
        'HBE_GIFTCARD_FOOTER_DESC'    => 'Zawsze trafiony prezent — obdarowany sam wybierze, co pokocha.',
        'HBE_GIFTCARD_FLOAT_LABEL'    => 'Karta podarunkowa',
        'HBE_GIFTCARD_URL'            => '',
    ] as $key => $default) {
        if (Configuration::get($key) === false) {
            Configuration::updateValue($key, $default);
        }
    }

    return (bool) $ok;
}
