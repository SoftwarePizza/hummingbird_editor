<?php
/**
 * 1.19.0 — progi rabatowe z kodami („kupuj więcej, płać mniej”).
 *
 * Pasek „do rabatu X brakuje Ci Y” w koszyku, podglądzie/modalu i na karcie
 * produktu + sekcja strony głównej, wszystko z kodów reguł koszyka wpisanych
 * w panelu (BO → Hummingbird → Koszyk → Progi rabatowe). Startuje WYŁĄCZONE —
 * sklep bez takich reguł nie zobaczy żadnej zmiany.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_19_0($module)
{
    foreach ([
        HbEditorDiscountTiers::CONF_ENABLED      => 0,
        HbEditorDiscountTiers::CONF_CODES        => '',
        HbEditorDiscountTiers::CONF_SHOW_CART    => 1,
        HbEditorDiscountTiers::CONF_SHOW_PRODUCT => 1,
        HbEditorDiscountTiers::CONF_HOME_ENABLED => 0,
    ] as $key => $default) {
        if (Configuration::get($key) === false) {
            Configuration::updateValue($key, $default);
        }
    }

    if (!$module->isRegisteredInHook('displayHbeTiers')) {
        $module->registerHook('displayHbeTiers');
    }

    return true;
}
