<?php
/**
 * Sekcje motywu i „Treści motywu” — pola edytowalne przez obsługę, opisane schematem JSON w motywie.
 *
 * Motyw (najpierw dziecko, potem rodzic) trzyma w katalogu `hbe/`:
 *   sections/<slug>.json + sections/<slug>.tpl  — sekcja (blok w hooku, np. displayHome),
 *   fields.json                                  — teksty/zdjęcia globalne ($hbe_theme w Smarty).
 *
 * Schemat sekcji: {"label","help","fields":[ pole, … ]}, pole: {key,label,type,help,default,
 * placeholder,rows,width,count,row_label,fields,defaults}. Typy: text | textarea | html | url
 * (per język), image (jedno zdjęcie dla wszystkich języków, plik w img/hb_editor/), group
 * (stała liczba `count` powtórzeń własnych `fields`; `defaults` = lista wartości per wiersz).
 * fields.json: {"groups":[{key,label,help,fields:[…]}]}.
 *
 * Dane sekcji (kolumna section_data): {"slug":"tiles","langs":{"1":{"title":"…",
 * "tiles.0.title":"…"}},"files":{"tiles.0.image":"theme_….jpg"}}. Klucz pola w grupie to
 * <grupa>.<indeks>.<pole>. Klucz nieobecny = wartość domyślna ze schematu; obecny pusty = pusto.
 *
 * Szablon sekcji dostaje $sec (wartości: teksty jako string, zdjęcia jako [file,url,webp_url],
 * grupy jako listy) i $sec_block (id, slug); pola globalne są w $hbe_theme na całym froncie.
 */
declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

class HbEditorThemeSection
{
    const STYPE       = 'theme';
    const CONF_FIELDS = 'HBE_THEME_FIELDS';
    const FILE_PREFIX = 'theme_';
    const IMG_DIR     = 'hb_editor/';
    /** Chromium do PDF-ów sekcji (schemat: "pdf": {template,target,label}). */
    const CHROMIUM    = '/usr/bin/chromium';

    /** Hooki proponowane w panelu przy dodawaniu sekcji (blok zadziała też w każdym innym). */
    const HOOKS = [
        'displayHome',
        'displayProductAdditionalInfo',
        'displayFooterProduct',
        'displayContentWrapperTop',
        'displayContentWrapperBottom',
        'displayFooterBefore',
        'displayFooterAfter',
        'displayNotFound',
        'displayLeftColumn',
        'displayRightColumn',
    ];

    /** @var array<string,array|null> */
    private static $schemaCache = [];
    /** @var array<int,array>|null */
    private static $globalCache = [];

    /* ── Pliki motywu ─────────────────────────────────────────────────────── */

    /**
     * Katalogi motywu z ewentualnym `hbe/` — dziecko przed rodzicem (rodzic z theme.yml).
     *
     * @return string[]
     */
    public static function themeDirs(): array
    {
        $shop = Context::getContext()->shop;
        $name = (string) ($shop->theme_name ?? '');
        if ($name === '' && isset($shop->theme) && is_object($shop->theme) && method_exists($shop->theme, 'getName')) {
            $name = (string) $shop->theme->getName();
        }
        if ($name === '' || !preg_match('/^[A-Za-z0-9_\-]+$/', $name)) {
            return [];
        }
        $dirs = [_PS_ALL_THEMES_DIR_ . $name . '/'];
        $yml  = _PS_ALL_THEMES_DIR_ . $name . '/config/theme.yml';
        if (is_file($yml) && preg_match('/^parent:\s*([A-Za-z0-9_\-]+)/m', (string) file_get_contents($yml), $m)) {
            $dirs[] = _PS_ALL_THEMES_DIR_ . $m[1] . '/';
        }

        return $dirs;
    }

    /** Pierwszy istniejący plik `hbe/<rel>` w łańcuchu motywów albo null. */
    public static function findFile(string $rel): ?string
    {
        foreach (self::themeDirs() as $dir) {
            $path = $dir . 'hbe/' . $rel;
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function loadSchema(string $slug): ?array
    {
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return null;
        }
        if (array_key_exists($slug, self::$schemaCache)) {
            return self::$schemaCache[$slug];
        }
        $file   = self::findFile('sections/' . $slug . '.json');
        $schema = $file ? json_decode((string) file_get_contents($file), true) : null;
        if (!is_array($schema) || empty($schema['fields']) || !is_array($schema['fields'])) {
            $schema = null;
        } else {
            $schema['slug']  = $slug;
            $schema['label'] = (string) ($schema['label'] ?? $slug);
            $schema['help']  = (string) ($schema['help'] ?? '');
        }

        return self::$schemaCache[$slug] = $schema;
    }

    /**
     * Sekcje dostępne w motywie: slug => schemat (posortowane po etykiecie).
     *
     * @return array<string,array>
     */
    public static function listSections(): array
    {
        $out = [];
        foreach (self::themeDirs() as $dir) {
            foreach (glob($dir . 'hbe/sections/*.json') ?: [] as $file) {
                $slug = basename($file, '.json');
                if (isset($out[$slug])) {
                    continue;
                }
                $schema = self::loadSchema($slug);
                if ($schema) {
                    $out[$slug] = $schema;
                }
            }
        }
        uasort($out, static function (array $a, array $b): int {
            return strcasecmp($a['label'], $b['label']);
        });

        return $out;
    }

    public static function loadFieldsSchema(): ?array
    {
        $file   = self::findFile('fields.json');
        $schema = $file ? json_decode((string) file_get_contents($file), true) : null;
        if (!is_array($schema) || empty($schema['groups']) || !is_array($schema['groups'])) {
            return null;
        }
        foreach ($schema['groups'] as $i => &$g) {
            $g['key']   = preg_replace('/[^a-z0-9_\-]/i', '', (string) ($g['key'] ?? 'g' . $i)) ?: 'g' . $i;
            $g['label'] = (string) ($g['label'] ?? $g['key']);
            $g['help']  = (string) ($g['help'] ?? '');
            if (empty($g['fields']) || !is_array($g['fields'])) {
                $g['fields'] = [];
            }
        }
        unset($g);

        return $schema;
    }

    /* ── Nazwy pól formularza ────────────────────────────────────────────── */

    public static function inputName(string $path): string
    {
        return 'tf[' . $path . ']';
    }

    /** $_FILES nie zniesie kropek w nazwie (PHP zamienia je na podkreślenia), stąd własna forma. */
    public static function fileInput(string $path): string
    {
        return 'tfimg_' . preg_replace('/[^a-z0-9]+/i', '_', $path);
    }

    /* ── Wartości ────────────────────────────────────────────────────────── */

    /**
     * Wartość tekstowa pola dla języka: zapisana (także pusta) → język domyślny sklepu →
     * dowolny język → domyślna ze schematu.
     */
    public static function langValue(array $data, string $path, int $idLang, string $default): string
    {
        $langs = is_array($data['langs'] ?? null) ? $data['langs'] : [];
        if (isset($langs[$idLang]) && is_array($langs[$idLang]) && array_key_exists($path, $langs[$idLang])) {
            return (string) $langs[$idLang][$path];
        }
        $def = (int) Configuration::get('PS_LANG_DEFAULT');
        if (isset($langs[$def]) && is_array($langs[$def]) && array_key_exists($path, $langs[$def])) {
            return (string) $langs[$def][$path];
        }
        foreach ($langs as $l) {
            if (is_array($l) && array_key_exists($path, $l) && (string) $l[$path] !== '') {
                return (string) $l[$path];
            }
        }

        return $default;
    }

    private static function fileValue(array $data, string $path, string $default): string
    {
        $files = is_array($data['files'] ?? null) ? $data['files'] : [];

        return array_key_exists($path, $files) ? (string) $files[$path] : $default;
    }

    /**
     * Wartości do szablonu frontu (rekurencyjnie po grupach).
     *
     * @param Hummingbird_editor $module do adresów zdjęć
     */
    public static function resolve(array $fields, array $data, int $idLang, $module, string $prefix = '', array $groupDefaults = []): array
    {
        $out = [];
        foreach ($fields as $f) {
            $key = (string) ($f['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string) ($f['type'] ?? 'text');
            if ($type === 'group') {
                $count = max(1, (int) ($f['count'] ?? 1));
                $defs  = is_array($f['defaults'] ?? null) ? $f['defaults'] : [];
                $list  = [];
                for ($i = 0; $i < $count; $i++) {
                    $list[] = self::resolve(
                        is_array($f['fields'] ?? null) ? $f['fields'] : [],
                        $data,
                        $idLang,
                        $module,
                        $prefix . $key . '.' . $i . '.',
                        is_array($defs[$i] ?? null) ? $defs[$i] : []
                    );
                }
                $out[$key] = $list;
                continue;
            }
            $path    = $prefix . $key;
            $default = array_key_exists($key, $groupDefaults) ? $groupDefaults[$key] : ($f['default'] ?? '');
            if ($type === 'list') {
                $out[$key] = self::listRows($data, $path, is_array($default) ? $default : []);
                continue;
            }
            if ($type === 'image') {
                $file = self::fileValue($data, $path, (string) $default);
                $src  = $file !== '' ? $module->hbeImageSources($file) : ['url' => '', 'webp_url' => ''];
                $out[$key] = ['file' => $file, 'url' => (string) $src['url'], 'webp_url' => (string) $src['webp_url']];
            } else {
                $out[$key] = self::langValue($data, $path, $idLang, (string) $default);
            }
        }

        return $out;
    }

    /**
     * Drzewo do formularza w panelu: liście z wartościami per język / adresem zdjęcia,
     * grupy jako wiersze liści.
     *
     * @param array<int,array<string,mixed>> $languages
     */
    public static function formTree(array $fields, array $data, array $languages, $module, string $prefix = '', array $groupDefaults = []): array
    {
        $nodes = [];
        foreach ($fields as $f) {
            $key = (string) ($f['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string) ($f['type'] ?? 'text');
            if ($type === 'group') {
                $count = max(1, (int) ($f['count'] ?? 1));
                $defs  = is_array($f['defaults'] ?? null) ? $f['defaults'] : [];
                $rows  = [];
                for ($i = 0; $i < $count; $i++) {
                    $rows[] = self::formTree(
                        is_array($f['fields'] ?? null) ? $f['fields'] : [],
                        $data,
                        $languages,
                        $module,
                        $prefix . $key . '.' . $i . '.',
                        is_array($defs[$i] ?? null) ? $defs[$i] : []
                    );
                }
                $nodes[] = [
                    'kind'      => 'group',
                    'key'       => $key,
                    'label'     => (string) ($f['label'] ?? $key),
                    'row_label' => (string) ($f['row_label'] ?? ''),
                    'help'      => (string) ($f['help'] ?? ''),
                    'rows'      => $rows,
                ];
                continue;
            }
            $path    = $prefix . $key;
            $default = array_key_exists($key, $groupDefaults) ? $groupDefaults[$key] : ($f['default'] ?? '');
            $node    = [
                'kind'        => 'field',
                'type'        => $type,
                'key'         => $key,
                'path'        => $path,
                'label'       => (string) ($f['label'] ?? $key),
                'help'        => (string) ($f['help'] ?? ''),
                'placeholder' => (string) ($f['placeholder'] ?? ''),
                'rows'        => (int) ($f['rows'] ?? ($type === 'html' ? 6 : 3)),
                'width'       => (int) ($f['width'] ?? ($type === 'html' ? 12 : ($type === 'textarea' ? 6 : 4))),
                'input'       => self::inputName($path),
                'file_input'  => self::fileInput($path),
            ];
            if ($type === 'list') {
                $node['width']       = 12;
                $node['row_label']   = (string) ($f['row_label'] ?? 'Pozycja');
                $node['add_label']   = (string) ($f['add_label'] ?? ('+ ' . $node['row_label']));
                $node['schema_json'] = json_encode(self::listFieldsSchema(is_array($f['fields'] ?? null) ? $f['fields'] : []), JSON_UNESCAPED_UNICODE);
                $node['value_json']  = json_encode(self::listRows($data, $path, is_array($default) ? $default : []), JSON_UNESCAPED_UNICODE);
                $nodes[] = $node;
                continue;
            }
            if ($type === 'image') {
                $file         = self::fileValue($data, $path, (string) $default);
                $node['file'] = $file;
                $node['url']  = $file !== '' ? (string) $module->hbeImageSources($file)['url'] : '';
            } else {
                $vals = [];
                foreach ($languages as $lang) {
                    $lid        = (int) $lang['id_lang'];
                    $vals[$lid] = self::langValue($data, $path, $lid, (string) $default);
                }
                $node['values'] = $vals;
            }
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * Liście schematu z pełnymi ścieżkami (do zapisu).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function flatten(array $fields, string $prefix = ''): array
    {
        $out = [];
        foreach ($fields as $f) {
            $key = (string) ($f['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string) ($f['type'] ?? 'text');
            if ($type === 'group') {
                $count = max(1, (int) ($f['count'] ?? 1));
                for ($i = 0; $i < $count; $i++) {
                    foreach (self::flatten(is_array($f['fields'] ?? null) ? $f['fields'] : [], $prefix . $key . '.' . $i . '.') as $leaf) {
                        $out[] = $leaf;
                    }
                }
                continue;
            }
            $leaf         = $f;
            $leaf['type'] = $type;
            $leaf['path'] = $prefix . $key;
            $out[]        = $leaf;
        }

        return $out;
    }

    /* ── Zapis z formularza ──────────────────────────────────────────────── */

    /**
     * Wczytuje POST (tf[ścieżka][id_lang], tfdel[ścieżka]) i $_FILES (tfimg_…) i zwraca
     * zaktualizowane dane. Wszystkie pola tekstowe z formularza są zapisywane (także puste),
     * więc od pierwszego zapisu wartości domyślne ze schematu przestają obowiązywać.
     *
     * @param array<int,array<string,mixed>> $languages
     * @param string[]                        $errors
     */
    public static function collect(array $leaves, array $data, array $languages, string $filePrefix, array &$errors): array
    {
        $data['langs'] = is_array($data['langs'] ?? null) ? $data['langs'] : [];
        $data['files'] = is_array($data['files'] ?? null) ? $data['files'] : [];
        $data['lists'] = is_array($data['lists'] ?? null) ? $data['lists'] : [];
        $posted = Tools::getValue('tf');
        $posted = is_array($posted) ? $posted : [];
        $del    = Tools::getValue('tfdel');
        $del    = is_array($del) ? $del : [];

        foreach ($leaves as $leaf) {
            $path = (string) $leaf['path'];
            if ($leaf['type'] === 'image') {
                $old      = (string) ($data['files'][$path] ?? '');
                $uploaded = self::upload(self::fileInput($path), $filePrefix, $path, (string) $leaf['label'], $errors);
                if ($uploaded !== null) {
                    self::unlinkOwn($old);
                    $data['files'][$path] = $uploaded;
                } elseif (!empty($del[$path])) {
                    self::unlinkOwn($old);
                    $data['files'][$path] = '';
                }
                continue;
            }
            if ($leaf['type'] === 'list') {
                $lists = Tools::getValue('tflist');
                if (is_array($lists) && array_key_exists($path, $lists)) {
                    $rows = json_decode((string) $lists[$path], true);
                    $data['lists'][$path] = self::sanitizeRows(
                        is_array($rows) ? $rows : [],
                        is_array($leaf['fields'] ?? null) ? $leaf['fields'] : []
                    );
                }
                continue;
            }
            if (!isset($posted[$path]) || !is_array($posted[$path])) {
                continue;
            }
            foreach ($languages as $lang) {
                $lid = (int) $lang['id_lang'];
                if (!array_key_exists($lid, $posted[$path])) {
                    continue;
                }
                $value = (string) $posted[$path][$lid];
                if ($leaf['type'] !== 'html') {
                    $value = strip_tags($value);
                }
                if (!isset($data['langs'][$lid]) || !is_array($data['langs'][$lid])) {
                    $data['langs'][$lid] = [];
                }
                $data['langs'][$lid][$path] = $value;
            }
        }

        return $data;
    }

    /**
     * Przesłane zdjęcie pola → plik w img/hb_editor/ (plus wariant webp). Null = nic nie przesłano.
     *
     * @param string[] $errors
     */
    private static function upload(string $input, string $filePrefix, string $path, string $label, array &$errors): ?string
    {
        if (!isset($_FILES[$input]) || empty($_FILES[$input]['name'])
            || (int) ($_FILES[$input]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $file = $_FILES[$input];
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $label . ': błąd przesyłania (kod ' . (int) $file['error'] . ')';
            return null;
        }
        if ((int) $file['size'] > 8 * 1024 * 1024) {
            $errors[] = $label . ': plik większy niż 8 MB';
            return null;
        }
        $allowed = [
            'image/jpeg'    => 'jpg',
            'image/png'     => 'png',
            'image/gif'     => 'gif',
            'image/webp'    => 'webp',
            'image/svg+xml' => 'svg',
        ];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($file['tmp_name']);
        $ext   = $allowed[$mime] ?? null;
        if ($ext === null && strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) === 'svg'
            && stripos((string) file_get_contents($file['tmp_name'], false, null, 0, 4096), '<svg') !== false) {
            $ext = 'svg';
        }
        if ($ext === null) {
            $errors[] = $label . ': dozwolone są JPG, PNG, GIF, WebP i SVG (otrzymano ' . $mime . ')';
            return null;
        }
        $name = self::FILE_PREFIX . preg_replace('/[^a-z0-9]+/', '-', strtolower($filePrefix . '-' . $path))
            . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = _PS_IMG_DIR_ . self::IMG_DIR . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $errors[] = $label . ': nie udało się zapisać pliku';
            return null;
        }
        @chmod($dest, 0644);
        if ($ext !== 'svg' && class_exists('HbEditorSlide')) {
            HbEditorSlide::generateWebpVariant($dest);
        }

        return $name;
    }

    /** Kasuje tylko pliki wgrane przez ten mechanizm (prefiks theme_) — nie zdjęcia współdzielone. */
    private static function unlinkOwn(string $file): void
    {
        if ($file === '' || strpos($file, self::FILE_PREFIX) !== 0 || strpos($file, '/') !== false) {
            return;
        }
        foreach ([$file, (string) preg_replace('/\.[^.]+$/', '.webp', $file)] as $name) {
            $path = _PS_IMG_DIR_ . self::IMG_DIR . $name;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Adresy zdjęć pól (ścieżka => url) — do odświeżenia podglądów po zapisie.
     *
     * @return array<string,string>
     */
    public static function fileUrls(array $leaves, array $data, $module): array
    {
        $out = [];
        foreach ($leaves as $leaf) {
            if ($leaf['type'] !== 'image') {
                continue;
            }
            $file = self::fileValue($data, (string) $leaf['path'], (string) ($leaf['default'] ?? ''));
            $out[(string) $leaf['path']] = $file !== '' ? (string) $module->hbeImageSources($file)['url'] : '';
        }

        return $out;
    }

    /* ── Listy dynamiczne (typ pola `list`) ─────────────────────────────── */

    /**
     * Wiersze listy: zapisane (section_data.lists[path]) albo domyślne ze schematu.
     * Lista nie jest per język (treść jednojęzyczna, np. menu kawiarni).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function listRows(array $data, string $path, array $default): array
    {
        $lists = is_array($data['lists'] ?? null) ? $data['lists'] : [];
        if (isset($lists[$path]) && is_array($lists[$path])) {
            return array_values($lists[$path]);
        }

        return array_values($default);
    }

    /**
     * Czyści wiersze z POST wg schematu: znane klucze, tekst bez tagów, checkbox 0/1,
     * listy zagnieżdżone rekurencyjnie; każdy wiersz ma flagę `hidden`.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function sanitizeRows(array $rows, array $fields, int $depth = 0): array
    {
        $out = [];
        if ($depth > 3) {
            return $out;
        }
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $clean = ['hidden' => !empty($row['hidden']) ? 1 : 0];
            foreach ($fields as $f) {
                $k = (string) ($f['key'] ?? '');
                if ($k === '' || $k === 'hidden') {
                    continue;
                }
                $t = (string) ($f['type'] ?? 'text');
                if ($t === 'list') {
                    $clean[$k] = self::sanitizeRows(
                        is_array($row[$k] ?? null) ? $row[$k] : [],
                        is_array($f['fields'] ?? null) ? $f['fields'] : [],
                        $depth + 1
                    );
                } elseif ($t === 'checkbox') {
                    $clean[$k] = !empty($row[$k]) ? 1 : 0;
                } else {
                    $clean[$k] = trim(strip_tags((string) ($row[$k] ?? '')));
                }
            }
            $out[] = $clean;
        }

        return $out;
    }

    /**
     * Schemat wierszy dla edytora list w JS (klucz, etykieta, typ, szerokość, podpowiedź,
     * pola zagnieżdżone).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function listFieldsSchema(array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $k = (string) ($f['key'] ?? '');
            if ($k === '') {
                continue;
            }
            $t = (string) ($f['type'] ?? 'text');
            $e = [
                'key'         => $k,
                'label'       => (string) ($f['label'] ?? $k),
                'type'        => in_array($t, ['text', 'textarea', 'checkbox', 'list'], true) ? $t : 'text',
                'width'       => (int) ($f['width'] ?? ($t === 'textarea' ? 12 : 4)),
                'placeholder' => (string) ($f['placeholder'] ?? ''),
            ];
            if ($t === 'list') {
                $e['row_label'] = (string) ($f['row_label'] ?? 'Pozycja');
                $e['add_label'] = (string) ($f['add_label'] ?? '');
                $e['fields']    = self::listFieldsSchema(is_array($f['fields'] ?? null) ? $f['fields'] : []);
            }
            $out[] = $e;
        }

        return $out;
    }

    /* ── PDF sekcji (Chromium --print-to-pdf) ────────────────────────────── */

    /**
     * Opis PDF-a sekcji ze schematu: "pdf": {"template": "<tpl bez .tpl>", "target": "img/…/plik.pdf",
     * "label": "…"}. Null = sekcja bez PDF-a. Cel tylko w img/.
     *
     * @return array{template:string,target:string,label:string,exists:bool,url:string}|null
     */
    public static function pdfInfo(array $schema): ?array
    {
        $pdf = $schema['pdf'] ?? null;
        if (!is_array($pdf) || empty($pdf['target']) || empty($pdf['template'])) {
            return null;
        }
        $target = (string) $pdf['target'];
        if (!preg_match('#^img/[a-z0-9_\-/]+\.pdf$#i', $target) || strpos($target, '..') !== false) {
            return null;
        }
        $path = _PS_ROOT_DIR_ . '/' . $target;
        $ok   = is_file($path);

        return [
            'template' => preg_replace('/[^a-z0-9\-]/', '', (string) $pdf['template']),
            'target'   => $target,
            'label'    => (string) ($pdf['label'] ?? 'PDF'),
            'exists'   => $ok,
            'url'      => $ok ? __PS_BASE_URI__ . $target . '?' . filemtime($path) : '',
        ];
    }

    /**
     * Renderuje szablon PDF sekcji do HTML i drukuje Chromium do pliku docelowego.
     * Zwraca adres nowego PDF-a albo null (błąd w $errors; zapis sekcji nie ma przez to padać).
     *
     * @param string[] $errors
     */
    public static function renderPdf($module, array $schema, array $sd, array &$errors): ?string
    {
        $info = self::pdfInfo($schema);
        if (!$info) {
            return null;
        }
        $tpl = self::findFile('sections/' . $info['template'] . '.tpl');
        if (!$tpl) {
            $errors[] = 'PDF: brak szablonu hbe/sections/' . $info['template'] . '.tpl w motywie';
            return null;
        }
        if (!is_executable(self::CHROMIUM)) {
            $errors[] = 'PDF: brak Chromium (' . self::CHROMIUM . ')';
            return null;
        }
        $context  = Context::getContext();
        $idLang   = (int) $context->language->id;
        $logo     = (string) Configuration::get('PS_LOGO');
        $logoData = '';
        if ($logo !== '' && is_file(_PS_IMG_DIR_ . $logo)) {
            $mime     = preg_match('/\.svg$/i', $logo) ? 'image/svg+xml' : ((string) (@mime_content_type(_PS_IMG_DIR_ . $logo) ?: 'image/png'));
            $logoData = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents(_PS_IMG_DIR_ . $logo));
        }
        $address = trim(
            (string) Configuration::get('PS_SHOP_ADDR1') . ', '
            . (string) Configuration::get('PS_SHOP_CODE') . ' ' . (string) Configuration::get('PS_SHOP_CITY'),
            ", \t"
        );
        $context->smarty->assign([
            'sec'       => self::resolve($schema['fields'], $sd, $idLang, $module),
            'hbe_theme' => self::globalValues($module),
            'pdf_meta'  => [
                'logo_data' => $logoData,
                'shop_name' => (string) Configuration::get('PS_SHOP_NAME'),
                'address'   => $address,
                'generated' => date('d.m.Y'),
            ],
        ]);
        $html = (string) $context->smarty->fetch($tpl);

        $tmpDir   = rtrim(sys_get_temp_dir(), '/');
        $stamp    = bin2hex(random_bytes(4));
        $htmlFile = $tmpDir . '/hbe-pdf-' . $stamp . '.html';
        $pdfTmp   = $tmpDir . '/hbe-pdf-' . $stamp . '.pdf';
        file_put_contents($htmlFile, $html);
        $uid = function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();
        $cmd = 'timeout 90 ' . escapeshellarg(self::CHROMIUM)
            . ' --headless=new --disable-gpu --no-sandbox --hide-scrollbars'
            . ' --user-data-dir=' . escapeshellarg($tmpDir . '/hbe-chrome-' . $uid)
            . ' --no-pdf-header-footer --print-to-pdf=' . escapeshellarg($pdfTmp)
            . ' ' . escapeshellarg('file://' . $htmlFile) . ' 2>&1';
        $outLines = [];
        $code     = 0;
        exec($cmd, $outLines, $code);
        @unlink($htmlFile);
        if (!is_file($pdfTmp) || filesize($pdfTmp) < 1000) {
            @unlink($pdfTmp);
            $errors[] = 'PDF: Chromium nie wygenerował pliku (kod ' . (int) $code . '). ' . implode(' ', array_slice($outLines, -2));
            return null;
        }
        $dest = _PS_ROOT_DIR_ . '/' . $info['target'];
        if (!@rename($pdfTmp, $dest)) {
            @copy($pdfTmp, $dest);
            @unlink($pdfTmp);
        }
        @chmod($dest, 0644);
        clearstatcache(true, $dest);

        return __PS_BASE_URI__ . $info['target'] . '?' . filemtime($dest);
    }

    /* ── Sekcje w dowolnym szablonie / treści CMS ────────────────────────── */

    /**
     * Renderuje pierwszą aktywną sekcję motywu o danym slugu (dowolny hook) —
     * dla {hbe_section slug='…'} i skrótu [hbe-section:slug].
     */
    public static function renderBySlug($module, string $slug): string
    {
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return '';
        }
        $idShop = (int) Context::getContext()->shop->id;
        $row = Db::getInstance()->getRow(
            'SELECT b.* FROM `' . _DB_PREFIX_ . 'hb_editor_block` b
             INNER JOIN `' . _DB_PREFIX_ . 'hb_editor_block_shop` bs ON bs.id_block = b.id_block AND bs.id_shop = ' . $idShop . '
             WHERE b.section_type = "' . pSQL(self::STYPE) . '" AND b.active = 1
               AND b.section_data LIKE "%\"slug\":\"' . pSQL($slug) . '\"%"
             ORDER BY b.position ASC'
        );
        if (!$row) {
            return '';
        }
        $sd = json_decode((string) $row['section_data'], true);
        if (!is_array($sd) || (string) ($sd['slug'] ?? '') !== $slug) {
            return '';
        }

        return self::render($module, $row, $sd);
    }

    /** Podmienia [hbe-section:slug] w HTML-u (np. treść strony CMS) na wyrenderowane sekcje. */
    public static function replaceShortcodes($module, string $html): string
    {
        if (strpos($html, '[hbe-section:') === false) {
            return $html;
        }

        return (string) preg_replace_callback('/\[hbe-section:([a-z0-9\-]+)\]/', static function (array $m) use ($module): string {
            return self::renderBySlug($module, $m[1]);
        }, $html);
    }

    /* ── Front ───────────────────────────────────────────────────────────── */

    /** Renderuje sekcję motywu (blok section_type=theme). Brak schematu/szablonu = pusty string. */
    public static function render($module, array $block, array $sd): string
    {
        $slug   = (string) ($sd['slug'] ?? '');
        $schema = self::loadSchema($slug);
        $tpl    = $schema ? self::findFile('sections/' . $slug . '.tpl') : null;
        if (!$schema || !$tpl) {
            return '';
        }
        $context = Context::getContext();
        $idLang  = (int) $context->language->id;
        $context->smarty->assign([
            'sec'       => self::resolve($schema['fields'], $sd, $idLang, $module),
            'sec_block' => ['id' => (int) ($block['id_block'] ?? 0), 'slug' => $slug],
            'hbe_theme' => self::globalValues($module),
            'sec_pdf'   => self::pdfInfo($schema) ?: ['url' => '', 'exists' => false],
        ]);

        return (string) $context->smarty->fetch($tpl);
    }

    /**
     * Pola globalne motywu dla bieżącego języka (cache na żądanie).
     *
     * @return array<string,mixed>
     */
    public static function globalValues($module): array
    {
        $idLang = (int) Context::getContext()->language->id;
        if (isset(self::$globalCache[$idLang])) {
            return self::$globalCache[$idLang];
        }
        $schema = self::loadFieldsSchema();
        $out    = [];
        if ($schema) {
            $data = self::globalData();
            foreach ($schema['groups'] as $g) {
                $out += self::resolve($g['fields'], $data, $idLang, $module);
            }
        }

        return self::$globalCache[$idLang] = $out;
    }

    public static function globalData(): array
    {
        $raw = Configuration::get(self::CONF_FIELDS);
        $d   = $raw ? json_decode((string) $raw, true) : null;

        return is_array($d) ? $d : [];
    }

    public static function saveGlobalData(array $data): void
    {
        Configuration::updateValue(self::CONF_FIELDS, json_encode($data, JSON_UNESCAPED_UNICODE), true);
        self::$globalCache = [];
    }

    /* ── Okładki stron CMS (img/cms/<link_rewrite>.jpg) ──────────────────── */

    /**
     * Kategorie CMS (bez korzenia) i strony CMS z bieżącą okładką.
     *
     * @return array<int,array{kind:string,id:int,name:string,rewrite:string,url:string,input:string}>
     */
    public static function cmsCovers(int $idLang, int $idShop): array
    {
        $db  = Db::getInstance();
        $out = [];
        $cats = (array) $db->executeS(
            'SELECT c.id_cms_category AS id, cl.name, cl.link_rewrite
             FROM `' . _DB_PREFIX_ . 'cms_category` c
             INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
                 ON cl.id_cms_category = c.id_cms_category AND cl.id_lang = ' . (int) $idLang . ' AND cl.id_shop = ' . (int) $idShop . '
             WHERE c.id_cms_category > 1 AND c.active = 1
             ORDER BY c.position ASC'
        );
        foreach ($cats as $r) {
            $out[] = self::coverRow('category', (int) $r['id'], (string) $r['name'], (string) $r['link_rewrite']);
        }
        $pages = (array) $db->executeS(
            'SELECT c.id_cms AS id, c.id_cms_category, cl.meta_title AS name, cl.link_rewrite
             FROM `' . _DB_PREFIX_ . 'cms` c
             INNER JOIN `' . _DB_PREFIX_ . 'cms_lang` cl
                 ON cl.id_cms = c.id_cms AND cl.id_lang = ' . (int) $idLang . ' AND cl.id_shop = ' . (int) $idShop . '
             WHERE c.active = 1
             ORDER BY c.id_cms_category ASC, c.position ASC'
        );
        foreach ($pages as $r) {
            $out[] = self::coverRow('page', (int) $r['id'], (string) $r['name'], (string) $r['link_rewrite']);
        }

        return $out;
    }

    private static function coverRow(string $kind, int $id, string $name, string $rewrite): array
    {
        $ok   = (bool) preg_match('/^[a-z0-9\-]+$/', $rewrite);
        $file = _PS_IMG_DIR_ . 'cms/' . $rewrite . '.jpg';
        $url  = ($ok && is_file($file)) ? __PS_BASE_URI__ . 'img/cms/' . $rewrite . '.jpg?' . filemtime($file) : '';

        return [
            'kind'    => $kind,
            'id'      => $id,
            'name'    => $name,
            'rewrite' => $ok ? $rewrite : '',
            'url'     => $url,
            'input'   => 'cmscover_' . $kind . '_' . $id,
        ];
    }

    /**
     * Zapisuje przesłaną okładkę jako img/cms/<rewrite>.jpg (konwersja do JPEG). Null = brak pliku.
     *
     * @param string[] $errors
     */
    public static function saveCmsCover(string $input, string $rewrite, string $label, array &$errors): ?string
    {
        if ($rewrite === '' || !isset($_FILES[$input]) || empty($_FILES[$input]['name'])
            || (int) ($_FILES[$input]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $file = $_FILES[$input];
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $label . ': błąd przesyłania (kod ' . (int) $file['error'] . ')';
            return null;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            $errors[] = $label . ': okładka musi być JPG, PNG lub WebP';
            return null;
        }
        $dest = _PS_IMG_DIR_ . 'cms/' . $rewrite . '.jpg';
        if (!ImageManager::resize($file['tmp_name'], $dest, null, null, 'jpg')) {
            $errors[] = $label . ': nie udało się zapisać okładki';
            return null;
        }
        @chmod($dest, 0644);
        clearstatcache(true, $dest);

        return __PS_BASE_URI__ . 'img/cms/' . $rewrite . '.jpg?' . filemtime($dest);
    }
}
