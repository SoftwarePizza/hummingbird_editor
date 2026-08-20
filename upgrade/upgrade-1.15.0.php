<?php
/**
 * 1.15.0 — "Darmowy odbiór osobisty" zamiast samego "Za darmo!".
 *
 * Dokłada hook actionPresentCart (wiersz "Wysyłka" w koszyku) i override
 * DeliveryOptionsFinder (lista przewoźników w kroku "Przesyłka" oraz
 * podsumowanie zamówienia). Sama etykieta pojawia się dopiero, gdy w
 * BO → Hummingbird → Kasa ktoś wskaże przewoźników będących odbiorem
 * osobistym — do tego czasu sklep wygląda dokładnie tak, jak przedtem.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_15_0($module)
{
    if (!$module->isRegisteredInHook('actionPresentCart')) {
        $module->registerHook('actionPresentCart');
    }

    // installOverrides() scala metody z tym, co już leży w override/ sklepu,
    // i samo przebudowuje class_index; przy powtórnym wywołaniu zgłasza
    // konflikt na istniejącej metodzie, stąd sprawdzenie pliku.
    $target = _PS_OVERRIDE_DIR_ . 'classes/checkout/DeliveryOptionsFinder.php';
    if (!file_exists($target) || strpos((string) file_get_contents($target), 'relabelFreePickupOptions') === false) {
        try {
            $module->installOverrides();
        } catch (Exception $e) {
            // Sklep z własnym override'em tej klasy: zostawiamy jego wersję,
            // kasa działa dalej po staremu (koszyk i tak dostaje etykietę).
            PrestaShopLogger::addLog(
                'hummingbird_editor 1.15.0: nie udało się wgrać override DeliveryOptionsFinder — ' . $e->getMessage(),
                2
            );
        }
    }

    return true;
}
