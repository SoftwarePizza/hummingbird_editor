<?php
/**
 * 1.13.0 — zakladka "Miniatury": wyglad kafla produktu z panelu.
 *
 * Do tej pory kazdy sklep dostawal swoj wyglad miniatury recznym CSS-em
 * w motywie (zerowanie 40 px paddingu, przerwy zamiast kresek w karuzeli,
 * liczba kolumn). Teraz opisuja to ustawienia HBE_MINI_*, a modul sklada
 * z nich arkusz wstrzykiwany za <body>.
 *
 * Wartosci startowe = wyglad motywu, a HBE_MINI_ENABLED = 0 sprawia, ze po
 * aktualizacji nie zmienia sie ani jeden piksel — dopoki sklep sam nie wybierze
 * zestawu w BO. Sklepy, ktore maja swoj CSS w motywie, moga go zostawic:
 * arkusz modulu leci pozniej, wiec wybor z panelu jest ostatnim slowem.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_13_0($module)
{
    foreach (Hummingbird_editor::MINIATURE_DEFAULTS as $key => $default) {
        $name = 'HBE_MINI_' . strtoupper($key);
        if (Configuration::get($name) === false) {
            Configuration::updateValue($name, $default);
        }
    }

    return true;
}
