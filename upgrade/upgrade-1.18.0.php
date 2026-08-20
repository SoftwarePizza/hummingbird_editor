<?php
/**
 * 1.18.0 — scalenie dwoch linii rozwoju modulu:
 *
 *  - origin (Rosenthal/Karenski) mial jako 1.15.0 zakladke "Stopka" (pasek
 *    prawny z HBE_FOOTER_LINK_*, telefon i e-mail, kolumny ps_linklist),
 *  - linia izpol miala jako 1.15.0 "Darmowy odbior osobisty" (hook
 *    actionPresentCart + override DeliveryOptionsFinder), potem 1.16.0 (stan
 *    magazynowy) i 1.17.0 (karuzele).
 *
 * Ten sam numer po obu stronach oznacza, ze kazdy sklep ma w bazie "1.15.0"
 * i NIE uruchomi cudzego upgrade-1.15.0.php. Tutaj dokladamy wiec obie rzeczy
 * idempotentnie — kazdy sklep dostaje to, czego mu brakuje.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_18_0($module)
{
    // --- zakladka "Stopka" (z linii origin) ---------------------------------
    for ($i = 1; $i <= Hummingbird_editor::FOOTER_LINK_SLOTS; $i++) {
        foreach (['label', 'url'] as $part) {
            $key = Hummingbird_editor::footerLinkKey($i, $part);
            if (Configuration::get($key) === false) {
                Configuration::updateValue($key, '');
            }
        }
    }
    // Zasiew po id stron CMS Rosenthala — w innym sklepie te id to inne strony,
    // wiec seed sam sprawdza istnienie, a sloty juz wypelnione zostawia w spokoju.
    // Na sklepie z innym ukladem CMS pasek ustawia sie recznie w BO
    // (Hummingbird Editor > Stopka > Linki na dole stopki).
    $module->seedFooterLegalLinks();

    // --- "Darmowy odbior osobisty" (z linii izpol, tam bylo to 1.15.0) --------
    if (!$module->isRegisteredInHook('actionPresentCart')) {
        $module->registerHook('actionPresentCart');
    }
    $target = _PS_OVERRIDE_DIR_ . 'classes/checkout/DeliveryOptionsFinder.php';
    if (!file_exists($target) || strpos((string) file_get_contents($target), 'relabelFreePickupOptions') === false) {
        try {
            $module->installOverrides();
        } catch (Exception $e) {
            // Sklep z wlasnym override'em tej klasy: zostaje jego wersja.
        }
    }

    return true;
}
