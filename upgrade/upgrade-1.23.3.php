<?php
/**
 * 1.23.3 — rabat za calosc: kwota dla bramki juz nie gubi rabatu.
 *
 * Poprawka 1.23.1 (doliczanie ilosci zdjetych przez zamowienie z tego samego
 * koszyka) dzialala w swiezym procesie, ale nie w zadaniu webowym: w SRODKU
 * validateOrder() jest okno, w ktorym wiersz ps_orders juz istnieje i stan
 * magazynu jest zdjety, a order_detail dopiero powstaje. Hooki stanow licza
 * wtedy ceny, wiec getAllStockOrderedQuantity() pytalo o pozycje zamowienia,
 * dostawalo zero i CACHE'OWALO je na reszte zadania. Kwota liczona chwile
 * pozniej dla bramki (Montonio getBaseOrderData -> getOrderTotal) szla wiec
 * bez rabatu — zamowienia 18717, 18734, 18769.
 *
 * Teraz zero z tego okna nie trafia do cache, a nowy hook actionValidateOrder
 * czysci wszystko, co hook cenowy zapamietal o zamowieniach koszyka przed
 * validateOrder (w tym zatrute "koszyk nie ma zamowienia").
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_23_3($module)
{
    if (!$module->isRegisteredInHook('actionValidateOrder')) {
        if (!$module->registerHook('actionValidateOrder')) {
            return false;
        }
    }

    return true;
}
