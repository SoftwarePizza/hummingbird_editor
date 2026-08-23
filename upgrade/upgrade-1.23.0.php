<?php
/**
 * 1.23.0 — strona 404 zamiast slepego zaulka.
 *
 * Motyw (templates/errors/404.tpl) wola hook displayNotFound; modul
 * dokłada tam kafle kategorii i skroty do stron, ktorych ludzie i tak szukaja.
 * Nazwy ida z bazy w biezacym jezyku, wiec te same kafle dzialaja na kazdej
 * domenie jezykowej bez osobnych tlumaczen.
 *
 * Bez konfiguracji modul sam wybiera kategorie (dzieci kategorii domowej
 * z produktami) — na sklepie, ktory ma plaskie drzewo, to wystarcza. Sklep
 * z rozbudowanym drzewem wpisuje wlasna liste w HBE_404_CATEGORIES.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_23_0($module)
{
    foreach ([
        Hummingbird_editor::CONF_404_CATEGORIES => '',
        Hummingbird_editor::CONF_404_CMS        => '',
    ] as $key => $default) {
        if (Configuration::get($key) === false) {
            Configuration::updateValue($key, $default);
        }
    }

    if (!$module->isRegisteredInHook('displayNotFound')) {
        $module->registerHook('displayNotFound');
    }

    return true;
}
