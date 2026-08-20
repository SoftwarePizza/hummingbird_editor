<?php
/**
 * 1.15.0 — zakladka "Stopka": pasek prawny na dole + telefon i e-mail.
 *
 * Pasek prawny (Polityka prywatnosci / Regulamin / RODO / GPSR) byl do tej pory
 * wpisany na sztywno w `_partials/footer.tpl` motywu, z id-kami stron CMS
 * Rosenthala. Teraz renderuje go motyw z tego, co poda modul, wiec zasiewamy
 * sloty stronami CMS TEGO sklepu — inaczej pasek zniknalby po aktualizacji.
 *
 * Telefon i e-mail nie maja wlasnych kluczy: zakladka pisze wprost do
 * PS_SHOP_PHONE / PS_SHOP_EMAIL, czyli tam, gdzie zapisuje je BO > Ustawienia
 * sklepu > Kontakt i skad czyta je ps_contactinfo. Nic do zasiania.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_15_0($module)
{
    for ($i = 1; $i <= Hummingbird_editor::FOOTER_LINK_SLOTS; $i++) {
        foreach (['label', 'url'] as $part) {
            $key = Hummingbird_editor::footerLinkKey($i, $part);
            if (Configuration::get($key) === false) {
                Configuration::updateValue($key, '');
            }
        }
    }

    $module->seedFooterLegalLinks();

    return true;
}
