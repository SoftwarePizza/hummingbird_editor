<?php
/**
 * 1.16.0 — stan magazynowy pod przyciskiem koszyka: próg zachęty i rabat
 * za zabranie całości.
 *
 * Sam blok „Na stanie: 6,2 m — tyle maksymalnie możesz dodać" renderuje motyw;
 * moduł dokłada sterowanie z panelu (BO → Hummingbird → Karta produktu →
 * „Stan magazynowy przy koszyku") oraz hook cenowy dla rabatu. Na sklepach,
 * których motyw tego bloku nie ma, ustawienia leżą bezczynnie.
 *
 * Rabat startuje WYŁĄCZONY — to pieniądze, więc włącza go sklep świadomie.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_16_0($module)
{
    if (Configuration::get(Hummingbird_editor::CONF_STOCK_HINT_ENABLED) === false) {
        Configuration::updateValue(Hummingbird_editor::CONF_STOCK_HINT_ENABLED, 1);
    }
    if (Configuration::get(Hummingbird_editor::CONF_STOCK_HINT_THRESHOLD) === false) {
        Configuration::updateValue(Hummingbird_editor::CONF_STOCK_HINT_THRESHOLD, '3');
    }
    if (Configuration::get(Hummingbird_editor::CONF_ALLSTOCK_DISCOUNT_ENABLED) === false) {
        Configuration::updateValue(Hummingbird_editor::CONF_ALLSTOCK_DISCOUNT_ENABLED, 0);
    }
    if (Configuration::get(Hummingbird_editor::CONF_ALLSTOCK_DISCOUNT_RATE) === false) {
        Configuration::updateValue(Hummingbird_editor::CONF_ALLSTOCK_DISCOUNT_RATE, '5');
    }
    if (Configuration::get(Hummingbird_editor::CONF_ALLSTOCK_DISCOUNT_RATE_SALE) === false) {
        Configuration::updateValue(Hummingbird_editor::CONF_ALLSTOCK_DISCOUNT_RATE_SALE, '2');
    }

    // Bez tego hooka rabat nie ma jak zadziałać: rdzeń podaje w nim cenę
    // pozycji koszyka przez referencję.
    if (!$module->isRegisteredInHook('actionProductPriceCalculation')) {
        $module->registerHook('actionProductPriceCalculation');
    }
    // Czysci zapamietane ilosci pozycji koszyka, gdy klient zmieni ilosc
    // w trakcie tego samego zadania.
    if (!$module->isRegisteredInHook('actionCartUpdateQuantityBefore')) {
        $module->registerHook('actionCartUpdateQuantityBefore');
    }

    return true;
}
