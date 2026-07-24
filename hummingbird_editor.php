<?php
declare(strict_types=1);
/**
 * Hummingbird Editor
 *
 * Visual content-block editor: text / image / raw HTML
 * – Multi-store  – Multi-language
 * – Responsive: separate desktop & mobile content
 * – Hooks: standard PrestaShop hooks + any custom hook
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/HbEditorConfig.php';
require_once __DIR__ . '/classes/HbEditorBlock.php';
require_once __DIR__ . '/classes/HbEditorSlide.php';

class Hummingbird_editor extends Module
{
    const IMG_DIR = 'hb_editor/';

    /** Configuration key: page identifiers (CSV) of menu items using the flat submenu layout on desktop. */
    const MENU_FLAT_ITEMS_KEY = 'HBE_MENU_FLAT_ITEMS';

    /** Configuration key: same as MENU_FLAT_ITEMS_KEY but for the mobile drawer. */
    const MENU_FLAT_ITEMS_MOBILE_KEY = 'HBE_MENU_FLAT_ITEMS_MOBILE';

    /**
     * Social networks offered in the footer "Kontakt" column, in display order.
     * Key -> label; the profile URL lives in `HBE_SOCIAL_<KEY>` and an empty URL
     * hides that icon. The theme owns the matching SVG per key.
     */
    const SOCIAL_NETWORKS = [
        'instagram' => 'Instagram',
        'facebook'  => 'Facebook',
        'youtube'   => 'YouTube',
        'pinterest' => 'Pinterest',
        'tiktok'    => 'TikTok',
        'linkedin'  => 'LinkedIn',
        'x'         => 'X (Twitter)',
    ];

    /** Configuration key holding the profile URL for a social network key. */
    public static function socialConfigKey(string $network): string
    {
        return 'HBE_SOCIAL_' . strtoupper($network);
    }

    /** How the free-shipping threshold shown on the cart progress bar is resolved. */
    public const FREE_SHIPPING_MODE_AUTO   = 'auto';
    public const FREE_SHIPPING_MODE_MANUAL = 'manual';
    public const FREE_SHIPPING_MODE_SHOP   = 'shop';
    public const FREE_SHIPPING_MODE_OFF    = 'off';

    public function __construct()
    {
        $this->name    = 'hummingbird_editor';
        $this->tab     = 'front_office_features';
        $this->version = '1.12.0';
        $this->author  = 'Custom';
        $this->need_instance   = 0;
        $this->bootstrap       = true;
        $this->ps_versions_compliancy = ['min' => '1.7.7', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Hummingbird Editor');
        $this->description = $this->l(
            'Visual page-builder blocks: text / image / HTML — per hook, multi-store, multi-language, responsive'
        );
    }

    /* ── Install / Uninstall ─────────────────────────────────────────────── */

    public function install(): bool
    {
        HbEditorBlock::upgradeSchema();

        if (Configuration::get('HBE_TOPBAR_ENABLED') === false) {
            Configuration::updateValue('HBE_TOPBAR_ENABLED', 1);
        }
        if (Configuration::get('HBE_TOPBAR_TEXT') === false) {
            Configuration::updateValue('HBE_TOPBAR_TEXT', 'Promocja na wszystkie produkty -20% KUP TERAZ');
        }
        if (Configuration::get('HBE_TOPBAR_URL') === false) {
            Configuration::updateValue('HBE_TOPBAR_URL', '');
        }
        if (Configuration::get('HBE_TOPBAR_LINK_TEXT') === false) {
            Configuration::updateValue('HBE_TOPBAR_LINK_TEXT', '');
        }
        if (Configuration::get('HBE_HIDE_CURRENCY_DESKTOP') === false) {
            Configuration::updateValue('HBE_HIDE_CURRENCY_DESKTOP', 0);
        }
        if (Configuration::get('HBE_HIDE_CURRENCY_MOBILE') === false) {
            Configuration::updateValue('HBE_HIDE_CURRENCY_MOBILE', 0);
        }
        if (Configuration::get('HBE_HIDE_LANGUAGE_DESKTOP') === false) {
            Configuration::updateValue('HBE_HIDE_LANGUAGE_DESKTOP', 0);
        }
        if (Configuration::get('HBE_HIDE_LANGUAGE_MOBILE') === false) {
            Configuration::updateValue('HBE_HIDE_LANGUAGE_MOBILE', 0);
        }
        if (Configuration::get('HBE_HIDE_QUICKVIEW') === false) {
            Configuration::updateValue('HBE_HIDE_QUICKVIEW', 0);
            foreach (array_keys(self::SOCIAL_NETWORKS) as $network) {
                Configuration::updateValue(self::socialConfigKey($network), '');
            }
        }
        if (Configuration::get('HBE_INFOBAR_ENABLED') === false) {
            Configuration::updateValue('HBE_INFOBAR_ENABLED', 0);
        }
        if (Configuration::get('HBE_INFOBAR_TEXT') === false) {
            Configuration::updateValue('HBE_INFOBAR_TEXT', 'Sprawdź naszą ofertę!');
        }
        if (Configuration::get('HBE_INFOBAR_URL') === false) {
            Configuration::updateValue('HBE_INFOBAR_URL', '');
        }
        if (Configuration::get('HBE_INFOBAR_LINK_TEXT') === false) {
            Configuration::updateValue('HBE_INFOBAR_LINK_TEXT', '');
        }
        if (Configuration::get('HBE_INFOBAR_BG') === false) {
            Configuration::updateValue('HBE_INFOBAR_BG', '#222222');
        }
        if (Configuration::get('HBE_INFOBAR_COLOR') === false) {
            Configuration::updateValue('HBE_INFOBAR_COLOR', '#ffffff');
        }
        // Info bar 2 (second copy below slider)
        if (Configuration::get('HBE_INFOBAR2_ENABLED') === false) {
            Configuration::updateValue('HBE_INFOBAR2_ENABLED', 0);
        }
        if (Configuration::get('HBE_INFOBAR2_TEXT') === false) {
            Configuration::updateValue('HBE_INFOBAR2_TEXT', '');
        }
        if (Configuration::get('HBE_INFOBAR2_URL') === false) {
            Configuration::updateValue('HBE_INFOBAR2_URL', '');
        }
        if (Configuration::get('HBE_INFOBAR2_LINK_TEXT') === false) {
            Configuration::updateValue('HBE_INFOBAR2_LINK_TEXT', '');
        }
        if (Configuration::get('HBE_INFOBAR2_BG') === false) {
            Configuration::updateValue('HBE_INFOBAR2_BG', '#222222');
        }
        if (Configuration::get('HBE_INFOBAR2_COLOR') === false) {
            Configuration::updateValue('HBE_INFOBAR2_COLOR', '#ffffff');
        }
        // Image hero banner
        if (Configuration::get('HBE_IMGHERO_ENABLED') === false) {
            Configuration::updateValue('HBE_IMGHERO_ENABLED', 0);
        }
        if (Configuration::get('HBE_IMGHERO_IMAGE') === false) {
            Configuration::updateValue('HBE_IMGHERO_IMAGE', '');
        }
        if (Configuration::get('HBE_IMGHERO_IMAGE_MOBILE') === false) {
            Configuration::updateValue('HBE_IMGHERO_IMAGE_MOBILE', '');
        }
        if (Configuration::get('HBE_IMGHERO_TITLE') === false) {
            Configuration::updateValue('HBE_IMGHERO_TITLE', '');
        }
        if (Configuration::get('HBE_IMGHERO_DESC') === false) {
            Configuration::updateValue('HBE_IMGHERO_DESC', '');
        }
        if (Configuration::get('HBE_IMGHERO_CTA_TEXT') === false) {
            Configuration::updateValue('HBE_IMGHERO_CTA_TEXT', '');
        }
        if (Configuration::get('HBE_IMGHERO_CTA_URL') === false) {
            Configuration::updateValue('HBE_IMGHERO_CTA_URL', '');
        }
        // Baner 2
        if (Configuration::get('HBE_IMGHERO2_ENABLED') === false) {
            Configuration::updateValue('HBE_IMGHERO2_ENABLED', 0);
        }
        foreach (['HBE_IMGHERO2_IMAGE', 'HBE_IMGHERO2_IMAGE_MOBILE', 'HBE_IMGHERO2_TITLE', 'HBE_IMGHERO2_DESC',
                  'HBE_IMGHERO2_CTA_TEXT', 'HBE_IMGHERO2_CTA_URL'] as $k) {
            if (Configuration::get($k) === false) {
                Configuration::updateValue($k, '');
            }
        }
        // 3-column text links block
        if (Configuration::get('HBE_COLS3_ENABLED') === false) {
            Configuration::updateValue('HBE_COLS3_ENABLED', 0);
        }
        foreach ([1, 2, 3] as $i) {
            if (Configuration::get('HBE_COLS3_TEXT_' . $i) === false) {
                Configuration::updateValue('HBE_COLS3_TEXT_' . $i, '');
            }
            if (Configuration::get('HBE_COLS3_URL_' . $i) === false) {
                Configuration::updateValue('HBE_COLS3_URL_' . $i, '');
            }
        }
        // 3-column text+desc+links block
        if (Configuration::get('HBE_COLS3D_ENABLED') === false) {
            Configuration::updateValue('HBE_COLS3D_ENABLED', 0);
        }
        foreach ([1, 2, 3] as $i) {
            if (Configuration::get('HBE_COLS3D_TITLE_' . $i) === false) {
                Configuration::updateValue('HBE_COLS3D_TITLE_' . $i, '');
            }
            if (Configuration::get('HBE_COLS3D_DESC_' . $i) === false) {
                Configuration::updateValue('HBE_COLS3D_DESC_' . $i, '');
            }
            if (Configuration::get('HBE_COLS3D_URL_' . $i) === false) {
                Configuration::updateValue('HBE_COLS3D_URL_' . $i, '');
            }
            if (Configuration::get('HBE_COLS3D_IMG_' . $i) === false) {
                Configuration::updateValue('HBE_COLS3D_IMG_' . $i, '');
            }
        }
        // displayHome element order (comma-separated)
        if (Configuration::get('HBE_HOME_ORDER') === false) {
            Configuration::updateValue('HBE_HOME_ORDER', 'infobar,imghero,cols3,tagline');
        }
        // Tagline text block
        if (Configuration::get('HBE_TAGLINE_ENABLED') === false) {
            Configuration::updateValue('HBE_TAGLINE_ENABLED', 0);
        }
        if (Configuration::get('HBE_TAGLINE_TEXT') === false) {
            Configuration::updateValue('HBE_TAGLINE_TEXT', '');
        }
        if (Configuration::get('HBE_TAGLINE_LINK_TEXT') === false) {
            Configuration::updateValue('HBE_TAGLINE_LINK_TEXT', '');
        }
        if (Configuration::get('HBE_TAGLINE_LINK_URL') === false) {
            Configuration::updateValue('HBE_TAGLINE_LINK_URL', '');
        }

        // Kategorie two-column section
        if (Configuration::get('HBE_KATCOLS_ENABLED') === false) {
            Configuration::updateValue('HBE_KATCOLS_ENABLED', 0);
        }
        foreach (['HBE_KATCOLS_TITLE', 'HBE_KATCOLS_HDR_TEXT', 'HBE_KATCOLS_HDR_LINK_TEXT', 'HBE_KATCOLS_HDR_URL',
                  'HBE_KATCOLS_L_IMAGE', 'HBE_KATCOLS_L_IMAGE_MOBILE', 'HBE_KATCOLS_L_CAPTION', 'HBE_KATCOLS_L_URL',
                  'HBE_KATCOLS_R_IMAGE', 'HBE_KATCOLS_R_IMAGE_MOBILE', 'HBE_KATCOLS_R_CAPTION', 'HBE_KATCOLS_R_URL'] as $k) {
            if (Configuration::get($k) === false) {
                Configuration::updateValue($k, '');
            }
        }
        if (Configuration::get('HBE_KATCOLS_IMAGE_ML') === false) {
            Configuration::updateValue('HBE_KATCOLS_IMAGE_ML', 0);
        }
        // Split-block (3 columns)
        if (Configuration::get('HBE_SPLITBLOCK_ENABLED') === false) {
            Configuration::updateValue('HBE_SPLITBLOCK_ENABLED', 0);
        }
        foreach (['HBE_SPLITBLOCK_TITLE', 'HBE_SPLITBLOCK_DESC', 'HBE_SPLITBLOCK_CTA_TEXT',
                  'HBE_SPLITBLOCK_CTA_URL', 'HBE_SPLITBLOCK_M_IMAGE', 'HBE_SPLITBLOCK_M_IMAGE_MOBILE',
                  'HBE_SPLITBLOCK_R_IMAGE', 'HBE_SPLITBLOCK_R_IMAGE_MOBILE'] as $k) {
            if (Configuration::get($k) === false) {
                Configuration::updateValue($k, '');
            }
        }
        foreach (['HBE_IMGHERO_IMAGE_ML', 'HBE_IMGHERO2_IMAGE_ML', 'HBE_SPLITBLOCK_IMAGE_ML', 'HBE_ICONS4_IMAGE_ML'] as $k) {
            if (Configuration::get($k) === false) {
                Configuration::updateValue($k, 0);
            }
        }
        // Icons 4 columns
        if (Configuration::get('HBE_ICONS4_ENABLED') === false) {
            Configuration::updateValue('HBE_ICONS4_ENABLED', 0);
        }
        foreach ([1, 2, 3, 4] as $i) {
            foreach (['HBE_ICONS4_IMG_' . $i, 'HBE_ICONS4_IMG_' . $i . '_MOBILE', 'HBE_ICONS4_TITLE_' . $i, 'HBE_ICONS4_DESC_' . $i] as $k) {
                if (Configuration::get($k) === false) {
                    Configuration::updateValue($k, '');
                }
            }
        }

        // "Inne sklepy online" — 3 promoted sister shops with mini galleries
        if (Configuration::get('HBE_SHOPS_ENABLED') === false) {
            Configuration::updateValue('HBE_SHOPS_ENABLED', 0);
        }
        foreach (['HBE_SHOPS_EYEBROW', 'HBE_SHOPS_TITLE', 'HBE_SHOPS_TEXT', 'HBE_SHOPS_CTA'] as $k) {
            if (Configuration::get($k) === false) {
                Configuration::updateValue($k, '');
            }
        }
        foreach ([1, 2, 3] as $i) {
            foreach (['HBE_SHOPS_NAME_' . $i, 'HBE_SHOPS_DESC_' . $i, 'HBE_SHOPS_URL_' . $i,
                      'HBE_SHOPS_IMG_' . $i . '_1', 'HBE_SHOPS_IMG_' . $i . '_2', 'HBE_SHOPS_IMG_' . $i . '_3'] as $k) {
                if (Configuration::get($k) === false) {
                    Configuration::updateValue($k, '');
                }
            }
        }

        // Brands logo strip
        if (Configuration::get('HBE_BRANDS_ENABLED') === false) {
            Configuration::updateValue('HBE_BRANDS_ENABLED', 0);
        }
        if (Configuration::get('HBE_BRANDS_TITLE') === false) {
            Configuration::updateValue('HBE_BRANDS_TITLE', '');
        }
        for ($i = 1; $i <= 8; $i++) {
            foreach (['HBE_BRANDS_IMG_' . $i, 'HBE_BRANDS_LINK_' . $i, 'HBE_BRANDS_ALT_' . $i, 'HBE_BRANDS_MANU_' . $i] as $k) {
                if (Configuration::get($k) === false) {
                    Configuration::updateValue($k, '');
                }
            }
        }

        // Carousel section headers
        foreach (['HBE_NP', 'HBE_BS', 'HBE_CP'] as $prefix) {
            if (Configuration::get($prefix . '_TITLE') === false) {
                Configuration::updateValue($prefix . '_TITLE', '');
            }
            if (Configuration::get($prefix . '_TEXT') === false) {
                Configuration::updateValue($prefix . '_TEXT', '');
            }
            if (Configuration::get($prefix . '_LINK_TEXT') === false) {
                Configuration::updateValue($prefix . '_LINK_TEXT', '');
            }
            if (Configuration::get($prefix . '_LINK_URL') === false) {
                Configuration::updateValue($prefix . '_LINK_URL', '');
            }
        }

        // Slider global settings (ported from bemo_slider) — defaults
        $sliderDefaults = [
            'HBE_SLIDER_SPEED'          => 5000,
            'HBE_SLIDER_AUTOPLAY'       => 1,
            'HBE_SLIDER_PAUSE_ON_HOVER' => 1,
            'HBE_SLIDER_SHOW_ARROWS'    => 0,
            'HBE_SLIDER_ARROW_STYLE'    => 'classic',
            'HBE_SLIDER_SHOW_DOTS'      => 1,
        ];
        foreach ($sliderDefaults as $key => $val) {
            if (Configuration::get($key) === false) {
                Configuration::updateValue($key, $val);
            }
        }

        // FAQ section
        if (Configuration::get('HBE_FAQ_ENABLED') === false) {
            Configuration::updateValue('HBE_FAQ_ENABLED', 0);
        }
        foreach (['HBE_FAQ_BG' => '#ffffff', 'HBE_FAQ_QUESTION_COLOR' => '#242424',
                  'HBE_FAQ_ANSWER_COLOR' => '#4a4a4a', 'HBE_FAQ_BORDER_COLOR' => '#e5e5e5'] as $k => $v) {
            if (Configuration::get($k) === false) {
                Configuration::updateValue($k, $v);
            }
        }

        // Related products carousel (below FAQ on product page)
        if (Configuration::get('HBE_RELATED_ENABLED') === false) {
            Configuration::updateValue('HBE_RELATED_ENABLED', 1);
        }
        if (Configuration::get('HBE_RELATED_TITLE') === false) {
            Configuration::updateValue('HBE_RELATED_TITLE', 'Inni kupili również');
        }

        // Image + text section (below the description on the product page)
        if (Configuration::get('HBE_IMGTEXT_ENABLED') === false) {
            Configuration::updateValue('HBE_IMGTEXT_ENABLED', 0);
        }
        if (Configuration::get('HBE_IMGTEXT_BG') === false) {
            Configuration::updateValue('HBE_IMGTEXT_BG', '#f5f1ea');
        }
        foreach (['HBE_IMGTEXT_IMAGE', 'HBE_IMGTEXT_IMAGE_MOBILE', 'HBE_IMGTEXT_TITLE',
                  'HBE_IMGTEXT_DESC', 'HBE_IMGTEXT_CTA_TEXT', 'HBE_IMGTEXT_CTA_URL'] as $k) {
            if (Configuration::get($k) === false) {
                Configuration::updateValue($k, '');
            }
        }
        if (Configuration::get('HBE_IMGTEXT_IMAGE_ML') === false) {
            Configuration::updateValue('HBE_IMGTEXT_IMAGE_ML', 0);
        }

        // Listing banners (injected after the 2nd product row on category pages)
        for ($i = 1; $i <= 5; $i++) {
            if (Configuration::get('HBE_LISTBAN_' . $i . '_ENABLED') === false) {
                Configuration::updateValue('HBE_LISTBAN_' . $i . '_ENABLED', 0);
            }
            foreach (['_IMAGE', '_IMAGE_MOBILE', '_TITLE', '_CTA_TEXT', '_URL', '_CATS'] as $suffix) {
                if (Configuration::get('HBE_LISTBAN_' . $i . $suffix) === false) {
                    Configuration::updateValue('HBE_LISTBAN_' . $i . $suffix, '');
                }
            }
        }

        // Free-shipping bar: read the threshold from the carriers unless told otherwise.
        if (Configuration::get('HBE_CART_FREE_SHIPPING_MODE') === false) {
            Configuration::updateValue('HBE_CART_FREE_SHIPPING_MODE', self::FREE_SHIPPING_MODE_AUTO);
        }
        if (Configuration::get('HBE_CART_FREE_SHIPPING_THRESHOLD') === false) {
            Configuration::updateValue('HBE_CART_FREE_SHIPPING_THRESHOLD', 0);
        }

        // Rosenthal Care promo block (cart) — off by default; enabled in BO.
        if (Configuration::get('HBE_CARE_ENABLED') === false) {
            Configuration::updateValue('HBE_CARE_ENABLED', 0);
        }
        if (Configuration::get('HBE_CARE_PRODUCT_ID') === false) {
            Configuration::updateValue('HBE_CARE_PRODUCT_ID', 4682);
        }
        if (Configuration::get('HBE_CARE_HEADING') === false) {
            Configuration::updateValue('HBE_CARE_HEADING', 'Czy chcesz objąć produkty Rosenthal Care?');
        }
        if (Configuration::get('HBE_CARE_TEXT') === false) {
            Configuration::updateValue('HBE_CARE_TEXT', "Tylko za 10 zł możesz skorzystać z wymiany uszkodzonego / stłuczonego produktu na nowy za 50% jego wartości.\nDo rozliczenia na podstawie zachowanego paragonu przyjmujemy ceny aktualne w dniu wymiany.\nWymiany można dokonać w okresie 12 miesięcy od dokonania zakupu pod warunkiem, że uszkodzony produkt znajduje się w aktualnej ofercie.");
        }
        if (Configuration::get('HBE_CARE_BUTTON') === false) {
            Configuration::updateValue('HBE_CARE_BUTTON', 'Dodaj Rosenthal Care');
        }
        if (Configuration::get('HBE_CARE_LOGIN_REQUIRED') === false) {
            Configuration::updateValue('HBE_CARE_LOGIN_REQUIRED', 1);
        }
        // Karta podarunkowa — placements off by default; URL falls back to the
        // giftcard purchase page (giftcard/choicegiftcard) at render time.
        foreach ([
            'HBE_GIFTCARD_MENU_ENABLED'   => 0,
            'HBE_GIFTCARD_FOOTER_ENABLED' => 0,
            'HBE_GIFTCARD_FLOAT_ENABLED'  => 0,
            'HBE_GIFTCARD_FLOAT_POSITION' => 'right',
            'HBE_GIFTCARD_MENU_LABEL'     => 'Karta podarunkowa',
            'HBE_GIFTCARD_FOOTER_LABEL'   => 'Karta podarunkowa',
            'HBE_GIFTCARD_FOOTER_DESC'    => 'Zawsze trafiony prezent — obdarowany sam wybierze, co pokocha.',
            'HBE_GIFTCARD_FLOAT_LABEL'    => 'Karta podarunkowa',
            'HBE_GIFTCARD_URL'            => '',
        ] as $gcKey => $gcDefault) {
            if (Configuration::get($gcKey) === false) {
                Configuration::updateValue($gcKey, $gcDefault);
            }
        }

        return parent::install()
            && $this->createTables()
            && $this->createImgDir()
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayAfterBodyOpeningTag')
            && $this->registerHook('displayHome')
            && $this->registerHook('displayProductSections')
            && $this->registerHook('displayFooterProduct')
            && $this->registerHook('displayListingBanner')
            && $this->registerHook('displayShoppingCartFooter')
            && $this->registerHook('displayFooter')
            && $this->registerHook('actionMainMenuModifier')
            && $this->installTab();
    }

    public function uninstall(): bool
    {
        Configuration::deleteByName('HBE_TOPBAR_ENABLED');
        Configuration::deleteByName('HBE_TOPBAR_TEXT');
        Configuration::deleteByName('HBE_TOPBAR_URL');
        Configuration::deleteByName('HBE_TOPBAR_LINK_TEXT');
        Configuration::deleteByName('HBE_HIDE_CURRENCY_DESKTOP');
        Configuration::deleteByName('HBE_HIDE_CURRENCY_MOBILE');
        Configuration::deleteByName('HBE_HIDE_LANGUAGE_DESKTOP');
        Configuration::deleteByName('HBE_HIDE_LANGUAGE_MOBILE');
        Configuration::deleteByName('HBE_HIDE_QUICKVIEW');
        foreach (array_keys(self::SOCIAL_NETWORKS) as $network) {
            Configuration::deleteByName(self::socialConfigKey($network));
        }
        Configuration::deleteByName('HBE_INFOBAR_ENABLED');
        Configuration::deleteByName('HBE_INFOBAR_TEXT');
        Configuration::deleteByName('HBE_INFOBAR_URL');
        Configuration::deleteByName('HBE_INFOBAR_BG');
        Configuration::deleteByName('HBE_INFOBAR_COLOR');
        Configuration::deleteByName('HBE_INFOBAR_LINK_TEXT');
        Configuration::deleteByName('HBE_INFOBAR2_ENABLED');
        Configuration::deleteByName('HBE_INFOBAR2_TEXT');
        Configuration::deleteByName('HBE_INFOBAR2_URL');
        Configuration::deleteByName('HBE_INFOBAR2_BG');
        Configuration::deleteByName('HBE_INFOBAR2_COLOR');
        Configuration::deleteByName('HBE_INFOBAR2_LINK_TEXT');
        Configuration::deleteByName('HBE_IMGHERO_ENABLED');
        Configuration::deleteByName('HBE_IMGHERO_IMAGE');
        Configuration::deleteByName('HBE_IMGHERO_IMAGE_MOBILE');
        Configuration::deleteByName('HBE_IMGHERO_IMAGE_ML');
        Configuration::deleteByName('HBE_IMGHERO_TITLE');
        Configuration::deleteByName('HBE_IMGHERO_DESC');
        Configuration::deleteByName('HBE_IMGHERO_CTA_TEXT');
        Configuration::deleteByName('HBE_IMGHERO_CTA_URL');
        foreach (['HBE_IMGHERO2_ENABLED', 'HBE_IMGHERO2_IMAGE', 'HBE_IMGHERO2_IMAGE_MOBILE', 'HBE_IMGHERO2_IMAGE_ML', 'HBE_IMGHERO2_TITLE',
                  'HBE_IMGHERO2_DESC', 'HBE_IMGHERO2_CTA_TEXT', 'HBE_IMGHERO2_CTA_URL'] as $k) {
            Configuration::deleteByName($k);
        }
        Configuration::deleteByName('HBE_COLS3_ENABLED');
        foreach ([1, 2, 3] as $i) {
            Configuration::deleteByName('HBE_COLS3_TEXT_' . $i);
            Configuration::deleteByName('HBE_COLS3_URL_' . $i);
        }
        Configuration::deleteByName('HBE_COLS3D_ENABLED');
        foreach ([1, 2, 3] as $i) {
            Configuration::deleteByName('HBE_COLS3D_TITLE_' . $i);
            Configuration::deleteByName('HBE_COLS3D_DESC_' . $i);
            Configuration::deleteByName('HBE_COLS3D_URL_' . $i);
            Configuration::deleteByName('HBE_COLS3D_IMG_' . $i);
        }
        Configuration::deleteByName('HBE_TAGLINE_ENABLED');
        Configuration::deleteByName('HBE_TAGLINE_TEXT');
        Configuration::deleteByName('HBE_TAGLINE_LINK_TEXT');
        Configuration::deleteByName('HBE_TAGLINE_LINK_URL');
        foreach (['HBE_KATCOLS_ENABLED', 'HBE_KATCOLS_TITLE', 'HBE_KATCOLS_HDR_TEXT', 'HBE_KATCOLS_HDR_LINK_TEXT',
                  'HBE_KATCOLS_HDR_URL', 'HBE_KATCOLS_IMAGE_ML',
                  'HBE_KATCOLS_L_IMAGE', 'HBE_KATCOLS_L_IMAGE_MOBILE', 'HBE_KATCOLS_L_CAPTION', 'HBE_KATCOLS_L_URL',
                  'HBE_KATCOLS_R_IMAGE', 'HBE_KATCOLS_R_IMAGE_MOBILE', 'HBE_KATCOLS_R_CAPTION', 'HBE_KATCOLS_R_URL'] as $k) {
            Configuration::deleteByName($k);
        }
        foreach (['HBE_SPLITBLOCK_ENABLED', 'HBE_SPLITBLOCK_TITLE', 'HBE_SPLITBLOCK_DESC', 'HBE_SPLITBLOCK_CTA_TEXT',
                  'HBE_SPLITBLOCK_CTA_URL', 'HBE_SPLITBLOCK_IMAGE_ML',
                  'HBE_SPLITBLOCK_M_IMAGE', 'HBE_SPLITBLOCK_M_IMAGE_MOBILE',
                  'HBE_SPLITBLOCK_R_IMAGE', 'HBE_SPLITBLOCK_R_IMAGE_MOBILE'] as $k) {
            Configuration::deleteByName($k);
        }
        Configuration::deleteByName('HBE_ICONS4_ENABLED');
        Configuration::deleteByName('HBE_ICONS4_IMAGE_ML');
        foreach ([1, 2, 3, 4] as $i) {
            Configuration::deleteByName('HBE_ICONS4_IMG_' . $i);
            Configuration::deleteByName('HBE_ICONS4_IMG_' . $i . '_MOBILE');
            Configuration::deleteByName('HBE_ICONS4_TITLE_' . $i);
            Configuration::deleteByName('HBE_ICONS4_DESC_' . $i);
        }
        foreach (['HBE_SHOPS_ENABLED', 'HBE_SHOPS_EYEBROW', 'HBE_SHOPS_TITLE', 'HBE_SHOPS_TEXT', 'HBE_SHOPS_CTA'] as $k) {
            Configuration::deleteByName($k);
        }
        foreach ([1, 2, 3] as $i) {
            Configuration::deleteByName('HBE_SHOPS_NAME_' . $i);
            Configuration::deleteByName('HBE_SHOPS_DESC_' . $i);
            Configuration::deleteByName('HBE_SHOPS_URL_' . $i);
            foreach ([1, 2, 3] as $j) {
                Configuration::deleteByName('HBE_SHOPS_IMG_' . $i . '_' . $j);
            }
        }
        foreach (['HBE_SLIDER_SPEED', 'HBE_SLIDER_AUTOPLAY', 'HBE_SLIDER_PAUSE_ON_HOVER',
                  'HBE_SLIDER_SHOW_ARROWS', 'HBE_SLIDER_ARROW_STYLE', 'HBE_SLIDER_SHOW_DOTS'] as $k) {
            Configuration::deleteByName($k);
        }
        Configuration::deleteByName('HBE_HOME_ORDER');
        foreach (['HBE_NP', 'HBE_BS', 'HBE_CP'] as $prefix) {
            Configuration::deleteByName($prefix . '_TITLE');
            Configuration::deleteByName($prefix . '_TEXT');
            Configuration::deleteByName($prefix . '_LINK_TEXT');
            Configuration::deleteByName($prefix . '_LINK_URL');
        }

        foreach (['HBE_FAQ_ENABLED', 'HBE_FAQ_BG', 'HBE_FAQ_QUESTION_COLOR',
                  'HBE_FAQ_ANSWER_COLOR', 'HBE_FAQ_BORDER_COLOR'] as $k) {
            Configuration::deleteByName($k);
        }
        // also delete per-language items
        foreach (Language::getLanguages(false) as $lang) {
            Configuration::deleteByName('HBE_FAQ_ITEMS_' . (int)$lang['id_lang']);
        }
        Configuration::deleteByName('HBE_RELATED_ENABLED');
        Configuration::deleteByName('HBE_RELATED_TITLE');
        foreach (['HBE_IMGTEXT_ENABLED', 'HBE_IMGTEXT_BG', 'HBE_IMGTEXT_IMAGE', 'HBE_IMGTEXT_IMAGE_MOBILE',
                  'HBE_IMGTEXT_IMAGE_ML', 'HBE_IMGTEXT_TITLE', 'HBE_IMGTEXT_DESC',
                  'HBE_IMGTEXT_CTA_TEXT', 'HBE_IMGTEXT_CTA_URL'] as $k) {
            Configuration::deleteByName($k);
        }
        for ($i = 1; $i <= 5; $i++) {
            foreach (['_ENABLED', '_IMAGE', '_IMAGE_MOBILE', '_TITLE', '_CTA_TEXT', '_URL', '_CATS'] as $suffix) {
                Configuration::deleteByName('HBE_LISTBAN_' . $i . $suffix);
            }
        }
        foreach (['HBE_CARE_ENABLED', 'HBE_CARE_PRODUCT_ID', 'HBE_CARE_HEADING',
                  'HBE_CARE_TEXT', 'HBE_CARE_BUTTON', 'HBE_CARE_LOGIN_REQUIRED'] as $k) {
            Configuration::deleteByName($k);
        }
        foreach (['HBE_CART_FREE_SHIPPING_MODE', 'HBE_CART_FREE_SHIPPING_THRESHOLD'] as $k) {
            Configuration::deleteByName($k);
        }
        Configuration::deleteByName('HBE_PRODUCT_SUMMARY_SOURCE');
        foreach ([
            'HBE_GIFTCARD_MENU_ENABLED', 'HBE_GIFTCARD_MENU_LABEL',
            'HBE_GIFTCARD_FOOTER_ENABLED', 'HBE_GIFTCARD_FOOTER_LABEL', 'HBE_GIFTCARD_FOOTER_DESC',
            'HBE_GIFTCARD_FLOAT_ENABLED', 'HBE_GIFTCARD_FLOAT_LABEL', 'HBE_GIFTCARD_FLOAT_POSITION',
            'HBE_GIFTCARD_URL',
        ] as $k) {
            Configuration::deleteByName($k);
        }

        return parent::uninstall()
            && $this->dropTables()
            && $this->removeTab();
    }

    /* ── Tables ──────────────────────────────────────────────────────────── */

    private function createTables(): bool
    {
        $db = Db::getInstance();
        $p  = _DB_PREFIX_;
        $e  = _MYSQL_ENGINE_;

        $queries = [
            "CREATE TABLE IF NOT EXISTS `{$p}hb_editor_block` (
                `id_block`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `hook_name`        VARCHAR(255)     NOT NULL DEFAULT '',
                `type`             VARCHAR(20)      NOT NULL DEFAULT 'text',
                `section_type`     VARCHAR(50)      NOT NULL DEFAULT '',
                `section_data`     MEDIUMTEXT                DEFAULT NULL,
                `position`         INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `active`           TINYINT(1)       NOT NULL DEFAULT 1,
                `mobile_different` TINYINT(1)       NOT NULL DEFAULT 0,
                `image_desktop`    VARCHAR(255)               DEFAULT NULL,
                `image_mobile`     VARCHAR(255)               DEFAULT NULL,
                `date_add`         DATETIME         NOT NULL,
                `date_upd`         DATETIME         NOT NULL,
                PRIMARY KEY (`id_block`),
                KEY `idx_hook_active` (`hook_name`, `active`)
            ) ENGINE={$e} DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `{$p}hb_editor_block_lang` (
                `id_block`        INT(10) UNSIGNED NOT NULL,
                `id_lang`         INT(10) UNSIGNED NOT NULL,
                `content_desktop` MEDIUMTEXT                 DEFAULT NULL,
                `content_mobile`  MEDIUMTEXT                 DEFAULT NULL,
                `link_desktop`    VARCHAR(2048)              DEFAULT NULL,
                `link_mobile`     VARCHAR(2048)              DEFAULT NULL,
                PRIMARY KEY (`id_block`, `id_lang`)
            ) ENGINE={$e} DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `{$p}hb_editor_block_shop` (
                `id_block` INT(10) UNSIGNED NOT NULL,
                `id_shop`  INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`id_block`, `id_shop`)
            ) ENGINE={$e} DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($queries as $sql) {
            if (!$db->execute($sql)) {
                return false;
            }
        }
        return $this->ensureSliderSchema();
    }

    /**
     * Create the slider tables (ported from bemo_slider). Idempotent — safe to
     * call from install() and from the upgrade script on an existing install.
     */
    public function ensureSliderSchema(): bool
    {
        $db = Db::getInstance();
        $p  = _DB_PREFIX_;
        $e  = _MYSQL_ENGINE_;

        $queries = [
            "CREATE TABLE IF NOT EXISTS `{$p}hb_editor_slider` (
                `id_hb_slide` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_shop`     INT(10) UNSIGNED NOT NULL,
                `position`    INT(10) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_hb_slide`, `id_shop`)
            ) ENGINE={$e} DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `{$p}hb_editor_slider_slides` (
                `id_hb_slide`            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `position`               INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `active`                 TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `active_mobile`          TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                `text_position`          INT(10)          DEFAULT 0,
                `show_text`              TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                `overlay_is_transparent` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `overlay_color`          VARCHAR(7)       DEFAULT '#000000',
                `overlay_opacity`        INT(10) UNSIGNED DEFAULT 50,
                `cta_enabled`            TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `cta_text`               VARCHAR(100)     NOT NULL DEFAULT '',
                `cta_color`              VARCHAR(7)       NOT NULL DEFAULT '#ffffff',
                `cta_bg`                 VARCHAR(7)       NOT NULL DEFAULT '#000000',
                `cta_size`               VARCHAR(2)       NOT NULL DEFAULT 'md',
                `cta_radius`             INT(10) UNSIGNED NOT NULL DEFAULT 4,
                PRIMARY KEY (`id_hb_slide`)
            ) ENGINE={$e} DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS `{$p}hb_editor_slider_slides_lang` (
                `id_hb_slide`  INT(10) UNSIGNED NOT NULL,
                `id_lang`      INT(10) UNSIGNED NOT NULL,
                `title`        VARCHAR(255) NOT NULL DEFAULT '',
                `description`  TEXT,
                `url`          VARCHAR(255) NOT NULL DEFAULT '',
                `image`        VARCHAR(255) NOT NULL DEFAULT '',
                `image_mobile` VARCHAR(255) NOT NULL DEFAULT '',
                PRIMARY KEY (`id_hb_slide`, `id_lang`)
            ) ENGINE={$e} DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($queries as $sql) {
            if (!$db->execute($sql)) {
                return false;
            }
        }
        return true;
    }

    private function dropTables(): bool
    {
        $p = _DB_PREFIX_;
        foreach ([
            'hb_editor_block_shop', 'hb_editor_block_lang', 'hb_editor_block',
            'hb_editor_slider_slides_lang', 'hb_editor_slider_slides', 'hb_editor_slider',
        ] as $t) {
            Db::getInstance()->execute("DROP TABLE IF EXISTS `{$p}{$t}`");
        }
        return true;
    }

    private function createImgDir(): bool
    {
        $path = _PS_IMG_DIR_ . self::IMG_DIR;
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                return false;
            }
        }

        // Slider images live inside the module (ported from bemo_slider).
        $sliderPath = _PS_MODULE_DIR_ . $this->name . '/images/';
        if (!is_dir($sliderPath)) {
            return (bool) mkdir($sliderPath, 0755, true);
        }
        return true;
    }

    /* ── Admin tab ───────────────────────────────────────────────────────── */

    private function installTab(): bool
    {
        $tab             = new Tab();
        $tab->active     = 1;
        $tab->class_name = 'AdminHbEditor';
        $tab->module     = $this->name;
        // Top-level menu entry, pinned right below the Dashboard
        $tab->id_parent  = 0;
        $tab->icon       = 'design_services';
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Hummingbird Editor';
        }
        if (!$tab->add()) {
            return false;
        }
        Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'tab`
            SET `position` = `position` + 1
            WHERE `id_parent` = 0 AND `position` >= 2 AND `id_tab` != ' . (int) $tab->id
        );
        Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'tab`
            SET `position` = 2
            WHERE `id_tab` = ' . (int) $tab->id
        );
        return true;
    }

    private function removeTab(): bool
    {
        $id = (int) Tab::getIdFromClassName('AdminHbEditor');
        if ($id) {
            $tab = new Tab($id);
            return (bool) $tab->delete();
        }
        return true;
    }

    /* ── Hook registration helper ────────────────────────────────────────── */

    /**
     * Ensure a hook name exists in PS and this module is hooked to it.
     * Called from the admin controller when a block with a new hook is saved.
     */
    public function ensureHookRegistered(string $hookName): bool
    {
        $hookName = preg_replace('/[^a-zA-Z0-9_]/', '', $hookName);
        if (!$hookName) {
            return false;
        }

        if (!Hook::getIdByName($hookName)) {
            Db::getInstance()->insert('hook', [
                'name'        => pSQL($hookName),
                'title'       => pSQL($hookName),
                'description' => '',
                'position'    => 1,
                'live_edit'   => 0,
            ], false, true, Db::INSERT_IGNORE);
        }

        if (!$this->isRegisteredInHook($hookName)) {
            return $this->registerHook($hookName);
        }
        return true;
    }

    /* ── Main menu: flat submenu layout (no left tabs) ───────────────────── */

    /**
     * Page identifiers (e.g. "category-3") of the top-level menu items that
     * should use the flat submenu layout (no tabs / drill-down; sub-categories
     * shown directly as image tiles) for the given device.
     *
     * @param string $device 'desktop' or 'mobile'
     *
     * @return string[]
     */
    public function getMenuFlatItems(string $device = 'desktop'): array
    {
        $key = ($device === 'mobile') ? self::MENU_FLAT_ITEMS_MOBILE_KEY : self::MENU_FLAT_ITEMS_KEY;
        $raw = (string) Configuration::get($key);

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Flag the configured top-level menu items so the theme renders them with
     * the flat layout, independently per device. Hooked into ps_mainmenu's
     * actionMainMenuModifier, which passes the built menu tree by reference —
     * so no core module edit is required.
     */
    public function hookActionMainMenuModifier(array $params): void
    {
        if (empty($params['menu']['children']) || !is_array($params['menu']['children'])) {
            return;
        }

        $flatDesktop = $this->getMenuFlatItems('desktop');
        $flatMobile  = $this->getMenuFlatItems('mobile');

        foreach ($params['menu']['children'] as &$node) {
            if (!empty($node['page_identifier'])) {
                $node['flat_desktop'] = in_array($node['page_identifier'], $flatDesktop, true);
                $node['flat_mobile']  = in_array($node['page_identifier'], $flatMobile, true);
            }
        }
        unset($node);

        // Karta podarunkowa — append a top-level leaf item to the main menu.
        // ($params['menu'] is passed by reference by ps_mainmenu, and PHP keeps
        // that binding through the by-value method arg, so appending sticks.)
        if ((int) Configuration::get('HBE_GIFTCARD_MENU_ENABLED')) {
            $label = trim($this->hbeLocConfig('HBE_GIFTCARD_MENU_LABEL'));
            if ($label !== '') {
                $params['menu']['children'][] = [
                    'type'               => 'giftcard',
                    'page_identifier'    => 'hbe-giftcard',
                    'label'              => $label,
                    'url'                => $this->getGiftcardUrl(),
                    'depth'              => 1,
                    'current'            => false,
                    'open_in_new_window' => false,
                    'children'           => [],
                    'image_urls'         => [],
                ];
            }
        }
    }

    /**
     * Shared target for every Karta-podarunkowa placement: the configured URL,
     * or — when blank — the gift-card purchase page (the entry point that used
     * to live in the giftcard module's left-column block).
     */
    public function getGiftcardUrl(?int $idLang = null): string
    {
        $url = trim($this->hbeLocConfig('HBE_GIFTCARD_URL', $idLang));

        if ($url === '') {
            return $this->context->link->getModuleLink('giftcard', 'choicegiftcard');
        }
        if (!preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    /**
     * Karta podarunkowa — footer promo block (own footer column, styled to sit
     * next to the ps_linklist columns). Rendered via displayFooter.
     */
    public function hookDisplayFooter(array $params = []): string
    {
        if (!(int) Configuration::get('HBE_GIFTCARD_FOOTER_ENABLED')) {
            return '';
        }

        $label = trim($this->hbeLocConfig('HBE_GIFTCARD_FOOTER_LABEL'));
        if ($label === '') {
            return '';
        }

        $this->context->smarty->assign([
            'hbe_giftcard_footer_label' => $label,
            'hbe_giftcard_footer_desc'  => trim($this->hbeLocConfig('HBE_GIFTCARD_FOOTER_DESC')),
            'hbe_giftcard_url'          => $this->getGiftcardUrl(),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/giftcard-footer.tpl');
    }

    /* ── Magic method — handles every custom hook via __call() ───────────── */

    /**
     * PS8 uses is_callable([$module, $methodName]) which returns TRUE when
     * __call() is defined (explicitly documented in PS8 Hook.php).
     */
    public function __call(string $name, array $args): ?string
    {
        if (strncmp($name, 'hook', 4) === 0) {
            $hookName = lcfirst(substr($name, 4));
            $params   = $args[0] ?? [];
            return $this->renderHookBlocks($hookName, $params);
        }
        return null;
    }

    /* ── Standard hooks ──────────────────────────────────────────────────── */

    /**
     * Current front controller page name ('index', 'product', 'category', …).
     */
    private function currentPage(): string
    {
        return (string) ($this->context->controller->php_self ?? '');
    }

    /**
     * Configured social profiles, ready to render: only networks with a URL,
     * in SOCIAL_NETWORKS order.
     *
     * @return array<int,array{key:string,label:string,url:string}>
     */
    public function getSocialLinks(): array
    {
        $links = [];

        foreach (self::SOCIAL_NETWORKS as $key => $label) {
            $url = trim((string) HbEditorConfig::get(self::socialConfigKey($key)));
            if ($url === '') {
                continue;
            }
            $links[] = [
                'key'   => $key,
                'label' => $label,
                'url'   => $this->hbeSliderValidateUrl($url),
            ];
        }

        return $links;
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        $page = $this->currentPage();

        // Footer social icons — the theme's ps_contactinfo override renders them.
        $this->context->smarty->assign('hbe_social_links', $this->getSocialLinks());

        // Single source of truth for the "free shipping from X" copy (product page perk).
        $this->context->smarty->assign(
            'hbe_free_shipping_threshold',
            $this->getFreeShippingThresholdFormatted()
        );

        // Core styles (header: topbar, search overlay, custom blocks) — every page.
        $this->context->controller->registerStylesheet(
            'hb-editor-front',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 200]
        );

        if ($page === 'index') {
            $this->context->controller->registerStylesheet(
                'hb-editor-home',
                'modules/' . $this->name . '/views/css/home.css',
                ['media' => 'all', 'priority' => 200]
            );
            $this->context->controller->registerJavascript(
                'hb-editor-slider',
                'modules/' . $this->name . '/views/js/slider.js',
                ['position' => 'bottom', 'priority' => 200]
            );
        }

        if ($page === 'product') {
            $this->context->controller->registerStylesheet(
                'hb-editor-product',
                'modules/' . $this->name . '/views/css/product.css',
                ['media' => 'all', 'priority' => 200]
            );
            // Karta produktu: which description feeds the summary slot under
            // the price ('' = theme standard, 'short' or 'full'). Read by the
            // theme's catalog/product.tpl.
            $this->context->smarty->assign(
                'hbe_product_summary_source',
                (string) HbEditorConfig::get('HBE_PRODUCT_SUMMARY_SOURCE')
            );
            if ((int) Configuration::get('HBE_FAQ_ENABLED')) {
                $this->context->controller->registerJavascript(
                    'hb-editor-faq',
                    'modules/' . $this->name . '/views/js/faq.js',
                    ['position' => 'bottom', 'priority' => 200]
                );
            }
            if ((int) Configuration::get('HBE_RELATED_ENABLED')) {
                $this->context->controller->registerJavascript(
                    'hb-editor-related',
                    'modules/' . $this->name . '/views/js/related-carousel.js',
                    ['position' => 'bottom', 'priority' => 200]
                );
            }
        }

        if ($page === 'category') {
            $this->context->controller->registerStylesheet(
                'hb-editor-listing',
                'modules/' . $this->name . '/views/css/listing.css',
                ['media' => 'all', 'priority' => 200]
            );
        }

        // Carousel section header vars — consumed by the theme overrides of
        // ps_bestsellers / ps_newproducts / ps_featuredproducts / ps_categoryproducts.
        if (in_array($page, ['index', 'product', 'category'], true)) {
            $this->context->smarty->assign([
                'hbe_np_title'     => $this->hbeLocConfig('HBE_NP_TITLE'),
                'hbe_np_text'      => $this->hbeLocConfig('HBE_NP_TEXT'),
                'hbe_np_link_text' => $this->hbeLocConfig('HBE_NP_LINK_TEXT'),
                'hbe_np_link_url'  => $this->hbeLocConfig('HBE_NP_LINK_URL'),
                'hbe_bs_title'     => $this->hbeLocConfig('HBE_BS_TITLE'),
                'hbe_bs_text'      => $this->hbeLocConfig('HBE_BS_TEXT'),
                'hbe_bs_link_text' => $this->hbeLocConfig('HBE_BS_LINK_TEXT'),
                'hbe_bs_link_url'  => $this->hbeLocConfig('HBE_BS_LINK_URL'),
                'hbe_cp_title'     => $this->hbeLocConfig('HBE_CP_TITLE'),
                'hbe_cp_text'      => $this->hbeLocConfig('HBE_CP_TEXT'),
                'hbe_cp_link_text' => $this->hbeLocConfig('HBE_CP_LINK_TEXT'),
                'hbe_cp_link_url'  => $this->hbeLocConfig('HBE_CP_LINK_URL'),
            ]);

            // Carousel-source override: when a slot has a category configured,
            // fetch that category's products and hand them to the theme carousel
            // template, which shows them in place of the native module's list.
            // The non-empty guard means an empty/inactive category falls back to
            // the native list instead of rendering an empty carousel.
            if ($page === 'index') {
                $npCat = (int) Configuration::get('HBE_NP_CATEGORY_ID');
                if ($npCat > 0 && ($np = $this->getCategoryCarouselProducts($npCat))) {
                    $this->context->smarty->assign('hbe_np_override_products', $np);
                }
                $bsCat = (int) Configuration::get('HBE_BS_CATEGORY_ID');
                if ($bsCat > 0 && ($bs = $this->getCategoryCarouselProducts($bsCat))) {
                    $this->context->smarty->assign('hbe_bs_override_products', $bs);
                }
            }
            if ($page === 'product') {
                $cpCat = (int) Configuration::get('HBE_CP_CATEGORY_ID');
                if ($cpCat > 0 && ($cp = $this->getCategoryCarouselProducts($cpCat))) {
                    $this->context->smarty->assign('hbe_cp_override_products', $cp);
                }
            }
        }

        $this->setupCartPreview();
        $this->setupWishlistPreview();
    }

    /**
     * Present a category's products as a listing array, ready for the theme's
     * productlist.tpl — same recipe ps_categoryproducts uses, so cards render
     * identically. Returns [] for an invalid, inactive or empty category.
     * Used by the carousel-source override (index + product carousels).
     *
     * @return array<int,array<string,mixed>>
     */
    private function getCategoryCarouselProducts(int $idCategory, int $limit = 12): array
    {
        if ($idCategory <= 0 || $limit <= 0) {
            return [];
        }

        $category = new Category($idCategory, (int) $this->context->language->id);
        if (!Validate::isLoadedObject($category) || !$category->active) {
            return [];
        }

        $searchProvider = new \PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider(
            $this->getTranslator(),
            $category
        );
        $searchContext = new \PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext($this->context);
        $query = new \PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery();
        $query->setResultsPerPage($limit)
            ->setPage(1)
            ->setSortOrder(\PrestaShop\PrestaShop\Core\Product\Search\SortOrder::random());

        $result = $searchProvider->runQuery($searchContext, $query);

        $assembler = new ProductAssembler($this->context);
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presentationSettings->showPrices = (bool) Configuration::showPrices();
        $presenter = new \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter(
            new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever($this->context->link),
            $this->context->link,
            new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter(),
            new \PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $out = [];
        $rawProducts = $result->getProducts();
        $bulk = method_exists($assembler, 'assembleProducts');
        if ($bulk) {
            $rawProducts = $assembler->assembleProducts($rawProducts);
        }
        foreach ($rawProducts as $rawProduct) {
            $out[] = $presenter->present(
                $presentationSettings,
                $bulk ? $rawProduct : $assembler->assembleProduct($rawProduct),
                $this->context->language
            );
        }

        return $out;
    }

    /**
     * Wishlist preview drawer (Figma: Ulubione). Managed here so the core
     * blockwishlist module stays untouched — the drawer talks to
     * blockwishlist's own AJAX API from wishlist-preview.js.
     */
    private function setupWishlistPreview(): void
    {
        if (!$this->isWishlistPreviewEnabled()) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'hb-editor-wishlist-preview',
            'modules/' . $this->name . '/views/css/wishlist-preview.css',
            ['media' => 'all', 'priority' => 200]
        );
        $this->context->controller->registerJavascript(
            'hb-editor-wishlist-preview',
            'modules/' . $this->name . '/views/js/wishlist-preview.js',
            ['position' => 'bottom', 'priority' => 200]
        );

        Media::addJsDef([
            'hbeWishlistPreview' => [
                'getAllWishlistUrl' => $this->context->link->getModuleLink(
                    'blockwishlist',
                    'action',
                    ['action' => 'getAllWishlist']
                ),
                'addUrl' => $this->context->link->getModuleLink(
                    'blockwishlist',
                    'action',
                    ['action' => 'addProductToWishlist']
                ),
                'loginUrl' => $this->context->link->getPageLink('authentication', true, null, [
                    'back' => $this->context->link->getModuleLink('blockwishlist', 'lists'),
                ]),
                'i18n' => [
                    'loading'   => $this->l('Wczytywanie…'),
                    'empty'     => $this->l('Twoja lista ulubionych jest pusta.'),
                    'login'     => $this->l('Zaloguj się, aby zobaczyć swoje ulubione produkty.'),
                    'loginLink' => $this->l('Zaloguj się'),
                    'error'     => $this->l('Nie udało się wczytać ulubionych. Spróbuj ponownie.'),
                    'added'     => $this->l('Produkt dodany do ulubionych.'),
                    'tax'       => $this->l('(brutto)'),
                ],
            ],
        ]);
    }

    /** Feature toggle — enabled by default, disable with HBE_WISHLIST_PREVIEW_ENABLED = 0. */
    private function isWishlistPreviewEnabled(): bool
    {
        $flag = Configuration::get('HBE_WISHLIST_PREVIEW_ENABLED');
        if ($flag !== false && !(int) $flag) {
            return false;
        }

        return Module::isEnabled('blockwishlist');
    }

    /**
     * Cart preview feature (Modal Figma design). Managed here so the core
     * ps_shoppingcart module stays untouched (it is overwritten on updates).
     *
     * Registers the assets and exposes the variables consumed by the theme
     * overrides of ps_shoppingcart.tpl / modal.tpl:
     *   $hbe_cart_hover_enabled, $hbe_cart_modal_enabled, $hbe_cart_free_shipping
     */
    private function setupCartPreview(): void
    {
        $hoverEnabled = (bool) Configuration::get('PS_BLOCK_CART_HOVER');
        $modalEnabled = (bool) Configuration::get('PS_BLOCK_CART_PREVIEW_MODAL');

        if (!$hoverEnabled && !$modalEnabled) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'hb-editor-cart-preview',
            'modules/' . $this->name . '/views/css/cart-preview.css',
            ['media' => 'all', 'priority' => 200, 'version' => $this->assetVersion('views/css/cart-preview.css')]
        );
        $this->context->controller->registerJavascript(
            'hb-editor-cart-preview',
            'modules/' . $this->name . '/views/js/cart-preview.js',
            ['position' => 'bottom', 'priority' => 200, 'version' => $this->assetVersion('views/js/cart-preview.js')]
        );

        $this->context->smarty->assign([
            'hbe_cart_hover_enabled' => $hoverEnabled,
            'hbe_cart_modal_enabled' => $modalEnabled,
            'hbe_cart_free_shipping' => $this->getCartFreeShippingData(),
            'hbe_cart_preview_url'   => $this->context->link->getModuleLink($this->name, 'cartpreview'),
        ]);
    }

    /**
     * Cache-busting stamp appended to an asset URL (PS turns it into "?<stamp>").
     *
     * Module assets are served straight from disk, with no Cache-Control header
     * and no version in the URL, so after a deploy browsers happily keep running
     * the previous file. Keying on the file's mtime means every change to the
     * asset produces a new URL on its own — nobody has to remember to bump it.
     */
    private function assetVersion(string $relativeFile): string
    {
        $mtime = @filemtime(_PS_MODULE_DIR_ . $this->name . '/' . $relativeFile);

        return $mtime ? (string) $mtime : $this->version;
    }

    /**
     * Free-shipping threshold in the shop's default currency, resolved from the
     * mode set in BO → Hummingbird → Koszyk. 0 disables the progress bar.
     *
     * - auto   → read back from the carrier price ranges (see detectCarrierFreeShippingThreshold)
     * - manual → the amount typed in the BO field
     * - shop   → Dostawa → Preferencje (PS_SHIPPING_FREE_PRICE)
     * - off    → no bar
     */
    public function getFreeShippingThreshold(): float
    {
        $manual = (float) Configuration::get('HBE_CART_FREE_SHIPPING_THRESHOLD');

        switch ((string) Configuration::get('HBE_CART_FREE_SHIPPING_MODE')) {
            case self::FREE_SHIPPING_MODE_OFF:
                return 0.0;

            case self::FREE_SHIPPING_MODE_MANUAL:
                return max(0.0, $manual);

            case self::FREE_SHIPPING_MODE_SHOP:
                return max(0.0, (float) Configuration::get('PS_SHIPPING_FREE_PRICE'));

            case self::FREE_SHIPPING_MODE_AUTO:
                return $this->detectCarrierFreeShippingThreshold();

            default:
                // Mode never saved (fresh install, or an upgrade that predates it):
                // honour a manual amount if one was set, otherwise read the carriers.
                return $manual > 0 ? $manual : $this->detectCarrierFreeShippingThreshold();
        }
    }

    /**
     * Lowest cart total from which a real carrier ships for free, read straight
     * from the carrier price ranges (Sprzedaż → Przewoźnicy → Koszty wysyłki).
     * This keeps the bar honest: it tracks the number the checkout actually
     * charges on, instead of a shop setting that drifts out of sync.
     *
     * Only carriers that charge for at least one range are considered. A carrier
     * that is free across its whole range is a pickup point ("Odbiór osobisty"),
     * not a free-shipping offer, and would otherwise drag the threshold to ~0.
     *
     * Limited to price-based carriers: a weight-based carrier's ranges answer
     * "from how many kg", which is not the question the bar asks.
     *
     * @return float 0.0 when no carrier offers a free range
     */
    public function detectCarrierFreeShippingThreshold(): float
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        // delivery.id_shop is nullable — NULL means "every shop", which is how the
        // rows actually look here. Matching it with `= id_shop` silently finds nothing.
        $idShop = (int) $this->context->shop->id;
        $shopScope = '(d.id_shop IS NULL OR d.id_shop = ' . $idShop . ')';

        $sql = new DbQuery();
        $sql->select('MIN(rp.delimiter1)');
        $sql->from('delivery', 'd');
        $sql->innerJoin('range_price', 'rp', 'rp.id_range_price = d.id_range_price');
        $sql->innerJoin('carrier', 'c', 'c.id_carrier = d.id_carrier');
        $sql->where('c.active = 1 AND c.deleted = 0 AND c.is_free = 0');
        $sql->where('c.shipping_method = ' . (int) Carrier::SHIPPING_METHOD_PRICE);
        $sql->where($shopScope);
        $sql->where('d.price = 0 AND rp.delimiter1 > 0');
        $sql->where(
            'd.id_carrier IN (SELECT paid.id_carrier FROM ' . _DB_PREFIX_ . 'delivery paid
                WHERE paid.price > 0
                  AND (paid.id_shop IS NULL OR paid.id_shop = ' . $idShop . '))'
        );

        $value = Db::getInstance()->getValue($sql);

        return $cache = ($value === false || $value === null) ? 0.0 : (float) $value;
    }

    /**
     * The resolved threshold converted to the customer's currency and formatted
     * ("250,00 zł"), for the copy that only announces the amount (the product
     * page perk). Empty string when the bar is off, so templates can test it.
     */
    public function getFreeShippingThresholdFormatted(): string
    {
        $threshold = $this->getFreeShippingThreshold();
        if ($threshold <= 0) {
            return '';
        }

        // No locale outside a front-office request (CLI, some ajax entry points):
        // drop the copy rather than fatal the page that embeds it.
        $locale   = $this->context->getCurrentLocale();
        $currency = $this->context->currency;
        if ($locale === null || !Validate::isLoadedObject($currency)) {
            return '';
        }

        return $locale->formatPrice(
            (float) Tools::convertPrice($threshold, $currency),
            $currency->iso_code
        );
    }

    /**
     * Remaining amount to reach free shipping plus a progress value (0-100) for
     * the progress bar. The actual free-shipping decision stays in the core
     * (Cart::getPackageShippingCost) — this only mirrors it for the customer.
     *
     * @return array<string,mixed>
     */
    public function getCartFreeShippingData(): array
    {
        $default = [
            'enabled'             => false,
            'reached'             => false,
            'remaining'           => 0.0,
            'remaining_formatted' => '',
            'progress'            => 0,
        ];

        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            return $default;
        }

        $threshold = $this->getFreeShippingThreshold();
        if ($threshold <= 0) {
            return $default;
        }

        // PS_SHIPPING_FREE_PRICE is stored in the default currency: convert to the cart currency.
        $currency = Currency::getCurrencyInstance((int) $cart->id_currency);
        $threshold = (float) Tools::convertPrice($threshold, $currency);

        // Order total (with discounts, without shipping) — the same basis the core uses for the free-shipping test.
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);

        $remaining = max(0.0, $threshold - $total);
        $progress = $threshold > 0 ? (int) min(100, round($total / $threshold * 100)) : 0;

        return [
            'enabled'             => true,
            'reached'             => $remaining <= 0,
            'remaining'           => $remaining,
            'remaining_formatted' => $this->context->getCurrentLocale()->formatPrice($remaining, $currency->iso_code),
            'progress'            => $progress,
        ];
    }

    public function hookDisplayTop(): string
    {
        return '';
    }

    /**
     * Rosenthal Care promo block, rendered in the cart below the product list
     * (theme cart.tpl runs displayShoppingCartFooter there). Recreates the old
     * cart.tpl block: a short pitch plus a button that adds the configured care
     * product to the cart via the theme's native ajax add-to-cart.
     *
     * Off by default; everything is configurable in BO → Hummingbird → Koszyk.
     */
    public function hookDisplayShoppingCartFooter(array $params = []): string
    {
        if (!(int) Configuration::get('HBE_CARE_ENABLED')) {
            return '';
        }

        $idProduct = (int) Configuration::get('HBE_CARE_PRODUCT_ID');
        if ($idProduct <= 0) {
            return '';
        }

        // Skip a missing/disabled product so the cart never shows a dead button.
        $product = new Product($idProduct, false, (int) $this->context->language->id);
        if (!Validate::isLoadedObject($product) || !$product->active) {
            return '';
        }

        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            return '';
        }

        // Already covered — no point promoting it again (and avoids double-adds).
        if ($this->cartContainsProduct($cart, $idProduct)) {
            return '';
        }

        $textRaw = (string) Configuration::get('HBE_CARE_TEXT');
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $textRaw))));

        $cartShowUrl = $this->context->link->getPageLink('cart', true, null, ['action' => 'show']);

        $this->context->smarty->assign('hbe_care', [
            'heading'        => (string) Configuration::get('HBE_CARE_HEADING'),
            'lines'          => $lines,
            'button'         => (string) Configuration::get('HBE_CARE_BUTTON'),
            'id_product'     => $idProduct,
            'login_required' => (int) Configuration::get('HBE_CARE_LOGIN_REQUIRED') === 1,
            'is_logged'      => (bool) $this->context->customer->isLogged(),
            'login_url'      => $this->context->link->getPageLink('authentication', true, null, ['back' => $cartShowUrl]),
            'cart_url'       => $this->context->link->getPageLink('cart', true),
            'static_token'   => Tools::getToken(false),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/rosenthal-care.tpl');
    }

    /** True when the cart already holds the given product id. */
    private function cartContainsProduct(Cart $cart, int $idProduct): bool
    {
        foreach ($cart->getProducts() as $product) {
            if ((int) $product['id_product'] === $idProduct) {
                return true;
            }
        }

        return false;
    }

    /**
     * Custom theme hook (like displayListingBanner), called once in
     * product.tpl below the buy section. Not a standard PS hook on purpose:
     * displayProductButtons is an alias of displayProductAdditionalInfo, so
     * registering there made the content render twice (the theme executes
     * the canonical hook in product-additional-info.tpl as well).
     */
    public function hookDisplayProductSections(array $params = []): string
    {
        return $this->renderFaq() . $this->renderRelated($params);
    }

    /**
     * "Inni kupili również" — shell of the one-card carousel rendered below the
     * FAQ. Items come from ps_crossselling data, fetched lazily by
     * related-carousel.js from the module front controller (related.php).
     */
    private function renderRelated(array $params = []): string
    {
        if (!(int) Configuration::get('HBE_RELATED_ENABLED')) {
            return '';
        }
        $cross = Module::getInstanceByName('ps_crossselling');
        if (!$cross || !$cross->active) {
            return '';
        }

        $idProduct = 0;
        if (isset($params['product'])) {
            $idProduct = (int) ($params['product']['id_product'] ?? $params['product']['id'] ?? 0);
        }
        if (!$idProduct) {
            $idProduct = (int) Tools::getValue('id_product');
        }
        if (!$idProduct) {
            return '';
        }

        $title = trim((string) $this->hbeLocConfig('HBE_RELATED_TITLE'));
        if ($title === '') {
            $title = 'Inni kupili również';
        }

        $this->context->smarty->assign([
            'hbe_related_title'    => $title,
            'hbe_related_ajax_url' => $this->context->link->getModuleLink(
                $this->name,
                'related',
                ['id_product' => $idProduct]
            ),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/related.tpl');
    }

    /**
     * Explicit hook (would otherwise go through __call) so the "image + text"
     * section renders below the product description, before any custom blocks
     * assigned to this hook.
     */
    public function hookDisplayFooterProduct(array $params = []): string
    {
        return $this->renderImgText() . $this->renderHookBlocks('displayFooterProduct', $params);
    }

    /**
     * Image + text split section (Figma: Image with Text) — cream text panel
     * with title/desc/outline CTA on the left, full-bleed image on the right.
     */
    private function renderImgText(): string
    {
        if (!(int) Configuration::get('HBE_IMGTEXT_ENABLED')) {
            return '';
        }
        $image = trim((string) Configuration::get('HBE_IMGTEXT_IMAGE'));
        if ($image === '') {
            return '';
        }
        $imageSources = $this->resolveHbEditorImageSources($image);
        $mobileImage = trim((string) Configuration::get('HBE_IMGTEXT_IMAGE_MOBILE'));
        $mobileSources = $this->resolveHbEditorImageSources($mobileImage);
        $url = trim($this->hbeLocConfig('HBE_IMGTEXT_CTA_URL'));
        if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
            $url = 'https://' . $url;
        }

        $this->context->smarty->assign([
            'hbe_imgtext_image_url' => $imageSources['url'],
            'hbe_imgtext_image_webp_url' => $imageSources['webp_url'],
            'hbe_imgtext_image_mobile_url' => $mobileSources['url'],
            'hbe_imgtext_image_mobile_webp_url' => $mobileSources['webp_url'],
            'hbe_imgtext_title'    => $this->hbeLocConfig('HBE_IMGTEXT_TITLE'),
            'hbe_imgtext_desc'     => $this->hbeLocConfig('HBE_IMGTEXT_DESC'),
            'hbe_imgtext_cta_text' => $this->hbeLocConfig('HBE_IMGTEXT_CTA_TEXT'),
            'hbe_imgtext_cta_url'  => $url,
            'hbe_imgtext_bg'       => (string) (Configuration::get('HBE_IMGTEXT_BG') ?: '#f5f1ea'),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/imgtext.tpl');
    }

    /**
     * Listing banner (Figma: Image Banner) — full-width photo with a serif
     * title and CTA bottom-left, injected by the theme after the 2nd product
     * row on category pages. First enabled banner slot (1-5) assigned to the
     * current category wins.
     */
    public function hookDisplayListingBanner(array $params = []): string
    {
        $idCategory = (int) ($params['id_category'] ?? Tools::getValue('id_category'));
        if (!$idCategory) {
            return '';
        }

        for ($i = 1; $i <= 5; $i++) {
            if (!(int) Configuration::get('HBE_LISTBAN_' . $i . '_ENABLED')) {
                continue;
            }
            $cats = array_filter(array_map('intval', explode(',', (string) Configuration::get('HBE_LISTBAN_' . $i . '_CATS'))));
            if (!in_array($idCategory, $cats, true)) {
                continue;
            }
            $image = trim((string) Configuration::get('HBE_LISTBAN_' . $i . '_IMAGE'));
            if ($image === '') {
                continue;
            }

            $imageSources = $this->resolveHbEditorImageSources($image);
            $mobileImage = trim((string) Configuration::get('HBE_LISTBAN_' . $i . '_IMAGE_MOBILE'));
            $mobileSources = $this->resolveHbEditorImageSources($mobileImage);
            $url = trim($this->hbeLocConfig('HBE_LISTBAN_' . $i . '_URL'));
            if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
                $url = 'https://' . $url;
            }

            $this->context->smarty->assign([
                'hbe_listban_image_url' => $imageSources['url'],
                'hbe_listban_image_webp_url' => $imageSources['webp_url'],
                'hbe_listban_image_mobile_url' => $mobileSources['url'],
                'hbe_listban_image_mobile_webp_url' => $mobileSources['webp_url'],
                'hbe_listban_title'    => $this->hbeLocConfig('HBE_LISTBAN_' . $i . '_TITLE'),
                'hbe_listban_cta_text' => $this->hbeLocConfig('HBE_LISTBAN_' . $i . '_CTA_TEXT'),
                'hbe_listban_url'      => $url,
            ]);

            return $this->display(__FILE__, 'views/templates/hook/listing-banner.tpl');
        }

        return '';
    }

    private function renderFaq(): string
    {
        if (!(int) Configuration::get('HBE_FAQ_ENABLED')) {
            return '';
        }
        $idLang = (int) $this->context->language->id;
        $rawItems = Configuration::get('HBE_FAQ_ITEMS_' . $idLang);
        if (!$rawItems) {
            $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
            $rawItems = Configuration::get('HBE_FAQ_ITEMS_' . $idLangDefault);
        }
        $items = [];
        if ($rawItems) {
            $decoded = json_decode($rawItems, true);
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    if (!empty($row['q'])) {
                        $items[] = ['q' => $row['q'], 'a' => $row['a'] ?? ''];
                    }
                }
            }
        }
        if (!$items) {
            return '';
        }
        $this->context->smarty->assign([
            'hbe_faq_items'          => $items,
            'hbe_faq_bg'             => (string) (Configuration::get('HBE_FAQ_BG') ?: '#ffffff'),
            'hbe_faq_question_color' => (string) (Configuration::get('HBE_FAQ_QUESTION_COLOR') ?: '#242424'),
            'hbe_faq_answer_color'   => (string) (Configuration::get('HBE_FAQ_ANSWER_COLOR') ?: '#4a4a4a'),
            'hbe_faq_border_color'   => (string) (Configuration::get('HBE_FAQ_BORDER_COLOR') ?: '#e5e5e5'),
        ]);
        return $this->display(__FILE__, 'views/templates/hook/faq.tpl');
    }

    public function hookDisplayAfterBodyOpeningTag(): string
    {
        $output = '';

        // Inline CSS for header element visibility toggles
        $css = '';

        // Anti-FOUC: the desktop header icons (account, wishlist, cart) carry no
        // mobile-hiding class, so on phones they render near the logo for a frame
        // until the theme's responsive-toggler empties them (innerHTML swap into
        // the #_mobile_* placeholders) on DOMContentLoaded. Injected here (right
        // after <body>, before the header) they never paint on mobile.
        $css .= '@media (max-width:767.98px){#_desktop_ps_customersignin,#_desktop_ps_shoppingcart,#_desktop_blockwishlist{display:none!important}}';

        if ((int) Configuration::get('HBE_HIDE_CURRENCY_DESKTOP')) {
            $css .= '@media (min-width:768px){#_desktop_ps_currencyselector{display:none!important}}';
        }
        if ((int) Configuration::get('HBE_HIDE_CURRENCY_MOBILE')) {
            $css .= '@media (max-width:767px){#_desktop_ps_currencyselector,#_mobile_ps_currencyselector{display:none!important}}';
        }
        if ((int) Configuration::get('HBE_HIDE_LANGUAGE_DESKTOP')) {
            $css .= '@media (min-width:768px){#_desktop_ps_languageselector{display:none!important}}';
        }
        if ((int) Configuration::get('HBE_HIDE_LANGUAGE_MOBILE')) {
            $css .= '@media (max-width:767px){#_desktop_ps_languageselector,#_mobile_ps_languageselector{display:none!important}}';
        }
        if ((int) Configuration::get('HBE_HIDE_QUICKVIEW')) {
            // Hide quickview button on product miniatures (both desktop hover and mobile touch variants).
            $css .= '.product-miniature__quickview-button,.product-miniature__quickview-touch,.js-quickview{display:none!important}';
        }
        if ($css !== '') {
            $output .= '<style>' . $css . '</style>';
        }

        $page = $this->currentPage();
        $jsBase = $this->context->link->getBaseLink() . 'modules/' . $this->name . '/views/js/';
        $jsDir = _PS_MODULE_DIR_ . $this->name . '/views/js/';

        // Cache-bust each script by its file mtime so browsers always fetch the
        // latest version after a deploy (these are injected as plain <script>,
        // not through the versioned asset pipeline).
        $jsTag = function (string $file) use ($jsBase, $jsDir): string {
            $ver = @filemtime($jsDir . $file) ?: '';
            return '<script src="' . htmlspecialchars($jsBase . $file . ($ver ? '?v=' . $ver : '')) . '" defer></script>';
        };

        // Search overlay lives in the header — every page.
        // (Injected directly: registerJavascript is unreliable in PS8.)
        $output .= $jsTag('search-overlay.js');

        // Expand/collapse for .ps-customtext blocks — rendered on home/listing/CMS only.
        if (in_array($page, ['index', 'category', 'cms'], true)) {
            $output .= $jsTag('expand-text.js');
        }

        // Drag-scroll + arrow nav for product carousels (featured/bestsellers on
        // home, categoryproducts/accessories on product).
        if (in_array($page, ['index', 'product'], true)) {
            $output .= $jsTag('carousel-drag.js');
        }

        // Wishlist preview drawer shell (Figma: Ulubione) — every page; rows
        // are filled client-side by wishlist-preview.js.
        if ($this->isWishlistPreviewEnabled()) {
            $this->context->smarty->assign([
                'hbe_wishlist_lists_url' => $this->context->link->getModuleLink('blockwishlist', 'lists'),
            ]);
            $output .= $this->display(__FILE__, 'views/templates/hook/wishlist-preview.tpl');
        }

        // Karta podarunkowa — floating pill button (position:fixed, so the DOM
        // spot at the top of <body> is irrelevant). Dismissible per session.
        if ((int) Configuration::get('HBE_GIFTCARD_FLOAT_ENABLED')) {
            $floatLabel = trim($this->hbeLocConfig('HBE_GIFTCARD_FLOAT_LABEL'));
            if ($floatLabel !== '') {
                $pos = Configuration::get('HBE_GIFTCARD_FLOAT_POSITION') === 'left' ? 'left' : 'right';
                $this->context->smarty->assign([
                    'hbe_giftcard_float_label'    => $floatLabel,
                    'hbe_giftcard_float_position' => $pos,
                    'hbe_giftcard_url'            => $this->getGiftcardUrl(),
                ]);
                $output .= $this->display(__FILE__, 'views/templates/hook/giftcard-float.tpl');
            }
        }

        if (!(int) Configuration::get('HBE_TOPBAR_ENABLED')) {
            return $output;
        }

        $text = trim($this->hbeLocConfig('HBE_TOPBAR_TEXT'));
        if ($text === '') {
            return $output;
        }

        $url = trim($this->hbeLocConfig('HBE_TOPBAR_URL'));
        if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
            $url = 'https://' . $url;
        }
        $linkText = trim($this->hbeLocConfig('HBE_TOPBAR_LINK_TEXT'));

        $this->context->smarty->assign([
            'hbe_topbar_text' => $text,
            'hbe_topbar_url' => $url,
            'hbe_topbar_link_text' => $linkText,
        ]);

        return $output . $this->display(__FILE__, 'views/templates/hook/topbar.tpl');
    }

    /* ── Info bar + Image Hero (displayHome) — ordered ──────────────────── */

    public function hookDisplayHome(array $params = []): string
    {
        $rawOrder = (string) Configuration::get('HBE_HOME_ORDER') ?: 'infobar,imghero,cols3,tagline';
        $order = array_filter(array_map('trim', explode(',', $rawOrder)));
        if (!$order) {
            $order = ['infobar', 'imghero', 'cols3', 'tagline'];
        }

        // Load custom displayHome blocks
        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;
        $customBlocks = HbEditorBlock::getByHook('displayHome', $idShop, $idLang);
        $blockMap = [];
        foreach ($customBlocks as $cb) {
            $blockMap[(int) $cb['id_block']] = $cb;
        }
        // Append any blocks not yet in the order string
        foreach ($blockMap as $id => $cb) {
            if (!in_array((string) $id, $order, true)) {
                $order[] = (string) $id;
            }
        }

        $output = '';
        foreach ($order as $component) {
            if ($component === 'infobar') {
                $output .= $this->renderInfoBar();
            } elseif ($component === 'infobar2') {
                $output .= $this->renderInfoBar2();
            } elseif ($component === 'imghero') {
                $output .= $this->renderImgHero();
            } elseif ($component === 'imghero2') {
                $output .= $this->renderImgHero2();
            } elseif ($component === 'cols3') {
                $output .= $this->renderCols3();
            } elseif ($component === 'cols3desc') {
                $output .= $this->renderCols3Desc();
            } elseif ($component === 'tagline') {
                $output .= $this->renderTagline();
            } elseif ($component === 'katcols') {
                $output .= $this->renderKatcols();
            } elseif ($component === 'splitblock') {
                $output .= $this->renderSplitBlock();
            } elseif ($component === 'icons4') {
                $output .= $this->renderIcons4();
            } elseif ($component === 'brands') {
                $output .= $this->renderBrands();
            } elseif ($component === 'shops') {
                $output .= $this->renderShops();
            } elseif ($component === 'slider') {
                $output .= $this->renderSlider();
            } elseif (ctype_digit($component) && isset($blockMap[(int) $component])) {
                $block = $blockMap[(int) $component];
                if (!(int) $block['active']) {
                    continue;
                }
                if (!empty($block['section_type'])) {
                    $output .= $this->renderSectionBlock($block);
                    continue;
                }
                $block['image_desktop_url'] = $block['image_desktop']
                    ? $this->context->link->getMediaLink(_PS_IMG_ . self::IMG_DIR . $block['image_desktop'])
                    : '';
                $block['image_mobile_url'] = $block['image_mobile']
                    ? $this->context->link->getMediaLink(_PS_IMG_ . self::IMG_DIR . $block['image_mobile'])
                    : '';
                $desktopSources = $this->resolveHbEditorImageSources((string) $block['image_desktop']);
                $mobileSources = $this->resolveHbEditorImageSources((string) $block['image_mobile']);
                $block['image_desktop_webp_url'] = $desktopSources['webp_url'];
                $block['image_mobile_webp_url'] = $mobileSources['webp_url'];
                $this->context->smarty->assign('hbe_block', $block);
                $output .= $this->display(__FILE__, 'views/templates/hook/block.tpl');
            } elseif (strncmp($component, 'module_', 7) === 0) {
                $modName = preg_replace('/[^a-zA-Z0-9_]/', '', substr($component, 7));
                if ($modName === '') {
                    continue;
                }
                $mod = Module::getInstanceByName($modName);
                if ($mod && $mod->active) {
                    if ($mod instanceof \PrestaShop\PrestaShop\Core\Module\WidgetInterface) {
                        $output .= (string) $mod->renderWidget('displayHome', $params);
                    } elseif (method_exists($mod, 'hookDisplayHome')) {
                        $output .= (string) $mod->hookDisplayHome($params);
                    }
                }
            }
        }
        return $output;
    }

    private function renderInfoBar(): string
    {
        if (!(int) Configuration::get('HBE_INFOBAR_ENABLED')) {
            return '';
        }
        $text = trim($this->hbeLocConfig('HBE_INFOBAR_TEXT'));
        if ($text === '') {
            return '';
        }
        $url = trim($this->hbeLocConfig('HBE_INFOBAR_URL'));
        if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
            $url = 'https://' . $url;
        }
        $linkText = trim($this->hbeLocConfig('HBE_INFOBAR_LINK_TEXT'));
        $bg    = (string) Configuration::get('HBE_INFOBAR_BG')    ?: '#222222';
        $color = (string) Configuration::get('HBE_INFOBAR_COLOR') ?: '#ffffff';

        $this->context->smarty->assign([
            'hbe_infobar_text'  => $text,
            'hbe_infobar_url'   => $url,
            'hbe_infobar_link_text' => $linkText,
            'hbe_infobar_bg'    => $bg,
            'hbe_infobar_color' => $color,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/infobar.tpl');
    }

    private function renderInfoBar2(): string
    {
        if (!(int) Configuration::get('HBE_INFOBAR2_ENABLED')) {
            return '';
        }
        $text = trim($this->hbeLocConfig('HBE_INFOBAR2_TEXT'));
        if ($text === '') {
            return '';
        }
        $url = trim($this->hbeLocConfig('HBE_INFOBAR2_URL'));
        if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
            $url = 'https://' . $url;
        }
        $linkText = trim($this->hbeLocConfig('HBE_INFOBAR2_LINK_TEXT'));
        $bg    = (string) Configuration::get('HBE_INFOBAR2_BG')    ?: '#222222';
        $color = (string) Configuration::get('HBE_INFOBAR2_COLOR') ?: '#ffffff';

        $this->context->smarty->assign([
            'hbe_infobar_text'  => $text,
            'hbe_infobar_url'   => $url,
            'hbe_infobar_link_text' => $linkText,
            'hbe_infobar_bg'    => $bg,
            'hbe_infobar_color' => $color,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/infobar.tpl');
    }

    private function renderImgHero(): string
    {
        if (!(int) Configuration::get('HBE_IMGHERO_ENABLED')) {
            return '';
        }
        $image = trim((string) Configuration::get('HBE_IMGHERO_IMAGE'));
        if ($image === '') {
            return '';
        }
        $imageSources = $this->resolveHbEditorImageSources($image);
        $mobileImage = trim((string) Configuration::get('HBE_IMGHERO_IMAGE_MOBILE'));
        $mobileSources = $this->resolveHbEditorImageSources($mobileImage);
        $url = trim($this->hbeLocConfig('HBE_IMGHERO_CTA_URL'));
        if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
            $url = 'https://' . $url;
        }

        $this->context->smarty->assign([
            'hbe_imghero_image_url' => $imageSources['url'],
            'hbe_imghero_image_webp_url' => $imageSources['webp_url'],
            'hbe_imghero_image_mobile_url' => $mobileSources['url'],
            'hbe_imghero_image_mobile_webp_url' => $mobileSources['webp_url'],
            'hbe_imghero_title'     => $this->hbeLocConfig('HBE_IMGHERO_TITLE'),
            'hbe_imghero_desc'      => $this->hbeLocConfig('HBE_IMGHERO_DESC'),
            'hbe_imghero_cta_text'  => $this->hbeLocConfig('HBE_IMGHERO_CTA_TEXT'),
            'hbe_imghero_cta_url'   => $url,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/imghero.tpl');
    }

    private function renderImgHero2(): string
    {
        if (!(int) Configuration::get('HBE_IMGHERO2_ENABLED')) {
            return '';
        }
        $image = trim((string) Configuration::get('HBE_IMGHERO2_IMAGE'));
        if ($image === '') {
            return '';
        }
        $imageSources = $this->resolveHbEditorImageSources($image);
        $mobileImage = trim((string) Configuration::get('HBE_IMGHERO2_IMAGE_MOBILE'));
        $mobileSources = $this->resolveHbEditorImageSources($mobileImage);
        $url = trim($this->hbeLocConfig('HBE_IMGHERO2_CTA_URL'));
        if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
            $url = 'https://' . $url;
        }
        $this->context->smarty->assign([
            'hbe_imghero2_image_url' => $imageSources['url'],
            'hbe_imghero2_image_webp_url' => $imageSources['webp_url'],
            'hbe_imghero2_image_mobile_url' => $mobileSources['url'],
            'hbe_imghero2_image_mobile_webp_url' => $mobileSources['webp_url'],
            'hbe_imghero2_title'     => $this->hbeLocConfig('HBE_IMGHERO2_TITLE'),
            'hbe_imghero2_desc'      => $this->hbeLocConfig('HBE_IMGHERO2_DESC'),
            'hbe_imghero2_cta_text'  => $this->hbeLocConfig('HBE_IMGHERO2_CTA_TEXT'),
            'hbe_imghero2_cta_url'   => $url,
        ]);
        return $this->display(__FILE__, 'views/templates/hook/imghero2.tpl');
    }

    private function renderCols3(): string
    {
        if (!(int) Configuration::get('HBE_COLS3_ENABLED')) {
            return '';
        }
        $cols = [];
        for ($i = 1; $i <= 3; $i++) {
            $url = trim($this->hbeLocConfig('HBE_COLS3_URL_' . $i));
            if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
                $url = 'https://' . $url;
            }
            $cols[] = [
                'text' => $this->hbeLocConfig('HBE_COLS3_TEXT_' . $i),
                'url'  => $url,
            ];
        }
        $this->context->smarty->assign('hbe_cols3', $cols);
        return $this->display(__FILE__, 'views/templates/hook/cols3.tpl');
    }

    private function renderCols3Desc(): string
    {
        if (!(int) Configuration::get('HBE_COLS3D_ENABLED')) {
            return '';
        }
        $cols = [];
        for ($i = 1; $i <= 3; $i++) {
            $url = trim($this->hbeLocConfig('HBE_COLS3D_URL_' . $i));
            if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
                $url = 'https://' . $url;
            }
            $imgSources = $this->resolveHbEditorImageSources((string) Configuration::get('HBE_COLS3D_IMG_' . $i));
            $cols[] = [
                'title' => $this->hbeLocConfig('HBE_COLS3D_TITLE_' . $i),
                'desc'  => $this->hbeLocConfig('HBE_COLS3D_DESC_' . $i),
                'url'   => $url,
                'img_url'      => $imgSources['url'],
                'img_webp_url' => $imgSources['webp_url'],
            ];
        }
        $this->context->smarty->assign('hbe_cols3desc', $cols);
        return $this->display(__FILE__, 'views/templates/hook/cols3desc.tpl');
    }

    private function renderTagline(): string
    {
        if (!(int) Configuration::get('HBE_TAGLINE_ENABLED')) {
            return '';
        }
        $idLang = (int) $this->context->language->id;
        $get = static function (string $key) use ($idLang): string {
            return trim((string) (HbEditorConfig::get($key, $idLang) ?? ''));
        };
        $text = $get('HBE_TAGLINE_TEXT');
        if ($text === '') {
            return '';
        }
        $linkUrl = $get('HBE_TAGLINE_LINK_URL');
        if ($linkUrl !== '' && !preg_match('#^https?://#i', $linkUrl) && strpos($linkUrl, '/') !== 0) {
            $linkUrl = 'https://' . $linkUrl;
        }
        $this->context->smarty->assign([
            'hbe_tagline_text'      => $text,
            'hbe_tagline_link_text' => $get('HBE_TAGLINE_LINK_TEXT'),
            'hbe_tagline_link_url'  => $linkUrl,
        ]);
        return $this->display(__FILE__, 'views/templates/hook/tagline.tpl');
    }

    private function renderKatcols(): string
    {
        if (!(int) Configuration::get('HBE_KATCOLS_ENABLED')) {
            return '';
        }
        $idLang = (int) $this->context->language->id;
        $get = static function (string $key) use ($idLang): string {
            return (string) (HbEditorConfig::get($key, $idLang) ?? '');
        };
        $sanitizeUrl = static function (string $url): string {
            if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
                $url = 'https://' . $url;
            }
            return $url;
        };
        $mlImages = (int) Configuration::get('HBE_KATCOLS_IMAGE_ML');
        $lImage = $mlImages ? $get('HBE_KATCOLS_L_IMAGE') : (string) Configuration::get('HBE_KATCOLS_L_IMAGE');
        $rImage = $mlImages ? $get('HBE_KATCOLS_R_IMAGE') : (string) Configuration::get('HBE_KATCOLS_R_IMAGE');
        $lMobile = $mlImages ? $get('HBE_KATCOLS_L_IMAGE_MOBILE') : (string) Configuration::get('HBE_KATCOLS_L_IMAGE_MOBILE');
        $rMobile = $mlImages ? $get('HBE_KATCOLS_R_IMAGE_MOBILE') : (string) Configuration::get('HBE_KATCOLS_R_IMAGE_MOBILE');
        $lSources = $this->resolveHbEditorImageSources($lImage);
        $rSources = $this->resolveHbEditorImageSources($rImage);
        $lMobileSources = $this->resolveHbEditorImageSources($lMobile);
        $rMobileSources = $this->resolveHbEditorImageSources($rMobile);
        $this->context->smarty->assign([
            'hbe_katcols_title'         => $get('HBE_KATCOLS_TITLE'),
            'hbe_katcols_hdr_text'      => $get('HBE_KATCOLS_HDR_TEXT'),
            'hbe_katcols_hdr_link_text' => $get('HBE_KATCOLS_HDR_LINK_TEXT'),
            'hbe_katcols_hdr_url'       => $sanitizeUrl($get('HBE_KATCOLS_HDR_URL')),
            'hbe_katcols_l_caption'     => $get('HBE_KATCOLS_L_CAPTION'),
            'hbe_katcols_l_url'         => $sanitizeUrl($get('HBE_KATCOLS_L_URL')),
            'hbe_katcols_l_img_url'     => $lSources['url'],
            'hbe_katcols_l_img_webp_url' => $lSources['webp_url'],
            'hbe_katcols_l_img_mobile_url' => $lMobileSources['url'],
            'hbe_katcols_l_img_mobile_webp_url' => $lMobileSources['webp_url'],
            'hbe_katcols_r_caption'     => $get('HBE_KATCOLS_R_CAPTION'),
            'hbe_katcols_r_url'         => $sanitizeUrl($get('HBE_KATCOLS_R_URL')),
            'hbe_katcols_r_img_url'     => $rSources['url'],
            'hbe_katcols_r_img_webp_url' => $rSources['webp_url'],
            'hbe_katcols_r_img_mobile_url' => $rMobileSources['url'],
            'hbe_katcols_r_img_mobile_webp_url' => $rMobileSources['webp_url'],
        ]);
        return $this->display(__FILE__, 'views/templates/hook/katcols.tpl');
    }

    private function renderSplitBlock(): string
    {
        if (!(int) Configuration::get('HBE_SPLITBLOCK_ENABLED')) {
            return '';
        }
        $ctaUrl = trim($this->hbeLocConfig('HBE_SPLITBLOCK_CTA_URL'));
        if ($ctaUrl !== '' && !preg_match('#^https?://#i', $ctaUrl) && strpos($ctaUrl, '/') !== 0) {
            $ctaUrl = 'https://' . $ctaUrl;
        }
        $mImage = (string) Configuration::get('HBE_SPLITBLOCK_M_IMAGE');
        $rImage = (string) Configuration::get('HBE_SPLITBLOCK_R_IMAGE');
        $mMobile = (string) Configuration::get('HBE_SPLITBLOCK_M_IMAGE_MOBILE');
        $rMobile = (string) Configuration::get('HBE_SPLITBLOCK_R_IMAGE_MOBILE');
        $mSources = $this->resolveHbEditorImageSources($mImage);
        $rSources = $this->resolveHbEditorImageSources($rImage);
        $mMobileSources = $this->resolveHbEditorImageSources($mMobile);
        $rMobileSources = $this->resolveHbEditorImageSources($rMobile);
        $this->context->smarty->assign([
            'hbe_splitblock_title'    => $this->hbeLocConfig('HBE_SPLITBLOCK_TITLE'),
            'hbe_splitblock_desc'     => $this->hbeLocConfig('HBE_SPLITBLOCK_DESC'),
            'hbe_splitblock_cta_text' => $this->hbeLocConfig('HBE_SPLITBLOCK_CTA_TEXT'),
            'hbe_splitblock_cta_url'  => $ctaUrl,
            'hbe_splitblock_m_img_url' => $mSources['url'],
            'hbe_splitblock_m_img_webp_url' => $mSources['webp_url'],
            'hbe_splitblock_m_img_mobile_url' => $mMobileSources['url'],
            'hbe_splitblock_m_img_mobile_webp_url' => $mMobileSources['webp_url'],
            'hbe_splitblock_r_img_url' => $rSources['url'],
            'hbe_splitblock_r_img_webp_url' => $rSources['webp_url'],
            'hbe_splitblock_r_img_mobile_url' => $rMobileSources['url'],
            'hbe_splitblock_r_img_mobile_webp_url' => $rMobileSources['webp_url'],
        ]);
        return $this->display(__FILE__, 'views/templates/hook/splitblock.tpl');
    }

    private function renderBrands(): string
    {
        if (!(int) Configuration::get('HBE_BRANDS_ENABLED')) {
            return '';
        }
        $idLang = (int) $this->context->language->id;
        $brands = [];
        for ($i = 1; $i <= 8; $i++) {
            $manuId    = (int) Configuration::get('HBE_BRANDS_MANU_' . $i);
            $customImg = trim((string) Configuration::get('HBE_BRANDS_IMG_' . $i));
            $customAlt = trim($this->hbeLocConfig('HBE_BRANDS_ALT_' . $i));
            $link      = trim($this->hbeLocConfig('HBE_BRANDS_LINK_' . $i));
            $manu      = $manuId ? $this->hbeManufacturerData($manuId, $idLang) : null;

            // Image: custom upload wins, fall back to manufacturer logo
            if ($customImg !== '') {
                $sources = $this->resolveHbEditorImageSources($customImg);
                $imgUrl  = $sources['url'];
                $webpUrl = $sources['webp_url'];
            } elseif ($manu && $manu['logo_url'] !== '') {
                $imgUrl  = $manu['logo_url'];
                $webpUrl = '';
            } else {
                continue;
            }

            if ($link === '' && $manu) {
                $link = $manu['link'];
            }
            if ($link !== '' && !preg_match('#^https?://#i', $link) && strpos($link, '/') !== 0) {
                $link = 'https://' . $link;
            }

            $alt = $customAlt !== '' ? $customAlt : ($manu['name'] ?? '');

            $brands[] = [
                'img_url'      => $imgUrl,
                'img_webp_url' => $webpUrl,
                'link'         => $link,
                'alt'          => $alt,
            ];
        }
        if (!$brands) {
            return '';
        }
        $this->context->smarty->assign([
            'hbe_brands_title' => $this->hbeLocConfig('HBE_BRANDS_TITLE'),
            'hbe_brands'       => $brands,
        ]);
        return $this->display(__FILE__, 'views/templates/hook/brands.tpl');
    }

    /**
     * "Inne sklepy online" — an editorial closing section promoting up to
     * three sister shops, each with a 3-image mosaic gallery, name,
     * description and an outbound CTA link.
     */
    private function renderShops(): string
    {
        if (!(int) Configuration::get('HBE_SHOPS_ENABLED')) {
            return '';
        }
        $shops = [];
        for ($i = 1; $i <= 3; $i++) {
            $name = trim($this->hbeLocConfig('HBE_SHOPS_NAME_' . $i));
            $url  = trim($this->hbeLocConfig('HBE_SHOPS_URL_' . $i));
            if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
                $url = 'https://' . $url;
            }
            $images = [];
            for ($j = 1; $j <= 3; $j++) {
                $file = trim((string) Configuration::get('HBE_SHOPS_IMG_' . $i . '_' . $j));
                if ($file === '') {
                    continue;
                }
                $sources = $this->resolveHbEditorImageSources($file);
                if ($sources['url'] === '') {
                    continue;
                }
                $images[] = $sources;
            }
            if ($name === '' && !$images) {
                continue;
            }
            $shops[] = [
                'name'     => $name,
                'desc'     => trim($this->hbeLocConfig('HBE_SHOPS_DESC_' . $i)),
                'url'      => $url,
                // Sister shops live on their own domains — open in a new tab.
                'external' => (bool) preg_match('#^https?://#i', $url),
                'images'   => $images,
            ];
        }
        if (!$shops) {
            return '';
        }
        $this->context->smarty->assign([
            'hbe_shops_eyebrow' => trim($this->hbeLocConfig('HBE_SHOPS_EYEBROW')),
            'hbe_shops_title'   => trim($this->hbeLocConfig('HBE_SHOPS_TITLE')),
            'hbe_shops_text'    => trim($this->hbeLocConfig('HBE_SHOPS_TEXT')),
            'hbe_shops_cta'     => trim($this->hbeLocConfig('HBE_SHOPS_CTA')),
            'hbe_shops'         => $shops,
        ]);
        return $this->display(__FILE__, 'views/templates/hook/shops.tpl');
    }

    /* ── Slider (ported from bemo_slider) ──────────────────────────────────── */

    /**
     * Render the slider section (active slides for the current shop + language).
     */
    private function renderSlider(): string
    {
        $slides = $this->getSliderSlides(true);
        if (!$slides) {
            return '';
        }

        $config = $this->getSliderConfig();

        $this->context->smarty->assign('hbe_slider', [
            'speed'       => (int) $config['HBE_SLIDER_SPEED'],
            'autoplay'    => (int) $config['HBE_SLIDER_AUTOPLAY'],
            'pause'       => $config['HBE_SLIDER_PAUSE_ON_HOVER'] ? 'hover' : '',
            'show_arrows' => (int) $config['HBE_SLIDER_SHOW_ARROWS'],
            'arrow_style' => $config['HBE_SLIDER_ARROW_STYLE'],
            'show_dots'   => (int) $config['HBE_SLIDER_SHOW_DOTS'],
            'slides'      => $slides,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/slider.tpl');
    }

    /**
     * Global slider settings (with multishop fallback), defaults applied.
     *
     * @return array<string,mixed>
     */
    private function getSliderConfig(): array
    {
        $get = static function (string $key, $default) {
            $v = HbEditorConfig::get($key);
            return ($v === false || $v === null || $v === '') ? $default : $v;
        };

        return [
            'HBE_SLIDER_SPEED'          => (int) $get('HBE_SLIDER_SPEED', 5000),
            'HBE_SLIDER_AUTOPLAY'       => (int) $get('HBE_SLIDER_AUTOPLAY', 1),
            'HBE_SLIDER_PAUSE_ON_HOVER' => (int) $get('HBE_SLIDER_PAUSE_ON_HOVER', 1),
            'HBE_SLIDER_SHOW_ARROWS'    => (int) $get('HBE_SLIDER_SHOW_ARROWS', 0),
            'HBE_SLIDER_ARROW_STYLE'    => $get('HBE_SLIDER_ARROW_STYLE', 'classic') === 'corner' ? 'corner' : 'classic',
            'HBE_SLIDER_SHOW_DOTS'      => (int) $get('HBE_SLIDER_SHOW_DOTS', 1),
        ];
    }

    /**
     * Fetch slides for the current shop/language with default-language image
     * fallback, resolved image URLs (incl. webp + mobile) and overlay rgba.
     * Ported from bemo_slider::getSlides().
     *
     * @return array<int,array<string,mixed>>
     */
    public function getSliderSlides($active = null, bool $forceShowAll = false): array
    {
        $idShop        = (int) $this->context->shop->id;
        $idLang        = (int) $this->context->language->id;
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        $p             = _DB_PREFIX_;

        $slides = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS(
            'SELECT b.`id_hb_slide` as id_slide, b.`position`, bs.`active`, bs.`active_mobile`,
            bs.`text_position`, bs.`show_text`, bs.`overlay_is_transparent`, bs.`overlay_color`, bs.`overlay_opacity`,
            bs.`cta_enabled`, bs.`cta_text`, bs.`cta_color`, bs.`cta_bg`, bs.`cta_size`, bs.`cta_radius`,
            COALESCE(NULLIF(bsl.`title`, ""), bsl_default.`title`) as `title`,
            COALESCE(NULLIF(bsl.`url`, ""), bsl_default.`url`) as `url`,
            COALESCE(NULLIF(bsl.`description`, ""), bsl_default.`description`) as `description`,
            COALESCE(NULLIF(bsl.`image`, ""), bsl_default.`image`) as `image`,
            COALESCE(NULLIF(bsl.`image_mobile`, ""), bsl_default.`image_mobile`) as `image_mobile`
            FROM ' . $p . 'hb_editor_slider b
            LEFT JOIN ' . $p . 'hb_editor_slider_slides bs ON (b.id_hb_slide = bs.id_hb_slide)
            LEFT JOIN ' . $p . 'hb_editor_slider_slides_lang bsl ON (bs.id_hb_slide = bsl.id_hb_slide AND bsl.id_lang = ' . $idLang . ')
            LEFT JOIN ' . $p . 'hb_editor_slider_slides_lang bsl_default ON (bs.id_hb_slide = bsl_default.id_hb_slide AND bsl_default.id_lang = ' . $idLangDefault . ')
            WHERE b.id_shop = ' . $idShop .
            ($forceShowAll ? '' : ' AND (bsl.`image` IS NOT NULL AND bsl.`image` <> "" OR bsl_default.`image` IS NOT NULL AND bsl_default.`image` <> "")') .
            ($active ? ' AND bs.`active` = 1' : '') . '
            ORDER BY b.position'
        );

        if (!is_array($slides)) {
            return [];
        }

        $webUrl = _MODULE_DIR_ . $this->name . '/images/';
        $fsDir  = _PS_MODULE_DIR_ . $this->name . '/images/';

        foreach ($slides as &$slide) {
            if (empty($slide['image'])) {
                continue;
            }

            $slide['image_url']      = $this->context->link->getMediaLink($webUrl . $slide['image']);
            $slide['image_webp_url'] = '';
            $imgWebpFs = preg_replace('/\.[^.]+$/', '.webp', $fsDir . $slide['image']);
            if ($imgWebpFs && is_file($imgWebpFs)) {
                $slide['image_webp_url'] = $this->context->link->getMediaLink($webUrl . preg_replace('/\.[^.]+$/', '.webp', $slide['image']));
            }

            if (!empty($slide['image_mobile'])) {
                $slide['image_mobile_url']      = $this->context->link->getMediaLink($webUrl . $slide['image_mobile']);
                $slide['image_mobile_webp_url'] = '';
                $mobWebpFs = preg_replace('/\.[^.]+$/', '.webp', $fsDir . $slide['image_mobile']);
                if ($mobWebpFs && is_file($mobWebpFs)) {
                    $slide['image_mobile_webp_url'] = $this->context->link->getMediaLink($webUrl . preg_replace('/\.[^.]+$/', '.webp', $slide['image_mobile']));
                }
            } else {
                $slide['image_mobile_url']      = '';
                $slide['image_mobile_webp_url'] = '';
            }

            $slide['url'] = $this->hbeSliderValidateUrl((string) $slide['url']);

            // Overlay rgba from hex color + opacity
            if (isset($slide['overlay_color'], $slide['overlay_opacity'])) {
                $hex = str_replace('#', '', (string) $slide['overlay_color']);
                if (strlen($hex) === 3) {
                    $r = hexdec($hex[0] . $hex[0]);
                    $g = hexdec($hex[1] . $hex[1]);
                    $b = hexdec($hex[2] . $hex[2]);
                } else {
                    $r = hexdec(substr($hex, 0, 2));
                    $g = hexdec(substr($hex, 2, 2));
                    $b = hexdec(substr($hex, 4, 2));
                }
                $a = (float) $slide['overlay_opacity'] / 100;
                $slide['overlay_rgba'] = "rgba($r, $g, $b, $a)";
            } else {
                $slide['overlay_rgba'] = 'rgba(0, 0, 0, 0.5)';
            }
        }
        unset($slide);

        $slides = array_filter($slides, static function ($slide) {
            return !empty($slide['image']);
        });

        return array_values($slides);
    }

    /**
     * One-time migration from the legacy bemo_slider module:
     * copies slides + lang rows + shop positions into hb_editor_slider* tables,
     * moves image files into this module's images/ dir, carries over global
     * settings (BEMO_SLIDER_* → HBE_SLIDER_*) and appends 'slider' to the home
     * order. Idempotent: skips when the target slides table already has rows.
     *
     * @return array{success:bool,message:string,slides:int,images:int}
     */
    public function migrateFromBemoSlider(): array
    {
        $db = Db::getInstance();
        $p  = _DB_PREFIX_;

        $bemoExists = $db->executeS("SHOW TABLES LIKE '{$p}bemo_slider_slides'");
        if (!$bemoExists) {
            return ['success' => false, 'message' => 'bemo_slider tables not found — nothing to migrate.', 'slides' => 0, 'images' => 0];
        }

        $already = (int) $db->getValue("SELECT COUNT(*) FROM `{$p}hb_editor_slider_slides`");
        if ($already > 0) {
            return ['success' => false, 'message' => 'Slider already has slides — migration skipped.', 'slides' => 0, 'images' => 0];
        }

        // 1) Copy rows (preserve ids so slide/lang/position stay linked)
        $db->execute(
            "INSERT INTO `{$p}hb_editor_slider_slides`
                (id_hb_slide, position, active, active_mobile, text_position, show_text,
                 overlay_is_transparent, overlay_color, overlay_opacity,
                 cta_enabled, cta_text, cta_color, cta_bg, cta_size, cta_radius)
             SELECT id_bemo_slide, position, active, active_mobile, text_position, show_text,
                 overlay_is_transparent, overlay_color, overlay_opacity,
                 cta_enabled, cta_text, cta_color, cta_bg, cta_size, cta_radius
             FROM `{$p}bemo_slider_slides`"
        );
        $db->execute(
            "INSERT INTO `{$p}hb_editor_slider_slides_lang`
                (id_hb_slide, id_lang, title, description, url, image, image_mobile)
             SELECT id_bemo_slide, id_lang, title, description, url, image, image_mobile
             FROM `{$p}bemo_slider_slides_lang`"
        );
        $db->execute(
            "INSERT INTO `{$p}hb_editor_slider` (id_hb_slide, id_shop, position)
             SELECT id_bemo_slide, id_shop, position FROM `{$p}bemo_slider`"
        );

        $slidesCount = (int) $db->getValue("SELECT COUNT(*) FROM `{$p}hb_editor_slider_slides`");

        // 2) Copy image files (originals + webp variants), skip existing
        $srcDir = _PS_MODULE_DIR_ . 'bemo_slider/images/';
        $dstDir = _PS_MODULE_DIR_ . $this->name . '/images/';
        $imagesCopied = 0;
        if (is_dir($srcDir)) {
            if (!is_dir($dstDir)) {
                @mkdir($dstDir, 0755, true);
            }
            foreach ((array) scandir($srcDir) as $file) {
                if ($file === '.' || $file === '..' || $file === 'index.php') {
                    continue;
                }
                $srcFile = $srcDir . $file;
                $dstFile = $dstDir . $file;
                if (is_file($srcFile) && !is_file($dstFile)) {
                    if (@copy($srcFile, $dstFile)) {
                        $imagesCopied++;
                    }
                }
            }
        }

        // 3) Carry over global settings
        $map = [
            'BEMO_SLIDER_SPEED'          => 'HBE_SLIDER_SPEED',
            'BEMO_SLIDER_AUTOPLAY'       => 'HBE_SLIDER_AUTOPLAY',
            'BEMO_SLIDER_PAUSE_ON_HOVER' => 'HBE_SLIDER_PAUSE_ON_HOVER',
            'BEMO_SLIDER_SHOW_ARROWS'    => 'HBE_SLIDER_SHOW_ARROWS',
            'BEMO_SLIDER_SHOW_DOTS'      => 'HBE_SLIDER_SHOW_DOTS',
        ];
        foreach ($map as $from => $to) {
            $v = Configuration::get($from);
            if ($v !== false && $v !== null && $v !== '') {
                Configuration::updateValue($to, $v);
            }
        }

        // 4) Append 'slider' to the home order if missing
        $orderRaw   = (string) (Configuration::get('HBE_HOME_ORDER') ?: 'infobar,imghero,cols3,tagline');
        $orderParts = array_filter(array_map('trim', explode(',', $orderRaw)));
        if (!in_array('slider', $orderParts, true)) {
            array_unshift($orderParts, 'slider');
            Configuration::updateValue('HBE_HOME_ORDER', implode(',', $orderParts));
        }

        return [
            'success' => true,
            'message' => "Migrated {$slidesCount} slide(s) and {$imagesCopied} image file(s) from bemo_slider.",
            'slides'  => $slidesCount,
            'images'  => $imagesCopied,
        ];
    }

    /**
     * Normalize a slide URL (ported from bemo_slider::validateUrl()).
     */
    private function hbeSliderValidateUrl(string $link): string
    {
        if ($link === '' || strpos($link, '#') === 0) {
            return $link;
        }

        $host = parse_url($link, PHP_URL_HOST);
        if (empty($host)) {
            if (preg_match('/^(?!\-|index\.php)(?:(?:[a-z\d][a-z\d\-]{0,61})?[a-z\d]\.){1,126}(?!\d+)[a-z\d]{1,63}/i', $link)) {
                $link = '//' . $link;
            } else {
                $link = $this->context->link->getBaseLink() . ltrim($link, '/');
            }
        }

        return $link;
    }

    /**
     * Resolve a manufacturer's display data (name, logo URL, page link).
     * Logo URL is empty when the manufacturer has no uploaded logo file.
     *
     * @return array{name:string,logo_url:string,link:string}
     */
    private function hbeManufacturerData(int $idManufacturer, int $idLang): array
    {
        $out = ['name' => '', 'logo_url' => '', 'link' => ''];
        if ($idManufacturer <= 0) {
            return $out;
        }
        $manu = new Manufacturer($idManufacturer, $idLang);
        if (!Validate::isLoadedObject($manu)) {
            return $out;
        }
        $out['name'] = (string) $manu->name;
        $out['link'] = $this->context->link->getManufacturerLink($manu);
        if (is_file(_PS_MANU_IMG_DIR_ . $idManufacturer . '.jpg')) {
            $out['logo_url'] = $this->context->link->getManufacturerImageLink($idManufacturer, 'medium_default');
        }
        return $out;
    }

    private function renderIcons4(): string
    {
        if (!(int) Configuration::get('HBE_ICONS4_ENABLED')) {
            return '';
        }
        $cols = [];
        for ($i = 1; $i <= 4; $i++) {
            $img = (string) Configuration::get('HBE_ICONS4_IMG_' . $i);
            $imgSources = $this->resolveHbEditorImageSources($img);
            $imgMobile = (string) Configuration::get('HBE_ICONS4_IMG_' . $i . '_MOBILE');
            $imgMobileSources = $this->resolveHbEditorImageSources($imgMobile);
            $cols[] = [
                'img_url' => $imgSources['url'],
                'img_webp_url' => $imgSources['webp_url'],
                'img_mobile_url' => $imgMobileSources['url'],
                'img_mobile_webp_url' => $imgMobileSources['webp_url'],
                'title'   => $this->hbeLocConfig('HBE_ICONS4_TITLE_' . $i),
                'desc'    => $this->hbeLocConfig('HBE_ICONS4_DESC_' . $i),
            ];
        }
        $this->context->smarty->assign('hbe_icons4', $cols);
        return $this->display(__FILE__, 'views/templates/hook/icons4.tpl');
    }

    /**
     * Render a DB-backed section block (section_type + section_data JSON).
     * Parses the JSON, assigns Smarty vars and delegates to the same templates
     * used by the config-backed static sections.
     */
    private function renderSectionBlock(array $block): string
    {
        if (!(int) $block['active']) {
            return '';
        }
        $type = (string) ($block['section_type'] ?? '');
        if ($type === '') {
            return '';
        }
        $sd   = [];
        if (!empty($block['section_data'])) {
            $decoded = json_decode((string) $block['section_data'], true);
            if (is_array($decoded)) {
                $sd = $decoded;
            }
        }

        $idLang = (int) $this->context->language->id;

        // Helper: get per-lang value falling back to first available or empty string
        $lang = static function (string $key, int $lid) use ($sd): string {
            return (string) ($sd['langs'][$lid][$key]
                ?? $sd['langs'][array_key_first($sd['langs'] ?? [])][$key]
                ?? '');
        };

        $sanitizeUrl = static function (string $url): string {
            if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
                $url = 'https://' . $url;
            }
            return $url;
        };

        switch ($type) {
            case HbEditorBlock::STYPE_INFOBAR:
                $text = $lang('text', $idLang);
                if ($text === '') {
                    return '';
                }
                $this->context->smarty->assign([
                    'hbe_infobar_text'      => $text,
                    'hbe_infobar_url'       => $sanitizeUrl($lang('url', $idLang)),
                    'hbe_infobar_link_text' => $lang('link_text', $idLang),
                    'hbe_infobar_bg'        => $sd['bg']    ?? '#222222',
                    'hbe_infobar_color'     => $sd['color'] ?? '#ffffff',
                ]);
                return $this->display(__FILE__, 'views/templates/hook/infobar.tpl');

            case HbEditorBlock::STYPE_IMGHERO:
                $imgFile    = $sd['image']        ?? '';
                $mobileFile = $sd['image_mobile'] ?? '';
                if ($imgFile === '') {
                    return '';
                }
                $imgSrc    = $this->resolveHbEditorImageSources($imgFile);
                $mobSrc    = $this->resolveHbEditorImageSources($mobileFile);
                $ctaUrl    = $sanitizeUrl($lang('cta_url', $idLang));
                $this->context->smarty->assign([
                    'hbe_imghero_image_url'               => $imgSrc['url'],
                    'hbe_imghero_image_webp_url'           => $imgSrc['webp_url'],
                    'hbe_imghero_image_mobile_url'         => $mobSrc['url'],
                    'hbe_imghero_image_mobile_webp_url'    => $mobSrc['webp_url'],
                    'hbe_imghero_title'                    => $lang('title', $idLang),
                    'hbe_imghero_desc'                     => $lang('desc', $idLang),
                    'hbe_imghero_cta_text'                 => $lang('cta_text', $idLang),
                    'hbe_imghero_cta_url'                  => $ctaUrl,
                ]);
                return $this->display(__FILE__, 'views/templates/hook/imghero.tpl');

            case HbEditorBlock::STYPE_COLS3:
                $cols = [];
                for ($i = 1; $i <= 3; $i++) {
                    $cols[] = [
                        'text' => $lang('text_' . $i, $idLang),
                        'url'  => $sanitizeUrl($lang('url_' . $i, $idLang)),
                    ];
                }
                $this->context->smarty->assign('hbe_cols3', $cols);
                return $this->display(__FILE__, 'views/templates/hook/cols3.tpl');

            case HbEditorBlock::STYPE_COLS3DESC:
                $cols = [];
                for ($i = 1; $i <= 3; $i++) {
                    $cols[] = [
                        'title' => $lang('title_' . $i, $idLang),
                        'desc'  => $lang('desc_'  . $i, $idLang),
                        'url'   => $sanitizeUrl($lang('url_' . $i, $idLang)),
                    ];
                }
                $this->context->smarty->assign('hbe_cols3desc', $cols);
                return $this->display(__FILE__, 'views/templates/hook/cols3desc.tpl');

            case HbEditorBlock::STYPE_TAGLINE:
                $text = $lang('text', $idLang);
                if ($text === '') {
                    return '';
                }
                $this->context->smarty->assign([
                    'hbe_tagline_text'      => $text,
                    'hbe_tagline_link_text' => $lang('link_text', $idLang),
                    'hbe_tagline_link_url'  => $sanitizeUrl($lang('link_url', $idLang)),
                ]);
                return $this->display(__FILE__, 'views/templates/hook/tagline.tpl');

            case HbEditorBlock::STYPE_KATCOLS:
                $lSrc  = $this->resolveHbEditorImageSources($sd['l_image']        ?? '');
                $rSrc  = $this->resolveHbEditorImageSources($sd['r_image']        ?? '');
                $lmSrc = $this->resolveHbEditorImageSources($sd['l_image_mobile'] ?? '');
                $rmSrc = $this->resolveHbEditorImageSources($sd['r_image_mobile'] ?? '');
                $this->context->smarty->assign([
                    'hbe_katcols_title'              => $lang('title', $idLang),
                    'hbe_katcols_hdr_text'           => $lang('hdr_text', $idLang),
                    'hbe_katcols_hdr_link_text'      => $lang('hdr_link_text', $idLang),
                    'hbe_katcols_hdr_url'            => $sanitizeUrl($lang('hdr_url', $idLang)),
                    'hbe_katcols_l_caption'          => $lang('l_caption', $idLang),
                    'hbe_katcols_l_url'              => $sanitizeUrl($lang('l_url', $idLang)),
                    'hbe_katcols_l_img_url'          => $lSrc['url'],
                    'hbe_katcols_l_img_webp_url'     => $lSrc['webp_url'],
                    'hbe_katcols_l_img_mobile_url'   => $lmSrc['url'],
                    'hbe_katcols_l_img_mobile_webp_url' => $lmSrc['webp_url'],
                    'hbe_katcols_r_caption'          => $lang('r_caption', $idLang),
                    'hbe_katcols_r_url'              => $sanitizeUrl($lang('r_url', $idLang)),
                    'hbe_katcols_r_img_url'          => $rSrc['url'],
                    'hbe_katcols_r_img_webp_url'     => $rSrc['webp_url'],
                    'hbe_katcols_r_img_mobile_url'   => $rmSrc['url'],
                    'hbe_katcols_r_img_mobile_webp_url' => $rmSrc['webp_url'],
                ]);
                return $this->display(__FILE__, 'views/templates/hook/katcols.tpl');

            case HbEditorBlock::STYPE_SPLITBLOCK:
                $mSrc  = $this->resolveHbEditorImageSources($sd['m_image']        ?? '');
                $rSrc  = $this->resolveHbEditorImageSources($sd['r_image']        ?? '');
                $mmSrc = $this->resolveHbEditorImageSources($sd['m_image_mobile'] ?? '');
                $rmSrc = $this->resolveHbEditorImageSources($sd['r_image_mobile'] ?? '');
                $this->context->smarty->assign([
                    'hbe_splitblock_title'                => $lang('title', $idLang),
                    'hbe_splitblock_desc'                 => $lang('desc', $idLang),
                    'hbe_splitblock_cta_text'             => $lang('cta_text', $idLang),
                    'hbe_splitblock_cta_url'              => $sanitizeUrl($lang('cta_url', $idLang)),
                    'hbe_splitblock_m_img_url'            => $mSrc['url'],
                    'hbe_splitblock_m_img_webp_url'       => $mSrc['webp_url'],
                    'hbe_splitblock_m_img_mobile_url'     => $mmSrc['url'],
                    'hbe_splitblock_m_img_mobile_webp_url' => $mmSrc['webp_url'],
                    'hbe_splitblock_r_img_url'            => $rSrc['url'],
                    'hbe_splitblock_r_img_webp_url'       => $rSrc['webp_url'],
                    'hbe_splitblock_r_img_mobile_url'     => $rmSrc['url'],
                    'hbe_splitblock_r_img_mobile_webp_url' => $rmSrc['webp_url'],
                ]);
                return $this->display(__FILE__, 'views/templates/hook/splitblock.tpl');

            case HbEditorBlock::STYPE_ICONS4:
                $cols = [];
                $imgs = $sd['imgs'] ?? [];
                for ($i = 1; $i <= 4; $i++) {
                    $imgEntry = $imgs[$i - 1] ?? [];
                    $imgSrc   = $this->resolveHbEditorImageSources($imgEntry['d'] ?? '');
                    $mobSrc   = $this->resolveHbEditorImageSources($imgEntry['m'] ?? '');
                    $cols[] = [
                        'img_url'          => $imgSrc['url'],
                        'img_webp_url'     => $imgSrc['webp_url'],
                        'img_mobile_url'   => $mobSrc['url'],
                        'img_mobile_webp_url' => $mobSrc['webp_url'],
                        'title'            => $lang('title_' . $i, $idLang),
                        'desc'             => $lang('desc_'  . $i, $idLang),
                    ];
                }
                $this->context->smarty->assign('hbe_icons4', $cols);
                return $this->display(__FILE__, 'views/templates/hook/icons4.tpl');

            case HbEditorBlock::STYPE_BRANDS:
                $brandsData = [];
                $brandImgs  = $sd['imgs'] ?? [];
                $slot = 0;
                foreach ($brandImgs as $entry) {
                    $slot++;
                    $manuId    = (int) ($entry['manu'] ?? 0);
                    $customImg = (string) ($entry['img'] ?? '');
                    $link      = (string) ($entry['link'] ?? '');
                    $customAlt = (string) $lang('alt_' . $slot, $idLang);
                    $manu      = $manuId ? $this->hbeManufacturerData($manuId, $idLang) : null;

                    if ($customImg !== '') {
                        $src     = $this->resolveHbEditorImageSources($customImg);
                        $imgUrl  = $src['url'];
                        $webpUrl = $src['webp_url'];
                    } elseif ($manu && $manu['logo_url'] !== '') {
                        $imgUrl  = $manu['logo_url'];
                        $webpUrl = '';
                    } else {
                        continue;
                    }

                    if ($link === '' && $manu) {
                        $link = $manu['link'];
                    }
                    if ($link !== '' && !preg_match('#^https?://#i', $link) && strpos($link, '/') !== 0) {
                        $link = 'https://' . $link;
                    }

                    $brandsData[] = [
                        'img_url'      => $imgUrl,
                        'img_webp_url' => $webpUrl,
                        'link'         => $link,
                        'alt'          => $customAlt !== '' ? $customAlt : ($manu['name'] ?? ''),
                    ];
                }
                if (!$brandsData) {
                    return '';
                }
                $this->context->smarty->assign([
                    'hbe_brands_title' => $lang('title', $idLang),
                    'hbe_brands'       => $brandsData,
                ]);
                return $this->display(__FILE__, 'views/templates/hook/brands.tpl');
        }

        return '';
    }

    /**
     * Read a localized configuration value with fallback to the base (non-lang) row.
     */
    private function hbeLocConfig(string $key, ?int $idLang = null): string
    {
        if ($idLang === null) {
            $idLang = (int) $this->context->language->id;
        }

        return (string) (HbEditorConfig::get($key, $idLang) ?? '');
    }

    /**
     * Return original and WebP URLs for image files from img/hb_editor.
     * Uses WebP only if native file exists next to original.
     *
     * @return array{url:string,webp_url:string}
     */
    private function resolveHbEditorImageSources(string $filename): array
    {
        $filename = trim($filename);
        if ($filename === '') {
            return ['url' => '', 'webp_url' => ''];
        }

        $url = $this->context->link->getMediaLink(_PS_IMG_ . self::IMG_DIR . $filename);
        $webpUrl = '';

        if (preg_match('/\.webp$/i', $filename)) {
            $webpUrl = $url;
        } else {
            $candidates = [
                $filename . '.webp',
                preg_replace('/\.[^.]+$/', '.webp', $filename),
            ];
            foreach (array_unique($candidates) as $candidate) {
                if (!is_string($candidate) || $candidate === '') {
                    continue;
                }
                $path = _PS_IMG_DIR_ . self::IMG_DIR . $candidate;
                if (is_file($path)) {
                    $webpUrl = $this->context->link->getMediaLink(_PS_IMG_ . self::IMG_DIR . $candidate);
                    break;
                }
            }
        }

        return ['url' => $url, 'webp_url' => $webpUrl];
    }

    public function renderHookBlocks(string $hookName, array $params = []): string
    {
        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;
        $blocks = HbEditorBlock::getByHook($hookName, $idShop, $idLang);
        if (!$blocks) {
            return '';
        }

        $output = '';
        foreach ($blocks as $block) {
            if (!empty($block['section_type'])) {
                $output .= $this->renderSectionBlock($block);
                continue;
            }
            $desktopSources = $this->resolveHbEditorImageSources((string) $block['image_desktop']);
            $mobileSources = $this->resolveHbEditorImageSources((string) $block['image_mobile']);
            $block['image_desktop_url'] = $desktopSources['url'];
            $block['image_desktop_webp_url'] = $desktopSources['webp_url'];
            $block['image_mobile_url'] = $mobileSources['url'];
            $block['image_mobile_webp_url'] = $mobileSources['webp_url'];
            $this->context->smarty->assign('hbe_block', $block);
            $output .= $this->display(__FILE__, 'views/templates/hook/block.tpl');
        }
        return $output;
    }

    /* ── Admin redirect ──────────────────────────────────────────────────── */

    public function getContent(): void
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminHbEditor')
        );
    }

    /* ── Image upload helper (used by admin controller) ─────────────────── */

    /**
     * Handle image file upload for a block.
     * Returns filename on success, throws RuntimeException on failure.
     */
    public function uploadImage(int $idBlock, string $side, array $file): string
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $allowedExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload error: ' . $file['error']);
        }
        if ($file['size'] > 8 * 1024 * 1024) {
            throw new RuntimeException('File too large (max 8 MB)');
        }

        // Validate MIME by reading actual bytes (not trusting browser-supplied type)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMimes, true)) {
            throw new RuntimeException('Invalid file type: ' . $mime);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            throw new RuntimeException('Invalid file extension');
        }

        $newName = 'block_' . $idBlock . '_' . $side . '_' . time() . '.' . $ext;
        $destDir = _PS_IMG_DIR_ . self::IMG_DIR;
        $dest    = $destDir . $newName;

        // Remove old file
        $block = HbEditorBlock::getById($idBlock);
        if ($block) {
            $oldFile = $destDir . ($side === 'mobile' ? $block['image_mobile'] : $block['image_desktop']);
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Could not move uploaded file');
        }
        @chmod($dest, 0644);

        return $newName;
    }
}
