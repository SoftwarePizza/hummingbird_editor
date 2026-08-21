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
require_once __DIR__ . '/classes/HbEditorCarouselCache.php';

class Hummingbird_editor extends Module
{
    const IMG_DIR = 'hb_editor/';

    /** Configuration key: page identifiers (CSV) of menu items using the flat submenu layout on desktop. */
    const MENU_FLAT_ITEMS_KEY = 'HBE_MENU_FLAT_ITEMS';

    /** Configuration key: same as MENU_FLAT_ITEMS_KEY but for the mobile drawer. */
    const MENU_FLAT_ITEMS_MOBILE_KEY = 'HBE_MENU_FLAT_ITEMS_MOBILE';

    /**
     * Configuration key: page identifiers (CSV) of menu items rendered as a
     * multi-column link list instead of the tabbed submenu. Meant for branches
     * that are wide and shallow — a tabbed layout leaves the right-hand pane
     * empty for every child that has no children of its own.
     */
    const MENU_COLUMNS_ITEMS_KEY = 'HBE_MENU_COLUMNS_ITEMS';

    /** Configuration key: heading above the childless categories in the column layout. */
    const MENU_COLUMNS_REST_LABEL_KEY = 'HBE_MENU_COLUMNS_REST_LABEL';

    /** Fallback heading when MENU_COLUMNS_REST_LABEL_KEY is blank. */
    const MENU_COLUMNS_REST_LABEL_DEFAULT = 'Pozostałe rodzaje';

    /**
     * Configuration key: page identifiers (CSV) of menu items rendered as a
     * two-pane cascade — sub-categories listed on the left, the hovered one's
     * children on the right. Same problem as the column layout solves, but the
     * opposite trade-off: the panel stays short instead of showing everything
     * at once. Childless children are collected under MENU_COLUMNS_REST_LABEL_KEY
     * as one extra left-hand entry, so no entry ever opens an empty pane.
     * Takes precedence over MENU_COLUMNS_ITEMS_KEY when an id is in both.
     */
    const MENU_CASCADE_ITEMS_KEY = 'HBE_MENU_CASCADE_ITEMS';

    /**
     * Configuration key: menu PATHS (CSV) pruned from the main menu. A path is
     * the chain of page identifiers from the top level down, joined by '>' —
     * e.g. `category-22>category-119`. The category stays live in the shop; it
     * just stops being a menu entry, which ps_mainmenu itself has no setting
     * for (it renders the whole tree under its configured roots). Removing a
     * branch removes what hangs under it.
     *
     * Paths, not bare identifiers, because the same category legitimately
     * appears in two places: „Tkaniny na.." is both a top-level entry and a
     * child of „Tkaniny". Matching on the identifier alone would take out both.
     */
    const MENU_HIDDEN_ITEMS_KEY = 'HBE_MENU_HIDDEN_ITEMS';

    /**
     * Set by the back-office picker before it asks ps_mainmenu for the tree.
     * Without it the picker would receive the ALREADY pruned menu, so a hidden
     * entry would disappear from the form and there would be no way to bring
     * it back — the checkbox that unhides it has to exist.
     */
    public $skipMenuPrune = false;

    /**
     * Configuration key: menu paths (CSV, jak MENU_HIDDEN_ITEMS_KEY) oznaczone
     * jako najczęściej szukane. Sam fakt wyróżnienia jest tu, a jego WYGLĄD
     * osobno — żeby zmiana stylu nie wymagała przeklikiwania listy od nowa.
     */
    const MENU_FEATURED_ITEMS_KEY = 'HBE_MENU_FEATURED_ITEMS';

    /** Configuration key: wariant wyróżnienia — patrz MENU_FEATURED_STYLES. */
    const MENU_FEATURED_STYLE_KEY = 'HBE_MENU_FEATURED_STYLE';

    /** Warianty wyróżnienia: klucz -> etykieta w panelu. */
    const MENU_FEATURED_STYLES = [
        'bold'      => 'Wytłuszczenie — sama nazwa ciemniejsza i grubsza',
        'dot'       => 'Kropka — znacznik przed nazwą',
        'highlight' => 'Zakreślenie — miękkie tło pod nazwą',
    ];

    /** Domyślny wariant, gdy MENU_FEATURED_STYLE_KEY jest pusty lub nieznany. */
    const MENU_FEATURED_STYLE_DEFAULT = 'dot';

    /**
     * Configuration key: menu paths (CSV) spychane na koniec listy, niezależnie
     * od alfabetu. Dla pozycji-śmietników w rodzaju „Inne tkaniny", które
     * alfabetycznie lądują w środku, a znaczeniowo są na końcu.
     */
    const MENU_BOTTOM_ITEMS_KEY = 'HBE_MENU_BOTTOM_ITEMS';

    /**
     * Configuration key: menu paths (CSV) wyciągane na początek listy, przed
     * alfabetem. Lustro MENU_BOTTOM_ITEMS_KEY — dla pozycji, które mają być
     * pierwsze, bo są najważniejsze, a nie dlatego, że tak wypadły w alfabecie.
     */
    const MENU_TOP_ITEMS_KEY = 'HBE_MENU_TOP_ITEMS';

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

    /**
     * Pasek prawny na samym dole stopki (Polityka prywatnosci, Regulamin,
     * GPSR...). Stala liczba slotow, bo tak samo dzialaja logotypy marek i
     * ikony 4-kolumnowe — pusta etykieta znaczy "slot wolny", wiec sklep
     * uzywa tylu pozycji, ile potrzebuje.
     */
    const FOOTER_LINK_SLOTS = 8;

    /**
     * Zestaw startowy paska prawnego: strony CMS, ktore do wersji 1.15.0 byly
     * wpisane na sztywno w szablonie motywu. Klucz = id strony CMS, wartosc =
     * etykieta dokladnie taka, jaka pokazywala stopka przed zmiana (tytul CMS
     * bywa inny — np. "Programy lojalnosciowe" zamiast "Programy i karty").
     * Strony, ktorej w danym sklepie nie ma, po prostu nie zasiewamy.
     */
    const FOOTER_LINK_SEED = [
        2  => 'Polityka prywatności',
        3  => 'Regulamin',
        14 => 'Informacje o RODO',
        25 => 'Programy i karty',
        71 => 'Informacje GPSR',
    ];

    /** Configuration key holding one part ('label'|'url') of a footer legal link. */
    public static function footerLinkKey(int $slot, string $part): string
    {
        return 'HBE_FOOTER_LINK_' . (int) $slot . '_' . strtoupper($part);
    }

    /**
     * Ile pozycji da sie wpisac w jedna kolumne linkow w stopce (ZAKUPY ONLINE,
     * ROSENTHAL...). Kolumny nalezaja do ps_linklist — edytor rusza wylacznie
     * `custom_content`, czyli liste "etykieta + adres" per jezyk.
     */
    const FOOTER_BLOCK_ROWS = 10;

    /** How the free-shipping threshold shown on the cart progress bar is resolved. */
    public const FREE_SHIPPING_MODE_AUTO   = 'auto';
    public const FREE_SHIPPING_MODE_MANUAL = 'manual';
    public const FREE_SHIPPING_MODE_SHOP   = 'shop';
    public const FREE_SHIPPING_MODE_OFF    = 'off';

    /**
     * Kasa — przelaczniki zakladki "Kasa".
     *
     * Wszystkie domyslnie wylaczone (brak klucza = 0), bo kazdy z nich zmienia
     * wyglad albo kolejnosc kroku zamowienia, a tego samego modulu uzywaja
     * sklepy z wlasnymi kasami. Sklep, ktory nic nie wlaczy, dostaje kase
     * dokladnie taka, jaka daje motyw.
     *
     *  SKIN         — nowy wyglad krokow "Przesylka" i "Platnosc" oraz
     *                 podsumowania (views/css/checkout.css).
     *  ONEPAGE      — ukonczone kroki zostaja widoczne pod biezacym. To jest
     *                 dawna regula z pages/_checkout.scss motywu, przeniesiona
     *                 tutaj: pisana byla pod jednostronicowa kase i na sklepie
     *                 bez niej robila stos otwartych sekcji.
     *  TERMS_BOTTOM — zgody (regulamin) tuz nad przyciskiem "Zloz zamowienie",
     *                 zamiast pod lista metod platnosci.
     */
    public const CONF_CHECKOUT_SKIN         = 'HBE_CHECKOUT_SKIN';
    public const CONF_CHECKOUT_ONEPAGE      = 'HBE_CHECKOUT_ONEPAGE';
    public const CONF_CHECKOUT_TERMS_BOTTOM = 'HBE_CHECKOUT_TERMS_BOTTOM';

    /**
     * Przewoznicy, ktorzy sa odbiorem osobistym (lista `id_reference` po
     * przecinku, wybierana w BO -> Hummingbird -> Kasa).
     *
     * Sluzy jednej rzeczy: gdy taka dostawa nic nie kosztuje, zamiast golego
     * "Za darmo!" pokazujemy "Darmowy odbior osobisty" — w liscie przewoznikow,
     * w podsumowaniu zamowienia i w wierszu "Wysylka" w koszyku. PrestaShop nie
     * ma zadnego znacznika "to jest odbior w sklepie", stad recznie wskazana
     * lista; pusta = modul nie rusza zadnej etykiety.
     *
     * Trzymamy `id_reference`, a nie `id_carrier`, bo edycja przewoznika w BO
     * tworzy nowy wiersz z nowym `id_carrier` i tym samym `id_reference` —
     * inaczej ustawienie gubiloby sie przy kazdej zmianie ceny czy strefy.
     */
    public const CONF_PICKUP_CARRIERS = 'HBE_PICKUP_CARRIERS';

    /**
     * Domyslna etykieta darmowego odbioru osobistego w jezykach sklepu.
     *
     * Fraza idzie normalnie przez tlumaczenia modulu (BO -> Tlumaczenia), a ten
     * slownik jest tylko fallbackiem, zeby sklep wielojezyczny nie zostal z
     * angielskim kluczem, dopoki nikt nic nie przetlumaczyl.
     */
    public const PICKUP_LABELS = [
        'pl' => 'Darmowy odbiór osobisty',
        'en' => 'Free store pickup',
        'de' => 'Kostenlose Abholung vor Ort',
        'es' => 'Recogida gratuita en tienda',
        'fr' => 'Retrait gratuit en magasin',
        'it' => 'Ritiro gratuito in negozio',
        'nl' => 'Gratis afhalen in de winkel',
        'cs' => 'Osobní odběr zdarma',
        'da' => 'Gratis afhentning i butikken',
        'hu' => 'Ingyenes személyes átvétel',
        'lt' => 'Nemokamas atsiėmimas parduotuvėje',
        'ro' => 'Ridicare gratuită din magazin',
        'sv' => 'Gratis upphämtning i butik',
        'uk' => 'Безкоштовний самовивіз',
        'lv' => 'Bezmaksas saņemšana veikalā',
        'et' => 'Tasuta kättesaamine kauplusest',
    ];

    /**
     * Zoom na okladce karty produktu (powiekszenie w ramce zdjecia).
     *
     * ZOOM_LEVEL — '0' oznacza naturalna rozdzielczosc zrodla (piksel w piksel,
     * najostrzej); '2', '2.5', '3' skaluja wzgledem szerokosci ramki.
     */
    public const CONF_ZOOM_ENABLED = 'HBE_ZOOM_ENABLED';
    public const CONF_ZOOM_LEVEL   = 'HBE_ZOOM_LEVEL';

    /**
     * Podpowiedz stanu magazynowego pod przyciskiem koszyka.
     *
     * STOCK_HINT_THRESHOLD — ile jednostek (na izpolu: metrow) moze zostac na
     * belce, zeby zamiast zwyklej informacji o stanie pokazac zachete do
     * zabrania calosci. Blok renderuje motyw
     * (catalog/_partials/product-add-to-cart.tpl), tu jest tylko sterowanie.
     */
    public const CONF_STOCK_HINT_ENABLED   = 'HBE_STOCK_HINT_ENABLED';
    public const CONF_STOCK_HINT_THRESHOLD = 'HBE_STOCK_HINT_THRESHOLD';

    /**
     * Rabat za zabranie calego dostepnego stanu produktu.
     *
     * Naliczamy go wylacznie na pozycjach koszyka (hook
     * actionProductPriceCalculation dostaje cene przez referencje) — ceny
     * katalogowe na listingu i karcie zostaja nietkniete, bo rabat nalezy sie
     * za ILOSC, a nie za sam produkt. Wyjatkiem sa produkty sprzedawane ponad
     * stan: tam "calosc" nie istnieje i rabat sie nie pojawia.
     */
    public const CONF_ALLSTOCK_DISCOUNT_ENABLED   = 'HBE_ALLSTOCK_DISCOUNT_ENABLED';
    public const CONF_ALLSTOCK_DISCOUNT_RATE      = 'HBE_ALLSTOCK_DISCOUNT_RATE';
    public const CONF_ALLSTOCK_DISCOUNT_RATE_SALE = 'HBE_ALLSTOCK_DISCOUNT_RATE_SALE';

    /** Tolerancja przy porownywaniu ilosci ulamkowych (pproperties: krok 0,1). */
    private const ALLSTOCK_EPSILON = 0.000001;

    /** Ilosci pozycji koszyka w obrebie zadania (klucz: koszyk-produkt-wariant). */
    private static $allStockCartQuantities = [];

    /** Czy cart_product ma kolumne quantity_fractional (pproperties). */
    private static $allStockFractionalColumn = null;

    /**
     * Ceny jednostkowe SPRZED rabatu za calosc, zapamietane w hooku cenowym
     * (klucz: koszyk-produkt-wariant-podatek). Koszyk pokazuje z nich cene
     * przekreslona i laczna oszczednosc na pozycji.
     */
    private static $allStockPricesBefore = [];

    /**
     * Gorna waga oryginalu zdjecia, po ktory zoom moze siegnac.
     *
     * Miniatury PrestaShopa skaluja sie "do zmieszczenia w kwadracie", wiec przy
     * zdjeciach pionowych (izpol: 2:3) typ 1440x1440 daje realnie 960 px
     * szerokosci — mniej wiecej tyle, ile ma ramka okladki, czyli zadnego zoomu.
     * Powiekszenie musi wiec brac oryginal. Limit odcina pojedyncze wielkoludy
     * (na izpolu do 19 MB); 98% katalogu miesci sie ponizej.
     */
    public const ZOOM_ORIGINAL_MAX_BYTES = 2097152;

    /**
     * Wyglad miniatury produktu (kafla) — jedno miejsce na cala witryne.
     *
     * Motyw trzyma zdjecie miniatury w 40 px paddingu, karuzele skleja bez
     * odstepu i rozdziela kreska 1 px, a listing dostaje kolumny z ukladu
     * strony. Kazdy sklep chcial to inaczej i konczylo sie recznym CSS-em
     * w motywie, ktory ginal przy przebudowie assetow. Te ustawienia opisuja
     * ten sam wyglad danymi, a `getMiniatureCss()` sklada z nich arkusz.
     *
     * Wartosci domyslne = wyglad motywu, a przy `enabled = 0` nie leci ani
     * jedna regula, wiec swiezo zainstalowany modul niczego nie zmienia.
     *
     * Klucz konfiguracji: HBE_MINI_<NAZWA>, np. HBE_MINI_CAR_DESKTOP.
     */
    public const MINIATURE_DEFAULTS = [
        'enabled'     => 0,   // 0 = nie ruszaj wygladu z motywu
        'pad'         => 40,  // px paddingu wokol zdjecia (motyw: 40)
        'ratio'       => '',  // '' = proporcja pliku; inaczej np. '2/3'
        'fit'         => 'cover',
        'radius'      => 0,   // px zaokraglenia kadru
        'gap'         => 24,  // px odstepu miedzy kaflami (motyw: 1,5 rem)
        'car_desktop' => 3,   // kafli widocznych w karuzeli od 768 px
        'car_mobile'  => 1,   // kafli widocznych ponizej 768 px
        'car_border'  => 1,   // kreska 1 px wokol kafla karuzeli
        'list_cols'   => 0,   // 0 = kolumny listingu z motywu, inaczej 2..6
        'zoom'        => 1,   // powiekszenie zdjecia po najechaniu
    ];

    /** Dozwolone proporcje kadru (klucz = wartosc CSS `aspect-ratio`). */
    public const MINIATURE_RATIOS = ['', '1/1', '4/5', '3/4', '2/3', '4/3', '16/9'];

    /**
     * Gotowe zestawy do wyboru w BO. Nie zapisuje sie nazwy zestawu — zapisuja
     * sie same wartosci, a panel pokazuje, ktory zestaw im odpowiada. Dzieki
     * temu "wlasne" nie jest osobnym trybem, tylko brakiem trafienia.
     */
    public const MINIATURE_PRESETS = [
        'theme' => ['enabled' => 0],
        'full' => [
            'enabled' => 1, 'pad' => 0, 'ratio' => '2/3', 'fit' => 'cover',
            'radius' => 8, 'gap' => 24, 'car_desktop' => 3, 'car_mobile' => 2,
            'car_border' => 0, 'list_cols' => 0, 'zoom' => 1,
        ],
        'dense' => [
            'enabled' => 1, 'pad' => 0, 'ratio' => '1/1', 'fit' => 'cover',
            'radius' => 0, 'gap' => 8, 'car_desktop' => 4, 'car_mobile' => 2,
            'car_border' => 0, 'list_cols' => 4, 'zoom' => 1,
        ],
        'framed' => [
            'enabled' => 1, 'pad' => 16, 'ratio' => '3/4', 'fit' => 'contain',
            'radius' => 12, 'gap' => 16, 'car_desktop' => 3, 'car_mobile' => 2,
            'car_border' => 1, 'list_cols' => 0, 'zoom' => 0,
        ],
    ];

    /**
     * Sekcje, ktore renderuja karuzele produktowe: trzy wlasne motywu plus
     * sekcje tego modulu. Kazda ma ten sam szkielet HTML.
     */
    private const MINIATURE_CAROUSEL_SECTIONS =
        '.ps-newproducts,.ps-bestsellers,.ps-featuredproducts,.ps-viewedproduct,.hbe-products';

    /**
     * Strony, na ktorych moze stanac karuzela produktowa — dostaja carousel.css
     * i carousel-drag.js. ps_viewedproduct ("Juz obejrzane") siedzi na displayHome,
     * displayFooterProduct i displayShoppingCartFooter, stad koszyk na liscie.
     */
    private const CAROUSEL_PAGES = ['index', 'product', 'cart'];

    public function __construct()
    {
        $this->name    = 'hummingbird_editor';
        $this->tab     = 'front_office_features';
        $this->version = '1.18.0';
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

        // Pasek prawny na dole stopki
        for ($i = 1; $i <= self::FOOTER_LINK_SLOTS; $i++) {
            foreach (['label', 'url'] as $part) {
                $k = self::footerLinkKey($i, $part);
                if (Configuration::get($k) === false) {
                    Configuration::updateValue($k, '');
                }
            }
        }
        $this->seedFooterLegalLinks();

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

        // Zoom na okladce karty produktu — domyslnie wlaczony, w naturalnej
        // rozdzielczosci najwiekszej dostepnej miniatury.
        if (Configuration::get(self::CONF_ZOOM_ENABLED) === false) {
            Configuration::updateValue(self::CONF_ZOOM_ENABLED, 1);
        }
        if (Configuration::get(self::CONF_ZOOM_LEVEL) === false) {
            Configuration::updateValue(self::CONF_ZOOM_LEVEL, '0');
        }

        // Podpowiedz stanu magazynowego — wlaczona, z progiem zachety 3
        // jednostki. Na sklepach bez tego bloku w motywie ustawienie nic nie
        // robi, wiec wlaczenie go z gory niczego nie zmienia.
        if (Configuration::get(self::CONF_STOCK_HINT_ENABLED) === false) {
            Configuration::updateValue(self::CONF_STOCK_HINT_ENABLED, 1);
        }
        if (Configuration::get(self::CONF_STOCK_HINT_THRESHOLD) === false) {
            Configuration::updateValue(self::CONF_STOCK_HINT_THRESHOLD, '3');
        }

        // Rabat za zabranie calosci — DOMYSLNIE WYLACZONY. Wlacza go sklep
        // swiadomie, bo to realne pieniadze, a nie zmiana wygladu.
        if (Configuration::get(self::CONF_ALLSTOCK_DISCOUNT_ENABLED) === false) {
            Configuration::updateValue(self::CONF_ALLSTOCK_DISCOUNT_ENABLED, 0);
        }
        if (Configuration::get(self::CONF_ALLSTOCK_DISCOUNT_RATE) === false) {
            Configuration::updateValue(self::CONF_ALLSTOCK_DISCOUNT_RATE, '5');
        }
        // Produkt juz przeceniony dostaje mniej — rabaty by sie inaczej
        // nakladaly i konczylo sie to sprzedaza ponizej progu oplacalnosci.
        if (Configuration::get(self::CONF_ALLSTOCK_DISCOUNT_RATE_SALE) === false) {
            Configuration::updateValue(self::CONF_ALLSTOCK_DISCOUNT_RATE_SALE, '2');
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

        // Karuzele produktowe: cache na dobe + doladowywanie przy scrollu.
        // Pierwsza karuzela zostaje w HTML strony, zeby nad zgieciem nic nie
        // doskakiwalo; reszta dociaga sie, gdy zbliza sie do ekranu.
        foreach ([
            HbEditorCarouselCache::CONF_ENABLED  => 1,
            HbEditorCarouselCache::CONF_TTL      => HbEditorCarouselCache::DEFAULT_TTL,
            HbEditorCarouselCache::CONF_VARIANTS => HbEditorCarouselCache::DEFAULT_VARIANTS,
            HbEditorCarouselCache::CONF_LAZY     => 1,
            HbEditorCarouselCache::CONF_EAGER    => HbEditorCarouselCache::DEFAULT_EAGER,
        ] as $ccKey => $ccDefault) {
            if (Configuration::get($ccKey) === false) {
                Configuration::updateValue($ccKey, $ccDefault);
            }
        }
        HbEditorCarouselCache::warmKey();

        // Wyglad miniatur: same wartosci motywu, a `enabled = 0` sprawia, ze
        // swiezy modul nie dokłada do strony ani jednej reguly.
        foreach (self::MINIATURE_DEFAULTS as $miniKey => $miniDefault) {
            if (Configuration::get('HBE_MINI_' . strtoupper($miniKey)) === false) {
                Configuration::updateValue('HBE_MINI_' . strtoupper($miniKey), $miniDefault);
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
            && $this->registerHook('actionPresentCart')
            && $this->registerHook('actionProductPriceCalculation')
            && $this->registerHook('actionCartUpdateQuantityBefore')
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
        foreach (array_keys(self::MINIATURE_DEFAULTS) as $miniKey) {
            Configuration::deleteByName('HBE_MINI_' . strtoupper($miniKey));
        }
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
        Configuration::deleteByName(self::CONF_ZOOM_ENABLED);
        Configuration::deleteByName(self::CONF_ZOOM_LEVEL);
        Configuration::deleteByName(self::CONF_STOCK_HINT_ENABLED);
        Configuration::deleteByName(self::CONF_STOCK_HINT_THRESHOLD);
        Configuration::deleteByName(self::CONF_ALLSTOCK_DISCOUNT_ENABLED);
        Configuration::deleteByName(self::CONF_ALLSTOCK_DISCOUNT_RATE);
        Configuration::deleteByName(self::CONF_ALLSTOCK_DISCOUNT_RATE_SALE);
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
        foreach ([self::CONF_CHECKOUT_SKIN, self::CONF_CHECKOUT_ONEPAGE, self::CONF_CHECKOUT_TERMS_BOTTOM,
                  self::CONF_PICKUP_CARRIERS] as $k) {
            Configuration::deleteByName($k);
        }
        foreach ([
            'HBE_GIFTCARD_MENU_ENABLED', 'HBE_GIFTCARD_MENU_LABEL',
            'HBE_GIFTCARD_FOOTER_ENABLED', 'HBE_GIFTCARD_FOOTER_LABEL', 'HBE_GIFTCARD_FOOTER_DESC',
            'HBE_GIFTCARD_FLOAT_ENABLED', 'HBE_GIFTCARD_FLOAT_LABEL', 'HBE_GIFTCARD_FLOAT_POSITION',
            'HBE_GIFTCARD_URL',
        ] as $k) {
            Configuration::deleteByName($k);
        }
        foreach ([
            HbEditorCarouselCache::CONF_ENABLED, HbEditorCarouselCache::CONF_TTL,
            HbEditorCarouselCache::CONF_VARIANTS, HbEditorCarouselCache::CONF_LAZY,
            HbEditorCarouselCache::CONF_EAGER, HbEditorCarouselCache::CONF_WARM_KEY,
        ] as $k) {
            Configuration::deleteByName($k);
        }
        HbEditorCarouselCache::purge();

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
     * Page identifiers rendered as a multi-column link list on desktop.
     *
     * @return string[]
     */
    public function getMenuColumnItems(): array
    {
        $raw = (string) Configuration::get(self::MENU_COLUMNS_ITEMS_KEY);

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Page identifiers rendered as a two-pane cascade on desktop.
     *
     * @return string[]
     */
    public function getMenuCascadeItems(): array
    {
        $raw = (string) Configuration::get(self::MENU_CASCADE_ITEMS_KEY);

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Page identifiers pruned from the menu, at any depth.
     *
     * @return string[]
     */
    public function getMenuHiddenItems(): array
    {
        return $this->getMenuPathList(self::MENU_HIDDEN_ITEMS_KEY);
    }

    /**
     * Menu paths oznaczone jako najczęściej szukane.
     *
     * @return string[]
     */
    public function getMenuFeaturedItems(): array
    {
        return $this->getMenuPathList(self::MENU_FEATURED_ITEMS_KEY);
    }

    /**
     * Menu paths spychane na koniec listy.
     *
     * @return string[]
     */
    public function getMenuBottomItems(): array
    {
        return $this->getMenuPathList(self::MENU_BOTTOM_ITEMS_KEY);
    }

    /**
     * Menu paths wyciągane na początek listy.
     *
     * @return string[]
     */
    public function getMenuTopItems(): array
    {
        return $this->getMenuPathList(self::MENU_TOP_ITEMS_KEY);
    }

    /** Wariant wyróżnienia, zawsze jeden z MENU_FEATURED_STYLES. */
    public function getMenuFeaturedStyle(): string
    {
        $style = (string) Configuration::get(self::MENU_FEATURED_STYLE_KEY);

        return isset(self::MENU_FEATURED_STYLES[$style])
            ? $style
            : self::MENU_FEATURED_STYLE_DEFAULT;
    }

    /** @return string[] */
    private function getMenuPathList(string $key): array
    {
        $raw = (string) Configuration::get($key);

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Drop the configured nodes from the tree. Runs before the layout flags are
     * stamped, so a hidden branch never reaches the templates and cannot
     * influence "does this pane have children" decisions.
     *
     * @param array<int, array<string, mixed>> $nodes
     * @param string[]                         $hidden Paths, see MENU_HIDDEN_ITEMS_KEY
     */
    private function pruneMenuNodes(array &$nodes, array $hidden, string $path = ''): void
    {
        foreach ($nodes as $key => &$node) {
            $id = (string) ($node['page_identifier'] ?? '');
            $nodePath = ($path === '') ? $id : $path . '>' . $id;

            if ($id !== '' && in_array($nodePath, $hidden, true)) {
                unset($nodes[$key]);
                continue;
            }

            if (!empty($node['children']) && is_array($node['children'])) {
                $this->pruneMenuNodes($node['children'], $hidden, $nodePath);
            }
        }
        unset($node);

        // Renumber: the templates iterate with foreach, but a sparse array
        // would leak the gaps into anything that later indexes by position.
        $nodes = array_values($nodes);
    }

    /**
     * Walk the whole menu tree and stamp every node with the layout flags the
     * theme templates read. Only top-level identifiers can opt into a layout;
     * deeper nodes always get `false`, which is what the templates expect —
     * they just must not be missing.
     *
     * @param array<int, array<string, mixed>> $nodes
     * @param string[]                         $flatDesktop
     * @param string[]                         $flatMobile
     * @param string[]                         $columns
     */
    private function flagMenuNodes(array &$nodes, array $cfg, int $depth = 0, string $path = ''): void
    {
        foreach ($nodes as &$node) {
            $id = (string) ($node['page_identifier'] ?? '');
            $isTop = ($depth === 0 && $id !== '');
            $nodePath = ($path === '') ? $id : $path . '>' . $id;

            $node['flat_desktop']  = $isTop && in_array($id, $cfg['flatDesktop'], true);
            $node['flat_mobile']   = $isTop && in_array($id, $cfg['flatMobile'], true);
            $node['menu_cascade']  = $isTop && in_array($id, $cfg['cascade'], true);
            // Cascade wins: the two layouts render the same branch, so an id
            // left in both configs must not produce two panels.
            $node['menu_columns']  = $isTop && !$node['menu_cascade'] && in_array($id, $cfg['columns'], true);
            $node['rest_label']    = $cfg['restLabel'];

            // Te dwa działają na KAŻDYM poziomie i po ścieżce, bo dotyczą
            // pozycji w środku panelu, a nie całej gałęzi.
            $node['menu_featured'] = $id !== '' && in_array($nodePath, $cfg['featured'], true);
            $node['menu_bottom']   = $id !== '' && in_array($nodePath, $cfg['bottom'], true);
            $node['menu_top']      = $id !== '' && !$node['menu_bottom'] && in_array($nodePath, $cfg['top'], true);
            $node['featured_style'] = $cfg['featuredStyle'];

            if (!empty($node['children']) && is_array($node['children'])) {
                $this->flagMenuNodes($node['children'], $cfg, $depth + 1, $nodePath);

                // Alfabet tylko pod kaskadą (patrz sortMenuNodes). Robione tutaj,
                // a nie osobną pętlą po $params['menu']['children'] — tamta pisała
                // do kopii i sortowanie nie dochodziło do szablonu.
                if ($node['menu_cascade']) {
                    $this->sortMenuNodes($node['children']);
                }
            }
        }
        unset($node);
    }

    /**
     * Sort a cascade branch by label, all the way down.
     *
     * The menu tree comes ordered by `position` from the category tree, which
     * has nothing to do with the alphabet — in one column that reads as random.
     * Only the cascade needs it: the other layouts group by parent, where the
     * catalogue order still carries meaning.
     *
     * Polish collation matters here (ą after a, ł after l, ż last), so intl's
     * Collator does the comparing when it is available; the fallback folds the
     * diacritics onto their base letters, which gets the same order for every
     * language this shop runs.
     *
     * @param array<int, array<string, mixed>> $nodes
     */
    private function sortMenuNodes(array &$nodes, ?string $locale = null): void
    {
        static $collator = null;

        if ($collator === null) {
            $collator = false;
            if (class_exists('Collator')) {
                try {
                    $collator = new Collator($locale ?: 'pl_PL');
                } catch (\Throwable $e) {
                    $collator = false;
                }
            }
        }

        $cmp = static function (array $a, array $b) use ($collator): int {
            $la = (string) ($a['label'] ?? '');
            $lb = (string) ($b['label'] ?? '');

            if ($collator instanceof Collator) {
                return (int) $collator->compare($la, $lb);
            }

            $fold = static function (string $v): string {
                return mb_strtolower(strtr($v, [
                    'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
                    'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
                    'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'E', 'Ł' => 'L', 'Ń' => 'N',
                    'Ó' => 'O', 'Ś' => 'S', 'Ź' => 'Z', 'Ż' => 'Z',
                ]), 'UTF-8');
            };

            return strcmp($fold($la), $fold($lb));
        };

        // Pozycje przypięte wychodzą poza alfabet — stąd porównanie flag PRZED
        // etykietą, a nie osobne przestawianie po sortowaniu. Trzy koszyki:
        // przypięte na górę (-1), zwykłe (0), przypięte na dół (1).
        $bucket = static function (array $n): int {
            if (!empty($n['menu_bottom'])) {
                return 1;
            }

            return !empty($n['menu_top']) ? -1 : 0;
        };

        usort($nodes, static function (array $a, array $b) use ($cmp, $bucket): int {
            $ba = $bucket($a);
            $bb = $bucket($b);
            if ($ba !== $bb) {
                return $ba <=> $bb;
            }

            return $cmp($a, $b);
        });

        foreach ($nodes as &$node) {
            if (!empty($node['children']) && is_array($node['children'])) {
                $this->sortMenuNodes($node['children'], $locale);
            }
        }
        unset($node);
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

        $hidden = $this->getMenuHiddenItems();
        if ($hidden && !$this->skipMenuPrune) {
            $this->pruneMenuNodes($params['menu']['children'], $hidden);
        }

        $restLabel = trim((string) Configuration::get(self::MENU_COLUMNS_REST_LABEL_KEY));

        if ($restLabel === '') {
            $restLabel = self::MENU_COLUMNS_REST_LABEL_DEFAULT;
        }

        // Jeden worek zamiast siedmiu argumentów — flagMenuNodes schodzi
        // rekurencyjnie i każdy nowy przełącznik dokładał kolejny parametr
        // do przepisania w trzech miejscach.
        $cfg = [
            'flatDesktop'   => $this->getMenuFlatItems('desktop'),
            'flatMobile'    => $this->getMenuFlatItems('mobile'),
            'columns'       => $this->getMenuColumnItems(),
            'cascade'       => $this->getMenuCascadeItems(),
            'featured'      => $this->getMenuFeaturedItems(),
            'bottom'        => $this->getMenuBottomItems(),
            'top'           => $this->getMenuTopItems(),
            'featuredStyle' => $this->getMenuFeaturedStyle(),
            'restLabel'     => $restLabel,
        ];

        // The theme reads these flags on every nesting level (the submenu
        // functions recurse and pass each node down as $parent), so they have to
        // exist on every node — not just the top level. Missing keys used to
        // surface as "Undefined index: flat_mobile" notices inside the rendered
        // navigation.
        $this->flagMenuNodes($params['menu']['children'], $cfg);


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
                    'flat_desktop'       => false,
                    'flat_mobile'        => false,
                    'menu_columns'       => false,
                    'menu_cascade'       => false,
                    'menu_featured'      => false,
                    'menu_bottom'        => false,
                    'menu_top'           => false,
                    'featured_style'     => $cfg['featuredStyle'],
                    'rest_label'         => $restLabel,
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
     * Nazwa najszerszego typu miniatury produktu (na izpol: product_main_2x,
     * 1440 px). To zrodlo powiekszenia dla zoomu na okladce — skrypt podmienia
     * nim segment typu w URL-u zdjecia. Kazdy sklep ma swoj zestaw typow, wiec
     * czytamy go z bazy, zamiast wpisywac nazwe na sztywno.
     */
    /**
     * Oryginaly zdjec produktu jako [id_image => adres], pominawszy pliki
     * ciezsze niz ZOOM_ORIGINAL_MAX_BYTES. To zrodlo powiekszenia dla zoomu;
     * gdy zdjecia tu nie ma, skrypt schodzi do najwiekszej miniatury.
     *
     * Adresy sa wzgledne, wiec dzialaja tak samo na kazdej domenie jezykowej.
     *
     * @return array<int,string>
     */
    private function getZoomOriginals(int $idProduct): array
    {
        if ($idProduct <= 0) {
            return [];
        }

        $out = [];

        foreach (Image::getImages((int) $this->context->language->id, $idProduct) as $row) {
            $idImage = (int) $row['id_image'];
            $folder = Image::getImgFolderStatic($idImage);

            foreach (['jpg', 'png', 'webp'] as $ext) {
                $file = _PS_PROD_IMG_DIR_ . $folder . $idImage . '.' . $ext;
                if (!is_file($file)) {
                    continue;
                }
                if (filesize($file) <= self::ZOOM_ORIGINAL_MAX_BYTES) {
                    $out[$idImage] = __PS_BASE_URI__ . 'img/p/' . $folder . $idImage . '.' . $ext;
                }
                break;
            }
        }

        return $out;
    }

    public function getLargestProductImageType(): string
    {
        $best = '';
        $width = 0;

        foreach (ImageType::getImagesTypes('products') as $type) {
            if ((int) $type['width'] > $width) {
                $width = (int) $type['width'];
                $best = (string) $type['name'];
            }
        }

        return $best;
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

    /**
     * Pasek prawny na dole stopki. Motyw renderuje wylacznie to, co tu wroci,
     * wiec pusta etykieta = pozycja ukryta.
     *
     * @return array<int,array{label:string,url:string}>
     */
    public function getFooterLegalLinks(): array
    {
        $links = [];

        for ($i = 1; $i <= self::FOOTER_LINK_SLOTS; $i++) {
            $label = trim($this->hbeLocConfig(self::footerLinkKey($i, 'label')));
            if ($label === '') {
                continue;
            }

            $url = trim($this->hbeLocConfig(self::footerLinkKey($i, 'url')));
            $links[] = [
                'label' => $label,
                'url'   => $url === '' ? '#' : $this->hbeSliderValidateUrl($url),
            ];
        }

        return $links;
    }

    /**
     * Jednorazowo wypelnia pasek prawny stronami CMS tego sklepu. Adresy
     * budujemy przez Link, bo id-ki stron sa wspolne dla Rosenthala i
     * Karenskiego, ale `link_rewrite` juz nie (GPSR: "informacje-gpsr" vs
     * "informacja-gpsr"). Jesli cokolwiek jest juz ustawione, nie ruszamy —
     * wybor sklepu jest wazniejszy niz nasz zestaw startowy.
     */
    public function seedFooterLegalLinks(): void
    {
        for ($i = 1; $i <= self::FOOTER_LINK_SLOTS; $i++) {
            if (trim((string) Configuration::get(self::footerLinkKey($i, 'label'))) !== '') {
                return;
            }
        }

        $link = $this->context->link ?? new Link();
        $base = $link->getBaseLink();
        $languages = Language::getLanguages(true);
        $slot = 0;

        foreach (self::FOOTER_LINK_SEED as $idCms => $label) {
            $labels = [];
            $urls = [];

            foreach ($languages as $lang) {
                $idLang = (int) $lang['id_lang'];
                $cms = new CMS((int) $idCms, $idLang);
                if (!Validate::isLoadedObject($cms)) {
                    // Strony nie ma w tym sklepie — pomijamy cala pozycje.
                    continue 2;
                }
                $labels[$idLang] = $label;
                // Zapisujemy adres wzgledny, zeby przetrwal zmiane domeny
                // (klon dev, cutover) — modul dokleja baze przy renderowaniu.
                $urls[$idLang] = '/' . ltrim(
                    str_replace($base, '', $link->getCMSLink($cms, null, null, $idLang)),
                    '/'
                );
            }

            if (!$labels) {
                continue;
            }

            ++$slot;
            Configuration::updateValue(self::footerLinkKey($slot, 'label'), $labels, true);
            Configuration::updateValue(self::footerLinkKey($slot, 'label'), reset($labels));
            Configuration::updateValue(self::footerLinkKey($slot, 'url'), $urls, true);
            Configuration::updateValue(self::footerLinkKey($slot, 'url'), reset($urls));
        }
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        $page = $this->currentPage();

        // Footer social icons — the theme's ps_contactinfo override renders them.
        $this->context->smarty->assign('hbe_social_links', $this->getSocialLinks());

        // Pasek prawny na dole stopki — renderuje go motyw (_partials/footer.tpl).
        $this->context->smarty->assign('hbe_footer_links', $this->getFooterLegalLinks());

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
            if (HbEditorCarouselCache::lazyEnabled()) {
                Media::addJsDef([
                    'hbeCarouselLazy' => [
                        'url'     => $this->context->link->getModuleLink($this->name, 'carousel', [], true),
                        // Ten sam wariant losowania dla karuzel w HTML i doladowanych —
                        // inaczej wykluczanie produktow „z karuzel wyzej” nie trzyma sie kupy.
                        'variant' => $this->getCarouselVariant(),
                    ],
                ]);
            }
        }

        // Uklad karuzel produktowych — te same strony, na ktorych
        // hookDisplayAfterBodyOpeningTag dorzuca carousel-drag.js.
        if (in_array($page, self::CAROUSEL_PAGES, true)) {
            $this->context->controller->registerStylesheet(
                'hb-editor-carousel',
                'modules/' . $this->name . '/views/css/carousel.css',
                ['media' => 'all', 'priority' => 200]
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

            // Zoom na okladce. Skrypt sam odpada na dotyku; tutaj tylko podajemy
            // mu nazwe najwiekszego typu miniatury, bo motyw w srcset konczy na
            // 720 px, a `data-full-size-image-url` wskazuje wrecz home_default.
            if ((int) Configuration::get(self::CONF_ZOOM_ENABLED)) {
                Media::addJsDef([
                    'hbeZoom' => [
                        'type'      => $this->getLargestProductImageType(),
                        'level'     => (string) Configuration::get(self::CONF_ZOOM_LEVEL),
                        'originals' => $this->getZoomOriginals((int) Tools::getValue('id_product')),
                    ],
                ]);
                $this->context->controller->registerJavascript(
                    'hb-editor-product-zoom',
                    'modules/' . $this->name . '/views/js/product-zoom.js',
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

        if ($page === 'order') {
            // Arkusz kasy leci na kazda strone `order`, nie tylko przy wlaczonej
            // skorce: niesie tez bramke, ktora wygasza wkompilowana w theme.css
            // regule "pokaz wszystkie kroki naraz". Bez niego sklep z gotowym
            // motywem zostalby z ta regula na stale.
            $this->context->controller->registerStylesheet(
                'hb-editor-checkout',
                'modules/' . $this->name . '/views/css/checkout.css',
                ['media' => 'all', 'priority' => 200]
            );

            // Przelaczniki zakladki "Kasa" — czyta je checkout/checkout.tpl
            // (klasy modyfikujace na .checkout-grid) i steps/payment.tpl.
            $this->context->smarty->assign([
                'hbe_checkout_skin'         => (int) HbEditorConfig::get(self::CONF_CHECKOUT_SKIN),
                'hbe_checkout_onepage'      => (int) HbEditorConfig::get(self::CONF_CHECKOUT_ONEPAGE),
                'hbe_checkout_terms_bottom' => (int) HbEditorConfig::get(self::CONF_CHECKOUT_TERMS_BOTTOM),
            ]);
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

        if (!HbEditorCarouselCache::isEnabled()) {
            return $this->buildCategoryCarouselProducts($idCategory, $limit);
        }

        // Kolejnosc jest losowa, wiec jak przy karuzelach z edytora trzymamy
        // kilka wersji i losujemy — inaczej zamrozony na dobe wynik pokazywalby
        // wszystkim dokladnie te same produkty.
        $variants = HbEditorCarouselCache::variants();
        $file = HbEditorCarouselCache::fileForOverride(
            $idCategory,
            $limit,
            $variants > 1 ? random_int(0, $variants - 1) : 0
        );

        $raw = HbEditorCarouselCache::get($file);
        if ($raw === null && !HbEditorCarouselCache::claimRebuild($file)) {
            $raw = HbEditorCarouselCache::getStale($file);
        }
        if ($raw !== null) {
            $cached = @unserialize($raw, ['allowed_classes' => false]);
            if (is_array($cached)) {
                return $cached;
            }
        }

        // Presenter zwraca leniwe obiekty — do zapisu trzeba je splaszczyc do
        // zwyklych tablic (szablony i tak siegaja po nie jak po tablice).
        $plain = [];
        foreach ($this->buildCategoryCarouselProducts($idCategory, $limit) as $product) {
            $plain[] = $product instanceof \PrestaShop\PrestaShop\Adapter\Presenter\AbstractLazyArray
                ? $product->jsonSerialize()
                : (array) $product;
        }

        if ($plain) {
            HbEditorCarouselCache::set($file, serialize($plain));
        }

        return $plain;
    }

    /**
     * Wlasciwe pobranie i zaprezentowanie produktow kategorii — kosztowna czesc,
     * ktora cache w getCategoryCarouselProducts() ma omijac.
     *
     * @return array<int,mixed>
     */
    private function buildCategoryCarouselProducts(int $idCategory, int $limit): array
    {
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

    /* ── Darmowy odbior osobisty ──────────────────────────────────────────
       PrestaShop pisze przy bezplatnej dostawie samo "Za darmo!" — tak samo
       przy kurierze objetym progiem darmowej wysylki, jak przy odbiorze
       osobistym, gdzie nic nie jest wysylane. Przy przewoznikach wskazanych w
       BO jako odbior osobisty podmieniamy ten napis na "Darmowy odbior
       osobisty". Wchodzi w trzech miejscach: lista przewoznikow w kroku
       "Przesylka" i podsumowanie zamowienia (override DeliveryOptionsFinder,
       ktory oba te widoki zasila) oraz wiersz "Wysylka" w koszyku
       (hook actionPresentCart). Pusta lista = modul nie rusza niczego.
    ─────────────────────────────────────────────────────────────────────── */

    /**
     * `id_reference` przewoznikow oznaczonych jako odbior osobisty.
     *
     * @return int[]
     */
    public static function getPickupCarrierReferences(): array
    {
        $raw = HbEditorConfig::get(self::CONF_PICKUP_CARRIERS);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $refs = [];
        foreach (explode(',', $raw) as $ref) {
            $ref = (int) trim($ref);
            if ($ref > 0) {
                $refs[$ref] = $ref;
            }
        }

        return array_values($refs);
    }

    /**
     * Czy dany przewoznik (po `id_carrier`) jest odbiorem osobistym.
     *
     * Porownanie idzie po `id_reference`, bo kazda edycja przewoznika w BO
     * tworzy nowy `id_carrier`.
     */
    public static function isPickupCarrier(int $idCarrier): bool
    {
        static $references = [];

        $configured = self::getPickupCarrierReferences();
        if (!$configured || $idCarrier <= 0) {
            return false;
        }

        if (!isset($references[$idCarrier])) {
            $references[$idCarrier] = (int) Db::getInstance()->getValue(
                'SELECT `id_reference` FROM `' . _DB_PREFIX_ . 'carrier` WHERE `id_carrier` = ' . (int) $idCarrier
            );
        }

        return in_array($references[$idCarrier], $configured, true);
    }

    /**
     * Etykieta darmowego odbioru osobistego w jezyku klienta.
     *
     * Najpierw normalne tlumaczenie modulu (da sie nadpisac w BO), a gdy go nie
     * ma — wbudowany slownik, zeby sklep wielojezyczny nie zostal z angielskim
     * kluczem.
     */
    public function getFreePickupLabel(): string
    {
        $key = 'Free store pickup';
        $label = $this->trans($key, [], 'Modules.Hummingbirdeditor.Shop');
        if ($label !== $key) {
            return $label;
        }

        $iso = strtolower((string) ($this->context->language->iso_code ?? ''));

        return self::PICKUP_LABELS[$iso] ?? $key;
    }

    /**
     * Czy napis jest tym, ktorym PrestaShop oznacza bezplatna dostawe.
     *
     * Porownujemy z ta sama fraza, ktorej uzywa rdzen (`Free` z
     * Shop.Theme.Checkout), zamiast zgadywac zrodlo darmowosci — dzieki temu
     * podmiana lapie wszystkie przypadki i tylko te, w ktorych klient widzi
     * "Za darmo!".
     */
    private function isFreeShippingLabel($value): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        return $value === trim(
            $this->context->getTranslator()->trans('Free', [], 'Shop.Theme.Checkout')
        );
    }

    /**
     * Podmienia etykiete ceny w opcjach dostawy zwroconych przez
     * DeliveryOptionsFinder (krok "Przesylka" + podsumowanie zamowienia).
     * Wolane z override'u rdzenia; przy pustej konfiguracji nie zmienia nic.
     *
     * @param array<string,array<string,mixed>> $options
     *
     * @return array<string,array<string,mixed>>
     */
    public function relabelFreePickupOptions(array $options): array
    {
        if (!$options || !self::getPickupCarrierReferences()) {
            return $options;
        }

        $label = null;
        foreach ($options as $key => $carrier) {
            if (!is_array($carrier)) {
                continue;
            }

            $idCarrier = (int) ($carrier['id'] ?? $carrier['id_carrier'] ?? 0);
            $price = (string) ($carrier['price'] ?? '');

            if (!$this->isFreeShippingLabel($price) || !self::isPickupCarrier($idCarrier)) {
                continue;
            }

            $label = $label ?? $this->getFreePickupLabel();

            // `label` to gotowy napis "nazwa - termin - cena" (uzywany przez
            // niektore motywy i moduly), wiec przepisujemy w nim sama cene.
            if (isset($carrier['label'])) {
                $options[$key]['label'] = str_replace($price, $label, (string) $carrier['label']);
            }

            $options[$key]['price'] = $label;
        }

        return $options;
    }

    /**
     * Wiersz "Wysylka" w koszyku (i w prawej kolumnie kasy) — ten sam napis, co
     * przy wyborze przewoznika. Podmieniamy tylko wtedy, gdy dostawa nic nie
     * kosztuje i **wszystkie** paczki ida odbiorem osobistym; przy zwyklym
     * przewozniku z progiem darmowej wysylki zostaje "Za darmo!".
     */
    /**
     * Rabat za zabranie calego dostepnego stanu produktu.
     *
     * Rdzen podaje tu cene przez referencje (classes/Product.php, koniec
     * priceCalculation), wiec nie trzeba na to ani reguly koszyka, ani wiersza
     * w specific_price — nic nie zostaje w bazie i nie ma czego sprzatac, gdy
     * klient zmieni ilosc albo sklep przyjmie dostawe.
     *
     * Liczymy WYLACZNIE dla pozycji koszyka ($params['id_cart']): na listingu
     * i karcie produktu rdzen wola te metode bez koszyka, wiec ceny katalogowe
     * zostaja nietkniete. Inaczej produkt, ktorego zostala jedna sztuka, mialby
     * na listingu na stale obnizona cene — bo "calosc" to wtedy 1 szt.
     *
     * Klucz cache cen rdzenia zawiera i ilosc, i id koszyka, wiec zrabatowana
     * cena nie przecieka do innych wywolan w tym samym zadaniu.
     */
    public function hookActionProductPriceCalculation($params)
    {
        if (empty($params['id_cart']) || empty($params['id_product'])) {
            return;
        }
        if (!(int) Configuration::get(self::CONF_ALLSTOCK_DISCOUNT_ENABLED)) {
            return;
        }

        // Koszyk liczy kazda pozycje czterokrotnie: z obnizkami i bez
        // (use_reduc), z podatkiem i bez. Wariant "bez obnizek" to
        // price_without_reduction — cena PRZEKRESLONA w koszyku. Gdybysmy
        // i ja obnizyli, przecena -20% pokazywalaby 48,22 zamiast 49,20,
        // a produkt bez przeceny nie mialby z czego pokazac ceny sprzed rabatu.
        // Zamowienie tego nie dotyka: OrderDetail liczy original_product_price
        // bez koszyka, a product_price z price_with_reduction.
        if (array_key_exists('use_reduc', $params) && !$params['use_reduc']) {
            return;
        }

        // Produkt juz przeceniony (specific price z obnizka — promocja, wyprzedaz,
        // cena katalogowa nizsza od bazowej) dostaje wlasna, mniejsza stawke.
        // Rdzen podaje tu ta obnizke w $params['specific_price'], wiec nie trzeba
        // dopytywac bazy. Zerowa stawka = na przecenionych rabatu nie ma.
        $specificPrice = $params['specific_price'] ?? null;
        $onSale = is_array($specificPrice) && (float) ($specificPrice['reduction'] ?? 0) > 0;

        $rate = $this->getAllStockDiscountRate($onSale);
        if ($rate <= 0) {
            return;
        }

        $price = (float) ($params['price'] ?? 0);
        if ($price <= 0) {
            return;
        }

        $idProduct   = (int) $params['id_product'];
        $idAttribute = (int) ($params['id_product_attribute'] ?? 0);
        $idShop      = (int) ($params['id_shop'] ?? 0) ?: null;

        // Rabat za calosc tylko dla tkanin na CENTYMETRY (ilosc ulamkowa,
        // pproperties qty_policy = 2). Sztuki/kupony (qty_policy 0) i produkty
        // bez szablonu pproperties go nie dostaja — "wziecie calosci" 3 sztuk
        // to nie ta sama sytuacja, co koncowka belki.
        if (!$this->isAllStockFractionalProduct($idProduct)) {
            return;
        }

        // Ilosci NIE bierzemy z $params['quantity']: rdzen liczy cene jednostkowa
        // pozycji koszyka przez getCartPriceFromCatalogCore(), ktore ma ilosc
        // zadeklarowana jako int — przy 6,2 m dostajemy 6 i rabat wchodzilby
        // tylko w kwote pozycji, a cena jednostkowa w koszyku zostawalaby stara.
        // Prawdziwa ilosc lezy w cart_product (pproperties: quantity_fractional).
        $quantity = $this->getAllStockCartQuantity((int) $params['id_cart'], $idProduct, $idAttribute);
        if ($quantity <= 0) {
            $quantity = (float) ($params['quantity'] ?? 0);
        }
        if ($quantity <= 0) {
            return;
        }

        // Produkt sprzedawany ponad stan nie ma "calosci" — nie ma czego zabrac.
        if (Product::isAvailableWhenOutOfStock((int) StockAvailable::outOfStock($idProduct, $idShop))) {
            return;
        }

        // SUM(quantity + quantity_remainder), z cache w pamieci — a wiec takze
        // ulamki, ktorymi izpol sprzedaje tkaniny (6,2 m).
        $stock = (float) StockAvailable::getQuantityAvailableByProduct($idProduct, $idAttribute, $idShop);
        if ($stock <= 0 || $quantity + self::ALLSTOCK_EPSILON < $stock) {
            return;
        }

        // Cena, od ktorej schodzimy (juz po przecenie i rabacie grupy) — koszyk
        // pokaze ja przekreslona obok zrabatowanej. Bez ilosci w kluczu: rdzen
        // wola hook z roznymi ilosciami (int z getCartPriceFromCatalogCore,
        // ulamek z innych sciezek), a cena jednostkowa sprzed rabatu jest
        // dla nich ta sama.
        $useTax = !array_key_exists('use_tax', $params) || $params['use_tax'];
        self::$allStockPricesBefore[(int) $params['id_cart'] . '-' . $idProduct . '-' . $idAttribute . '-' . ($useTax ? 1 : 0)] = $price;

        $params['price'] = $price * (1 - $rate / 100);
    }

    /**
     * Czy ta pozycja koszyka spelnia warunek "klient bierze cala reszte".
     *
     * Ta sama regula, ktora decyduje o rabacie przy liczeniu ceny — dzieki temu
     * plakietka w koszyku nie moze sie rozjechac z kwota.
     */
    private function allStockConditionMet(int $idCart, int $idProduct, int $idAttribute, ?int $idShop): bool
    {
        // Rabat (i plakietka w koszyku) tylko dla tkanin na centymetry — patrz
        // hookActionProductPriceCalculation. Ten sam warunek trzyma plakietke
        // zgodna z faktycznie naliczona cena.
        if (!$this->isAllStockFractionalProduct($idProduct)) {
            return false;
        }

        if (Product::isAvailableWhenOutOfStock((int) StockAvailable::outOfStock($idProduct, $idShop))) {
            return false;
        }

        $stock = (float) StockAvailable::getQuantityAvailableByProduct($idProduct, $idAttribute, $idShop);
        if ($stock <= 0) {
            return false;
        }

        $quantity = $this->getAllStockCartQuantity($idCart, $idProduct, $idAttribute);

        return $quantity > 0 && $quantity + self::ALLSTOCK_EPSILON >= $stock;
    }

    /** Stawka rabatu dla pozycji: inna dla ceny zwyklej, inna dla przecenionej. */
    private function getAllStockDiscountRate(bool $onSale): float
    {
        $rate = (float) str_replace(
            ',',
            '.',
            (string) Configuration::get($onSale ? self::CONF_ALLSTOCK_DISCOUNT_RATE_SALE : self::CONF_ALLSTOCK_DISCOUNT_RATE)
        );

        return ($rate > 0 && $rate < 100) ? $rate : 0.0;
    }

    /** „5%" / „2,5%" — procent bez zbednych zer, w formacie sklepu. */
    private function formatAllStockRate(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 2, ',', ''), '0'), ',') . '%';
    }

    /**
     * Dopisuje pozycjom koszyka informacje o naliczonym rabacie za calosc.
     *
     * Szablon linii koszyka (motyw) czyta `hbe_allstock_discount` i pokazuje
     * plakietke. Nie liczymy tu nic na nowo — warunek jest wspolny z hookiem
     * cenowym, wiec plakietka pojawia sie dokladnie tam, gdzie cena juz spadla.
     */
    private function markAllStockDiscountLines($presentedCart): void
    {
        if (!$presentedCart instanceof ArrayAccess) {
            return;
        }
        if (!(int) Configuration::get(self::CONF_ALLSTOCK_DISCOUNT_ENABLED)) {
            return;
        }

        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            return;
        }

        $products = $presentedCart['products'] ?? null;
        if (!is_array($products) || !$products) {
            return;
        }

        $idShop = (int) $this->context->shop->id ?: null;
        $zmieniono = false;

        // Te same ustawienia, ktorymi presenter koszyka wybral ceny do pokazania
        // (brutto/netto) i zaokraglil je do grosza.
        $includeTaxes = (new TaxConfiguration())->includeTaxes();
        $precision = $this->context->getComputingPrecision();
        $roundType = (int) Configuration::get('PS_ROUND_TYPE');
        $priceFormatter = new PrestaShop\PrestaShop\Adapter\Product\PriceFormatter();

        foreach ($products as $index => $product) {
            $idProduct = (int) ($product['id_product'] ?? 0);
            if (!$idProduct) {
                continue;
            }
            $idAttribute = (int) ($product['id_product_attribute'] ?? 0);

            $rate = $this->getAllStockDiscountRate(!empty($product['has_discount']));
            if ($rate <= 0) {
                continue;
            }

            if (!$this->allStockConditionMet((int) $cart->id, $idProduct, $idAttribute, $idShop)) {
                continue;
            }

            $products[$index]['hbe_allstock_discount'] = $this->formatAllStockRate($rate);
            $zmieniono = true;

            // Cena sprzed rabatu: zapamietana w hooku cenowym w tym samym
            // zadaniu (getProducts() liczy ceny, zanim presenter cokolwiek
            // pokaze). Gdyby jej nie bylo, odwracamy mnozenie — na zaokraglonej
            // do grosza cenie moze to dac grosz roznicy, stad to tylko zapas.
            $priceAfter = Tools::ps_round((float) ($product['price_amount'] ?? 0), $precision);
            if ($priceAfter <= 0) {
                continue;
            }
            $key = (int) $cart->id . '-' . $idProduct . '-' . $idAttribute . '-' . ($includeTaxes ? 1 : 0);
            $priceBefore = self::$allStockPricesBefore[$key] ?? ($priceAfter / (1 - $rate / 100));
            $priceBefore = Tools::ps_round((float) $priceBefore, $precision);
            if ($priceBefore <= $priceAfter) {
                continue;
            }
            $products[$index]['hbe_allstock_price_before'] = $priceFormatter->format($priceBefore);

            // Laczna oszczednosc na pozycji = (ile kosztowalaby bez rabatu)
            // - (ile kosztuje). Kwote "po" bierzemy z presentera, zeby zgadzala
            // sie co do grosza z suma wiersza, a "przed" liczymy tak, jak rdzen
            // liczy sume wiersza (PS_ROUND_TYPE), na realnej ilosci (ulamki).
            $quantity = $this->getAllStockCartQuantity((int) $cart->id, $idProduct, $idAttribute);
            if ($quantity <= 0) {
                continue;
            }
            $ppSettings = $product['pp_settings'] ?? null;
            if (is_array($ppSettings) && isset($ppSettings['total_amount']) && (float) $ppSettings['total_amount'] > 0) {
                $totalAfter = (float) $ppSettings['total_amount'];
            } else {
                $totalAfter = (float) ($includeTaxes ? ($product['total_price_tax_incl'] ?? 0) : ($product['total_price_tax_excl'] ?? 0));
            }
            switch ($roundType) {
                case Order::ROUND_TOTAL:
                    $totalBefore = $priceBefore * $quantity;
                    break;
                case Order::ROUND_ITEM:
                    $totalBefore = Tools::ps_round($priceBefore, $precision) * $quantity;
                    break;
                case Order::ROUND_LINE:
                default:
                    $totalBefore = Tools::ps_round($priceBefore * $quantity, $precision);
                    break;
            }
            $savings = Tools::ps_round($totalBefore - $totalAfter, $precision);
            if ($savings > 0) {
                $products[$index]['hbe_allstock_savings'] = $priceFormatter->format($savings);
            }
        }

        if ($zmieniono) {
            $presentedCart['products'] = $products;
        }
    }

    /**
     * Ilosc danego produktu w koszyku — realna, wiec z czescia ulamkowa.
     *
     * `cart_product.quantity` trzyma na sklepie z pproperties liczbe pozycji
     * (zawsze 1), a metry siedza w `quantity_fractional`. Na sklepie bez tego
     * modulu kolumny nie ma i liczy sie zwykle `quantity` — stad sprawdzenie
     * schematu raz na zadanie.
     *
     * Wynik cache'ujemy, bo rdzen wola hook cenowy kilka razy na pozycje
     * (netto, brutto, kwota); cache znika przy kazdej zmianie ilosci w koszyku
     * (hookActionCartUpdateQuantityBefore).
     */
    /**
     * Czy produkt jest sprzedawany na CENTYMETRY (ilosc ulamkowa) — tylko takim
     * nalezy sie rabat za wziecie calosci belki.
     *
     * Zrodlo prawdy to pproperties: `pp_template.qty_policy` = 2 (ulamki).
     * 0 = sztuki/kupony; brak przypisanego szablonu (id_pp_template = 0) => brak
     * wiersza w JOIN => traktujemy jak brak ulamkow. Czytamy wprost z tabeli
     * (modul nie zaleze od klasy PP), z cache na czas zadania i try/catch, zeby
     * ewentualny brak tabeli nigdy nie wywrocil liczenia ceny — w najgorszym
     * razie rabatu po prostu nie ma.
     */
    private function isAllStockFractionalProduct(int $idProduct): bool
    {
        static $cache = [];
        if (!array_key_exists($idProduct, $cache)) {
            $fractional = false;
            try {
                $qtyPolicy = (int) Db::getInstance()->getValue(
                    'SELECT t.`qty_policy`
                     FROM `' . _DB_PREFIX_ . 'product` p
                     JOIN `' . _DB_PREFIX_ . 'pp_template` t ON t.`id_pp_template` = p.`id_pp_template`
                     WHERE p.`id_product` = ' . (int) $idProduct
                );
                $fractional = ($qtyPolicy === 2);
            } catch (Throwable $e) {
                $fractional = false;
            }
            $cache[$idProduct] = $fractional;
        }

        return $cache[$idProduct];
    }

    private function getAllStockCartQuantity(int $idCart, int $idProduct, int $idAttribute): float
    {
        if ($idCart <= 0 || $idProduct <= 0) {
            return 0.0;
        }

        $key = $idCart . '-' . $idProduct . '-' . $idAttribute;
        if (array_key_exists($key, self::$allStockCartQuantities)) {
            return self::$allStockCartQuantities[$key];
        }

        if (self::$allStockFractionalColumn === null) {
            self::$allStockFractionalColumn = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'cart_product` LIKE \'quantity_fractional\''
            );
        }

        $column = self::$allStockFractionalColumn
            ? 'IF(cp.`quantity_fractional` > 0, cp.`quantity_fractional`, cp.`quantity`)'
            : 'cp.`quantity`';

        $quantity = (float) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT SUM(' . $column . ') FROM `' . _DB_PREFIX_ . 'cart_product` cp'
            . ' WHERE cp.`id_cart` = ' . $idCart
            . ' AND cp.`id_product` = ' . $idProduct
            . ' AND cp.`id_product_attribute` = ' . $idAttribute
        );

        self::$allStockCartQuantities[$key] = $quantity;

        return $quantity;
    }

    /** Zmiana ilosci w koszyku uniewaznia zapamietane ilosci (patrz wyzej). */
    public function hookActionCartUpdateQuantityBefore($params)
    {
        self::$allStockCartQuantities = [];
        self::$allStockPricesBefore = [];
    }

    public function hookActionPresentCart(array $params): void
    {
        $presentedCart = $params['presentedCart'] ?? null;

        // Plakietka „rabat za calosc" na pozycjach — niezalezna od etykiety
        // darmowego odbioru osobistego nizej, wiec idzie przed jej warunkami.
        $this->markAllStockDiscountLines($presentedCart);

        if (!$presentedCart instanceof ArrayAccess || !self::getPickupCarrierReferences()) {
            return;
        }

        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart) || $cart->isVirtualCart()) {
            return;
        }

        $subtotals = $presentedCart['subtotals'];
        if (!is_array($subtotals) || empty($subtotals['shipping'])) {
            return;
        }

        $shipping = $subtotals['shipping'];
        if ((float) ($shipping['amount'] ?? 0) != 0.0 || !$this->isFreeShippingLabel($shipping['value'] ?? '')) {
            return;
        }

        $carrierIds = $this->getCartCarrierIds($cart);
        if (!$carrierIds) {
            return;
        }

        foreach ($carrierIds as $idCarrier) {
            if (!self::isPickupCarrier($idCarrier)) {
                return;
            }
        }

        $subtotals['shipping']['value'] = $this->getFreePickupLabel();
        $presentedCart['subtotals'] = $subtotals;
    }

    /**
     * Przewoznicy wybrani w koszyku (klucz opcji dostawy to lista `id_carrier`
     * po przecinku — po jednym na paczke).
     *
     * @return int[]
     */
    private function getCartCarrierIds(Cart $cart): array
    {
        $ids = [];
        foreach ((array) $cart->getDeliveryOption(null, false, false) as $optionKey) {
            foreach (explode(',', (string) $optionKey) as $idCarrier) {
                $idCarrier = (int) $idCarrier;
                if ($idCarrier > 0) {
                    $ids[$idCarrier] = $idCarrier;
                }
            }
        }

        if (!$ids && (int) $cart->id_carrier > 0) {
            $ids[(int) $cart->id_carrier] = (int) $cart->id_carrier;
        }

        return array_values($ids);
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

    /* ── Wyglad miniatur produktu ────────────────────────────────────────── */

    /**
     * Ustawienia kafla produktu, kazde sprowadzone do dozwolonego zakresu.
     *
     * Czyta je i front (do zbudowania CSS-a), i panel (do formularza), zeby
     * jedno i drugie widzialo dokladnie te same wartosci — takze wtedy, gdy
     * w bazie siedzi smiec po recznej edycji.
     *
     * @return array<string,int|string>
     */
    public function getMiniatureSettings(): array
    {
        $out = [];
        foreach (self::MINIATURE_DEFAULTS as $key => $default) {
            $raw = HbEditorConfig::get('HBE_MINI_' . strtoupper($key));
            // Pusty ciag zostaje pustym ciagiem: dla proporcji to prawidlowa
            // wartosc ("bez wymuszania"), a reszte i tak przepuszcza ponizej
            // clamp albo rzutowanie na bool.
            $out[$key] = ($raw === false || $raw === null) ? $default : $raw;
        }

        $clamp = static function ($value, int $min, int $max): int {
            return max($min, min($max, (int) $value));
        };

        return [
            'enabled'     => (int) (bool) $out['enabled'],
            'pad'         => $clamp($out['pad'], 0, 80),
            'ratio'       => in_array((string) $out['ratio'], self::MINIATURE_RATIOS, true)
                ? (string) $out['ratio']
                : '',
            'fit'         => (string) $out['fit'] === 'contain' ? 'contain' : 'cover',
            'radius'      => $clamp($out['radius'], 0, 40),
            'gap'         => $clamp($out['gap'], 0, 48),
            'car_desktop' => $clamp($out['car_desktop'], 2, 6),
            'car_mobile'  => $clamp($out['car_mobile'], 1, 3),
            'car_border'  => (int) (bool) $out['car_border'],
            // 0 = zostaw kolumny listingu takie, jakie daje uklad strony.
            'list_cols'   => (int) $out['list_cols'] === 0 ? 0 : $clamp($out['list_cols'], 2, 6),
            'zoom'        => (int) (bool) $out['zoom'],
        ];
    }

    /**
     * Szerokosc kafla karuzeli przy `n` kaflach na ekran i odstepie `gap`.
     *
     * Odstepow jest o jeden mniej niz kafli i trzeba je odjac od 100 %,
     * inaczej ostatni kafel wystaje poza widok i karuzela zatrzymuje sie
     * w polowie zdjecia.
     */
    private static function miniatureTrackWidth(int $count, int $gap): string
    {
        return 'calc((100% - ' . ($count - 1) * $gap . 'px) / ' . $count . ')';
    }

    /**
     * Arkusz opisujacy wybrany wyglad kafla — pusty, gdy sklep zostaje przy
     * motywie.
     *
     * Wstrzykiwany za <body>, czyli po wszystkich arkuszach z <head>. Wobec
     * theme.css hummingbirda wystarczy sam brak warstwy: theme.css jest
     * w @layer, a regula bez warstwy bije kazda warstwe niezaleznie od
     * specyficznosci.
     *
     * Z CSS-em modulow (w tym home.css tego modulu) i recznym custom.css motywu
     * jest inaczej — te tez sa poza warstwami, wiec decyduje specyficznosc,
     * a dopiero przy remisie kolejnosc. Dlatego kazdy selektor, ktory ma
     * konkurencje, konczy sie **podwojona klasa**: `.products.products` przebija
     * `.hbe-products .module-products__carousel .products{gap:0}` z home.css
     * i wlasne reguly motywow na siatce listingu. Bez tego karuzele edytora
     * zostawaly sklejone mimo ustawionej przerwy.
     */
    public function getMiniatureCss(): string
    {
        $s = $this->getMiniatureSettings();
        if (!$s['enabled']) {
            return '';
        }

        $carouselTracks = ':is(' . self::MINIATURE_CAROUSEL_SECTIONS . ')'
            . ' .module-products__carousel .products.products,'
            . '.hbe-products .hbe-products__skeleton.hbe-products__skeleton';
        $carouselItems = ':is(' . self::MINIATURE_CAROUSEL_SECTIONS . ')'
            . ' .module-products__carousel .product-miniature.product-miniature,'
            . '.hbe-products .hbe-products__skeleton-card.hbe-products__skeleton-card';

        $css = 'img.product-miniature__image{padding:' . $s['pad'] . 'px';
        if ($s['ratio'] !== '') {
            // Atrybuty width/height w HTML-u to wymiary RAMKI typu obrazka
            // (zwykle kwadrat), a nie pliku. Bez wymuszonej proporcji
            // przegladarka rezerwuje kwadrat i siatka przeskakuje, gdy zdjecia
            // dojada. object-fit pilnuje zdjec o innej proporcji: przytnie je
            // (cover) albo zmiesci w calosci (contain), zamiast rozjechac rzad.
            $css .= ';aspect-ratio:' . $s['ratio'] . ';object-fit:' . $s['fit'];
        }
        $css .= '}';

        // Powiekszenie na hoverze (scale 1.1 z motywu) miescilo sie dotad
        // w paddingu zdjecia; przy mniejszym paddingu wylewa sie poza kafel,
        // wiec kadr musi przycinac niezaleznie od zaokraglenia.
        $css .= '.product-miniature__image-container{overflow:hidden;border-radius:' . $s['radius'] . 'px}';
        if (!$s['zoom']) {
            $css .= '.product-miniature__inner:hover .product-miniature__image{transform:none}';
        }

        // Ten sam odstep rozdziela siatke listingu i pas karuzeli — kafle maja
        // wygladac tak samo niezaleznie od miejsca. Karuzele dostaja go osobna
        // regula, bo ich `gap: 0` jest zapisany selektorem o trzech klasach:
        // krotkie `.products` przegralo tam specyficznoscia mimo pozniejszej
        // kolejnosci i pas zostawal sklejony.
        $css .= '.products{gap:' . $s['gap'] . 'px}'
            . $carouselTracks . '{gap:' . $s['gap'] . 'px}';

        $css .= $carouselItems . '{'
            . ($s['car_border'] ? '' : 'border:0;')
            . 'flex-basis:' . self::miniatureTrackWidth($s['car_desktop'], $s['gap']) . ';'
            . 'min-width:' . self::miniatureTrackWidth($s['car_desktop'], $s['gap'])
            . '}';
        // Motyw zwezal kafle karuzeli progami max-width (991 px i 575 px);
        // regula wyzej bije je na kazdej szerokosci, wiec caly telefon obsluguje
        // ten jeden prog.
        $css .= '@media(max-width:767.98px){' . $carouselItems . '{'
            . 'flex-basis:' . self::miniatureTrackWidth($s['car_mobile'], $s['gap']) . ';'
            . 'min-width:' . self::miniatureTrackWidth($s['car_mobile'], $s['gap'])
            . '}}';

        if ($s['list_cols'] >= 2) {
            // Powyzej 1400 px pelna liczba kolumn, w srodkowym zakresie co
            // najwyzej trzy (inaczej kafel robi sie wezszy niz w karuzeli),
            // na telefonie zawsze dwa.
            $mid = min($s['list_cols'], 3);
            $grid = static function (int $cols): string {
                return '#js-product-list .products.products{grid-template-columns:repeat('
                    . $cols . ',minmax(0,1fr))}';
            };
            $css .= $grid(2);
            $css .= '@media(min-width:768px){' . $grid($mid) . '}';
            if ($s['list_cols'] > $mid) {
                $css .= '@media(min-width:1400px){' . $grid($s['list_cols']) . '}';
            }
        }

        return $css;
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
        // Wyglad kafla produktu (zdjecie, odstepy, kolumny) — pusty ciag,
        // dopoki sklep nie wybierze czegos innego niz uklad motywu.
        $css .= $this->getMiniatureCss();
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
        // home, categoryproducts/accessories on product, viewed products in cart).
        if (in_array($page, self::CAROUSEL_PAGES, true)) {
            $output .= $jsTag('carousel-drag.js');
        }

        // Doladowywanie karuzel produktowych przy scrollu — musi isc po
        // carousel-drag.js, bo korzysta z wystawionego przez niego inicjatora.
        if ($page === 'index' && HbEditorCarouselCache::lazyEnabled()) {
            $output .= $jsTag('carousel-lazy.js');
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

    /** Wariant losowania na te wizyte — jeden dla wszystkich karuzel strony (null = jeszcze nie wylosowany). */
    private $hbeCarouselVariant = null;

    /** Karuzele produktowe strony glownej w kolejnosci wyswietlania (memo na zadanie). */
    private $hbeCarouselOrder = null;

    /** Id produktow pokazanych przez karuzele: [id_block][wariant] => int[] (memo na zadanie). */
    private $hbeCarouselIds = [];

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

        // Karuzele produktowe ponad limit nie trafiaja do HTML strony — zostaje
        // po nich atrapa, a tresc dociaga carousel-lazy.js, gdy sekcja zbliza sie
        // do ekranu. Pierwsze $eagerLeft renderuja sie normalnie, zeby gora strony
        // byla kompletna od razu.
        $lazyCarousels = HbEditorCarouselCache::lazyEnabled();
        $eagerLeft = HbEditorCarouselCache::eagerCount();

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
                    if ($lazyCarousels && $block['section_type'] === HbEditorBlock::STYPE_PRODUCTS) {
                        if ($eagerLeft > 0) {
                            --$eagerLeft;
                        } else {
                            $output .= $this->renderProductsPlaceholder($block);
                            continue;
                        }
                    }
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

    /**
     * Surowe wiersze produktow z kategorii (bez prezentacji) — wspolne dla budowania
     * karuzeli i dla wyliczania, co pokazuja karuzele wyzej na stronie.
     *
     * Wlasne zapytanie zamiast CategoryProductSearchProvider: dostawca rdzenia nie
     * umie ani pomijac konkretnych id, ani filtrowac po dacie dodania, a obu
     * potrzebujemy (opcja „pomijaj produkty z karuzel wyzej” i regula „nie starsze
     * niz N miesiecy”). Warunki sa te same, ktore stosuje Category::getProducts()
     * na froncie: sklep, aktywny, widocznosc both/catalog, dostep grupy do
     * kategorii. Dalej wiersz z samym id_product wystarcza — ProductAssembler
     * dociaga reszte.
     *
     * @param int   $idCategory kategoria zrodlowa
     * @param int   $ile        ile produktow pokazac
     * @param bool  $losowo     czy losowa kolejnosc zamiast wg daty dodania
     * @param int[] $wyklucz    id produktow, ktorych nie pokazywac
     *
     * @return array<int,array{id_product:int}>
     */
    private function hbeFetchCategoryProducts(int $idCategory, int $ile, bool $losowo, array $wyklucz = []): array
    {
        if ($ile <= 0) {
            $ile = 8;
        }
        $category = new Category($idCategory);
        if (!Validate::isLoadedObject($category)
            || !$category->checkAccess((int) ($this->context->customer->id ?? 0))) {
            return [];
        }

        $where = 'cp.`id_category` = ' . (int) $idCategory . '
            AND product_shop.`active` = 1
            AND product_shop.`visibility` IN ("both", "catalog")';

        $wyklucz = array_values(array_filter(array_map('intval', $wyklucz)));
        if ($wyklucz) {
            $where .= ' AND p.`id_product` NOT IN (' . implode(',', $wyklucz) . ')';
        }

        $cutoff = HbEditorCarouselCache::maxAgeCutoff();
        if ($cutoff !== null) {
            $where .= ' AND p.`date_add` >= "' . pSQL($cutoff) . '"';
        }

        $rows = (array) Db::getInstance()->executeS(
            'SELECT p.`id_product`
             FROM `' . _DB_PREFIX_ . 'category_product` cp
             INNER JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = cp.`id_product`
             ' . Shop::addSqlAssociation('product', 'p') . '
             WHERE ' . $where . '
             ORDER BY ' . ($losowo ? 'RAND()' : 'p.`date_add` DESC, p.`id_product` DESC') . '
             LIMIT ' . (int) $ile
        );

        $wiersze = [];
        foreach ($rows as $row) {
            $wiersze[] = ['id_product' => (int) $row['id_product']];
        }

        return $wiersze;
    }

    /**
     * Pobiera i prezentuje produkty z kategorii — gotowa lista dla szablonu.
     *
     * @param int[] $wyklucz id produktow, ktorych nie pokazywac
     *
     * @return array lista produktow gotowa dla szablonu
     */
    private function hbeGetCategoryProducts(int $idCategory, int $ile, bool $losowo, array $wyklucz = []): array
    {
        $wiersze = $this->hbeFetchCategoryProducts($idCategory, $ile, $losowo, $wyklucz);

        $assembler = new ProductAssembler($this->context);
        $factory = new ProductPresenterFactory($this->context);
        $ustawienia = $factory->getPresentationSettings();
        $presenter = $factory->getPresenter();

        $lista = [];
        foreach ($wiersze as $surowy) {
            $lista[] = $presenter->present($ustawienia, $assembler->assembleProduct($surowy), $this->context->language);
        }

        return $lista;
    }

    /* ── Karuzele: kolejnosc na stronie, wariant wizyty, wykluczanie „tego, co wyzej” ── */

    /**
     * Ustawia wariant losowania dla tego zadania — endpoint doladowywania dostaje
     * go od strony (?v=), zeby karuzele dociagniete przy scrollu pasowaly do tych
     * juz w HTML.
     */
    public function setCarouselVariant(int $variant): void
    {
        $this->hbeCarouselVariant = max(0, min(HbEditorCarouselCache::variants() - 1, $variant));
    }

    /**
     * Wariant losowania na te wizyte. Losowany raz na zadanie, wspolny dla
     * wszystkich karuzel: karuzela wykluczajaca produkty z karuzel wyzej musi
     * wiedziec, ktory wariant tamtych gosc ogladal.
     */
    public function getCarouselVariant(): int
    {
        if ($this->hbeCarouselVariant === null) {
            $this->hbeCarouselVariant = random_int(0, HbEditorCarouselCache::variants() - 1);
        }

        return $this->hbeCarouselVariant;
    }

    /**
     * Aktywne karuzele produktowe strony glownej biezacego sklepu w kolejnosci
     * wyswietlania (HBE_HOME_ORDER, bloki spoza listy na koncu wg position) —
     * ta kolejnosc daje pierwszenstwo przy wykluczaniu produktow pokazanych wyzej.
     *
     * @return array<int,array{id_block:int,sd:array}> id_block => dane
     */
    private function hbeCarouselOrder(): array
    {
        if ($this->hbeCarouselOrder !== null) {
            return $this->hbeCarouselOrder;
        }

        $rows = (array) Db::getInstance()->executeS(
            'SELECT b.`id_block`, b.`section_data`
             FROM `' . _DB_PREFIX_ . 'hb_editor_block` b
             INNER JOIN `' . _DB_PREFIX_ . 'hb_editor_block_shop` bs
                 ON bs.id_block = b.id_block AND bs.id_shop = ' . (int) $this->context->shop->id . '
             WHERE b.`active` = 1
               AND b.`hook_name` = "displayHome"
               AND b.`section_type` = "' . pSQL(HbEditorBlock::STYPE_PRODUCTS) . '"
             ORDER BY b.`position` ASC, b.`id_block` ASC'
        );

        $byId = [];
        foreach ($rows as $row) {
            $sd = json_decode((string) $row['section_data'], true);
            $byId[(int) $row['id_block']] = [
                'id_block' => (int) $row['id_block'],
                'sd'       => is_array($sd) ? $sd : [],
            ];
        }

        // Pozycja w HBE_HOME_ORDER (ta sama, ktorej uzywa hookDisplayHome).
        $rank = [];
        foreach (explode(',', (string) Configuration::get('HBE_HOME_ORDER')) as $i => $part) {
            $part = trim($part);
            if ($part !== '' && ctype_digit($part)) {
                $rank[(int) $part] = $i;
            }
        }

        $ordered = [];
        foreach ($byId as $id => $b) {
            if (isset($rank[$id])) {
                $ordered[$rank[$id]] = $b;
            }
        }
        ksort($ordered);

        $out = [];
        foreach ($ordered as $b) {
            $out[$b['id_block']] = $b;
        }
        foreach ($byId as $id => $b) {
            if (!isset($out[$id])) {
                $out[$id] = $b;
            }
        }

        return $this->hbeCarouselOrder = $out;
    }

    /**
     * Karuzele stojace przed dana na stronie glownej. Blok spoza strony glownej
     * nie ma poprzedniczek.
     *
     * @return array<int,array{id_block:int,sd:array}>
     */
    private function hbeCarouselPredecessors(int $idBlock): array
    {
        $out = [];
        foreach ($this->hbeCarouselOrder() as $id => $b) {
            if ($id === $idBlock) {
                return $out;
            }
            $out[$id] = $b;
        }

        return [];
    }

    /**
     * Czy tresc karuzeli zalezy od wariantu losowania: sama losuje albo wyklucza
     * produkty karuzeli, ktora losuje (wtedy jej lista wykluczen — a wiec i ona —
     * zmienia sie z wariantem). Taka karuzela trzyma w cache tyle wpisow, ile
     * jest wariantow, choc sama ma ustalona kolejnosc.
     */
    private function hbeCarouselVariantDependent(int $idBlock, array $sd): bool
    {
        if (!empty($sd['randomized'])) {
            return true;
        }
        if (empty($sd['exclude_previous'])) {
            return false;
        }
        foreach ($this->hbeCarouselPredecessors($idBlock) as $id => $b) {
            if ($this->hbeCarouselVariantDependent($id, $b['sd'])) {
                return true;
            }
        }

        return false;
    }

    /** Wariant, pod ktorym karuzela faktycznie trzyma cache (0, gdy nie zalezy od losowania). */
    private function hbeCarouselEffectiveVariant(int $idBlock, array $sd, int $variant): int
    {
        return $this->hbeCarouselVariantDependent($idBlock, $sd) ? $variant : 0;
    }

    /**
     * Id produktow, ktorych karuzela ma nie pokazywac: suma produktow karuzel
     * stojacych wyzej na stronie glownej, dla tego samego wariantu losowania.
     * Pusta tablica, gdy opcja `exclude_previous` jest wylaczona.
     *
     * @param bool $buduj czy wolno budowac brakujace karuzele wyzej (kosztowne);
     *                    przy false brak ktorejkolwiek w cache daje null = „nie wiadomo”
     *
     * @return int[]|null
     */
    private function hbeCarouselExcludeIds(int $idBlock, array $sd, int $variant, bool $buduj): ?array
    {
        if ($idBlock <= 0 || empty($sd['exclude_previous'])) {
            return [];
        }

        $ids = [];
        foreach ($this->hbeCarouselPredecessors($idBlock) as $id => $b) {
            $shown = $this->hbeCarouselProductIds($id, $b['sd'], $variant, $buduj);
            if ($shown === null) {
                return null;
            }
            foreach ($shown as $p) {
                $ids[$p] = $p;
            }
        }
        sort($ids);

        return array_values($ids);
    }

    /**
     * Id produktow, ktore karuzela pokazuje w danym wariancie.
     *
     * Zrodlem prawdy jest jej gotowy HTML (atrybut data-hbe-ids) — z cache albo
     * zbudowany w tym zadaniu — bo tylko on mowi, co gosc naprawde widzi wyzej.
     * Bez cache liczymy z samego zapytania (dla karuzel losowych to przyblizenie).
     *
     * @return int[]|null null = nieznane (nie ma w cache, a budowac nie wolno)
     */
    private function hbeCarouselProductIds(int $idBlock, array $sd, int $variant, bool $buduj): ?array
    {
        $v = $this->hbeCarouselEffectiveVariant($idBlock, $sd, $variant);
        if (isset($this->hbeCarouselIds[$idBlock][$v])) {
            return $this->hbeCarouselIds[$idBlock][$v];
        }

        if ((int) ($sd['id_category'] ?? 0) <= 0) {
            return $this->hbeCarouselIds[$idBlock][$v] = [];
        }

        if (!HbEditorCarouselCache::isEnabled()) {
            $exclude = $this->hbeCarouselExcludeIds($idBlock, $sd, $variant, $buduj);
            if ($exclude === null) {
                return null;
            }
            $rows = $this->hbeFetchCategoryProducts(
                (int) $sd['id_category'],
                (int) ($sd['number'] ?? 8),
                !empty($sd['randomized']),
                $exclude
            );
            $ids = [];
            foreach ($rows as $row) {
                $ids[] = (int) $row['id_product'];
            }

            return $this->hbeCarouselIds[$idBlock][$v] = $ids;
        }

        if ($buduj) {
            // renderProductsCarousel() zapisuje memo po drodze (z cache albo z builda).
            $this->renderProductsCarousel($idBlock, $sd, $variant);

            return $this->hbeCarouselIds[$idBlock][$v] ?? [];
        }

        $exclude = $this->hbeCarouselExcludeIds($idBlock, $sd, $variant, false);
        if ($exclude === null) {
            return null;
        }
        $html = HbEditorCarouselCache::get($this->hbeCarouselFile($idBlock, $sd, $v, $exclude));
        if ($html === null) {
            return null;
        }

        return $this->hbeCarouselIds[$idBlock][$v] = $this->hbeExtractIds($html);
    }

    /**
     * Plik cache karuzeli. Klucz obejmuje konfiguracje bloku, wariant, liste
     * wykluczen i date graniczna reguly wieku — gdy karuzela wyzej sie przebuduje i pokaze inne produkty, ta
     * dostaje nowy klucz i tez sie przebudowuje, zamiast dublowac ja do konca TTL.
     *
     * @param int[] $exclude posortowana lista wykluczen
     */
    private function hbeCarouselFile(int $idBlock, array $sd, int $v, array $exclude): string
    {
        $fingerprint = (string) json_encode($sd);
        if ($exclude) {
            $fingerprint .= '|x:' . implode(',', $exclude);
        }
        // Regula wieku: data graniczna przesuwa sie co dobe, wiec wpis musi sie
        // wtedy przekrecic — inaczej produkt, ktory wlasnie „postarzal sie” ponad
        // limit, wisialby w karuzeli do konca TTL.
        $cutoff = HbEditorCarouselCache::maxAgeCutoff();
        if ($cutoff !== null) {
            $fingerprint .= '|age:' . substr($cutoff, 0, 10);
        }

        return HbEditorCarouselCache::fileForBlock($idBlock, $fingerprint, $v);
    }

    /**
     * Id produktow z gotowego HTML karuzeli (atrybut data-hbe-ids na <section>).
     *
     * @return int[]
     */
    private function hbeExtractIds(string $html): array
    {
        if (!preg_match('/\sdata-hbe-ids="([0-9,]*)"/', $html, $m)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $m[1]))));
    }

    /**
     * Wyciaga wartosc per jezyk z section_data z zapasem: gdy dany jezyk nie ma
     * wpisu, bierze pierwszy dostepny (tak samo jak zamkniete w renderSectionBlock).
     */
    private function hbeSectionLangValue(array $sd, string $key, int $idLang): string
    {
        $langs = $sd['langs'] ?? [];
        if (isset($langs[$idLang][$key])) {
            return (string) $langs[$idLang][$key];
        }

        $first = $langs ? array_key_first($langs) : null;

        return $first === null ? '' : (string) ($langs[$first][$key] ?? '');
    }

    /**
     * Karuzela produktowa gotowa do wstawienia w strone — z cache.
     *
     * Trafienie w cache oznacza odczyt jednego pliku zamiast wyszukiwania w
     * kategorii i prezentowania kilkunastu produktow. Karuzele z losowa
     * kolejnoscia trzymaja kilka wariantow i losuja miedzy nimi, zeby zamrozony
     * na dobe HTML nie oznaczal tych samych produktow przy kazdej wizycie.
     *
     * Wykluczanie „tego, co wyzej”: gdy blok ma `exclude_previous`, najpierw
     * ustala (z cache lub budujac) produkty karuzel stojacych przed nim na
     * stronie glownej — w tym samym wariancie — i pomija je u siebie.
     *
     * @param int        $idBlock id bloku (0 = renderuj bez cache)
     * @param array|null $sd      section_data; null = dociagnij z bazy (sciezka AJAX)
     * @param int|null   $variant wariant losowania; null = wariant tej wizyty
     */
    public function renderProductsCarousel(int $idBlock, ?array $sd = null, ?int $variant = null): string
    {
        if ($sd === null) {
            $block = $this->hbeProductsBlockData($idBlock);
            if ($block === null) {
                return '';
            }
            $sd = $block['sd'];
        }

        if ((int) ($sd['id_category'] ?? 0) <= 0) {
            return '';
        }

        $variant = $variant ?? $this->getCarouselVariant();
        $v = $this->hbeCarouselEffectiveVariant($idBlock, $sd, $variant);
        $exclude = $this->hbeCarouselExcludeIds($idBlock, $sd, $variant, true) ?? [];

        if ($idBlock <= 0 || !HbEditorCarouselCache::isEnabled()) {
            $html = $this->buildProductsCarousel($sd, $exclude);
            if ($idBlock > 0) {
                $this->hbeCarouselIds[$idBlock][$v] = $this->hbeExtractIds($html);
            }

            return $html;
        }

        $file = $this->hbeCarouselFile($idBlock, $sd, $v, $exclude);

        $html = HbEditorCarouselCache::get($file);
        if ($html === null) {
            if (HbEditorCarouselCache::claimRebuild($file)) {
                $html = HbEditorCarouselCache::detokenize($this->buildProductsCarousel($sd, $exclude));
                if ($this->hbeCarouselIsCacheable($html)) {
                    HbEditorCarouselCache::set($file, $html);
                }
            } else {
                // Ktos inny wlasnie odbudowuje — podajemy stara tresc zamiast
                // renderowac to samo rownolegle.
                $html = (string) HbEditorCarouselCache::getStale($file);
            }
        }

        $this->hbeCarouselIds[$idBlock][$v] = $this->hbeExtractIds($html);

        return HbEditorCarouselCache::retokenize($html);
    }

    /**
     * Wlasciwe zbudowanie karuzeli — kosztowna czesc, ktora cache ma omijac.
     *
     * @param int[] $exclude id produktow do pominiecia (pokazane w karuzelach wyzej)
     */
    private function buildProductsCarousel(array $sd, array $exclude = []): string
    {
        $idCategory = (int) ($sd['id_category'] ?? 0);
        if ($idCategory <= 0) {
            return '';
        }

        $produkty = $this->hbeGetCategoryProducts(
            $idCategory,
            (int) ($sd['number'] ?? 8),
            !empty($sd['randomized']),
            $exclude
        );
        if (!$produkty) {
            return '';
        }

        // Lista id w HTML: stad kolejne karuzele (i cache) wiedza, co ta pokazuje.
        $ids = [];
        foreach ($produkty as $p) {
            $ids[] = (int) ($p['id_product'] ?? 0);
        }

        $idLang = (int) $this->context->language->id;
        $this->context->smarty->assign([
            'hbe_products_title'    => $this->hbeSectionLangValue($sd, 'title', $idLang),
            'hbe_products_text'     => $this->hbeSectionLangValue($sd, 'text', $idLang),
            'hbe_products'          => $produkty,
            'hbe_products_ids'      => implode(',', array_filter($ids)),
            'hbe_products_all_link' => $this->context->link->getCategoryLink($idCategory),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/products.tpl');
    }

    /**
     * Lekka atrapa karuzeli wstawiana w HTML strony glownej: naglowek, link do
     * kategorii (zeby sekcja miala tresc dla robotow i dzialala bez JS) oraz
     * szkielet kart rezerwujacy wysokosc, zeby doladowanie nie przesuwalo strony.
     * Prawdziwa tresc wciaga carousel-lazy.js, gdy sekcja zbliza sie do ekranu.
     */
    private function renderProductsPlaceholder(array $block): string
    {
        $sd = [];
        if (!empty($block['section_data'])) {
            $decoded = json_decode((string) $block['section_data'], true);
            if (is_array($decoded)) {
                $sd = $decoded;
            }
        }

        $idCategory = (int) ($sd['id_category'] ?? 0);
        if ($idCategory <= 0) {
            return '';
        }

        // Karuzela, ktora po zbudowaniu okazala sie pusta (kategoria bez
        // produktow), nie zasluguje na atrape — inaczej przy kazdym wejsciu
        // mignelaby sekcja, ktora zaraz po doladowaniu znika.
        if (HbEditorCarouselCache::isEnabled()) {
            $idBlock = (int) $block['id_block'];
            $variant = $this->getCarouselVariant();
            // Bez budowania czegokolwiek: gdy karuzel wyzej nie ma jeszcze w cache,
            // klucza nie znamy i atrapa po prostu zostaje.
            $exclude = $this->hbeCarouselExcludeIds($idBlock, $sd, $variant, false);
            if ($exclude !== null) {
                $cached = HbEditorCarouselCache::get($this->hbeCarouselFile(
                    $idBlock,
                    $sd,
                    $this->hbeCarouselEffectiveVariant($idBlock, $sd, $variant),
                    $exclude
                ));
                if ($cached !== null && trim($cached) === '') {
                    return '';
                }
            }
        }

        $idLang = (int) $this->context->language->id;
        $this->context->smarty->assign([
            'hbe_products_lazy_id'    => (int) $block['id_block'],
            'hbe_products_title'      => $this->hbeSectionLangValue($sd, 'title', $idLang),
            'hbe_products_text'       => $this->hbeSectionLangValue($sd, 'text', $idLang),
            'hbe_products_all_link'   => $this->context->link->getCategoryLink($idCategory),
            'hbe_products_lazy_count' => max(2, min(12, (int) ($sd['number'] ?? 8))),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/products-lazy.tpl');
    }

    /**
     * Blok karuzeli widoczny publicznie w biezacym sklepie, wraz z odkodowanym
     * section_data. Zwraca null, gdy bloku nie ma, jest wylaczony, nie jest
     * karuzela albo nie nalezy do tego sklepu — endpoint AJAX opiera sie na tym
     * sprawdzeniu, wiec id z zewnatrz nie moze wyrenderowac cudzej tresci.
     *
     * @return array{id_block:string,section_data:string,sd:array}|null
     */
    private function hbeProductsBlockData(int $idBlock): ?array
    {
        if ($idBlock <= 0) {
            return null;
        }

        $row = Db::getInstance()->getRow(
            'SELECT b.`id_block`, b.`section_data`
             FROM `' . _DB_PREFIX_ . 'hb_editor_block` b
             INNER JOIN `' . _DB_PREFIX_ . 'hb_editor_block_shop` bs
                 ON bs.id_block = b.id_block AND bs.id_shop = ' . (int) $this->context->shop->id . '
             WHERE b.`id_block` = ' . $idBlock . '
               AND b.`active` = 1
               AND b.`section_type` = "' . pSQL(HbEditorBlock::STYPE_PRODUCTS) . '"'
        );
        if (!$row) {
            return null;
        }

        $decoded = json_decode((string) $row['section_data'], true);
        $row['sd'] = is_array($decoded) ? $decoded : [];

        return $row;
    }

    /**
     * Id wszystkich aktywnych karuzel produktowych strony glownej biezacego
     * sklepu, w kolejnosci wyswietlania — uzywane przez rozgrzewanie cache z crona.
     *
     * @return int[]
     */
    public function getProductsCarouselIds(): array
    {
        // Kolejnosc ze strony glownej: rozgrzewanie idzie od gory, wiec kazda
        // karuzela zastaje swieze listy produktow karuzel wyzej.
        return array_keys($this->hbeCarouselOrder());
    }

    /**
     * Buduje na nowo wszystkie warianty jednej karuzeli, nie ogladajac sie na
     * TTL. Wolane przez rozgrzewanie z crona, zeby to cron placil za odbudowe,
     * a nie pierwszy gosc po wygasnieciu cache.
     *
     * @return int liczba zapisanych wariantow
     */
    public function warmProductsCarousel(int $idBlock): int
    {
        $block = $this->hbeProductsBlockData($idBlock);
        if ($block === null) {
            return 0;
        }

        $sd = $block['sd'];
        if ((int) ($sd['id_category'] ?? 0) <= 0) {
            return 0;
        }

        $variants = $this->hbeCarouselVariantDependent($idBlock, $sd) ? HbEditorCarouselCache::variants() : 1;

        $built = 0;
        for ($variant = 0; $variant < $variants; ++$variant) {
            $exclude = $this->hbeCarouselExcludeIds($idBlock, $sd, $variant, true) ?? [];
            $html = HbEditorCarouselCache::detokenize($this->buildProductsCarousel($sd, $exclude));
            if (!$this->hbeCarouselIsCacheable($html)) {
                continue;
            }
            if (HbEditorCarouselCache::set($this->hbeCarouselFile($idBlock, $sd, $variant, $exclude), $html)) {
                ++$built;
            }
            // Kolejne karuzele w tym samym rozgrzewaniu maja widziec nowa liste.
            $this->hbeCarouselIds[$idBlock][$variant] = $this->hbeExtractIds($html);
        }

        return $built;
    }

    /**
     * Czy wyrenderowany HTML nadaje sie do utrwalenia.
     *
     * Pusty wynik jest w porzadku — kategoria bez produktow nie ma czego pokazac.
     * Odrzucamy natomiast render urwany w polowie (fatal w trakcie skladania
     * szablonu): bez tego jeden zly render zamrozilby polowe sekcji w cache na
     * cala dobe.
     */
    private function hbeCarouselIsCacheable(string $html): bool
    {
        if (trim($html) === '') {
            return true;
        }

        return strpos($html, '</section>') !== false;
    }

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

            case HbEditorBlock::STYPE_PRODUCTS:
                // Karuzela produktow z kategorii — funkcjonalnosc przeniesiona z modulu multislider.
                // Dane: id_category / number / randomized w section_data, tytul per jezyk w section_data['langs'].
                // Wlasciwe renderowanie (i cache) siedzi w renderProductsCarousel(),
                // bo siega po nie takze endpoint doladowywania przy scrollu.
                return $this->renderProductsCarousel((int) ($block['id_block'] ?? 0), $sd);

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
