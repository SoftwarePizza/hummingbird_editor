<?php
/**
 * 1.8.0 — "Inne sklepy online": an editorial closing section on displayHome
 * promoting three sister shops (Karenski, Lladró, Maison Berger Paris), each
 * with a 3-image mosaic gallery. Seeds the default Polish content, installs
 * the bundled gallery images into img/hb_editor/ and appends the section at
 * the very bottom of the home order.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_8_0($module)
{
    // 1) Bundled gallery images -> shared img/hb_editor/ pool (JPG + WebP twins;
    //    the front resolves the .webp variant automatically when it exists).
    $src = _PS_MODULE_DIR_ . 'hummingbird_editor/images/shops/';
    $dst = _PS_IMG_DIR_ . 'hb_editor/';
    if (is_dir($src) && is_dir($dst)) {
        foreach (glob($src . 'shops_*.*') ?: [] as $file) {
            $target = $dst . basename($file);
            if (!is_file($target)) {
                @copy($file, $target);
            }
        }
    }

    // 2) Default content (global rows; per-language values are edited in BO).
    $defaults = [
        'HBE_SHOPS_ENABLED' => 1,
        'HBE_SHOPS_EYEBROW' => 'Rosenthal poleca',
        'HBE_SHOPS_TITLE'   => 'Odwiedź nasze pozostałe sklepy online',
        'HBE_SHOPS_TEXT'    => 'Porcelana, rzeźba i domowe zapachy — trzy światy, ta sama dbałość o piękno. Poznaj sklepy, które prowadzimy z myślą o miłośnikach dobrego stylu.',
        'HBE_SHOPS_CTA'     => 'Odwiedź sklep',

        'HBE_SHOPS_NAME_1'  => 'Karenski',
        'HBE_SHOPS_DESC_1'  => 'Luksusowa porcelana i wyposażenie stołu — od klasyki Meissen po współczesny design najlepszych światowych manufaktur.',
        'HBE_SHOPS_URL_1'   => 'https://karenski.pl',
        'HBE_SHOPS_IMG_1_1' => 'shops_karenski_1.jpg',
        'HBE_SHOPS_IMG_1_2' => 'shops_karenski_2.jpg',
        'HBE_SHOPS_IMG_1_3' => 'shops_karenski_3.jpg',

        'HBE_SHOPS_NAME_2'  => 'Lladró',
        'HBE_SHOPS_DESC_2'  => 'Hiszpańskie rzeźby z porcelany, od 1953 roku tworzone ręcznie w Walencji. Figury pełne detali, które opowiadają historie.',
        'HBE_SHOPS_URL_2'   => 'https://lladro.pl',
        'HBE_SHOPS_IMG_2_1' => 'shops_lladro_1.jpg',
        'HBE_SHOPS_IMG_2_2' => 'shops_lladro_2.jpg',
        'HBE_SHOPS_IMG_2_3' => 'shops_lladro_3.jpg',

        'HBE_SHOPS_NAME_3'  => 'Maison Berger Paris',
        'HBE_SHOPS_DESC_3'  => 'Francuskie lampy zapachowe i perfumy do wnętrz z tradycją sięgającą 1898 roku. Zapach, który nadaje domowi nastrój.',
        'HBE_SHOPS_URL_3'   => 'https://maisonberger.pl',
        'HBE_SHOPS_IMG_3_1' => 'shops_maison_berger_1.jpg',
        'HBE_SHOPS_IMG_3_2' => 'shops_maison_berger_2.jpg',
        'HBE_SHOPS_IMG_3_3' => 'shops_maison_berger_3.jpg',
    ];
    foreach ($defaults as $key => $value) {
        if (Configuration::get($key) === false || Configuration::get($key) === '') {
            Configuration::updateValue($key, $value);
        }
    }

    // 3) Append the section at the very bottom of the displayHome order —
    //    in every HBE_HOME_ORDER row (the global one and any per-shop override),
    //    because the front renders only slugs present in the order string.
    $rows = Db::getInstance()->executeS(
        'SELECT id_configuration, value FROM `' . _DB_PREFIX_ . 'configuration` WHERE name = "HBE_HOME_ORDER"'
    );
    foreach ($rows ?: [] as $row) {
        $parts = array_filter(array_map('trim', explode(',', (string) $row['value'])));
        if (!in_array('shops', $parts, true)) {
            $parts[] = 'shops';
            Db::getInstance()->update(
                'configuration',
                ['value' => pSQL(implode(',', $parts))],
                'id_configuration = ' . (int) $row['id_configuration']
            );
        }
    }

    return true;
}
