<?php
/**
 * 1.7.0 — Rosenthal Care promo block in the cart. Registers the new
 * displayShoppingCartFooter hook and seeds the default configuration.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_7_0($module)
{
    $defaults = [
        'HBE_CARE_ENABLED'        => 0,
        'HBE_CARE_PRODUCT_ID'     => 4682,
        'HBE_CARE_HEADING'        => 'Czy chcesz objąć produkty Rosenthal Care?',
        'HBE_CARE_TEXT'           => "Tylko za 10 zł możesz skorzystać z wymiany uszkodzonego / stłuczonego produktu na nowy za 50% jego wartości.\nDo rozliczenia na podstawie zachowanego paragonu przyjmujemy ceny aktualne w dniu wymiany.\nWymiany można dokonać w okresie 12 miesięcy od dokonania zakupu pod warunkiem, że uszkodzony produkt znajduje się w aktualnej ofercie.",
        'HBE_CARE_BUTTON'         => 'Dodaj Rosenthal Care',
        'HBE_CARE_LOGIN_REQUIRED' => 1,
    ];
    foreach ($defaults as $key => $value) {
        if (Configuration::get($key) === false) {
            Configuration::updateValue($key, $value);
        }
    }

    return $module->registerHook('displayShoppingCartFooter');
}
