<?php
/**
 * 1.23.2 — listing nie wywraca sie na smieciowym parametrze `order`.
 *
 * Rdzen podaje `order` z adresu prosto do SortOrder::newFromString() i nie lapie
 * wyjatku, wiec ?order=414910502 (bot) albo ?order=product.name.bogus konczylo sie
 * biala strona 500 na kazdym listingu — kategorii, marki, wyszukiwarki.
 * Modul dokłada hook actionFrontControllerInitBefore, ktory kasuje parametr,
 * jesli nie jest postaci `product.<znane_pole>.<asc|desc|random>`.
 *
 * Hook leci na poczatku FrontController::init(), wiec obejmuje takze ajaksowe
 * odswiezanie listingu (actionFrontControllerSetMedia przy ajax=1 nie leci).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_23_2($module)
{
    if (!$module->isRegisteredInHook('actionFrontControllerInitBefore')) {
        $module->registerHook('actionFrontControllerInitBefore');
    }

    return true;
}
