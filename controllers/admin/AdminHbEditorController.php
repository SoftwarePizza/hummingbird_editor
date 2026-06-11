<?php
declare(strict_types=1);
/**
 * AdminHbEditorController – admin panel for Hummingbird Editor
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'hummingbird_editor/classes/HbEditorConfig.php';
require_once _PS_MODULE_DIR_ . 'hummingbird_editor/classes/HbEditorBlock.php';
require_once _PS_MODULE_DIR_ . 'hummingbird_editor/classes/HbEditorTransfer.php';
require_once _PS_MODULE_DIR_ . 'hummingbird_editor/classes/HbEditorSlide.php';

class AdminHbEditorController extends ModuleAdminController
{
    /* Standard PS hooks offered as suggestions in the UI */
    const STANDARD_HOOKS = [
        'displayHome', 'displayBanner', 'displayTop', 'displayNav',
        'displayLeftColumn', 'displayRightColumn', 'displayFooter',
        'displayFooterBefore', 'displayAfterBodyOpeningTag', 'displayHeader',
        'displayProductButtons', 'displayProductAdditionalInfo',
        'displayShoppingCart', 'displayOrderConfirmation', 'displayContentWrapperTop',
        'displayContentWrapperBottom', 'displayWrapperTop', 'displayWrapperBottom',
        'displayNotFound', 'displayMaintenance',
    ];

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = 'Hummingbird Editor';
    }

    /* ── Assets: CSS + JS — loaded via setMedia() so jQuery is available first ── */

    public function setMedia($isNewTheme = false): void
    {
        parent::setMedia($isNewTheme);
        // jQuery UI Sortable MUST be loaded before admin.js (used for block drag-to-reorder)
        $this->addJqueryPlugin('sortable');
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css?v=' . filemtime(_PS_MODULE_DIR_ . 'hummingbird_editor/views/css/admin.css'));
        $this->addJS($this->module->getPathUri() . 'views/js/admin.js?v=' . filemtime(_PS_MODULE_DIR_ . 'hummingbird_editor/views/js/admin.js'));
    }

    /* ── Main page ───────────────────────────────────────────────────────── */

    public function initContent(): void
    {
        // Direct-download endpoint for the XML export (plain GET, no AJAX wrapping).
        if (Tools::getValue('action') === 'ExportSettings') {
            $xml = HbEditorTransfer::exportXml();
            $filename = 'hbe-settings-' . date('Y-m-d_His') . '.xml';
            if (ob_get_level()) { @ob_end_clean(); }
            header('Content-Type: application/xml; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($xml));
            header('Cache-Control: no-store, no-cache, must-revalidate');
            echo $xml;
            exit;
        }

        parent::initContent();

        $languages = Language::getLanguages(true);
        $blocks    = HbEditorBlock::getAllForAdmin();

        // Enrich each block: add lang data + shop IDs
        foreach ($blocks as &$b) {
            $b['lang_data'] = HbEditorBlock::getLangData((int) $b['id_block']);
            $b['shop_ids']  = HbEditorBlock::getShopIds((int) $b['id_block']);
            $b['image_desktop_url'] = $b['image_desktop']
                ? __PS_BASE_URI__ . 'img/hb_editor/' . $b['image_desktop']
                : '';
            $b['image_mobile_url'] = $b['image_mobile']
                ? __PS_BASE_URI__ . 'img/hb_editor/' . $b['image_mobile']
                : '';
        }
        unset($b);

        // Group by hook
        $grouped = [];
        foreach ($blocks as $b) {
            $grouped[$b['hook_name']][] = $b;
        }

        // Build combined displayHome ordered list (static built-in items + custom blocks + managed modules)
        $staticSlugLabels = [
            'infobar'  => $this->module->l('Info bar'),
            'infobar2' => $this->module->l('Info bar 2 (druga kopia)'),
            'imghero'  => $this->module->l('Baner z obrazkiem'),
            'imghero2' => $this->module->l('Baner z obrazkiem 2'),
            'cols3'      => $this->module->l('Blok 3 kolumn'),
            'cols3desc'  => $this->module->l('Blok 3 kolumn z opisami'),
            'tagline'    => $this->module->l('Blok tagline'),
            'katcols'    => $this->module->l('Sekcja Kategorie'),
            'splitblock' => $this->module->l('Sekcja 3 kolumn (tekst+obraz+obraz)'),
            'icons4'     => $this->module->l('Blok 4 kolumn z ikonami'),
            'brands'     => $this->module->l('Pasek marek (logotypy)'),
            'slider'     => $this->module->l('Slider (banery)'),
        ];
        $homeOrderRaw = (string)(Configuration::get('HBE_HOME_ORDER') ?: 'infobar,imghero,cols3,tagline');
        $homeOrderParts = array_filter(array_map('trim', explode(',', $homeOrderRaw)));
        $homeBlocks = $grouped['displayHome'] ?? [];
        $homeBlockMap = [];
        foreach ($homeBlocks as $hb) {
            $homeBlockMap[(int)$hb['id_block']] = $hb;
        }

        // Managed modules (detached from PS hook, rendered by HBE in order)
        $managedModuleNames = array_values(array_filter(array_map(
            'trim', explode(',', (string)(Configuration::get('HBE_MANAGED_MODULES') ?: ''))
        )));
        $managedModuleMap = [];
        foreach ($managedModuleNames as $mn) {
            $mod = Module::getInstanceByName($mn);
            if ($mod) {
                $managedModuleMap[$mn] = [
                    'name'         => $mn,
                    'display_name' => $mod->displayName ?: $mn,
                    'active'       => (bool) $mod->active,
                ];
            }
        }

        // Build ordered combined list
        $homeOrderedItems = [];
        $seenStatic  = [];
        $seenBlocks  = [];
        $seenModules = [];
        foreach ($homeOrderParts as $entry) {
            if (isset($staticSlugLabels[$entry]) && !in_array($entry, $seenStatic, true)) {
                $homeOrderedItems[] = ['kind' => 'static', 'id' => $entry, 'label' => $staticSlugLabels[$entry]];
                $seenStatic[] = $entry;
            } elseif (is_numeric($entry) && isset($homeBlockMap[(int)$entry])) {
                $homeOrderedItems[] = ['kind' => 'block', 'id' => (string)(int)$entry, 'block' => $homeBlockMap[(int)$entry]];
                $seenBlocks[] = (int)$entry;
            } elseif (strncmp($entry, 'module_', 7) === 0) {
                $mn = substr($entry, 7);
                if (isset($managedModuleMap[$mn]) && !in_array($mn, $seenModules, true)) {
                    $homeOrderedItems[] = ['kind' => 'module', 'id' => $entry, 'module' => $managedModuleMap[$mn]];
                    $seenModules[] = $mn;
                }
            }
        }
        // Append any static items not yet in the order
        foreach ($staticSlugLabels as $slug => $label) {
            if (!in_array($slug, $seenStatic, true)) {
                $homeOrderedItems[] = ['kind' => 'static', 'id' => $slug, 'label' => $label];
            }
        }
        // Append any custom blocks not yet in the order
        foreach ($homeBlockMap as $id => $hb) {
            if (!in_array($id, $seenBlocks, true)) {
                $homeOrderedItems[] = ['kind' => 'block', 'id' => (string)$id, 'block' => $hb];
            }
        }
        // Append any managed modules not yet in the order
        foreach ($managedModuleMap as $mn => $modData) {
            if (!in_array($mn, $seenModules, true)) {
                $homeOrderedItems[] = ['kind' => 'module', 'id' => 'module_' . $mn, 'module' => $modData];
            }
        }
        unset($grouped['displayHome']); // rendered separately in combined section

        // Modules currently on displayHome hook NOT managed by HBE (available to add)
        $shopId = (int) $this->context->shop->id;
        $dbPrefix = _DB_PREFIX_;
        $availableModules = Db::getInstance()->executeS(
            'SELECT m.name, m.active, hm.position' .
            ' FROM `' . $dbPrefix . 'module` m' .
            ' JOIN `' . $dbPrefix . 'hook_module` hm ON hm.id_module = m.id_module' .
            ' JOIN `' . $dbPrefix . 'hook` h ON h.id_hook = hm.id_hook' .
            ' WHERE h.name = "displayHome"' .
            ' AND hm.id_shop = ' . $shopId .
            ' AND m.name != "hummingbird_editor"' .
            ' ORDER BY hm.position'
        );
        if (!$availableModules) {
            $availableModules = [];
        }
        // Enrich with display names and remove already-managed ones
        $availableModulesClean = [];
        foreach ($availableModules as $am) {
            if (!in_array($am['name'], $managedModuleNames, true)) {
                $mod = Module::getInstanceByName($am['name']);
                $availableModulesClean[] = [
                    'name'         => $am['name'],
                    'display_name' => $mod ? ($mod->displayName ?: $am['name']) : $am['name'],
                    'active'       => (bool)(int)$am['active'],
                ];
            }
        }

        $allShops = Shop::getShops(true, null, true);

        // Manufacturer list for the brands pickers (id, name, default logo URL)
        $idLangCtx = (int) $this->context->language->id;
        $manuRows  = Manufacturer::getManufacturers(false, $idLangCtx);
        $hbeManufacturers = [];
        $manuMap = [];
        if (is_array($manuRows)) {
            foreach ($manuRows as $mr) {
                $mid  = (int) $mr['id_manufacturer'];
                $logo = is_file(_PS_MANU_IMG_DIR_ . $mid . '.jpg')
                    ? $this->context->link->getManufacturerImageLink($mid, 'medium_default')
                    : '';
                $entry = ['id' => $mid, 'name' => (string) $mr['name'], 'logo_url' => $logo];
                $hbeManufacturers[] = $entry;
                $manuMap[$mid] = $entry;
            }
        }

        $this->context->smarty->assign([
            'hbe_grouped'           => $grouped,
            'hbe_home_ordered'      => $homeOrderedItems,
            'hbe_available_modules' => $availableModulesClean,
            'hbe_languages'      => $languages,
            'hbe_all_shops'      => $allShops,
            'hbe_standard_hooks' => self::STANDARD_HOOKS,
            'hbe_used_hooks'     => HbEditorBlock::getUsedHookNames(),
            'hbe_ajax_url'       => $this->context->link->getAdminLink($this->controller_name),
            'hbe_token'          => Tools::getAdminTokenLite($this->controller_name),
            'hbe_img_url'        => __PS_BASE_URI__ . 'img/hb_editor/',
            'hbe_module_uri'     => __PS_BASE_URI__ . 'modules/hummingbird_editor/',
            'hbe_tpl_dir'        => _PS_MODULE_DIR_ . 'hummingbird_editor/views/templates/admin/',
            'hbe_lang_id'        => (int) $this->context->language->id,
            'hbe_topbar_enabled' => (int) Configuration::get('HBE_TOPBAR_ENABLED'),
            'hbe_topbar_text'    => (string) Configuration::get('HBE_TOPBAR_TEXT'),
            'hbe_topbar_url'     => (string) Configuration::get('HBE_TOPBAR_URL'),
            'hbe_topbar_text_lang' => $this->getConfigPerLang('HBE_TOPBAR_TEXT', $languages),
            'hbe_topbar_url_lang'  => $this->getConfigPerLang('HBE_TOPBAR_URL',  $languages),
            'hbe_topbar_link_text_lang' => $this->getConfigPerLang('HBE_TOPBAR_LINK_TEXT', $languages),
            'hbe_infobar_enabled' => (int) Configuration::get('HBE_INFOBAR_ENABLED'),
            'hbe_infobar_text'    => (string) Configuration::get('HBE_INFOBAR_TEXT'),
            'hbe_infobar_url'     => (string) Configuration::get('HBE_INFOBAR_URL'),
            'hbe_infobar_bg'      => (string) (Configuration::get('HBE_INFOBAR_BG') ?: '#222222'),
            'hbe_infobar_color'   => (string) (Configuration::get('HBE_INFOBAR_COLOR') ?: '#ffffff'),
            'hbe_infobar_text_lang' => $this->getConfigPerLang('HBE_INFOBAR_TEXT', $languages),
            'hbe_infobar_url_lang'  => $this->getConfigPerLang('HBE_INFOBAR_URL',  $languages),
            'hbe_infobar_link_text_lang' => $this->getConfigPerLang('HBE_INFOBAR_LINK_TEXT', $languages),
            'hbe_infobar2_enabled' => (int) Configuration::get('HBE_INFOBAR2_ENABLED'),
            'hbe_infobar2_text'    => (string) Configuration::get('HBE_INFOBAR2_TEXT'),
            'hbe_infobar2_url'     => (string) Configuration::get('HBE_INFOBAR2_URL'),
            'hbe_infobar2_bg'      => (string) (Configuration::get('HBE_INFOBAR2_BG') ?: '#222222'),
            'hbe_infobar2_color'   => (string) (Configuration::get('HBE_INFOBAR2_COLOR') ?: '#ffffff'),
            'hbe_infobar2_text_lang' => $this->getConfigPerLang('HBE_INFOBAR2_TEXT', $languages),
            'hbe_infobar2_url_lang'  => $this->getConfigPerLang('HBE_INFOBAR2_URL',  $languages),
            'hbe_infobar2_link_text_lang' => $this->getConfigPerLang('HBE_INFOBAR2_LINK_TEXT', $languages),
            // Carousel headers
            'hbe_np_title'     => (string) Configuration::get('HBE_NP_TITLE'),
            'hbe_np_text'      => (string) Configuration::get('HBE_NP_TEXT'),
            'hbe_np_link_text' => (string) Configuration::get('HBE_NP_LINK_TEXT'),
            'hbe_np_link_url'  => (string) Configuration::get('HBE_NP_LINK_URL'),
            'hbe_bs_title'     => (string) Configuration::get('HBE_BS_TITLE'),
            'hbe_bs_text'      => (string) Configuration::get('HBE_BS_TEXT'),
            'hbe_bs_link_text' => (string) Configuration::get('HBE_BS_LINK_TEXT'),
            'hbe_bs_link_url'  => (string) Configuration::get('HBE_BS_LINK_URL'),
            'hbe_cp_title'     => (string) Configuration::get('HBE_CP_TITLE'),
            'hbe_cp_text'      => (string) Configuration::get('HBE_CP_TEXT'),
            'hbe_cp_link_text' => (string) Configuration::get('HBE_CP_LINK_TEXT'),
            'hbe_cp_link_url'  => (string) Configuration::get('HBE_CP_LINK_URL'),
            'hbe_np_title_lang'     => $this->getConfigPerLang('HBE_NP_TITLE',     $languages),
            'hbe_np_text_lang'      => $this->getConfigPerLang('HBE_NP_TEXT',      $languages),
            'hbe_np_link_text_lang' => $this->getConfigPerLang('HBE_NP_LINK_TEXT', $languages),
            'hbe_np_link_url_lang'  => $this->getConfigPerLang('HBE_NP_LINK_URL',  $languages),
            'hbe_bs_title_lang'     => $this->getConfigPerLang('HBE_BS_TITLE',     $languages),
            'hbe_bs_text_lang'      => $this->getConfigPerLang('HBE_BS_TEXT',      $languages),
            'hbe_bs_link_text_lang' => $this->getConfigPerLang('HBE_BS_LINK_TEXT', $languages),
            'hbe_bs_link_url_lang'  => $this->getConfigPerLang('HBE_BS_LINK_URL',  $languages),
            'hbe_cp_title_lang'     => $this->getConfigPerLang('HBE_CP_TITLE',     $languages),
            'hbe_cp_text_lang'      => $this->getConfigPerLang('HBE_CP_TEXT',      $languages),
            'hbe_cp_link_text_lang' => $this->getConfigPerLang('HBE_CP_LINK_TEXT', $languages),
            'hbe_cp_link_url_lang'  => $this->getConfigPerLang('HBE_CP_LINK_URL',  $languages),
            'hbe_hide_currency_desktop' => (int) Configuration::get('HBE_HIDE_CURRENCY_DESKTOP'),
            'hbe_hide_currency_mobile'  => (int) Configuration::get('HBE_HIDE_CURRENCY_MOBILE'),
            'hbe_hide_language_desktop' => (int) Configuration::get('HBE_HIDE_LANGUAGE_DESKTOP'),
            'hbe_hide_language_mobile'  => (int) Configuration::get('HBE_HIDE_LANGUAGE_MOBILE'),
            'hbe_hide_quickview'        => (int) Configuration::get('HBE_HIDE_QUICKVIEW'),
            // Cart preview (ps_shoppingcart feature toggles)
            'hbe_cart_hover'             => (int) Configuration::get('PS_BLOCK_CART_HOVER'),
            'hbe_cart_preview_modal'     => (int) Configuration::get('PS_BLOCK_CART_PREVIEW_MODAL'),
            'hbe_cart_free_ship_manual'  => (float) Configuration::get('HBE_CART_FREE_SHIPPING_THRESHOLD'),
            // Image hero banner
            'hbe_imghero_enabled'  => (int) Configuration::get('HBE_IMGHERO_ENABLED'),
            'hbe_imghero_image'    => (string) Configuration::get('HBE_IMGHERO_IMAGE'),
            'hbe_imghero_img_url'  => Configuration::get('HBE_IMGHERO_IMAGE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_IMGHERO_IMAGE')
                : '',
            'hbe_imghero_img_mobile_url' => Configuration::get('HBE_IMGHERO_IMAGE_MOBILE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_IMGHERO_IMAGE_MOBILE')
                : '',
            'hbe_imghero_ml_images' => (int) Configuration::get('HBE_IMGHERO_IMAGE_ML'),
            'hbe_imghero_title'    => (string) Configuration::get('HBE_IMGHERO_TITLE'),
            'hbe_imghero_desc'     => (string) Configuration::get('HBE_IMGHERO_DESC'),
            'hbe_imghero_cta_text' => (string) Configuration::get('HBE_IMGHERO_CTA_TEXT'),
            'hbe_imghero_cta_url'  => (string) Configuration::get('HBE_IMGHERO_CTA_URL'),
            'hbe_imghero_title_lang'    => $this->getConfigPerLang('HBE_IMGHERO_TITLE',    $languages),
            'hbe_imghero_desc_lang'     => $this->getConfigPerLang('HBE_IMGHERO_DESC',     $languages),
            'hbe_imghero_cta_text_lang' => $this->getConfigPerLang('HBE_IMGHERO_CTA_TEXT', $languages),
            'hbe_imghero_cta_url_lang'  => $this->getConfigPerLang('HBE_IMGHERO_CTA_URL',  $languages),
            // Baner 2
            'hbe_imghero2_enabled'  => (int) Configuration::get('HBE_IMGHERO2_ENABLED'),
            'hbe_imghero2_img_url'  => Configuration::get('HBE_IMGHERO2_IMAGE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_IMGHERO2_IMAGE')
                : '',
            'hbe_imghero2_img_mobile_url' => Configuration::get('HBE_IMGHERO2_IMAGE_MOBILE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_IMGHERO2_IMAGE_MOBILE')
                : '',
            'hbe_imghero2_ml_images' => (int) Configuration::get('HBE_IMGHERO2_IMAGE_ML'),
            'hbe_imghero2_title'    => (string) Configuration::get('HBE_IMGHERO2_TITLE'),
            'hbe_imghero2_desc'     => (string) Configuration::get('HBE_IMGHERO2_DESC'),
            'hbe_imghero2_cta_text' => (string) Configuration::get('HBE_IMGHERO2_CTA_TEXT'),
            'hbe_imghero2_cta_url'  => (string) Configuration::get('HBE_IMGHERO2_CTA_URL'),
            'hbe_imghero2_title_lang'    => $this->getConfigPerLang('HBE_IMGHERO2_TITLE',    $languages),
            'hbe_imghero2_desc_lang'     => $this->getConfigPerLang('HBE_IMGHERO2_DESC',     $languages),
            'hbe_imghero2_cta_text_lang' => $this->getConfigPerLang('HBE_IMGHERO2_CTA_TEXT', $languages),
            'hbe_imghero2_cta_url_lang'  => $this->getConfigPerLang('HBE_IMGHERO2_CTA_URL',  $languages),
            // 3-column text links block
            'hbe_cols3_enabled' => (int) Configuration::get('HBE_COLS3_ENABLED'),
            'hbe_cols3_text_1'  => (string) Configuration::get('HBE_COLS3_TEXT_1'),
            'hbe_cols3_url_1'   => (string) Configuration::get('HBE_COLS3_URL_1'),
            'hbe_cols3_text_2'  => (string) Configuration::get('HBE_COLS3_TEXT_2'),
            'hbe_cols3_url_2'   => (string) Configuration::get('HBE_COLS3_URL_2'),
            'hbe_cols3_text_3'  => (string) Configuration::get('HBE_COLS3_TEXT_3'),
            'hbe_cols3_url_3'   => (string) Configuration::get('HBE_COLS3_URL_3'),
            'hbe_cols3_text_1_lang' => $this->getConfigPerLang('HBE_COLS3_TEXT_1', $languages),
            'hbe_cols3_url_1_lang'  => $this->getConfigPerLang('HBE_COLS3_URL_1',  $languages),
            'hbe_cols3_text_2_lang' => $this->getConfigPerLang('HBE_COLS3_TEXT_2', $languages),
            'hbe_cols3_url_2_lang'  => $this->getConfigPerLang('HBE_COLS3_URL_2',  $languages),
            'hbe_cols3_text_3_lang' => $this->getConfigPerLang('HBE_COLS3_TEXT_3', $languages),
            'hbe_cols3_url_3_lang'  => $this->getConfigPerLang('HBE_COLS3_URL_3',  $languages),
            // 3-column text+desc+link block
            'hbe_cols3d_enabled'  => (int) Configuration::get('HBE_COLS3D_ENABLED'),
            'hbe_cols3d_title_1'  => (string) Configuration::get('HBE_COLS3D_TITLE_1'),
            'hbe_cols3d_desc_1'   => (string) Configuration::get('HBE_COLS3D_DESC_1'),
            'hbe_cols3d_url_1'    => (string) Configuration::get('HBE_COLS3D_URL_1'),
            'hbe_cols3d_title_2'  => (string) Configuration::get('HBE_COLS3D_TITLE_2'),
            'hbe_cols3d_desc_2'   => (string) Configuration::get('HBE_COLS3D_DESC_2'),
            'hbe_cols3d_url_2'    => (string) Configuration::get('HBE_COLS3D_URL_2'),
            'hbe_cols3d_title_3'  => (string) Configuration::get('HBE_COLS3D_TITLE_3'),
            'hbe_cols3d_desc_3'   => (string) Configuration::get('HBE_COLS3D_DESC_3'),
            'hbe_cols3d_url_3'    => (string) Configuration::get('HBE_COLS3D_URL_3'),
            'hbe_cols3d_title_1_lang' => $this->getConfigPerLang('HBE_COLS3D_TITLE_1', $languages),
            'hbe_cols3d_desc_1_lang'  => $this->getConfigPerLang('HBE_COLS3D_DESC_1',  $languages),
            'hbe_cols3d_url_1_lang'   => $this->getConfigPerLang('HBE_COLS3D_URL_1',   $languages),
            'hbe_cols3d_title_2_lang' => $this->getConfigPerLang('HBE_COLS3D_TITLE_2', $languages),
            'hbe_cols3d_desc_2_lang'  => $this->getConfigPerLang('HBE_COLS3D_DESC_2',  $languages),
            'hbe_cols3d_url_2_lang'   => $this->getConfigPerLang('HBE_COLS3D_URL_2',   $languages),
            'hbe_cols3d_title_3_lang' => $this->getConfigPerLang('HBE_COLS3D_TITLE_3', $languages),
            'hbe_cols3d_desc_3_lang'  => $this->getConfigPerLang('HBE_COLS3D_DESC_3',  $languages),
            'hbe_cols3d_url_3_lang'   => $this->getConfigPerLang('HBE_COLS3D_URL_3',   $languages),
            // Tagline text block
            'hbe_tagline_enabled'   => (int) Configuration::get('HBE_TAGLINE_ENABLED'),
            'hbe_tagline_text'      => (string) Configuration::get('HBE_TAGLINE_TEXT'),
            'hbe_tagline_link_text' => (string) Configuration::get('HBE_TAGLINE_LINK_TEXT'),
            'hbe_tagline_link_url'  => (string) Configuration::get('HBE_TAGLINE_LINK_URL'),
            'hbe_tagline_text_lang'      => $this->getConfigPerLang('HBE_TAGLINE_TEXT', $languages),
            'hbe_tagline_link_text_lang' => $this->getConfigPerLang('HBE_TAGLINE_LINK_TEXT', $languages),
            'hbe_tagline_link_url_lang'  => $this->getConfigPerLang('HBE_TAGLINE_LINK_URL', $languages),
            // Kategorie two-column section
            'hbe_katcols_enabled'        => (int) Configuration::get('HBE_KATCOLS_ENABLED'),
            'hbe_katcols_title'          => (string) Configuration::get('HBE_KATCOLS_TITLE'),
            'hbe_katcols_hdr_text'       => (string) Configuration::get('HBE_KATCOLS_HDR_TEXT'),
            'hbe_katcols_hdr_link_text'  => (string) Configuration::get('HBE_KATCOLS_HDR_LINK_TEXT'),
            'hbe_katcols_hdr_url'        => (string) Configuration::get('HBE_KATCOLS_HDR_URL'),
            'hbe_katcols_l_image'        => (string) Configuration::get('HBE_KATCOLS_L_IMAGE'),
            'hbe_katcols_l_img_url'      => Configuration::get('HBE_KATCOLS_L_IMAGE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_KATCOLS_L_IMAGE') : '',
            'hbe_katcols_l_img_mobile_url' => Configuration::get('HBE_KATCOLS_L_IMAGE_MOBILE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_KATCOLS_L_IMAGE_MOBILE') : '',
            'hbe_katcols_l_caption'      => (string) Configuration::get('HBE_KATCOLS_L_CAPTION'),
            'hbe_katcols_l_url'          => (string) Configuration::get('HBE_KATCOLS_L_URL'),
            'hbe_katcols_r_image'        => (string) Configuration::get('HBE_KATCOLS_R_IMAGE'),
            'hbe_katcols_r_img_url'      => Configuration::get('HBE_KATCOLS_R_IMAGE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_KATCOLS_R_IMAGE') : '',
            'hbe_katcols_r_img_mobile_url' => Configuration::get('HBE_KATCOLS_R_IMAGE_MOBILE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_KATCOLS_R_IMAGE_MOBILE') : '',
            'hbe_katcols_ml_images'      => (int) Configuration::get('HBE_KATCOLS_IMAGE_ML'),
            'hbe_katcols_r_caption'      => (string) Configuration::get('HBE_KATCOLS_R_CAPTION'),
            'hbe_katcols_r_url'          => (string) Configuration::get('HBE_KATCOLS_R_URL'),
            'hbe_katcols_title_lang'         => $this->getConfigPerLang('HBE_KATCOLS_TITLE', $languages),
            'hbe_katcols_hdr_text_lang'      => $this->getConfigPerLang('HBE_KATCOLS_HDR_TEXT', $languages),
            'hbe_katcols_hdr_link_text_lang' => $this->getConfigPerLang('HBE_KATCOLS_HDR_LINK_TEXT', $languages),
            'hbe_katcols_hdr_url_lang'       => $this->getConfigPerLang('HBE_KATCOLS_HDR_URL', $languages),
            'hbe_katcols_l_caption_lang'     => $this->getConfigPerLang('HBE_KATCOLS_L_CAPTION', $languages),
            'hbe_katcols_l_url_lang'         => $this->getConfigPerLang('HBE_KATCOLS_L_URL', $languages),
            'hbe_katcols_r_caption_lang'     => $this->getConfigPerLang('HBE_KATCOLS_R_CAPTION', $languages),
            'hbe_katcols_r_url_lang'         => $this->getConfigPerLang('HBE_KATCOLS_R_URL', $languages),
            // Split-block (3 columns)
            'hbe_splitblock_enabled'   => (int) Configuration::get('HBE_SPLITBLOCK_ENABLED'),
            'hbe_splitblock_title'     => (string) Configuration::get('HBE_SPLITBLOCK_TITLE'),
            'hbe_splitblock_desc'      => (string) Configuration::get('HBE_SPLITBLOCK_DESC'),
            'hbe_splitblock_cta_text'  => (string) Configuration::get('HBE_SPLITBLOCK_CTA_TEXT'),
            'hbe_splitblock_cta_url'   => (string) Configuration::get('HBE_SPLITBLOCK_CTA_URL'),
            'hbe_splitblock_title_lang'    => $this->getConfigPerLang('HBE_SPLITBLOCK_TITLE',    $languages),
            'hbe_splitblock_desc_lang'     => $this->getConfigPerLang('HBE_SPLITBLOCK_DESC',     $languages),
            'hbe_splitblock_cta_text_lang' => $this->getConfigPerLang('HBE_SPLITBLOCK_CTA_TEXT', $languages),
            'hbe_splitblock_cta_url_lang'  => $this->getConfigPerLang('HBE_SPLITBLOCK_CTA_URL',  $languages),
            'hbe_splitblock_m_img_url' => Configuration::get('HBE_SPLITBLOCK_M_IMAGE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_SPLITBLOCK_M_IMAGE') : '',
            'hbe_splitblock_m_img_mobile_url' => Configuration::get('HBE_SPLITBLOCK_M_IMAGE_MOBILE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_SPLITBLOCK_M_IMAGE_MOBILE') : '',
            'hbe_splitblock_r_img_url' => Configuration::get('HBE_SPLITBLOCK_R_IMAGE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_SPLITBLOCK_R_IMAGE') : '',
            'hbe_splitblock_r_img_mobile_url' => Configuration::get('HBE_SPLITBLOCK_R_IMAGE_MOBILE')
                ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_SPLITBLOCK_R_IMAGE_MOBILE') : '',
            'hbe_splitblock_ml_images' => (int) Configuration::get('HBE_SPLITBLOCK_IMAGE_ML'),
            // Icons 4 columns
            'hbe_icons4_enabled' => (int) Configuration::get('HBE_ICONS4_ENABLED'),
            'hbe_icons4_ml_images' => (int) Configuration::get('HBE_ICONS4_IMAGE_ML'),
            'hbe_icons4_img_url_1' => Configuration::get('HBE_ICONS4_IMG_1') ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_ICONS4_IMG_1') : '',
            'hbe_icons4_img_mobile_url_1' => Configuration::get('HBE_ICONS4_IMG_1_MOBILE') ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_ICONS4_IMG_1_MOBILE') : '',
            'hbe_icons4_title_1'   => (string) Configuration::get('HBE_ICONS4_TITLE_1'),
            'hbe_icons4_desc_1'    => (string) Configuration::get('HBE_ICONS4_DESC_1'),
            'hbe_icons4_img_url_2' => Configuration::get('HBE_ICONS4_IMG_2') ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_ICONS4_IMG_2') : '',
            'hbe_icons4_img_mobile_url_2' => Configuration::get('HBE_ICONS4_IMG_2_MOBILE') ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_ICONS4_IMG_2_MOBILE') : '',
            'hbe_icons4_title_2'   => (string) Configuration::get('HBE_ICONS4_TITLE_2'),
            'hbe_icons4_desc_2'    => (string) Configuration::get('HBE_ICONS4_DESC_2'),
            'hbe_icons4_img_url_3' => Configuration::get('HBE_ICONS4_IMG_3') ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_ICONS4_IMG_3') : '',
            'hbe_icons4_img_mobile_url_3' => Configuration::get('HBE_ICONS4_IMG_3_MOBILE') ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_ICONS4_IMG_3_MOBILE') : '',
            'hbe_icons4_title_3'   => (string) Configuration::get('HBE_ICONS4_TITLE_3'),
            'hbe_icons4_desc_3'    => (string) Configuration::get('HBE_ICONS4_DESC_3'),
            'hbe_icons4_img_url_4' => Configuration::get('HBE_ICONS4_IMG_4') ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_ICONS4_IMG_4') : '',
            'hbe_icons4_img_mobile_url_4' => Configuration::get('HBE_ICONS4_IMG_4_MOBILE') ? __PS_BASE_URI__ . 'img/hb_editor/' . Configuration::get('HBE_ICONS4_IMG_4_MOBILE') : '',
            'hbe_icons4_title_4'   => (string) Configuration::get('HBE_ICONS4_TITLE_4'),
            'hbe_icons4_desc_4'    => (string) Configuration::get('HBE_ICONS4_DESC_4'),
            'hbe_icons4_title_1_lang' => $this->getConfigPerLang('HBE_ICONS4_TITLE_1', $languages),
            'hbe_icons4_desc_1_lang'  => $this->getConfigPerLang('HBE_ICONS4_DESC_1',  $languages),
            'hbe_icons4_title_2_lang' => $this->getConfigPerLang('HBE_ICONS4_TITLE_2', $languages),
            'hbe_icons4_desc_2_lang'  => $this->getConfigPerLang('HBE_ICONS4_DESC_2',  $languages),
            'hbe_icons4_title_3_lang' => $this->getConfigPerLang('HBE_ICONS4_TITLE_3', $languages),
            'hbe_icons4_desc_3_lang'  => $this->getConfigPerLang('HBE_ICONS4_DESC_3',  $languages),
            'hbe_icons4_title_4_lang' => $this->getConfigPerLang('HBE_ICONS4_TITLE_4', $languages),
            'hbe_icons4_desc_4_lang'  => $this->getConfigPerLang('HBE_ICONS4_DESC_4',  $languages),
            // Per-language image filename + URL arrays (id_lang => …)
            'hbe_imghero_image_lang'      => $this->getImageFilenamesPerLang('HBE_IMGHERO_IMAGE',      $languages),
            'hbe_imghero_image_lang_urls' => $this->getImageUrlsPerLang('HBE_IMGHERO_IMAGE',           $languages),
            'hbe_imghero_image_mobile_lang_urls' => $this->getImageUrlsPerLang('HBE_IMGHERO_IMAGE_MOBILE', $languages),
            'hbe_imghero2_image_lang'      => $this->getImageFilenamesPerLang('HBE_IMGHERO2_IMAGE',    $languages),
            'hbe_imghero2_image_lang_urls' => $this->getImageUrlsPerLang('HBE_IMGHERO2_IMAGE',         $languages),
            'hbe_imghero2_image_mobile_lang_urls' => $this->getImageUrlsPerLang('HBE_IMGHERO2_IMAGE_MOBILE', $languages),
            'hbe_katcols_l_image_lang'      => $this->getImageFilenamesPerLang('HBE_KATCOLS_L_IMAGE',  $languages),
            'hbe_katcols_l_image_lang_urls' => $this->getImageUrlsPerLang('HBE_KATCOLS_L_IMAGE',       $languages),
            'hbe_katcols_l_image_mobile_lang_urls' => $this->getImageUrlsPerLang('HBE_KATCOLS_L_IMAGE_MOBILE', $languages),
            'hbe_katcols_r_image_lang'      => $this->getImageFilenamesPerLang('HBE_KATCOLS_R_IMAGE',  $languages),
            'hbe_katcols_r_image_lang_urls' => $this->getImageUrlsPerLang('HBE_KATCOLS_R_IMAGE',       $languages),
            'hbe_katcols_r_image_mobile_lang_urls' => $this->getImageUrlsPerLang('HBE_KATCOLS_R_IMAGE_MOBILE', $languages),
            'hbe_splitblock_m_image_lang'      => $this->getImageFilenamesPerLang('HBE_SPLITBLOCK_M_IMAGE', $languages),
            'hbe_splitblock_m_image_lang_urls' => $this->getImageUrlsPerLang('HBE_SPLITBLOCK_M_IMAGE',      $languages),
            'hbe_splitblock_m_image_mobile_lang_urls' => $this->getImageUrlsPerLang('HBE_SPLITBLOCK_M_IMAGE_MOBILE', $languages),
            'hbe_splitblock_r_image_lang'      => $this->getImageFilenamesPerLang('HBE_SPLITBLOCK_R_IMAGE', $languages),
            'hbe_splitblock_r_image_lang_urls' => $this->getImageUrlsPerLang('HBE_SPLITBLOCK_R_IMAGE',      $languages),
            'hbe_splitblock_r_image_mobile_lang_urls' => $this->getImageUrlsPerLang('HBE_SPLITBLOCK_R_IMAGE_MOBILE', $languages),
            'hbe_icons4_img_1_lang'      => $this->getImageFilenamesPerLang('HBE_ICONS4_IMG_1', $languages),
            'hbe_icons4_img_1_lang_urls' => $this->getImageUrlsPerLang('HBE_ICONS4_IMG_1',      $languages),
            'hbe_icons4_img_1_mobile_lang_urls' => $this->getImageUrlsPerLang('HBE_ICONS4_IMG_1_MOBILE', $languages),
            'hbe_icons4_img_2_lang'      => $this->getImageFilenamesPerLang('HBE_ICONS4_IMG_2', $languages),
            'hbe_icons4_img_2_lang_urls' => $this->getImageUrlsPerLang('HBE_ICONS4_IMG_2',      $languages),
            'hbe_icons4_img_2_mobile_lang_urls' => $this->getImageUrlsPerLang('HBE_ICONS4_IMG_2_MOBILE', $languages),
            'hbe_icons4_img_3_lang'      => $this->getImageFilenamesPerLang('HBE_ICONS4_IMG_3', $languages),
            'hbe_icons4_img_3_lang_urls' => $this->getImageUrlsPerLang('HBE_ICONS4_IMG_3',      $languages),
            'hbe_icons4_img_3_mobile_lang_urls' => $this->getImageUrlsPerLang('HBE_ICONS4_IMG_3_MOBILE', $languages),
            'hbe_icons4_img_4_lang'      => $this->getImageFilenamesPerLang('HBE_ICONS4_IMG_4', $languages),
            'hbe_icons4_img_4_lang_urls' => $this->getImageUrlsPerLang('HBE_ICONS4_IMG_4',      $languages),
            'hbe_icons4_img_4_mobile_lang_urls' => $this->getImageUrlsPerLang('HBE_ICONS4_IMG_4_MOBILE', $languages),
            // Brands
            'hbe_brands_enabled'    => (int) Configuration::get('HBE_BRANDS_ENABLED'),
            'hbe_brands_title_lang' => $this->getConfigPerLang('HBE_BRANDS_TITLE', $languages),
            'hbe_brands_items'      => (function () use ($languages, $manuMap): array {
                $items = [];
                for ($i = 1; $i <= 8; $i++) {
                    $f      = Configuration::get('HBE_BRANDS_IMG_' . $i);
                    $manuId = (int) Configuration::get('HBE_BRANDS_MANU_' . $i);
                    $manu   = $manuMap[$manuId] ?? null;
                    $items[] = [
                        'n'               => $i,
                        'img_url'         => $f ? __PS_BASE_URI__ . 'img/hb_editor/' . $f : '',
                        'link'            => (string) Configuration::get('HBE_BRANDS_LINK_' . $i),
                        'alt_lang'        => $this->getConfigPerLang('HBE_BRANDS_ALT_' . $i, $languages),
                        'id_manufacturer' => $manuId,
                        'manu_name'       => $manu['name'] ?? '',
                        'manu_logo_url'   => $manu['logo_url'] ?? '',
                    ];
                }
                return $items;
            })(),
            'hbe_manufacturers'     => $hbeManufacturers,
        ]);

        $this->assignSliderTab($languages);

        $this->setTemplate('main.tpl');
    }

    /* ── Slider tab (ported from bemo_slider) ────────────────────────────── */

    private function sliderImgDir(): string
    {
        return _PS_MODULE_DIR_ . 'hummingbird_editor/images/';
    }

    private function sliderImgBaseUrl(): string
    {
        return __PS_BASE_URI__ . 'modules/hummingbird_editor/images/';
    }

    private function sliderExists(int $idSlide): bool
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT id_hb_slide FROM `' . _DB_PREFIX_ . 'hb_editor_slider` WHERE id_hb_slide = ' . (int) $idSlide
        );
    }

    /**
     * Full-page POST handling for the slider tab. Mirrors bemo_slider::_postProcess.
     */
    public function postProcess()
    {
        $ret = parent::postProcess();

        if (Tools::isSubmit('submitSlide')
            || Tools::isSubmit('delete_id_slide')
            || Tools::isSubmit('copy_id_slide')
            || Tools::isSubmit('submitSlider')
            || Tools::isSubmit('changeStatus')
        ) {
            $this->handleSliderPostProcess();
        }

        return $ret;
    }

    private function sliderRedirect(int $conf): void
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminHbEditor') . '&conf=' . (int) $conf . '#hbe-tab-slider');
    }

    private function handleSliderPostProcess(): void
    {
        $errors = [];
        $imgDir = $this->sliderImgDir();
        if (!is_dir($imgDir)) {
            @mkdir($imgDir, 0755, true);
        }

        if (Tools::isSubmit('submitSlider')) {
            HbEditorConfig::set('HBE_SLIDER_SPEED', (int) Tools::getValue('HBE_SLIDER_SPEED'));
            HbEditorConfig::set('HBE_SLIDER_AUTOPLAY', (int) Tools::getValue('HBE_SLIDER_AUTOPLAY'));
            HbEditorConfig::set('HBE_SLIDER_PAUSE_ON_HOVER', (int) Tools::getValue('HBE_SLIDER_PAUSE_ON_HOVER'));
            HbEditorConfig::set('HBE_SLIDER_SHOW_ARROWS', (int) Tools::getValue('HBE_SLIDER_SHOW_ARROWS'));
            HbEditorConfig::set('HBE_SLIDER_ARROW_STYLE', Tools::getValue('HBE_SLIDER_ARROW_STYLE') === 'corner' ? 'corner' : 'classic');
            HbEditorConfig::set('HBE_SLIDER_SHOW_DOTS', (int) Tools::getValue('HBE_SLIDER_SHOW_DOTS'));
            $this->sliderRedirect(6);
            return;
        }

        if (Tools::isSubmit('changeStatus') && Tools::isSubmit('id_slide')) {
            $slide = new HbEditorSlide((int) Tools::getValue('id_slide'));
            $slide->active = $slide->active == 0 ? 1 : 0;
            $slide->update();
            $this->sliderRedirect(4);
            return;
        }

        if (Tools::isSubmit('delete_id_slide')) {
            $slide = new HbEditorSlide((int) Tools::getValue('delete_id_slide'));
            $slide->delete();
            $this->sliderRedirect(1);
            return;
        }

        if (Tools::isSubmit('copy_id_slide')) {
            $this->sliderCopySlide((int) Tools::getValue('copy_id_slide'));
            $this->sliderRedirect(19);
            return;
        }

        if (!Tools::isSubmit('submitSlide')) {
            return;
        }

        // ── Add / edit slide ──
        if (Tools::getValue('id_slide')) {
            $slide = new HbEditorSlide((int) Tools::getValue('id_slide'));
            if (!Validate::isLoadedObject($slide)) {
                $this->errors[] = $this->module->l('Nieprawidłowe ID slajdu');
                return;
            }
        } else {
            $slide = new HbEditorSlide();
            $slide->position = (int) HbEditorSlide::getNextPosition((int) $this->context->shop->id);
        }

        $slide->active                 = (int) Tools::getValue('active_slide');
        $slide->active_mobile          = (int) Tools::getValue('active_mobile');
        $slide->text_position          = (int) Tools::getValue('text_position');
        $slide->show_text              = (int) Tools::getValue('show_text');
        $slide->overlay_is_transparent = (int) Tools::getValue('overlay_is_transparent');
        $slide->overlay_color          = Tools::getValue('overlay_color');
        $slide->overlay_opacity        = (int) Tools::getValue('overlay_opacity');

        $slide->cta_enabled = (int) Tools::getValue('cta_enabled', 0);
        $ctaText = trim((string) Tools::getValue('cta_text', ''));
        $slide->cta_text   = Tools::strlen($ctaText) > 100 ? Tools::substr($ctaText, 0, 100) : $ctaText;
        $ctaColor = Tools::getValue('cta_color', '#ffffff');
        $slide->cta_color  = preg_match('/^#[0-9a-fA-F]{6}$/', $ctaColor) ? $ctaColor : '#ffffff';
        $ctaBg = Tools::getValue('cta_bg', '#000000');
        $slide->cta_bg     = preg_match('/^#[0-9a-fA-F]{6}$/', $ctaBg) ? $ctaBg : '#000000';
        $ctaSize = Tools::getValue('cta_size', 'md');
        $slide->cta_size   = in_array($ctaSize, ['sm', 'md', 'lg']) ? $ctaSize : 'md';
        $ctaRadius = (int) Tools::getValue('cta_radius', 4);
        $slide->cta_radius = ($ctaRadius >= 0 && $ctaRadius <= 100) ? $ctaRadius : 4;

        $languages = Language::getLanguages(false);

        // Master-image (apply one upload to all languages) — desktop + mobile
        $sharedDesktop = $this->sliderResolveMasterImage($languages, 'image', '', $errors);
        $sharedMobile  = $this->sliderResolveMasterImage($languages, 'image_mobile', 'mobile_', $errors);

        foreach ($languages as $language) {
            $lid = (int) $language['id_lang'];
            $slide->title[$lid]       = Tools::getValue('title_' . $lid);
            $slide->url[$lid]         = Tools::getValue('url_' . $lid);
            $slide->description[$lid] = Tools::getValue('description_' . $lid);

            $slide->image[$lid] = $sharedDesktop !== null
                ? $sharedDesktop
                : $this->sliderUploadLangImage('image_' . $lid, '', $errors);

            $slide->image_mobile[$lid] = $sharedMobile !== null
                ? $sharedMobile
                : $this->sliderUploadLangImage('image_mobile_' . $lid, 'mobile_', $errors);
        }

        if ($errors) {
            $this->errors = array_merge($this->errors, $errors);
            return;
        }

        // Shop associations
        $selectedShops = [];
        foreach (Shop::getShops(true) as $shop) {
            if (Tools::isSubmit('checkBoxShopAsso_' . $shop['id_shop'])) {
                $selectedShops[] = (int) $shop['id_shop'];
            }
        }
        if (!$selectedShops) {
            $selectedShops[] = (int) $this->context->shop->id;
        }

        if (!Tools::getValue('id_slide')) {
            if ($slide->add()) {
                $slide->updateShops($selectedShops);
                $this->sliderRedirect(3);
            } else {
                $this->errors[] = $this->module->l('Nie można dodać slajdu');
            }
        } else {
            if ($slide->update()) {
                $slide->updateShops($selectedShops);
                $this->sliderRedirect(4);
            } else {
                $this->errors[] = $this->module->l('Nie można zaktualizować slajdu');
            }
        }
    }

    /**
     * If a language is flagged "apply to all", upload/keep its file and return
     * the filename to share across languages. Returns null when not flagged.
     *
     * @param array<int,array> $languages
     * @param string $field      'image' or 'image_mobile'
     * @param string $prefix     filename prefix ('' desktop, 'mobile_' mobile)
     * @param array  $errors
     * @return string|null
     */
    private function sliderResolveMasterImage(array $languages, string $field, string $prefix, array &$errors): ?string
    {
        $masterLid = null;
        foreach ($languages as $lang) {
            $flag = $field === 'image' ? 'apply_image_all_langs_' : 'apply_image_mobile_all_langs_';
            if (Tools::getValue($flag . $lang['id_lang'])) {
                $masterLid = (int) $lang['id_lang'];
                break;
            }
        }
        if ($masterLid === null) {
            return null;
        }

        $uploaded = $this->sliderUploadLangImage($field . '_' . $masterLid, $prefix, $errors);
        if ($uploaded !== '') {
            return $uploaded;
        }
        // Fall back to the existing file kept on that language
        $old = Tools::getValue($field . '_old_' . $masterLid);
        return $old ?: null;
    }

    /**
     * Validate + move a single uploaded slide image, generate its webp variant.
     * Returns the new filename, the kept old filename, or '' when none.
     *
     * @param string $inputName  $_FILES key (e.g. image_2, image_mobile_2)
     * @param string $prefix     filename prefix ('' desktop, 'mobile_' mobile)
     * @param array  $errors
     */
    private function sliderUploadLangImage(string $inputName, string $prefix, array &$errors): string
    {
        $oldKey = preg_replace('/^(image(?:_mobile)?)_(\d+)$/', '$1_old_$2', $inputName);

        if (isset($_FILES[$inputName]) && !empty($_FILES[$inputName]['tmp_name'])) {
            $type = Tools::strtolower(Tools::substr(strrchr($_FILES[$inputName]['name'], '.'), 1));
            $imagesize = @getimagesize($_FILES[$inputName]['tmp_name']);
            $allowed = ['jpg', 'gif', 'jpeg', 'png', 'webp'];

            if (!empty($type) && !empty($imagesize)
                && in_array(Tools::strtolower(Tools::substr(strrchr($imagesize['mime'], '/'), 1)), $allowed)
                && in_array($type, $allowed)
            ) {
                $name = $prefix . sha1(microtime()) . '_' . $_FILES[$inputName]['name'];
                $destination = $this->sliderImgDir() . $name;
                if ($error = ImageManager::validateUpload($_FILES[$inputName])) {
                    $errors[] = $error;
                } elseif (!move_uploaded_file($_FILES[$inputName]['tmp_name'], $destination)) {
                    $errors[] = $this->module->l('Wystąpił błąd podczas przesyłania obrazu');
                } else {
                    HbEditorSlide::generateWebpVariant($destination);
                    return $name;
                }
            }
        }

        $old = Tools::getValue($oldKey);
        return $old !== false && $old !== '' ? (string) $old : '';
    }

    private function sliderCopySlide(int $srcId): void
    {
        $src  = new HbEditorSlide($srcId);
        if (!Validate::isLoadedObject($src)) {
            return;
        }
        $copy = new HbEditorSlide();
        $copy->active          = 0;
        $copy->active_mobile   = $src->active_mobile;
        $copy->text_position   = $src->text_position;
        $copy->show_text       = $src->show_text;
        $copy->overlay_is_transparent = $src->overlay_is_transparent;
        $copy->overlay_color   = $src->overlay_color;
        $copy->overlay_opacity = $src->overlay_opacity;
        $copy->cta_enabled     = $src->cta_enabled;
        $copy->cta_text        = $src->cta_text;
        $copy->cta_color       = $src->cta_color;
        $copy->cta_bg          = $src->cta_bg;
        $copy->cta_size        = $src->cta_size;
        $copy->cta_radius      = $src->cta_radius;
        $copy->position        = 0;

        $imgDir = $this->sliderImgDir();
        foreach (Language::getLanguages(false) as $lang) {
            $lid = (int) $lang['id_lang'];
            $copy->title[$lid]       = $src->title[$lid] ?? '';
            $copy->description[$lid] = $src->description[$lid] ?? '';
            $copy->url[$lid]         = $src->url[$lid] ?? '';

            $copy->image[$lid] = $this->sliderCopyImageFile($src->image[$lid] ?? '', 'copy_', $imgDir);
            $copy->image_mobile[$lid] = $this->sliderCopyImageFile($src->image_mobile[$lid] ?? '', 'copy_m_', $imgDir);
        }

        $shopIds = HbEditorSlide::getAssociatedIdsShop((int) $src->id);
        if ($copy->add() && $shopIds) {
            $copy->updateShops($shopIds);
        }
    }

    private function sliderCopyImageFile(string $file, string $prefix, string $imgDir): string
    {
        if ($file !== '' && file_exists($imgDir . $file)) {
            $ext  = pathinfo($file, PATHINFO_EXTENSION);
            $name = sha1(uniqid($prefix, true)) . '.' . $ext;
            @copy($imgDir . $file, $imgDir . $name);
            $srcWebp = preg_replace('/\.[^.]+$/', '.webp', $imgDir . $file);
            if (is_string($srcWebp) && is_file($srcWebp)) {
                @copy($srcWebp, preg_replace('/\.[^.]+$/', '.webp', $imgDir . $name));
            }
            return $name;
        }
        return '';
    }

    /** AJAX: persist new slide order (ported from AdminBemoSliderController). */
    public function ajaxProcessUpdateSlidesPosition(): void
    {
        $slides  = Tools::getValue('slides');
        $idShop  = (int) $this->context->shop->id;
        $success = true;
        if (is_array($slides)) {
            foreach ($slides as $position => $idSlide) {
                $res = Db::getInstance()->update(
                    'hb_editor_slider',
                    ['position' => (int) $position + 1],
                    'id_hb_slide = ' . (int) $idSlide . ' AND id_shop = ' . $idShop
                );
                if (!$res) {
                    $success = false;
                }
            }
        }
        $this->ajaxDie(json_encode(['success' => $success]));
    }

    /** Build all Smarty vars for the Slider tab (list + settings + add/edit form). */
    private function assignSliderTab(array $activeLanguages): void
    {
        $languages = Language::getLanguages(false);
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $defaultLangName = '';
        foreach ($languages as $l) {
            if ((int) $l['id_lang'] === $defaultLang) {
                $defaultLangName = (string) $l['name'];
                break;
            }
        }

        // Add / edit mode detection
        $mode = 'list';
        $editSlide = null;
        $associatedShops = [(int) $this->context->shop->id];

        if (Tools::isSubmit('addSlide')) {
            $mode = 'form';
        } elseif (Tools::isSubmit('id_slide') && $this->sliderExists((int) Tools::getValue('id_slide'))) {
            $mode = 'form';
            $editSlide = new HbEditorSlide((int) Tools::getValue('id_slide'));
            $associatedShops = HbEditorSlide::getAssociatedIdsShop((int) Tools::getValue('id_slide')) ?: $associatedShops;
        }

        // Slides list (force show all) with status flags
        $slides = $this->module->getSliderSlides(null, true);
        foreach ($slides as &$s) {
            $shopIds = HbEditorSlide::getAssociatedIdsShop((int) $s['id_slide']);
            $s['is_shared'] = $shopIds && count($shopIds) > 1;
        }
        unset($s);

        $this->context->smarty->assign([
            'hbe_slider_mode'          => $mode,
            'hbe_slider_edit'          => $editSlide,
            'hbe_slider_slides'        => $slides,
            'hbe_slider_languages'     => $languages,
            'hbe_slider_default_lang'  => $defaultLang,
            'hbe_slider_default_lang_name' => $defaultLangName,
            'hbe_slider_shops'         => Shop::getShops(true),
            'hbe_slider_associated'    => $associatedShops,
            'hbe_slider_image_baseurl' => $this->sliderImgBaseUrl(),
            'hbe_slider_form_action'   => $this->context->link->getAdminLink('AdminHbEditor'),
            'hbe_slider_max_file_size' => (int) (Tools::getMaxUploadSize() / 1024 / 1024) . ' MB',
            'hbe_slider_speed'         => (int) (HbEditorConfig::get('HBE_SLIDER_SPEED') ?: 5000),
            'hbe_slider_autoplay'      => (int) HbEditorConfig::get('HBE_SLIDER_AUTOPLAY'),
            'hbe_slider_pause'         => (int) HbEditorConfig::get('HBE_SLIDER_PAUSE_ON_HOVER'),
            'hbe_slider_arrows'        => (int) HbEditorConfig::get('HBE_SLIDER_SHOW_ARROWS'),
            'hbe_slider_arrow_style'   => HbEditorConfig::get('HBE_SLIDER_ARROW_STYLE') === 'corner' ? 'corner' : 'classic',
            'hbe_slider_dots'          => (int) HbEditorConfig::get('HBE_SLIDER_SHOW_DOTS'),
        ]);
    }

    /* ── AJAX: create new block ──────────────────────────────────────────── */

    public function ajaxProcessCreateBlock(): void
    {
        $hookName = trim(Tools::getValue('hook_name'));
        $type     = Tools::getValue('type');
        $active   = (int) Tools::getValue('active', 1);
        $mobileDiff = (int) Tools::getValue('mobile_different', 0);
        $shopIds  = Tools::getValue('shop_ids', []);

        if (!$hookName || !in_array($type, HbEditorBlock::getTypes(), true)) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Invalid data']));
        }

        $hookName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $hookName);

        $position = HbEditorBlock::getNextPosition($hookName);
        $idBlock  = HbEditorBlock::create([
            'hook_name'        => $hookName,
            'type'             => $type,
            'active'           => $active,
            'mobile_different' => $mobileDiff,
            'position'         => $position,
        ]);

        if (!$idBlock) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'DB error']));
        }

        // Shops
        if (!$shopIds) {
            $shopIds = [Context::getContext()->shop->id];
        }
        HbEditorBlock::setShops($idBlock, (array) $shopIds);

        // Register hook in PS if new
        $this->module->ensureHookRegistered($hookName);

        $this->ajaxDie(json_encode([
            'success'  => true,
            'id_block' => $idBlock,
        ]));
    }

    /* ── AJAX: save (update) block ───────────────────────────────────────── */

    public function ajaxProcessSaveBlock(): void
    {
        $idBlock  = (int) Tools::getValue('id_block');
        $hookName = trim(Tools::getValue('hook_name'));
        $type     = Tools::getValue('type');
        $active   = (int) Tools::getValue('active', 1);
        $mobileDiff = (int) Tools::getValue('mobile_different', 0);
        $shopIds  = Tools::getValue('shop_ids', []);

        if (!$idBlock || !$hookName || !in_array($type, HbEditorBlock::getTypes(), true)) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Invalid data']));
        }

        $hookName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $hookName);

        HbEditorBlock::update($idBlock, [
            'hook_name'        => $hookName,
            'type'             => $type,
            'active'           => $active,
            'mobile_different' => $mobileDiff,
        ]);

        // Lang data
        $languages = Language::getLanguages(true);
        $langData  = [];
        foreach ($languages as $lang) {
            $lid = (int) $lang['id_lang'];
            $langData[$lid] = [
                'content_desktop' => Tools::getValue('content_desktop_' . $lid, ''),
                'content_mobile'  => Tools::getValue('content_mobile_' . $lid, ''),
                'link_desktop'    => Tools::getValue('link_desktop_' . $lid, ''),
                'link_mobile'     => Tools::getValue('link_mobile_' . $lid, ''),
            ];
        }
        HbEditorBlock::saveLang($idBlock, $langData);

        // Shops
        if (!$shopIds) {
            $shopIds = [Context::getContext()->shop->id];
        }
        HbEditorBlock::setShops($idBlock, (array) $shopIds);

        // Register hook if needed
        $this->module->ensureHookRegistered($hookName);

        $this->ajaxDie(json_encode(['success' => true]));
    }

    /* ── AJAX: delete block ──────────────────────────────────────────────── */

    public function ajaxProcessDeleteBlock(): void
    {
        $idBlock = (int) Tools::getValue('id_block');
        if (!$idBlock) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Invalid id']));
        }

        // Delete images from disk
        $block = HbEditorBlock::getById($idBlock);
        if ($block) {
            $imgDir = _PS_IMG_DIR_ . 'hb_editor/';
            foreach (['image_desktop', 'image_mobile'] as $field) {
                if (!empty($block[$field]) && is_file($imgDir . $block[$field])) {
                    @unlink($imgDir . $block[$field]);
                }
            }
        }

        $ok = HbEditorBlock::delete($idBlock);
        $this->ajaxDie(json_encode(['success' => $ok]));
    }

    /* ── AJAX: duplicate block ──────────────────────────────────────────── */

    public function ajaxProcessDuplicateBlock(): void
    {
        $idBlock = (int) Tools::getValue('id_block');
        if (!$idBlock) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Invalid id']));
        }

        $newId = HbEditorBlock::duplicate($idBlock);

        if (!$newId) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Duplicate failed']));
        }

        $this->ajaxDie(json_encode(['success' => true, 'id_block' => $newId]));
    }

    /* ── AJAX: clone static section as a new DB block ────────────────────── */

    public function ajaxProcessCloneStaticSection(): void
    {
        $slug = trim((string) Tools::getValue('slug'));
        $validSlugs = HbEditorBlock::getSectionTypes();

        if (!in_array($slug, $validSlugs, true)) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Unknown section type']));
        }

        $languages  = Language::getLanguages(true);
        $imgDir     = _PS_IMG_DIR_ . 'hb_editor/';

        // Helper: copy an image file to a new unique filename; returns null if missing
        $copyImg = static function (?string $filename) use ($imgDir): ?string {
            if (!$filename || !is_file($imgDir . $filename)) {
                return null;
            }
            $ext     = pathinfo($filename, PATHINFO_EXTENSION);
            $newName = 'block_' . uniqid('', true) . ($ext ? '.' . $ext : '');
            @copy($imgDir . $filename, $imgDir . $newName);
            $webpSrc = preg_replace('/\.[^.]+$/', '.webp', $imgDir . $filename);
            if (is_file((string) $webpSrc)) {
                $webpDst = preg_replace('/\.[^.]+$/', '.webp', $imgDir . $newName);
                @copy((string) $webpSrc, (string) $webpDst);
            }
            return $newName;
        };

        // Helper: read per-lang config values → [ id_lang => value ]
        $perLang = function (string $key) use ($languages): array {
            $out = [];
            foreach ($languages as $lang) {
                $id  = (int) $lang['id_lang'];
                $val = Configuration::get($key, $id);
                $out[$id] = ($val === false || $val === null) ? '' : (string) $val;
            }
            return $out;
        };

        $sd = [];

        switch ($slug) {
            case HbEditorBlock::STYPE_INFOBAR:
                $sd['bg']    = (string) (Configuration::get('HBE_INFOBAR_BG')    ?: '#222222');
                $sd['color'] = (string) (Configuration::get('HBE_INFOBAR_COLOR') ?: '#ffffff');
                $sd['langs'] = [];
                foreach ($languages as $lang) {
                    $id = (int) $lang['id_lang'];
                    $sd['langs'][$id] = [
                        'text'      => $perLang('HBE_INFOBAR_TEXT')[$id]      ?? '',
                        'url'       => $perLang('HBE_INFOBAR_URL')[$id]       ?? '',
                        'link_text' => $perLang('HBE_INFOBAR_LINK_TEXT')[$id] ?? '',
                    ];
                }
                break;

            case HbEditorBlock::STYPE_IMGHERO:
                $imgOrig    = (string) Configuration::get('HBE_IMGHERO_IMAGE');
                $mobileOrig = (string) Configuration::get('HBE_IMGHERO_IMAGE_MOBILE');
                $sd['image']        = $copyImg($imgOrig)    ?? $imgOrig;
                $sd['image_mobile'] = $copyImg($mobileOrig) ?? $mobileOrig;
                $sd['langs'] = [];
                foreach ($languages as $lang) {
                    $id = (int) $lang['id_lang'];
                    $sd['langs'][$id] = [
                        'title'    => $perLang('HBE_IMGHERO_TITLE')[$id]    ?? '',
                        'desc'     => $perLang('HBE_IMGHERO_DESC')[$id]     ?? '',
                        'cta_text' => $perLang('HBE_IMGHERO_CTA_TEXT')[$id] ?? '',
                        'cta_url'  => $perLang('HBE_IMGHERO_CTA_URL')[$id]  ?? '',
                    ];
                }
                break;

            case HbEditorBlock::STYPE_COLS3:
                $sd['langs'] = [];
                foreach ($languages as $lang) {
                    $id = (int) $lang['id_lang'];
                    $entry = [];
                    for ($i = 1; $i <= 3; $i++) {
                        $entry['text_' . $i] = $perLang('HBE_COLS3_TEXT_' . $i)[$id] ?? '';
                        $entry['url_' . $i]  = $perLang('HBE_COLS3_URL_'  . $i)[$id] ?? '';
                    }
                    $sd['langs'][$id] = $entry;
                }
                break;

            case HbEditorBlock::STYPE_COLS3DESC:
                $sd['langs'] = [];
                foreach ($languages as $lang) {
                    $id = (int) $lang['id_lang'];
                    $entry = [];
                    for ($i = 1; $i <= 3; $i++) {
                        $entry['title_' . $i] = $perLang('HBE_COLS3D_TITLE_' . $i)[$id] ?? '';
                        $entry['desc_'  . $i] = $perLang('HBE_COLS3D_DESC_'  . $i)[$id] ?? '';
                        $entry['url_'   . $i] = $perLang('HBE_COLS3D_URL_'   . $i)[$id] ?? '';
                    }
                    $sd['langs'][$id] = $entry;
                }
                break;

            case HbEditorBlock::STYPE_TAGLINE:
                $sd['langs'] = [];
                foreach ($languages as $lang) {
                    $id = (int) $lang['id_lang'];
                    $sd['langs'][$id] = [
                        'text'      => $perLang('HBE_TAGLINE_TEXT')[$id]      ?? '',
                        'link_text' => $perLang('HBE_TAGLINE_LINK_TEXT')[$id] ?? '',
                        'link_url'  => $perLang('HBE_TAGLINE_LINK_URL')[$id]  ?? '',
                    ];
                }
                break;

            case HbEditorBlock::STYPE_KATCOLS:
                $lOrig  = (string) Configuration::get('HBE_KATCOLS_L_IMAGE');
                $rOrig  = (string) Configuration::get('HBE_KATCOLS_R_IMAGE');
                $lmOrig = (string) Configuration::get('HBE_KATCOLS_L_IMAGE_MOBILE');
                $rmOrig = (string) Configuration::get('HBE_KATCOLS_R_IMAGE_MOBILE');
                $sd['l_image']        = $copyImg($lOrig)  ?? $lOrig;
                $sd['r_image']        = $copyImg($rOrig)  ?? $rOrig;
                $sd['l_image_mobile'] = $copyImg($lmOrig) ?? $lmOrig;
                $sd['r_image_mobile'] = $copyImg($rmOrig) ?? $rmOrig;
                $sd['langs'] = [];
                foreach ($languages as $lang) {
                    $id = (int) $lang['id_lang'];
                    $sd['langs'][$id] = [
                        'title'         => $perLang('HBE_KATCOLS_TITLE')[$id]         ?? '',
                        'hdr_text'      => $perLang('HBE_KATCOLS_HDR_TEXT')[$id]      ?? '',
                        'hdr_link_text' => $perLang('HBE_KATCOLS_HDR_LINK_TEXT')[$id] ?? '',
                        'hdr_url'       => $perLang('HBE_KATCOLS_HDR_URL')[$id]       ?? '',
                        'l_caption'     => $perLang('HBE_KATCOLS_L_CAPTION')[$id]     ?? '',
                        'l_url'         => $perLang('HBE_KATCOLS_L_URL')[$id]         ?? '',
                        'r_caption'     => $perLang('HBE_KATCOLS_R_CAPTION')[$id]     ?? '',
                        'r_url'         => $perLang('HBE_KATCOLS_R_URL')[$id]         ?? '',
                    ];
                }
                break;

            case HbEditorBlock::STYPE_SPLITBLOCK:
                $mOrig  = (string) Configuration::get('HBE_SPLITBLOCK_M_IMAGE');
                $rOrig  = (string) Configuration::get('HBE_SPLITBLOCK_R_IMAGE');
                $mmOrig = (string) Configuration::get('HBE_SPLITBLOCK_M_IMAGE_MOBILE');
                $rmOrig = (string) Configuration::get('HBE_SPLITBLOCK_R_IMAGE_MOBILE');
                $sd['m_image']        = $copyImg($mOrig)  ?? $mOrig;
                $sd['r_image']        = $copyImg($rOrig)  ?? $rOrig;
                $sd['m_image_mobile'] = $copyImg($mmOrig) ?? $mmOrig;
                $sd['r_image_mobile'] = $copyImg($rmOrig) ?? $rmOrig;
                $sd['langs'] = [];
                foreach ($languages as $lang) {
                    $id = (int) $lang['id_lang'];
                    $sd['langs'][$id] = [
                        'title'    => $perLang('HBE_SPLITBLOCK_TITLE')[$id]    ?? '',
                        'desc'     => $perLang('HBE_SPLITBLOCK_DESC')[$id]     ?? '',
                        'cta_text' => $perLang('HBE_SPLITBLOCK_CTA_TEXT')[$id] ?? '',
                        'cta_url'  => $perLang('HBE_SPLITBLOCK_CTA_URL')[$id]  ?? '',
                    ];
                }
                break;

            case HbEditorBlock::STYPE_ICONS4:
                $imgs = [];
                for ($i = 1; $i <= 4; $i++) {
                    $dOrig = (string) Configuration::get('HBE_ICONS4_IMG_' . $i);
                    $mOrig = (string) Configuration::get('HBE_ICONS4_IMG_' . $i . '_MOBILE');
                    $imgs[] = [
                        'd' => $copyImg($dOrig) ?? $dOrig,
                        'm' => $copyImg($mOrig) ?? $mOrig,
                    ];
                }
                $sd['imgs'] = $imgs;
                $sd['langs'] = [];
                foreach ($languages as $lang) {
                    $id = (int) $lang['id_lang'];
                    $entry = [];
                    for ($i = 1; $i <= 4; $i++) {
                        $entry['title_' . $i] = $perLang('HBE_ICONS4_TITLE_' . $i)[$id] ?? '';
                        $entry['desc_'  . $i] = $perLang('HBE_ICONS4_DESC_'  . $i)[$id] ?? '';
                    }
                    $sd['langs'][$id] = $entry;
                }
                break;

            case HbEditorBlock::STYPE_BRANDS:
                $brandImgs = [];
                for ($i = 1; $i <= 8; $i++) {
                    $imgOrig = (string) Configuration::get('HBE_BRANDS_IMG_' . $i);
                    $brandImgs[] = [
                        'img'  => $copyImg($imgOrig) ?? $imgOrig,
                        'link' => (string) Configuration::get('HBE_BRANDS_LINK_' . $i),
                        'manu' => (int) Configuration::get('HBE_BRANDS_MANU_' . $i),
                    ];
                }
                $sd['imgs'] = $brandImgs;
                $sd['langs'] = [];
                foreach ($languages as $lang) {
                    $id = (int) $lang['id_lang'];
                    $entry = ['title' => $perLang('HBE_BRANDS_TITLE')[$id] ?? ''];
                    for ($i = 1; $i <= 8; $i++) {
                        $entry['alt_' . $i] = $perLang('HBE_BRANDS_ALT_' . $i)[$id] ?? '';
                    }
                    $sd['langs'][$id] = $entry;
                }
                break;
        }

        $position = HbEditorBlock::getNextPosition('displayHome');
        $newId    = HbEditorBlock::create([
            'hook_name'    => 'displayHome',
            'type'         => 'text',
            'section_type' => $slug,
            'section_data' => json_encode($sd, JSON_UNESCAPED_UNICODE),
            'active'       => 1,
            'position'     => $position,
        ]);

        if (!$newId) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'DB error']));
        }

        HbEditorBlock::setShops($newId, [(int) Context::getContext()->shop->id]);

        // Append the new block to displayHome order
        $orderRaw   = (string) (Configuration::get('HBE_HOME_ORDER') ?: 'infobar,imghero,cols3,tagline');
        $orderParts = array_filter(array_map('trim', explode(',', $orderRaw)));
        $orderParts[] = (string) $newId;
        Configuration::updateValue('HBE_HOME_ORDER', implode(',', $orderParts));

        $this->module->ensureHookRegistered('displayHome');

        $this->ajaxDie(json_encode(['success' => true, 'id_block' => $newId]));
    }

    /* ── AJAX: save section block data (JSON editor) ────────────────────── */

    public function ajaxProcessSaveSectionBlock(): void
    {
        $idBlock = (int) Tools::getValue('id_block');
        if (!$idBlock) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Invalid id']));
        }

        $block = HbEditorBlock::getById($idBlock);
        if (!$block || empty($block['section_type'])) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Block not found or not a section block']));
        }

        $raw = trim((string) Tools::getValue('section_data', '{}'));
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]));
        }

        $active   = (int) Tools::getValue('active', 0);
        $shopIds  = Tools::getValue('shop_ids', []);

        HbEditorBlock::update($idBlock, [
            'active'       => $active,
            'section_data' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
        ]);

        if ($shopIds) {
            HbEditorBlock::setShops($idBlock, (array) $shopIds);
        }

        $this->ajaxDie(json_encode(['success' => true]));
    }

    /* ── AJAX: save brands configuration ────────────────────────────────── */

    public function ajaxProcessSaveBrands(): void
    {
        Configuration::updateValue('HBE_BRANDS_ENABLED', (int) Tools::getValue('enabled', 0));
        $this->saveLocalizedFromForm('HBE_BRANDS_TITLE', Tools::getValue('HBE_BRANDS_TITLE', ''));

        $response = ['success' => true];
        $uploadDir = _PS_IMG_DIR_ . 'hb_editor/';

        for ($i = 1; $i <= 8; $i++) {
            // Custom logo upload (validated + WebP variant), replaces previous file
            $fileKey = 'HBE_BRANDS_IMG_' . $i;
            $newName = $this->processImageUpload($fileKey, 'brand_' . $i);
            if ($newName !== null) {
                $old = (string) Configuration::get($fileKey);
                if ($old && $old !== $newName && is_file($uploadDir . $old)) {
                    @unlink($uploadDir . $old);
                    $oldWebp = preg_replace('/\.[^.]+$/', '.webp', $uploadDir . $old);
                    if (is_string($oldWebp) && is_file($oldWebp)) {
                        @unlink($oldWebp);
                    }
                }
                Configuration::updateValue($fileKey, $newName);
                $response['img_url_' . $i] = __PS_BASE_URI__ . 'img/hb_editor/' . $newName;
            }

            // Manufacturer association + custom link + custom alt/name
            Configuration::updateValue('HBE_BRANDS_MANU_' . $i, (int) Tools::getValue('HBE_BRANDS_MANU_' . $i, 0));
            Configuration::updateValue('HBE_BRANDS_LINK_' . $i, trim((string) Tools::getValue('HBE_BRANDS_LINK_' . $i, '')));
            $this->saveLocalizedFromForm('HBE_BRANDS_ALT_' . $i, Tools::getValue('HBE_BRANDS_ALT_' . $i, ''));
        }

        $this->ajaxDie(json_encode($response));
    }

    /* ── AJAX: reorder blocks ────────────────────────────────────────────── */

    public function ajaxProcessReorder(): void
    {
        $hookName = trim((string) Tools::getValue('hook_name', ''));
        $ids = Tools::getValue('ids', []);
        if (!is_array($ids)) {
            $this->ajaxDie(json_encode(['success' => false]));
        }

        if ($hookName === 'displayHome') {
            // Mixed IDs: static slugs (infobar, imghero, cols3, tagline, katcols) + numeric block IDs + module_NAME
            $staticSlugs = ['infobar', 'infobar2', 'imghero', 'imghero2', 'cols3', 'cols3desc', 'tagline', 'katcols', 'splitblock', 'icons4', 'brands'];
            $order = [];
            $blockPositions = [];
            $blockPos = 0;
            foreach (array_values($ids) as $id) {
                $id = (string) $id;
                if (in_array($id, $staticSlugs, true)) {
                    $order[] = $id;
                } elseif (ctype_digit($id)) {
                    $order[] = $id;
                    $blockPositions[] = ['id_block' => (int) $id, 'position' => $blockPos++];
                } elseif (strncmp($id, 'module_', 7) === 0) {
                    // Managed module entry — preserve as-is
                    $modName = preg_replace('/[^a-zA-Z0-9_]/', '', substr($id, 7));
                    if ($modName !== '') {
                        $order[] = 'module_' . $modName;
                    }
                }
            }
            Configuration::updateValue('HBE_HOME_ORDER', implode(',', $order));
            if ($blockPositions) {
                HbEditorBlock::updatePositions($blockPositions);
            }
        } else {
            $positions = [];
            foreach (array_values($ids) as $pos => $id) {
                $positions[] = ['id_block' => (int) $id, 'position' => $pos];
            }
            HbEditorBlock::updatePositions($positions);
        }
        $this->ajaxDie(json_encode(['success' => true]));
    }

    /* ── AJAX: toggle active ─────────────────────────────────────────────── */

    public function ajaxProcessToggleActive(): void
    {
        $idBlock = (int) Tools::getValue('id_block');
        $active  = (bool) Tools::getValue('active');
        $ok = HbEditorBlock::toggleActive($idBlock, $active);
        $this->ajaxDie(json_encode(['success' => $ok]));
    }

    /* ── AJAX: upload image ──────────────────────────────────────────────── */

    public function ajaxProcessUploadImage(): void
    {
        $idBlock = (int) Tools::getValue('id_block');
        $side    = Tools::getValue('side'); // 'desktop' or 'mobile'

        if (!$idBlock || !in_array($side, ['desktop', 'mobile'], true)) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Invalid params']));
        }
        if (empty($_FILES['image']['name'])) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'No file']));
        }

        try {
            $filename = $this->module->uploadImage($idBlock, $side, $_FILES['image']);
        } catch (RuntimeException $e) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }

        // Persist filename
        $field = 'image_' . $side;
        HbEditorBlock::update($idBlock, [$field => $filename]);

        $this->ajaxDie(json_encode([
            'success'  => true,
            'filename' => $filename,
            'url'      => _PS_IMG_ . 'hb_editor/' . $filename,
        ]));
    }

    /* ── AJAX: delete image ──────────────────────────────────────────────── */

    public function ajaxProcessDeleteImage(): void
    {
        $idBlock = (int) Tools::getValue('id_block');
        $side    = Tools::getValue('side');

        if (!$idBlock || !in_array($side, ['desktop', 'mobile'], true)) {
            $this->ajaxDie(json_encode(['success' => false]));
        }

        $block = HbEditorBlock::getById($idBlock);
        if ($block) {
            $field  = 'image_' . $side;
            $imgDir = _PS_IMG_DIR_ . 'hb_editor/';
            if (!empty($block[$field]) && is_file($imgDir . $block[$field])) {
                @unlink($imgDir . $block[$field]);
            }
        }

        HbEditorBlock::update($idBlock, ['image_' . $side => '']);
        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveTopBar(): void
    {
        $enabled = (int) Tools::getValue('enabled', 0);
        $textRaw = Tools::getValue('text', '');
        $urlRaw  = Tools::getValue('url', '');

        // Validate: when enabled, at least one language must have non-empty text.
        if ($enabled) {
            $hasText = false;
            $check = is_array($textRaw) ? $textRaw : [$textRaw];
            foreach ($check as $v) {
                if (trim((string) $v) !== '') { $hasText = true; break; }
            }
            if (!$hasText) {
                $this->ajaxDie(json_encode(['success' => false, 'error' => 'Text is required when enabled']));
            }
        }

        Configuration::updateValue('HBE_TOPBAR_ENABLED', $enabled);
        $this->saveLocalizedFromForm('HBE_TOPBAR_TEXT', $textRaw);
        $this->saveLocalizedFromForm('HBE_TOPBAR_URL',  $urlRaw, true);
        $this->saveLocalizedFromForm('HBE_TOPBAR_LINK_TEXT', Tools::getValue('link_text', ''));

        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveInfoBar(): void
    {
        $enabled = (int) Tools::getValue('enabled', 0);
        $textRaw = Tools::getValue('text', '');
        $urlRaw  = Tools::getValue('url', '');
        $bg      = trim((string) Tools::getValue('bg', '#222222'));
        $color   = trim((string) Tools::getValue('color', '#ffffff'));

        if ($enabled) {
            $hasText = false;
            $check = is_array($textRaw) ? $textRaw : [$textRaw];
            foreach ($check as $v) {
                if (trim((string) $v) !== '') { $hasText = true; break; }
            }
            if (!$hasText) {
                $this->ajaxDie(json_encode(['success' => false, 'error' => 'Text is required when enabled']));
            }
        }
        // Validate colours (must be #rrggbb or empty)
        if ($bg !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $bg)) {
            $bg = '#222222';
        }
        if ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#ffffff';
        }

        Configuration::updateValue('HBE_INFOBAR_ENABLED', $enabled);
        $this->saveLocalizedFromForm('HBE_INFOBAR_TEXT', $textRaw);
        $this->saveLocalizedFromForm('HBE_INFOBAR_URL',  $urlRaw, true);
        $this->saveLocalizedFromForm('HBE_INFOBAR_LINK_TEXT', Tools::getValue('link_text', ''));
        Configuration::updateValue('HBE_INFOBAR_BG', $bg);
        Configuration::updateValue('HBE_INFOBAR_COLOR', $color);

        // Register displayHome hook for existing installs (no-op if already registered)
        $this->module->ensureHookRegistered('displayHome');

        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveInfoBar2(): void
    {
        $enabled = (int) Tools::getValue('enabled', 0);
        $textRaw = Tools::getValue('text', '');
        $urlRaw  = Tools::getValue('url', '');
        $bg      = trim((string) Tools::getValue('bg', '#222222'));
        $color   = trim((string) Tools::getValue('color', '#ffffff'));

        if ($enabled) {
            $hasText = false;
            $check = is_array($textRaw) ? $textRaw : [$textRaw];
            foreach ($check as $v) {
                if (trim((string) $v) !== '') { $hasText = true; break; }
            }
            if (!$hasText) {
                $this->ajaxDie(json_encode(['success' => false, 'error' => 'Text is required when enabled']));
            }
        }
        if ($bg !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $bg)) {
            $bg = '#222222';
        }
        if ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#ffffff';
        }

        Configuration::updateValue('HBE_INFOBAR2_ENABLED', $enabled);
        $this->saveLocalizedFromForm('HBE_INFOBAR2_TEXT', $textRaw);
        $this->saveLocalizedFromForm('HBE_INFOBAR2_URL',  $urlRaw, true);
        $this->saveLocalizedFromForm('HBE_INFOBAR2_LINK_TEXT', Tools::getValue('link_text', ''));
        Configuration::updateValue('HBE_INFOBAR2_BG', $bg);
        Configuration::updateValue('HBE_INFOBAR2_COLOR', $color);

        $this->module->ensureHookRegistered('displayHome');

        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveHeaderToggles(): void
    {
        Configuration::updateValue('HBE_HIDE_CURRENCY_DESKTOP', (int) Tools::getValue('hide_currency_desktop', 0));
        Configuration::updateValue('HBE_HIDE_CURRENCY_MOBILE',  (int) Tools::getValue('hide_currency_mobile', 0));
        Configuration::updateValue('HBE_HIDE_LANGUAGE_DESKTOP', (int) Tools::getValue('hide_language_desktop', 0));
        Configuration::updateValue('HBE_HIDE_LANGUAGE_MOBILE',  (int) Tools::getValue('hide_language_mobile', 0));
        Configuration::updateValue('HBE_HIDE_QUICKVIEW',         (int) Tools::getValue('hide_quickview', 0));
        $this->ajaxDie(json_encode(['success' => true]));
    }

    /**
     * Saves the cart-preview toggles. These write the same configuration keys
     * the ps_shoppingcart module reads to render the hover preview and the
     * add-to-cart modal replacement.
     */
    public function ajaxProcessSaveCartSettings(): void
    {
        Configuration::updateValue('PS_BLOCK_CART_HOVER',         (int) Tools::getValue('cart_hover', 0));
        Configuration::updateValue('PS_BLOCK_CART_PREVIEW_MODAL', (int) Tools::getValue('cart_preview_modal', 0));

        // Manual free-shipping threshold override (0 = use the shop setting). Accept comma decimals.
        $threshold = (float) str_replace(',', '.', (string) Tools::getValue('cart_free_shipping_threshold', 0));
        Configuration::updateValue('HBE_CART_FREE_SHIPPING_THRESHOLD', $threshold > 0 ? $threshold : 0);

        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveCarouselHeaders(): void
    {
        $allowed = ['np', 'bs', 'cp'];
        foreach ($allowed as $key) {
            $prefix = 'HBE_' . strtoupper($key);
            $this->saveLocalizedFromForm($prefix . '_TITLE',     Tools::getValue($key . '_title', ''));
            $this->saveLocalizedFromForm($prefix . '_TEXT',      Tools::getValue($key . '_text', ''));
            $this->saveLocalizedFromForm($prefix . '_LINK_TEXT', Tools::getValue($key . '_link_text', ''));
            $this->saveLocalizedFromForm($prefix . '_LINK_URL',  Tools::getValue($key . '_link_url', ''), true);
        }
        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveImgHero(): void
    {
        $enabled  = (int) Tools::getValue('enabled', 0);
        $titleRaw   = Tools::getValue('title', '');
        $descRaw    = Tools::getValue('desc', '');
        $ctaTextRaw = Tools::getValue('cta_text', '');
        $ctaUrlRaw  = Tools::getValue('cta_url', '');

        $mlImages = (int) Tools::getValue('ml_images', 0);
        Configuration::updateValue('HBE_IMGHERO_IMAGE_ML', $mlImages);

        $this->saveLocalizedImage('HBE_IMGHERO_IMAGE', 'image', 'imghero', (bool) $mlImages);
        $this->handleMobileImage('HBE_IMGHERO_IMAGE', 'image', 'imghero_mobile', (bool) $mlImages);
        $currentImage = (string) Configuration::get('HBE_IMGHERO_IMAGE');

        Configuration::updateValue('HBE_IMGHERO_ENABLED',  $enabled);
        $this->saveLocalizedFromForm('HBE_IMGHERO_TITLE',    $titleRaw);
        $this->saveLocalizedFromForm('HBE_IMGHERO_DESC',     $descRaw);
        $this->saveLocalizedFromForm('HBE_IMGHERO_CTA_TEXT', $ctaTextRaw);
        $this->saveLocalizedFromForm('HBE_IMGHERO_CTA_URL',  $ctaUrlRaw, true);

        $this->ajaxDie(json_encode([
            'success'   => true,
            'img_url'   => $currentImage ? _PS_IMG_ . 'hb_editor/' . $currentImage : '',
            'img_name'  => $currentImage,
        ]));
    }

    public function ajaxProcessDeleteImgHeroImage(): void
    {
        $idLang = max(0, (int) Tools::getValue('lang_id_target', 0));
        $key = Tools::getValue('variant') === 'mobile' ? 'HBE_IMGHERO_IMAGE_MOBILE' : 'HBE_IMGHERO_IMAGE';
        $this->deleteLocalizedImage($key, $idLang);
        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveImgHero2(): void
    {
        $enabled  = (int) Tools::getValue('enabled', 0);
        $titleRaw   = Tools::getValue('title', '');
        $descRaw    = Tools::getValue('desc', '');
        $ctaTextRaw = Tools::getValue('cta_text', '');
        $ctaUrlRaw  = Tools::getValue('cta_url', '');

        $mlImages = (int) Tools::getValue('ml_images', 0);
        Configuration::updateValue('HBE_IMGHERO2_IMAGE_ML', $mlImages);

        $this->saveLocalizedImage('HBE_IMGHERO2_IMAGE', 'image', 'imghero2', (bool) $mlImages);
        $this->handleMobileImage('HBE_IMGHERO2_IMAGE', 'image', 'imghero2_mobile', (bool) $mlImages);
        $currentImage = (string) Configuration::get('HBE_IMGHERO2_IMAGE');

        Configuration::updateValue('HBE_IMGHERO2_ENABLED',  $enabled);
        $this->saveLocalizedFromForm('HBE_IMGHERO2_TITLE',    $titleRaw);
        $this->saveLocalizedFromForm('HBE_IMGHERO2_DESC',     $descRaw);
        $this->saveLocalizedFromForm('HBE_IMGHERO2_CTA_TEXT', $ctaTextRaw);
        $this->saveLocalizedFromForm('HBE_IMGHERO2_CTA_URL',  $ctaUrlRaw, true);

        $this->ajaxDie(json_encode([
            'success'  => true,
            'img_url'  => $currentImage ? __PS_BASE_URI__ . 'img/hb_editor/' . $currentImage : '',
            'img_name' => $currentImage,
        ]));
    }

    public function ajaxProcessDeleteImgHero2Image(): void
    {
        $idLang = max(0, (int) Tools::getValue('lang_id_target', 0));
        $key = Tools::getValue('variant') === 'mobile' ? 'HBE_IMGHERO2_IMAGE_MOBILE' : 'HBE_IMGHERO2_IMAGE';
        $this->deleteLocalizedImage($key, $idLang);
        $this->ajaxDie(json_encode(['success' => true]));
    }

    /* ── AJAX: add module to HBE management ──────────────────────────────── */

    public function ajaxProcessAddManagedModule(): void
    {
        $modName = preg_replace('/[^a-zA-Z0-9_]/', '', trim((string) Tools::getValue('module_name', '')));
        if ($modName === '' || $modName === 'hummingbird_editor') {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Invalid module name']));
        }

        $mod = Module::getInstanceByName($modName);
        if (!$mod) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Module not found']));
        }

        // Unregister from displayHome — HBE will render it in order
        $mod->unregisterHook('displayHome');

        // Add to managed list
        $current = array_values(array_filter(array_map(
            'trim', explode(',', (string)(Configuration::get('HBE_MANAGED_MODULES') ?: ''))
        )));
        if (!in_array($modName, $current, true)) {
            $current[] = $modName;
            Configuration::updateValue('HBE_MANAGED_MODULES', implode(',', $current));
        }

        // Append module_NAME to the end of the home order
        $orderRaw = (string)(Configuration::get('HBE_HOME_ORDER') ?: 'infobar,imghero,cols3,tagline');
        $orderParts = array_filter(array_map('trim', explode(',', $orderRaw)));
        $key = 'module_' . $modName;
        if (!in_array($key, $orderParts, true)) {
            $orderParts[] = $key;
            Configuration::updateValue('HBE_HOME_ORDER', implode(',', $orderParts));
        }

        $this->ajaxDie(json_encode(['success' => true]));
    }

    /* ── AJAX: release module back to PS hook system ─────────────────────── */

    public function ajaxProcessReleaseManagedModule(): void
    {
        $modName = preg_replace('/[^a-zA-Z0-9_]/', '', trim((string) Tools::getValue('module_name', '')));
        if ($modName === '') {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Invalid module name']));
        }

        // Re-register to displayHome
        $mod = Module::getInstanceByName($modName);
        if ($mod) {
            $mod->registerHook('displayHome');
        }

        // Remove from managed list
        $current = array_values(array_filter(array_map(
            'trim', explode(',', (string)(Configuration::get('HBE_MANAGED_MODULES') ?: ''))
        )));
        $current = array_values(array_filter($current, fn($n) => $n !== $modName));
        Configuration::updateValue('HBE_MANAGED_MODULES', implode(',', $current));

        // Remove module_NAME from home order
        $orderRaw = (string)(Configuration::get('HBE_HOME_ORDER') ?: '');
        $orderParts = array_filter(array_map('trim', explode(',', $orderRaw)));
        $orderParts = array_values(array_filter($orderParts, fn($e) => $e !== 'module_' . $modName));
        Configuration::updateValue('HBE_HOME_ORDER', implode(',', $orderParts));

        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveCols3(): void
    {
        $enabled = (int) Tools::getValue('enabled', 0);
        Configuration::updateValue('HBE_COLS3_ENABLED', $enabled);
        for ($i = 1; $i <= 3; $i++) {
            $this->saveLocalizedFromForm('HBE_COLS3_TEXT_' . $i, Tools::getValue('text_' . $i, ''));
            $this->saveLocalizedFromForm('HBE_COLS3_URL_'  . $i, Tools::getValue('url_'  . $i, ''), true);
        }
        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveCols3Desc(): void
    {
        $enabled = (int) Tools::getValue('enabled', 0);
        Configuration::updateValue('HBE_COLS3D_ENABLED', $enabled);
        for ($i = 1; $i <= 3; $i++) {
            $this->saveLocalizedFromForm('HBE_COLS3D_TITLE_' . $i, Tools::getValue('title_' . $i, ''));
            $this->saveLocalizedFromForm('HBE_COLS3D_DESC_'  . $i, Tools::getValue('desc_'  . $i, ''));
            $this->saveLocalizedFromForm('HBE_COLS3D_URL_'   . $i, Tools::getValue('url_'   . $i, ''), true);
        }
        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveTagline(): void
    {
        $enabled   = (int) Tools::getValue('enabled', 0);
        $textRaw     = Tools::getValue('text', []);
        $linkTextRaw = Tools::getValue('link_text', []);
        $linkUrlRaw  = Tools::getValue('link_url', []);

        Configuration::updateValue('HBE_TAGLINE_ENABLED', $enabled);

        // Backward-compat: a single string (old form) also accepted.
        if (!is_array($textRaw))     { $textRaw     = [$this->getActiveLangId() => (string) $textRaw]; }
        if (!is_array($linkTextRaw)) { $linkTextRaw = [$this->getActiveLangId() => (string) $linkTextRaw]; }
        if (!is_array($linkUrlRaw))  { $linkUrlRaw  = [$this->getActiveLangId() => (string) $linkUrlRaw]; }

        $languages = Language::getLanguages(true);
        $textVals = $linkTextVals = $linkUrlVals = [];
        foreach ($languages as $lang) {
            $id = (int) $lang['id_lang'];
            $textVals[$id]     = trim((string) ($textRaw[$id] ?? ''));
            $linkTextVals[$id] = trim((string) ($linkTextRaw[$id] ?? ''));
            $url = trim((string) ($linkUrlRaw[$id] ?? ''));
            if ($url !== '' && !preg_match('#^https?://#i', $url) && strpos($url, '/') !== 0) {
                $url = 'https://' . $url;
            }
            $linkUrlVals[$id] = $url;
        }

        Configuration::updateValue('HBE_TAGLINE_TEXT',      $textVals,     true);
        Configuration::updateValue('HBE_TAGLINE_LINK_TEXT', $linkTextVals, true);
        Configuration::updateValue('HBE_TAGLINE_LINK_URL',  $linkUrlVals,  true);

        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveKatcols(): void
    {
        $enabled     = (int) Tools::getValue('enabled', 0);

        $titleRaw       = Tools::getValue('title', []);
        $hdrTextRaw     = Tools::getValue('hdr_text', []);
        $hdrLinkTextRaw = Tools::getValue('hdr_link_text', []);
        $hdrUrlRaw      = Tools::getValue('hdr_url', []);
        $lCaptionRaw    = Tools::getValue('l_caption', []);
        $lUrlRaw        = Tools::getValue('l_url', []);
        $rCaptionRaw    = Tools::getValue('r_caption', []);
        $rUrlRaw        = Tools::getValue('r_url', []);

        $activeLang = $this->getActiveLangId();
        $toArr = static function ($v) use ($activeLang): array {
            if (is_array($v)) { return $v; }
            return [$activeLang => (string) $v];
        };
        $titleRaw       = $toArr($titleRaw);
        $hdrTextRaw     = $toArr($hdrTextRaw);
        $hdrLinkTextRaw = $toArr($hdrLinkTextRaw);
        $hdrUrlRaw      = $toArr($hdrUrlRaw);
        $lCaptionRaw    = $toArr($lCaptionRaw);
        $lUrlRaw        = $toArr($lUrlRaw);
        $rCaptionRaw    = $toArr($rCaptionRaw);
        $rUrlRaw        = $toArr($rUrlRaw);

        $sanitizeUrl = static function (string $u): string {
            if ($u !== '' && !preg_match('#^https?://#i', $u) && strpos($u, '/') !== 0) {
                $u = 'https://' . $u;
            }
            return $u;
        };

        $languages = Language::getLanguages(true);
        $titleVals = $hdrTextVals = $hdrLinkTextVals = $hdrUrlVals
            = $lCaptionVals = $lUrlVals = $rCaptionVals = $rUrlVals = [];
        foreach ($languages as $lang) {
            $id = (int) $lang['id_lang'];
            $titleVals[$id]       = trim((string) ($titleRaw[$id] ?? ''));
            $hdrTextVals[$id]     = trim((string) ($hdrTextRaw[$id] ?? ''));
            $hdrLinkTextVals[$id] = trim((string) ($hdrLinkTextRaw[$id] ?? ''));
            $hdrUrlVals[$id]      = $sanitizeUrl(trim((string) ($hdrUrlRaw[$id] ?? '')));
            $lCaptionVals[$id]    = trim((string) ($lCaptionRaw[$id] ?? ''));
            $lUrlVals[$id]        = $sanitizeUrl(trim((string) ($lUrlRaw[$id] ?? '')));
            $rCaptionVals[$id]    = trim((string) ($rCaptionRaw[$id] ?? ''));
            $rUrlVals[$id]        = $sanitizeUrl(trim((string) ($rUrlRaw[$id] ?? '')));
        }

        $response = ['success' => true];
        $mlImages = (int) Tools::getValue('ml_images', 0);
        Configuration::updateValue('HBE_KATCOLS_IMAGE_ML', $mlImages);

        $this->saveLocalizedImage('HBE_KATCOLS_L_IMAGE', 'l_image', 'katcols_l', (bool) $mlImages);
        $this->saveLocalizedImage('HBE_KATCOLS_R_IMAGE', 'r_image', 'katcols_r', (bool) $mlImages);
        $this->handleMobileImage('HBE_KATCOLS_L_IMAGE', 'l_image', 'katcols_l_mobile', (bool) $mlImages);
        $this->handleMobileImage('HBE_KATCOLS_R_IMAGE', 'r_image', 'katcols_r_mobile', (bool) $mlImages);
        $lImg = (string) Configuration::get('HBE_KATCOLS_L_IMAGE');
        $rImg = (string) Configuration::get('HBE_KATCOLS_R_IMAGE');
        if ($lImg) { $response['l_img_url'] = __PS_BASE_URI__ . 'img/hb_editor/' . $lImg; }
        if ($rImg) { $response['r_img_url'] = __PS_BASE_URI__ . 'img/hb_editor/' . $rImg; }

        Configuration::updateValue('HBE_KATCOLS_ENABLED', $enabled);
        Configuration::updateValue('HBE_KATCOLS_TITLE',         $titleVals);
        Configuration::updateValue('HBE_KATCOLS_HDR_TEXT',      $hdrTextVals);
        Configuration::updateValue('HBE_KATCOLS_HDR_LINK_TEXT', $hdrLinkTextVals);
        Configuration::updateValue('HBE_KATCOLS_HDR_URL',       $hdrUrlVals);
        Configuration::updateValue('HBE_KATCOLS_L_CAPTION',     $lCaptionVals);
        Configuration::updateValue('HBE_KATCOLS_L_URL',         $lUrlVals);
        Configuration::updateValue('HBE_KATCOLS_R_CAPTION',     $rCaptionVals);
        Configuration::updateValue('HBE_KATCOLS_R_URL',         $rUrlVals);
        // Keep the non-language base rows in sync with the active language
        Configuration::updateValue('HBE_KATCOLS_TITLE',         $titleVals[$activeLang] ?? '');
        Configuration::updateValue('HBE_KATCOLS_HDR_TEXT',      $hdrTextVals[$activeLang] ?? '');
        Configuration::updateValue('HBE_KATCOLS_HDR_LINK_TEXT', $hdrLinkTextVals[$activeLang] ?? '');
        Configuration::updateValue('HBE_KATCOLS_HDR_URL',       $hdrUrlVals[$activeLang] ?? '');
        Configuration::updateValue('HBE_KATCOLS_L_CAPTION',     $lCaptionVals[$activeLang] ?? '');
        Configuration::updateValue('HBE_KATCOLS_L_URL',         $lUrlVals[$activeLang] ?? '');
        Configuration::updateValue('HBE_KATCOLS_R_CAPTION',     $rCaptionVals[$activeLang] ?? '');
        Configuration::updateValue('HBE_KATCOLS_R_URL',         $rUrlVals[$activeLang] ?? '');

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessDeleteKatcolsImage(): void
    {
        $side = Tools::getValue('side') === 'r' ? 'r' : 'l';
        $key  = $side === 'l' ? 'HBE_KATCOLS_L_IMAGE' : 'HBE_KATCOLS_R_IMAGE';
        if (Tools::getValue('variant') === 'mobile') { $key .= '_MOBILE'; }
        $idLang = max(0, (int) Tools::getValue('lang_id_target', 0));
        $this->deleteLocalizedImage($key, $idLang);
        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveSplitBlock(): void
    {
        $enabled    = (int) Tools::getValue('enabled', 0);
        $titleRaw   = Tools::getValue('title', '');
        $descRaw    = Tools::getValue('desc', '');
        $ctaTextRaw = Tools::getValue('cta_text', '');
        $ctaUrlRaw  = Tools::getValue('cta_url', '');

        $response = ['success' => true];
        $mlImages = (int) Tools::getValue('ml_images', 0);
        Configuration::updateValue('HBE_SPLITBLOCK_IMAGE_ML', $mlImages);

        $this->saveLocalizedImage('HBE_SPLITBLOCK_M_IMAGE', 'm_image', 'splitblock_m', (bool) $mlImages);
        $this->saveLocalizedImage('HBE_SPLITBLOCK_R_IMAGE', 'r_image', 'splitblock_r', (bool) $mlImages);
        $this->handleMobileImage('HBE_SPLITBLOCK_M_IMAGE', 'm_image', 'splitblock_m_mobile', (bool) $mlImages);
        $this->handleMobileImage('HBE_SPLITBLOCK_R_IMAGE', 'r_image', 'splitblock_r_mobile', (bool) $mlImages);
        $mImg = (string) Configuration::get('HBE_SPLITBLOCK_M_IMAGE');
        $rImg = (string) Configuration::get('HBE_SPLITBLOCK_R_IMAGE');
        if ($mImg) { $response['m_img_url'] = __PS_BASE_URI__ . 'img/hb_editor/' . $mImg; }
        if ($rImg) { $response['r_img_url'] = __PS_BASE_URI__ . 'img/hb_editor/' . $rImg; }

        Configuration::updateValue('HBE_SPLITBLOCK_ENABLED',  $enabled);
        $this->saveLocalizedFromForm('HBE_SPLITBLOCK_TITLE',    $titleRaw);
        $this->saveLocalizedFromForm('HBE_SPLITBLOCK_DESC',     $descRaw);
        $this->saveLocalizedFromForm('HBE_SPLITBLOCK_CTA_TEXT', $ctaTextRaw);
        $this->saveLocalizedFromForm('HBE_SPLITBLOCK_CTA_URL',  $ctaUrlRaw, true);
        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessDeleteSplitBlockImage(): void
    {
        $side = Tools::getValue('side') === 'r' ? 'r' : 'm';
        $key  = $side === 'm' ? 'HBE_SPLITBLOCK_M_IMAGE' : 'HBE_SPLITBLOCK_R_IMAGE';
        if (Tools::getValue('variant') === 'mobile') { $key .= '_MOBILE'; }
        $idLang = max(0, (int) Tools::getValue('lang_id_target', 0));
        $this->deleteLocalizedImage($key, $idLang);
        $this->ajaxDie(json_encode(['success' => true]));
    }

    public function ajaxProcessSaveIcons4(): void
    {
        $enabled = (int) Tools::getValue('enabled', 0);
        Configuration::updateValue('HBE_ICONS4_ENABLED', $enabled);
        $mlImages = (int) Tools::getValue('ml_images', 0);
        Configuration::updateValue('HBE_ICONS4_IMAGE_ML', $mlImages);

        $response = ['success' => true];

        for ($i = 1; $i <= 4; $i++) {
            $this->saveLocalizedFromForm('HBE_ICONS4_TITLE_' . $i, Tools::getValue('title_' . $i, ''));
            $this->saveLocalizedFromForm('HBE_ICONS4_DESC_'  . $i, Tools::getValue('desc_'  . $i, ''));

            $this->saveLocalizedImage('HBE_ICONS4_IMG_' . $i, 'img_' . $i, 'icons4_' . $i, (bool) $mlImages);
            $this->handleMobileImage('HBE_ICONS4_IMG_' . $i, 'img_' . $i, 'icons4_' . $i . '_mobile', (bool) $mlImages);
            $img = (string) Configuration::get('HBE_ICONS4_IMG_' . $i);
            if ($img) { $response['img_url_' . $i] = __PS_BASE_URI__ . 'img/hb_editor/' . $img; }
        }

        $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessDeleteIcons4Image(): void
    {
        $col = max(1, min(4, (int) Tools::getValue('col', 1)));
        $key = 'HBE_ICONS4_IMG_' . $col;
        if (Tools::getValue('variant') === 'mobile') { $key .= '_MOBILE'; }
        $idLang = max(0, (int) Tools::getValue('lang_id_target', 0));
        $this->deleteLocalizedImage($key, $idLang);
        $this->ajaxDie(json_encode(['success' => true]));
    }

    private function getActiveLangId(): int
    {
        $langId = (int) Tools::getValue('lang_id', (int) $this->context->language->id);
        if ($langId <= 0) {
            $langId = (int) $this->context->language->id;
        }

        return $langId;
    }

    private function getLocalizedConfigValue(string $key): string
    {
        $langId = $this->getActiveLangId();
        $value = Configuration::get($key, $langId);
        if ($value === false || $value === null || $value === '') {
            $value = Configuration::get($key);
        }

        return (string) ($value ?? '');
    }

    /**
     * Returns config value per language indexed by id_lang.
     * Falls back to non-language value if the per-lang one is empty.
     *
     * @param array<int,array<string,mixed>> $languages
     * @return array<int,string>
     */
    private function getConfigPerLang(string $key, array $languages): array
    {
        // No fallback to base row: admin form must show the *actual* per-language value
        // (empty when that lang has not been set yet). The front renderer falls back
        // separately via hbeLocConfig().
        $out = [];
        foreach ($languages as $lang) {
            $id = (int) $lang['id_lang'];
            $val = Configuration::get($key, $id);
            $out[$id] = ($val === false || $val === null) ? '' : (string) $val;
        }
        return $out;
    }

    /**
     * Build {id_lang => URL} array for an image config key. Empty filenames produce empty URLs.
     * Does NOT fall back to base value: per-lang admin UI must distinguish "this lang has no image".
     *
     * @param array<int,array<string,mixed>> $languages
     * @return array<int,string>
     */
    private function getImageUrlsPerLang(string $key, array $languages): array
    {
        $out = [];
        foreach ($languages as $lang) {
            $id = (int) $lang['id_lang'];
            $val = Configuration::get($key, $id);
            $filename = (is_string($val) ? $val : '');
            $out[$id] = $filename !== '' ? __PS_BASE_URI__ . 'img/hb_editor/' . $filename : '';
        }
        return $out;
    }

    /**
     * Build {id_lang => filename} array (no fallback to base).
     *
     * @param array<int,array<string,mixed>> $languages
     * @return array<int,string>
     */
    private function getImageFilenamesPerLang(string $key, array $languages): array
    {
        $out = [];
        foreach ($languages as $lang) {
            $id = (int) $lang['id_lang'];
            $val = Configuration::get($key, $id);
            $out[$id] = is_string($val) ? $val : '';
        }
        return $out;
    }

    private function setLocalizedConfigValue(string $key, string $value): void
    {
        $langId = $this->getActiveLangId();
        $languages = Language::getLanguages(true);
        $values = [];
        foreach ($languages as $lang) {
            $id = (int) $lang['id_lang'];
            $current = Configuration::get($key, $id);
            $values[$id] = $current === false || $current === null ? '' : (string) $current;
        }
        $values[$langId] = $value;
        Configuration::updateValue($key, $values, true);
        // Also keep the non-language base row in sync with the active language so that
        // any code reading via Configuration::get($key) (without id_lang) still sees
        // the latest value in the admin's current language.
        Configuration::updateValue($key, $value);
    }

    /**
     * Save a multi-language form field.
     *
     * Accepts either:
     *   - array<int,string> keyed by id_lang (preferred — multilang form input
     *     name="text[1]", name="text[2]" etc.)
     *   - string (legacy single-value input) — applied to all languages.
     *
     * @param mixed $raw    Tools::getValue() result (array or scalar)
     * @param bool  $isUrl  When true, missing scheme is auto-prepended with https://
     */
    private function saveLocalizedFromForm(string $cfgKey, $raw, bool $isUrl = false): void
    {
        $languages = Language::getLanguages(true);
        $activeLang = $this->getActiveLangId();
        if (!is_array($raw)) {
            $raw = [$activeLang => (string) $raw];
        }
        $values = [];
        $primary = '';
        foreach ($languages as $lang) {
            $id = (int) $lang['id_lang'];
            $v = trim((string) ($raw[$id] ?? ''));
            if ($isUrl && $v !== '' && !preg_match('#^https?://#i', $v) && strpos($v, '/') !== 0) {
                $v = 'https://' . $v;
            }
            $values[$id] = $v;
            if ($id === $activeLang) {
                $primary = $v;
            }
        }
        Configuration::updateValue($cfgKey, $values, true);
        // Keep the base (non-lang) row in sync with the active language value so that
        // any legacy code reading Configuration::get($key) without id_lang still works.
        Configuration::updateValue($cfgKey, $primary);
    }

    /**
     * Process a single uploaded image. Returns the new filename on success, null otherwise.
     */
    private function processImageUpload(string $field, string $prefix): ?string
    {
        if (empty($_FILES[$field]['name']) || !is_uploaded_file($_FILES[$field]['tmp_name'] ?? '')) {
            return null;
        }
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES[$field]['tmp_name']);
        if (!in_array($mime, $allowedMimes, true)) {
            return null;
        }
        $ext = strtolower((string) pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }
        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destDir  = _PS_IMG_DIR_ . 'hb_editor/';
        if (!is_dir($destDir) && !@mkdir($destDir, 0755, true)) {
            return null;
        }
        if (!@move_uploaded_file($_FILES[$field]['tmp_name'], $destDir . $filename)) {
            return null;
        }
        $this->generateWebpVariant($destDir . $filename);
        return $filename;
    }

    /**
     * Generate a sibling .webp file for raster uploads (jpg/png/gif).
     * Silent no-op for SVG, already-webp, or environments without GD WebP support.
     */
    private function generateWebpVariant(string $sourcePath): void
    {
        if (!is_file($sourcePath)) {
            return;
        }
        $ext = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            return;
        }
        if (!function_exists('imagewebp') || !extension_loaded('gd')) {
            return;
        }
        $info = @getimagesize($sourcePath);
        if (!$info) {
            return;
        }
        $img = null;
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($sourcePath);
                if ($img) {
                    imagepalettetotruecolor($img);
                    imagealphablending($img, true);
                    imagesavealpha($img, true);
                }
                break;
            case IMAGETYPE_GIF:
                $img = @imagecreatefromgif($sourcePath);
                break;
        }
        if (!$img) {
            return;
        }
        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $sourcePath);
        @imagewebp($img, $webpPath, 82);
        imagedestroy($img);
    }

    /**
     * Save image config either as single (base row) or per-language depending on $ml.
     * - When $ml=false: process single $_FILES[$baseField] → write same filename to base row + every lang row.
     * - When $ml=true:  for each language, process $_FILES["{$baseField}_lang_{$id}"] separately.
     * Old files no longer referenced anywhere are deleted.
     */
    private function saveLocalizedImage(string $cfgKey, string $baseField, string $prefix, bool $ml): void
    {
        $languages = Language::getLanguages(true);
        $existing  = $this->getConfigPerLang($cfgKey, $languages);
        $baseExisting = (string) Configuration::get($cfgKey);
        $destDir = _PS_IMG_DIR_ . 'hb_editor/';

        $values = [];
        foreach ($languages as $lang) {
            $values[(int) $lang['id_lang']] = (string) ($existing[(int) $lang['id_lang']] ?? '');
        }

        // When switching ml=0 → ml=1, per-language rows may be empty while a
        // base image exists. Seed empty per-language slots from the base value
        // so toggling the multi-language flag (without re-uploading files)
        // does not wipe the saved image on save.
        if ($ml && $baseExisting !== '') {
            foreach ($values as $id => $v) {
                if ($v === '') {
                    $values[$id] = $baseExisting;
                }
            }
        }

        $filesToRemove = [];

        if ($ml) {
            foreach ($languages as $lang) {
                $id = (int) $lang['id_lang'];
                $newName = $this->processImageUpload($baseField . '_lang_' . $id, $prefix);
                if ($newName === null) {
                    continue;
                }
                $old = $values[$id] ?? '';
                if ($old && $old !== $newName) {
                    $filesToRemove[$old] = true;
                }
                $values[$id] = $newName;
            }
        } else {
            $newName = $this->processImageUpload($baseField, $prefix);
            if ($newName !== null) {
                if ($baseExisting && $baseExisting !== $newName) {
                    $filesToRemove[$baseExisting] = true;
                }
                foreach ($values as $id => $v) {
                    if ($v !== '' && $v !== $newName) {
                        $filesToRemove[$v] = true;
                    }
                    $values[$id] = $newName;
                }
                $baseExisting = $newName;
            }
        }

        // Pick base value (first non-empty)
        $base = '';
        foreach ($values as $v) {
            if ($v !== '') {
                $base = $v;
                break;
            }
        }
        if ($base === '' && !$ml) {
            $base = $baseExisting;
        }

        if ($ml) {
            Configuration::updateValue($cfgKey, $values, true);
        } else {
            // In single-image mode the base row is the source of truth.
            // Clear any per-language rows so Configuration::get($key) without
            // id_lang doesn't return an empty default-language value that
            // would shadow the base row.
            $idCfg = (int) Configuration::getIdByName($cfgKey);
            if ($idCfg > 0) {
                \Db::getInstance()->delete('configuration_lang', 'id_configuration = ' . $idCfg);
            }
        }
        Configuration::updateValue($cfgKey, $base);
        if (!$ml) {
            Configuration::loadConfiguration();
        }

        // Delete files no longer referenced by any lang or base
        $stillUsed = array_flip(array_filter(array_merge(array_values($values), [$base])));
        foreach (array_keys($filesToRemove) as $f) {
            if (!isset($stillUsed[$f]) && $f !== '' && is_file($destDir . $f)) {
                @unlink($destDir . $f);
            }
        }
    }

    /**
     * Process the optional mobile variant of an image. When the form posts
     * "<baseField>_mobile_clear=1" the mobile config is cleared (files removed).
     * Otherwise the mobile upload is processed identically to the desktop image,
     * stored under "<cfgKey>_MOBILE" with field names "<baseField>_mobile" /
     * "<baseField>_mobile_lang_<id>".
     */
    private function handleMobileImage(string $cfgKey, string $baseField, string $prefix, bool $ml): void
    {
        $mobileKey   = $cfgKey . '_MOBILE';
        $mobileField = $baseField . '_mobile';

        if ((int) Tools::getValue($baseField . '_mobile_clear', 0) === 1) {
            $this->deleteLocalizedImage($mobileKey, 0);
            return;
        }

        $this->saveLocalizedImage($mobileKey, $mobileField, $prefix, $ml);
    }

    /**
     * Delete one image: either base+all-langs (lang_id=0) or a single language (lang_id>0).
     */
    private function deleteLocalizedImage(string $cfgKey, int $idLang = 0): void
    {
        $languages = Language::getLanguages(true);
        $values    = $this->getConfigPerLang($cfgKey, $languages);
        $baseExisting = (string) Configuration::get($cfgKey);
        $destDir = _PS_IMG_DIR_ . 'hb_editor/';
        $oldFiles = [];

        if ($idLang > 0) {
            $oldFiles[] = (string) ($values[$idLang] ?? '');
            $values[$idLang] = '';
        } else {
            foreach ($values as $v) {
                $oldFiles[] = (string) $v;
            }
            foreach ($values as $id => $_) {
                $values[$id] = '';
            }
            $oldFiles[] = $baseExisting;
            $baseExisting = '';
        }

        // Pick remaining base
        $base = '';
        foreach ($values as $v) {
            if ($v !== '') {
                $base = $v;
                break;
            }
        }
        if ($base === '' && $idLang === 0) {
            // full delete
        }
        Configuration::updateValue($cfgKey, $values, true);
        Configuration::updateValue($cfgKey, $base);

        $stillUsed = array_flip(array_filter(array_merge(array_values($values), [$base])));
        foreach (array_unique(array_filter($oldFiles)) as $f) {
            if (!isset($stillUsed[$f]) && is_file($destDir . $f)) {
                @unlink($destDir . $f);
            }
        }
    }

    private function ajaxDie(string $json): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        die($json);
    }

    /**
     * Import settings from an uploaded XML file (POST multipart, field name "file").
     */
    public function ajaxProcessImportSettings(): void
    {
        if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Brak pliku XML']));
        }
        if (!empty($_FILES['file']['error'])) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Błąd uploadu pliku (' . (int) $_FILES['file']['error'] . ')']));
        }
        // Cap at 32 MB to keep memory sane.
        if (($_FILES['file']['size'] ?? 0) > 32 * 1024 * 1024) {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Plik za duży (max 32 MB)']));
        }
        $xml = (string) @file_get_contents($_FILES['file']['tmp_name']);
        if ($xml === '') {
            $this->ajaxDie(json_encode(['success' => false, 'error' => 'Pusty plik']));
        }
        $purge  = (int) Tools::getValue('purge_blocks', 1) === 1;
        $result = HbEditorTransfer::importXml($xml, $purge);
        $this->ajaxDie(json_encode($result));
    }
}
