<?php
/**
 * 1.14.0 — zoom na okladce karty produktu.
 *
 * Motyw konczy srcset okladki na 720 px, a `data-full-size-image-url` wskazuje
 * home_default (250 px), wiec do tej pory karta produktu nie miala z czego
 * zrobic powiekszenia. Nowy skrypt siega po najwieksza miniature ze sklepu
 * (na izpol: product_main_2x, 1440 px) i renderuje zoom w ramce zdjecia.
 *
 * Wlaczamy od razu: dziala tylko na urzadzeniach z kursorem, jest leniwy
 * (duzy plik leci dopiero przy pierwszym najechaniu) i nie rusza ukladu karty.
 * Sklep, ktory go nie chce, wylacza go na zakladce "Karta produktu".
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_14_0($module)
{
    if (Configuration::get(Hummingbird_editor::CONF_ZOOM_ENABLED) === false) {
        Configuration::updateValue(Hummingbird_editor::CONF_ZOOM_ENABLED, 1);
    }
    if (Configuration::get(Hummingbird_editor::CONF_ZOOM_LEVEL) === false) {
        Configuration::updateValue(Hummingbird_editor::CONF_ZOOM_LEVEL, '0');
    }

    return true;
}
