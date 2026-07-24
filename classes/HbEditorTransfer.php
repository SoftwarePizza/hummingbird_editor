<?php
declare(strict_types=1);
/**
 * Hummingbird Editor – Settings Exporter / Importer (XML).
 * Exports all HBE_* configuration values (incl. per-language), all custom
 * blocks (with lang & shop assignments) and bundles referenced images
 * from img/hb_editor/ as base64. The same XML can be imported into another
 * shop instance — languages are matched by ISO code, shop by current shop.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class HbEditorTransfer
{
    private const FORMAT_VERSION = '1.0';
    private const BACKUP_VERSION = '2.0';
    private const IMG_DIR_REL    = 'hb_editor';
    /** Slider images live inside the module, not in img/. */
    private const SLIDER_IMG_DIR_REL = 'hummingbird_editor/images/';

    /** @var array<string,array<string,bool>> cache of table columns for import resilience */
    private static $colCache = [];

    /**
     * Build XML string with everything.
     */
    public static function exportXml(): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->createElement('hbe_export');
        $root->setAttribute('version', self::FORMAT_VERSION);
        $root->setAttribute('exported_at', date('c'));
        $root->setAttribute('source_url', Tools::getShopDomainSsl(true));
        $root->setAttribute('module_version', '1.0.0');
        $dom->appendChild($root);

        // Languages map (id -> iso) for the export; used by importer to map back.
        $languages = Language::getLanguages(true);
        $langsEl = $dom->createElement('languages');
        $isoById = [];
        foreach ($languages as $lang) {
            $id = (int) $lang['id_lang'];
            $iso = (string) $lang['iso_code'];
            $isoById[$id] = $iso;
            $el = $dom->createElement('language');
            $el->setAttribute('id_lang', (string) $id);
            $el->setAttribute('iso_code', $iso);
            $el->setAttribute('name', (string) $lang['name']);
            $langsEl->appendChild($el);
        }
        $root->appendChild($langsEl);

        // Configuration values.
        $configsEl = $dom->createElement('configurations');
        $rows = (array) Db::getInstance()->executeS(
            'SELECT id_configuration, name, value FROM `' . _DB_PREFIX_ . 'configuration`
             WHERE name LIKE "HBE\_%" ORDER BY name'
        );
        $imageFilenames = [];
        foreach ($rows as $r) {
            $confEl = $dom->createElement('configuration');
            $confEl->setAttribute('name', (string) $r['name']);

            $defEl = $dom->createElement('default');
            $defEl->appendChild($dom->createCDATASection((string) ($r['value'] ?? '')));
            $confEl->appendChild($defEl);

            if (self::isImageKey((string) $r['name'])) {
                self::collectImageFilenames($r['value'] ?? '', $imageFilenames);
            }

            // Per-language values
            $langRows = (array) Db::getInstance()->executeS(
                'SELECT id_lang, value FROM `' . _DB_PREFIX_ . 'configuration_lang`
                 WHERE id_configuration = ' . (int) $r['id_configuration']
            );
            foreach ($langRows as $lr) {
                $iso = $isoById[(int) $lr['id_lang']] ?? '';
                if ($iso === '') { continue; }
                $langEl = $dom->createElement('lang');
                $langEl->setAttribute('iso_code', $iso);
                $langEl->appendChild($dom->createCDATASection((string) ($lr['value'] ?? '')));
                $confEl->appendChild($langEl);

                if (self::isImageKey((string) $r['name'])) {
                    self::collectImageFilenames($lr['value'] ?? '', $imageFilenames);
                }
            }
            $configsEl->appendChild($confEl);
        }
        $root->appendChild($configsEl);

        // Custom blocks.
        $blocksEl = $dom->createElement('blocks');
        $blocks = (array) Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'hb_editor_block` ORDER BY hook_name, position'
        );
        foreach ($blocks as $b) {
            $blockEl = $dom->createElement('block');
            $blockEl->setAttribute('hook_name', (string) $b['hook_name']);
            $blockEl->setAttribute('type', (string) $b['type']);
            $blockEl->setAttribute('position', (string) (int) $b['position']);
            $blockEl->setAttribute('active', (string) (int) $b['active']);
            $blockEl->setAttribute('mobile_different', (string) (int) $b['mobile_different']);
            $blockEl->setAttribute('image_desktop', (string) ($b['image_desktop'] ?? ''));
            $blockEl->setAttribute('image_mobile', (string) ($b['image_mobile'] ?? ''));

            if (!empty($b['image_desktop'])) {
                $imageFilenames[(string) $b['image_desktop']] = true;
            }
            if (!empty($b['image_mobile'])) {
                $imageFilenames[(string) $b['image_mobile']] = true;
            }

            $langRows = (array) Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'hb_editor_block_lang`
                 WHERE id_block = ' . (int) $b['id_block']
            );
            foreach ($langRows as $lr) {
                $iso = $isoById[(int) $lr['id_lang']] ?? '';
                if ($iso === '') { continue; }
                $langEl = $dom->createElement('lang');
                $langEl->setAttribute('iso_code', $iso);
                foreach (['content_desktop', 'content_mobile', 'link_desktop', 'link_mobile'] as $f) {
                    $fEl = $dom->createElement($f);
                    $fEl->appendChild($dom->createCDATASection((string) ($lr[$f] ?? '')));
                    $langEl->appendChild($fEl);
                }
                $blockEl->appendChild($langEl);
            }
            $blocksEl->appendChild($blockEl);
        }
        $root->appendChild($blocksEl);

        // Bundle referenced images as base64 (best-effort).
        $imagesEl = $dom->createElement('images');
        $imgDir = _PS_IMG_DIR_ . self::IMG_DIR_REL . '/';
        foreach (array_keys($imageFilenames) as $filename) {
            $filename = (string) $filename;
            if ($filename === '') { continue; }
            // Only a basename allowed
            $clean = basename($filename);
            $path = $imgDir . $clean;
            if (!is_file($path) || !is_readable($path)) { continue; }
            $data = @file_get_contents($path);
            if ($data === false) { continue; }
            $imgEl = $dom->createElement('image');
            $imgEl->setAttribute('filename', $clean);
            $imgEl->setAttribute('size', (string) strlen($data));
            // Base64 contains only [A-Za-z0-9+/=] so a plain text node is safe
            // and avoids libxml CDATA size limits when re-parsing.
            $imgEl->appendChild($dom->createTextNode(base64_encode($data)));
            $imagesEl->appendChild($imgEl);
        }
        $root->appendChild($imagesEl);

        return (string) $dom->saveXML();
    }

    /**
     * Import previously-exported XML payload.
     *
     * @return array{success:bool,error?:string,stats?:array<string,int>}
     */
    public static function importXml(string $xml, bool $purgeBlocks = true): array
    {
        $stats = ['configurations' => 0, 'config_lang' => 0, 'blocks' => 0, 'block_lang' => 0, 'images' => 0];

        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml);
        if ($sx === false) {
            return ['success' => false, 'error' => 'Nieprawidłowy plik XML'];
        }
        if ((string) $sx->getName() !== 'hbe_export') {
            return ['success' => false, 'error' => 'Plik nie jest eksportem Hummingbird Editor'];
        }

        // Languages: match by iso_code on this shop.
        $localLangs = Language::getLanguages(true);
        $idByIso = [];
        foreach ($localLangs as $l) {
            $idByIso[strtolower((string) $l['iso_code'])] = (int) $l['id_lang'];
        }

        // 1) Restore images first so their filenames are present on disk.
        $imgDir = _PS_IMG_DIR_ . self::IMG_DIR_REL . '/';
        if (!is_dir($imgDir)) {
            @mkdir($imgDir, 0755, true);
        }
        if (isset($sx->images->image)) {
            foreach ($sx->images->image as $imgNode) {
                $filename = basename((string) $imgNode['filename']);
                if ($filename === '' || strpos($filename, '..') !== false) { continue; }
                $bin = base64_decode((string) $imgNode, true);
                if ($bin === false) { continue; }
                $dest = $imgDir . $filename;
                if (@file_put_contents($dest, $bin) !== false) {
                    $stats['images']++;
                }
            }
        }

        // 2) Configuration values
        if (isset($sx->configurations->configuration)) {
            foreach ($sx->configurations->configuration as $confNode) {
                $name = (string) $confNode['name'];
                if ($name === '' || strncmp($name, 'HBE_', 4) !== 0) {
                    continue;
                }
                // Build localized values keyed by id_lang.
                $values = [];
                if (isset($confNode->lang)) {
                    foreach ($confNode->lang as $lEl) {
                        $iso = strtolower((string) $lEl['iso_code']);
                        if (!isset($idByIso[$iso])) { continue; }
                        $values[$idByIso[$iso]] = (string) $lEl;
                    }
                }
                $default = isset($confNode->default) ? (string) $confNode->default : '';
                if (!empty($values)) {
                    Configuration::updateValue($name, $values);
                    $stats['config_lang'] += count($values);
                }
                // Always also write the non-language base value (keeps non-lang reads working).
                Configuration::updateValue($name, $default);
                $stats['configurations']++;
            }
        }

        // 3) Blocks
        if (isset($sx->blocks->block)) {
            $db = Db::getInstance();
            $shopId = (int) Context::getContext()->shop->id;
            if ($purgeBlocks) {
                $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_block_lang`');
                $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_block_shop`');
                $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_block`');
            }
            foreach ($sx->blocks->block as $blockNode) {
                $hook = pSQL((string) $blockNode['hook_name']);
                $type = pSQL((string) $blockNode['type']);
                if ($hook === '' || $type === '') { continue; }
                $db->insert('hb_editor_block', [
                    'hook_name'        => $hook,
                    'type'             => $type,
                    'position'         => (int) $blockNode['position'],
                    'active'           => (int) $blockNode['active'],
                    'mobile_different' => (int) $blockNode['mobile_different'],
                    'image_desktop'    => $blockNode['image_desktop'] && (string) $blockNode['image_desktop'] !== ''
                        ? pSQL((string) $blockNode['image_desktop']) : null,
                    'image_mobile'     => $blockNode['image_mobile'] && (string) $blockNode['image_mobile'] !== ''
                        ? pSQL((string) $blockNode['image_mobile']) : null,
                    'date_add'         => date('Y-m-d H:i:s'),
                    'date_upd'         => date('Y-m-d H:i:s'),
                ]);
                $idBlock = (int) $db->Insert_ID();
                if ($idBlock <= 0) { continue; }
                $stats['blocks']++;

                // Always assign to the current shop on import.
                $db->insert('hb_editor_block_shop', [
                    'id_block' => $idBlock,
                    'id_shop'  => $shopId,
                ]);

                if (isset($blockNode->lang)) {
                    foreach ($blockNode->lang as $lEl) {
                        $iso = strtolower((string) $lEl['iso_code']);
                        if (!isset($idByIso[$iso])) { continue; }
                        $db->insert('hb_editor_block_lang', [
                            'id_block'        => $idBlock,
                            'id_lang'         => $idByIso[$iso],
                            'content_desktop' => isset($lEl->content_desktop) ? (string) $lEl->content_desktop : null,
                            'content_mobile'  => isset($lEl->content_mobile)  ? (string) $lEl->content_mobile  : null,
                            'link_desktop'    => isset($lEl->link_desktop)    ? (string) $lEl->link_desktop    : null,
                            'link_mobile'     => isset($lEl->link_mobile)     ? (string) $lEl->link_mobile     : null,
                        ]);
                        $stats['block_lang']++;
                    }
                }
            }
        }

        return ['success' => true, 'stats' => $stats];
    }

    /* ─────────────────────────────────────────────────────────────────────
     *  Full ZIP backup (v2) — bundles ALL config, blocks (incl. section_data),
     *  the slider (3 tables) and every referenced image from BOTH image
     *  directories (img/hb_editor/ + modules/hummingbird_editor/images/),
     *  including their .webp twins. Import restores everything on another shop.
     * ──────────────────────────────────────────────────────────────────── */

    /** Directory where generated backups are stored (gitignored, outside submodule). */
    public static function backupDir(): string
    {
        $dir = _PS_IMG_DIR_ . 'hb_editor_backups/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
            @file_put_contents($dir . 'index.php', "<?php\nheader('HTTP/1.1 404 Not Found');\n");
        }
        return $dir;
    }

    private static function blockImgDir(): string
    {
        return _PS_IMG_DIR_ . self::IMG_DIR_REL . '/';
    }

    private static function sliderImgDir(): string
    {
        return _PS_MODULE_DIR_ . self::SLIDER_IMG_DIR_REL;
    }

    /**
     * Build the full manifest (everything needed to recreate the module state).
     *
     * @return array<string,mixed>
     */
    public static function buildManifest(): array
    {
        $db = Db::getInstance();

        $isoById   = [];
        $langsOut  = [];
        foreach (Language::getLanguages(true) as $l) {
            $id  = (int) $l['id_lang'];
            $iso = (string) $l['iso_code'];
            $isoById[$id] = $iso;
            $langsOut[]   = ['id_lang' => $id, 'iso_code' => $iso, 'name' => (string) $l['name']];
        }

        // Configuration (HBE_*) with per-language values keyed by ISO.
        $configs = [];
        $rows = (array) $db->executeS(
            'SELECT id_configuration, name, value FROM `' . _DB_PREFIX_ . 'configuration`
             WHERE name LIKE "HBE\_%" ORDER BY name'
        );
        foreach ($rows as $r) {
            $entry = ['name' => (string) $r['name'], 'default' => (string) ($r['value'] ?? ''), 'lang' => []];
            $lr = (array) $db->executeS(
                'SELECT id_lang, value FROM `' . _DB_PREFIX_ . 'configuration_lang`
                 WHERE id_configuration = ' . (int) $r['id_configuration']
            );
            foreach ($lr as $x) {
                $iso = $isoById[(int) $x['id_lang']] ?? '';
                if ($iso === '') { continue; }
                $entry['lang'][$iso] = (string) ($x['value'] ?? '');
            }
            $configs[] = $entry;
        }

        // Custom blocks — ALL columns (incl. section_type / section_data).
        $blocks = [];
        $brows = (array) $db->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'hb_editor_block` ORDER BY hook_name, position'
        );
        foreach ($brows as $b) {
            $entry = [
                'hook_name'        => (string) $b['hook_name'],
                'type'             => (string) $b['type'],
                'section_type'     => (string) ($b['section_type'] ?? ''),
                'section_data'     => isset($b['section_data']) ? ($b['section_data'] === null ? null : (string) $b['section_data']) : null,
                'position'         => (int) $b['position'],
                'active'           => (int) $b['active'],
                'mobile_different' => (int) $b['mobile_different'],
                'image_desktop'    => ($b['image_desktop'] ?? null) !== null ? (string) $b['image_desktop'] : null,
                'image_mobile'     => ($b['image_mobile'] ?? null) !== null ? (string) $b['image_mobile'] : null,
                'lang'             => [],
            ];
            $lr = (array) $db->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'hb_editor_block_lang` WHERE id_block = ' . (int) $b['id_block']
            );
            foreach ($lr as $x) {
                $iso = $isoById[(int) $x['id_lang']] ?? '';
                if ($iso === '') { continue; }
                $entry['lang'][$iso] = [
                    'content_desktop' => ($x['content_desktop'] ?? null) !== null ? (string) $x['content_desktop'] : null,
                    'content_mobile'  => ($x['content_mobile']  ?? null) !== null ? (string) $x['content_mobile']  : null,
                    'link_desktop'    => ($x['link_desktop']    ?? null) !== null ? (string) $x['link_desktop']    : null,
                    'link_mobile'     => ($x['link_mobile']     ?? null) !== null ? (string) $x['link_mobile']     : null,
                ];
            }
            $blocks[] = $entry;
        }

        // Slider — slides config + per-language content (title/desc/url/image).
        $sliders   = [];
        $slideCols = self::slideConfigColumns();
        $srows = (array) $db->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'hb_editor_slider_slides` ORDER BY position, id_hb_slide'
        );
        foreach ($srows as $s) {
            $entry = [];
            foreach ($slideCols as $col) {
                if (array_key_exists($col, $s)) {
                    $entry[$col] = $s[$col];
                }
            }
            $entry['lang'] = [];
            $lr = (array) $db->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'hb_editor_slider_slides_lang` WHERE id_hb_slide = ' . (int) $s['id_hb_slide']
            );
            foreach ($lr as $x) {
                $iso = $isoById[(int) $x['id_lang']] ?? '';
                if ($iso === '') { continue; }
                $entry['lang'][$iso] = [
                    'title'        => (string) ($x['title'] ?? ''),
                    'description'  => (string) ($x['description'] ?? ''),
                    'url'          => (string) ($x['url'] ?? ''),
                    'image'        => (string) ($x['image'] ?? ''),
                    'image_mobile' => (string) ($x['image_mobile'] ?? ''),
                ];
            }
            $sliders[] = $entry;
        }

        $moduleVersion = '';
        $mod = Module::getInstanceByName('hummingbird_editor');
        if ($mod && isset($mod->version)) {
            $moduleVersion = (string) $mod->version;
        }

        return [
            'format'         => 'hbe_backup',
            'version'        => self::BACKUP_VERSION,
            'module_version' => $moduleVersion,
            'exported_at'    => date('c'),
            'source_url'     => Tools::getShopDomainSsl(true),
            'languages'      => $langsOut,
            'configurations' => $configs,
            'blocks'         => $blocks,
            'sliders'        => $sliders,
        ];
    }

    /**
     * Create a full backup ZIP on disk and return its path.
     *
     * @return array{success:bool,error?:string,path?:string,filename?:string,size?:int}
     */
    public static function exportZip(array $options = []): array
    {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'error' => 'Brak rozszerzenia PHP Zip (ZipArchive)'];
        }

        $manifest = self::buildManifest();
        // Optional: bundle appearance hooks + related modules snapshot.
        if (!empty($options['hooks'])) {
            $manifest['hooks_modules'] = self::buildHooksModules();
        }
        // One text blob used to detect which image files are actually referenced.
        $blob = (string) json_encode($manifest);

        $dir      = self::backupDir();
        $filename = 'hbe-backup-' . date('Y-m-d_His') . '.zip';
        $path     = $dir . $filename;

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'error' => 'Nie udało się utworzyć pliku ZIP'];
        }

        $zip->addFromString(
            'manifest.json',
            (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $imgCount = 0;
        $blockDir = self::blockImgDir();
        foreach (self::collectDirImages($blockDir, $blob) as $f) {
            if ($zip->addFile($blockDir . $f, 'images/hb_editor/' . $f)) { $imgCount++; }
        }
        $sliderDir = self::sliderImgDir();
        foreach (self::collectDirImages($sliderDir, $blob) as $f) {
            if ($zip->addFile($sliderDir . $f, 'images/slider/' . $f)) { $imgCount++; }
        }

        $zip->close();

        return [
            'success'  => true,
            'path'     => $path,
            'filename' => $filename,
            'size'     => (int) @filesize($path),
            'images'   => $imgCount,
        ];
    }

    /**
     * Import a full backup ZIP.
     *
     * @return array{success:bool,error?:string,stats?:array<string,int>}
     */
    public static function importZip(string $zipPath, bool $purge = true): array
    {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'error' => 'Brak rozszerzenia PHP Zip (ZipArchive)'];
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'error' => 'Nie udało się otworzyć archiwum ZIP'];
        }
        $json = $zip->getFromName('manifest.json');
        if ($json === false) {
            $zip->close();
            return ['success' => false, 'error' => 'Brak manifest.json w archiwum (to nie jest backup HBE)'];
        }
        $manifest = json_decode($json, true);
        if (!is_array($manifest) || ($manifest['format'] ?? '') !== 'hbe_backup') {
            $zip->close();
            return ['success' => false, 'error' => 'Nieprawidłowy plik backupu Hummingbird Editor'];
        }

        $stats = [
            'configurations' => 0, 'config_lang' => 0,
            'blocks' => 0, 'block_lang' => 0,
            'slides' => 0, 'slide_lang' => 0,
            'images' => 0,
            'hooks' => 0, 'hook_bindings' => 0, 'modules_enabled' => 0,
        ];

        // 1) Restore image files first (so their names exist on disk before DB).
        $blockDir  = self::blockImgDir();
        $sliderDir = self::sliderImgDir();
        if (!is_dir($blockDir))  { @mkdir($blockDir, 0755, true); }
        if (!is_dir($sliderDir)) { @mkdir($sliderDir, 0755, true); }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) { continue; }
            if (strncmp($name, 'images/hb_editor/', 17) === 0) {
                $destDir = $blockDir;
            } elseif (strncmp($name, 'images/slider/', 14) === 0) {
                $destDir = $sliderDir;
            } else {
                continue;
            }
            $base = basename($name);
            if ($base === '' || $base === 'index.php' || strpos($base, '..') !== false) { continue; }
            $data = $zip->getFromIndex($i);
            if ($data === false) { continue; }
            if (@file_put_contents($destDir . $base, $data) !== false) { $stats['images']++; }
        }
        $zip->close();

        // 2) Restore the database from the manifest.
        self::applyManifest($manifest, $purge, $stats);

        // 3) Optionally re-apply the hooks + related-modules snapshot.
        $missing = [];
        if (isset($manifest['hooks_modules']) && is_array($manifest['hooks_modules'])) {
            $missing = self::applyHooksModules($manifest['hooks_modules'], $stats);
        }

        return ['success' => true, 'stats' => $stats, 'missing_modules' => $missing];
    }

    /**
     * List backup ZIPs currently sitting in the server backup directory
     * (newest first). Used by the "import from server" picker.
     *
     * @return array<int,array{filename:string,size:int,size_h:string,mtime:int,date:string}>
     */
    public static function listServerBackups(): array
    {
        $dir = self::backupDir();
        $out = [];
        foreach ((array) glob($dir . 'hbe-backup-*.zip') as $p) {
            if (!is_file($p)) { continue; }
            $size = (int) @filesize($p);
            $mt   = (int) @filemtime($p);
            $out[] = [
                'filename' => basename($p),
                'size'     => $size,
                'size_h'   => self::humanSize($size),
                'mtime'    => $mt,
                'date'     => date('Y-m-d H:i', $mt),
            ];
        }
        usort($out, static function ($a, $b) { return $b['mtime'] <=> $a['mtime']; });
        return $out;
    }

    /**
     * Appearance-relevant hooks whose module assignments are worth transferring
     * between shops together with the editor content.
     */
    private const APPEARANCE_HOOKS = [
        'displayHome', 'displayBanner', 'displayTop', 'displayNav', 'displayNavFullWidth',
        'displayLeftColumn', 'displayRightColumn', 'displayFooter', 'displayFooterBefore',
        'displayFooterAfter', 'displayAfterBodyOpeningTag', 'displayHeader',
        'displayProductButtons', 'displayProductAdditionalInfo', 'displayShoppingCart',
        'displayOrderConfirmation', 'displayContentWrapperTop', 'displayContentWrapperBottom',
        'displayWrapperTop', 'displayWrapperBottom', 'displayNotFound', 'displayMaintenance',
        'displayReassurance', 'displayProductExtraContent', 'displayCustomerAccount',
    ];

    /**
     * Snapshot the module↔hook assignments for the appearance hooks (by NAME,
     * so it is portable across shops) plus the list of related modules
     * (those on the hooks + HBE-managed modules) with their active state.
     *
     * @return array<string,mixed>
     */
    public static function buildHooksModules(): array
    {
        $db     = Db::getInstance();
        $shopId = (int) Context::getContext()->shop->id;

        $hooksOut    = [];
        $moduleNames = [];
        foreach (self::APPEARANCE_HOOKS as $hn) {
            $idHook = (int) Hook::getIdByName($hn);
            if ($idHook <= 0) { continue; }
            $rows = (array) $db->executeS(
                'SELECT m.id_module, m.name, hm.position
                 FROM `' . _DB_PREFIX_ . 'hook_module` hm
                 JOIN `' . _DB_PREFIX_ . 'module` m ON m.id_module = hm.id_module
                 WHERE hm.id_hook = ' . $idHook . ' AND hm.id_shop = ' . $shopId . '
                 ORDER BY hm.position'
            );
            $entries = [];
            foreach ($rows as $r) {
                $mn = (string) $r['name'];
                $exc = (array) $db->executeS(
                    'SELECT file_name FROM `' . _DB_PREFIX_ . 'hook_module_exceptions`
                     WHERE id_module = ' . (int) $r['id_module'] . ' AND id_hook = ' . $idHook . ' AND id_shop = ' . $shopId
                );
                $files = [];
                foreach ($exc as $x) {
                    $fn = (string) ($x['file_name'] ?? '');
                    if ($fn !== '') { $files[] = $fn; }
                }
                $entries[] = ['module' => $mn, 'position' => (int) $r['position'], 'exceptions' => $files];
                $moduleNames[$mn] = true;
            }
            if ($entries) { $hooksOut[$hn] = $entries; }
        }

        // HBE-managed modules (detached from displayHome, rendered by the editor).
        foreach (explode(',', (string) (Configuration::get('HBE_MANAGED_MODULES') ?: '')) as $mn) {
            $mn = trim($mn);
            if ($mn !== '') { $moduleNames[$mn] = true; }
        }

        $modulesOut = [];
        foreach (array_keys($moduleNames) as $mn) {
            $mod = Module::getInstanceByName($mn);
            $modulesOut[] = [
                'name'      => $mn,
                'installed' => (bool) Module::isInstalled($mn),
                'active'    => (bool) ($mod && $mod->active),
                'version'   => $mod && isset($mod->version) ? (string) $mod->version : '',
            ];
        }

        $themeName = '';
        $ctx = Context::getContext();
        if ($ctx->shop && isset($ctx->shop->theme_name)) {
            $themeName = (string) $ctx->shop->theme_name;
        }

        return ['theme' => $themeName, 'hooks' => $hooksOut, 'modules' => $modulesOut];
    }

    /**
     * Re-apply a hooks/modules snapshot on the current shop (non-destructive:
     * it registers/repositions the recorded assignments and enables modules that
     * should be active, but never unhooks or disables anything extra). Modules
     * that are not installed on the target are collected and returned.
     *
     * @return array<int,string> names of modules referenced but not installed
     */
    public static function applyHooksModules(array $hm, array &$stats): array
    {
        $db      = Db::getInstance();
        $shopId  = (int) Context::getContext()->shop->id;
        $missing = [];

        // 1) Sync module active-state (enable-only, installed modules).
        foreach ((array) ($hm['modules'] ?? []) as $m) {
            $mn = (string) ($m['name'] ?? '');
            if ($mn === '') { continue; }
            if (!Module::isInstalled($mn)) { $missing[] = $mn; continue; }
            if (!empty($m['active'])) {
                $mod = Module::getInstanceByName($mn);
                if ($mod && !$mod->active) {
                    @$mod->enable();
                    $stats['modules_enabled']++;
                }
            }
        }

        // 2) Re-apply hook assignments (register + position + exceptions).
        foreach ((array) ($hm['hooks'] ?? []) as $hn => $entries) {
            $idHook = (int) Hook::getIdByName((string) $hn);
            if ($idHook <= 0) { continue; }
            foreach ((array) $entries as $e) {
                $mn = (string) ($e['module'] ?? '');
                if ($mn === '' || !Module::isInstalled($mn)) {
                    if ($mn !== '') { $missing[] = $mn; }
                    continue;
                }
                $mod = Module::getInstanceByName($mn);
                if (!$mod || !$mod->id) { continue; }

                $exists = (int) $db->getValue(
                    'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'hook_module`
                     WHERE id_hook = ' . $idHook . ' AND id_module = ' . (int) $mod->id . ' AND id_shop = ' . $shopId
                );
                if ($exists === 0) {
                    $mod->registerHook((string) $hn, [$shopId]);
                }
                $db->update(
                    'hook_module',
                    ['position' => (int) ($e['position'] ?? 0)],
                    'id_hook = ' . $idHook . ' AND id_module = ' . (int) $mod->id . ' AND id_shop = ' . $shopId
                );
                $stats['hook_bindings']++;

                $files = array_values(array_filter((array) ($e['exceptions'] ?? [])));
                if ($files) {
                    $mod->unregisterExceptions($idHook, [$shopId]);
                    $mod->registerExceptions($idHook, $files, [$shopId]);
                }
            }
            $stats['hooks']++;
        }

        // 3) Mirror the editor's behaviour: HBE-managed modules must be detached
        //    from displayHome (the editor renders them itself), otherwise they
        //    would render twice on the target.
        foreach (explode(',', (string) (Configuration::get('HBE_MANAGED_MODULES') ?: '')) as $mn) {
            $mn = trim($mn);
            if ($mn === '' || !Module::isInstalled($mn)) { continue; }
            $mod = Module::getInstanceByName($mn);
            if ($mod && $mod->id) {
                $mod->unregisterHook('displayHome', [$shopId]);
            }
        }

        return array_values(array_unique($missing));
    }

    /** Write the DB portion of a manifest onto the current shop. */
    private static function applyManifest(array $m, bool $purge, array &$stats): void
    {
        $db     = Db::getInstance();
        $shopId = (int) Context::getContext()->shop->id;

        $idByIso = [];
        foreach (Language::getLanguages(true) as $l) {
            $idByIso[strtolower((string) $l['iso_code'])] = (int) $l['id_lang'];
        }

        // Configuration
        foreach ((array) ($m['configurations'] ?? []) as $c) {
            $name = (string) ($c['name'] ?? '');
            if ($name === '' || strncmp($name, 'HBE_', 4) !== 0) { continue; }
            $values = [];
            foreach ((array) ($c['lang'] ?? []) as $iso => $val) {
                $iso = strtolower((string) $iso);
                if (!isset($idByIso[$iso])) { continue; }
                $values[$idByIso[$iso]] = (string) $val;
            }
            if ($values) {
                Configuration::updateValue($name, $values);
                $stats['config_lang'] += count($values);
            }
            Configuration::updateValue($name, (string) ($c['default'] ?? ''));
            $stats['configurations']++;
        }

        // Blocks
        if ($purge) {
            $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_block_lang`');
            $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_block_shop`');
            $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_block`');
        }
        foreach ((array) ($m['blocks'] ?? []) as $b) {
            $hook = pSQL((string) ($b['hook_name'] ?? ''));
            $type = pSQL((string) ($b['type'] ?? ''));
            if ($hook === '' || $type === '') { continue; }
            $data = self::onlyCols('hb_editor_block', [
                'hook_name'        => $hook,
                'type'             => $type,
                'section_type'     => pSQL((string) ($b['section_type'] ?? '')),
                'section_data'     => (($b['section_data'] ?? null) !== null) ? pSQL((string) $b['section_data'], true) : null,
                'position'         => (int) ($b['position'] ?? 0),
                'active'           => (int) ($b['active'] ?? 1),
                'mobile_different' => (int) ($b['mobile_different'] ?? 0),
                'image_desktop'    => !empty($b['image_desktop']) ? pSQL((string) $b['image_desktop']) : null,
                'image_mobile'     => !empty($b['image_mobile']) ? pSQL((string) $b['image_mobile']) : null,
                'date_add'         => date('Y-m-d H:i:s'),
                'date_upd'         => date('Y-m-d H:i:s'),
            ]);
            // NB: do NOT pass $null_values=true — DbCore::insert turns every empty
            // string into NULL then, which violates NOT NULL columns (e.g. section_type).
            if (!$db->insert('hb_editor_block', $data)) { continue; }
            $idBlock = (int) $db->Insert_ID();
            if ($idBlock <= 0) { continue; }
            $stats['blocks']++;

            $db->insert('hb_editor_block_shop', ['id_block' => $idBlock, 'id_shop' => $shopId]);

            foreach ((array) ($b['lang'] ?? []) as $iso => $lv) {
                $iso = strtolower((string) $iso);
                if (!isset($idByIso[$iso])) { continue; }
                $ok = $db->insert('hb_editor_block_lang', self::onlyCols('hb_editor_block_lang', [
                    'id_block'        => $idBlock,
                    'id_lang'         => $idByIso[$iso],
                    'content_desktop' => pSQL((string) ($lv['content_desktop'] ?? ''), true),
                    'content_mobile'  => pSQL((string) ($lv['content_mobile'] ?? ''), true),
                    'link_desktop'    => pSQL((string) ($lv['link_desktop'] ?? '')),
                    'link_mobile'     => pSQL((string) ($lv['link_mobile'] ?? '')),
                ]));
                if ($ok) { $stats['block_lang']++; }
            }
        }

        // Slider
        if ($purge) {
            $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_slider_slides_lang`');
            $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_slider_slides`');
            $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_slider`');
        }
        $stringCols = ['overlay_color', 'cta_text', 'cta_color', 'cta_bg', 'cta_size'];
        foreach ((array) ($m['sliders'] ?? []) as $s) {
            $row = [];
            foreach (self::slideConfigColumns() as $col) {
                if (!array_key_exists($col, $s)) { continue; }
                $row[$col] = in_array($col, $stringCols, true) ? pSQL((string) $s[$col]) : (int) $s[$col];
            }
            if (!$db->insert('hb_editor_slider_slides', self::onlyCols('hb_editor_slider_slides', $row))) { continue; }
            $idSlide = (int) $db->Insert_ID();
            if ($idSlide <= 0) { continue; }
            $stats['slides']++;

            $db->insert('hb_editor_slider', [
                'id_shop'     => $shopId,
                'id_hb_slide' => $idSlide,
                'position'    => (int) ($s['position'] ?? 0),
            ]);

            foreach ((array) ($s['lang'] ?? []) as $iso => $lv) {
                $iso = strtolower((string) $iso);
                if (!isset($idByIso[$iso])) { continue; }
                // slides_lang columns are NOT NULL DEFAULT '' — keep empty strings
                // as-is (no $null_values), otherwise the row is rejected.
                $ok = $db->insert('hb_editor_slider_slides_lang', self::onlyCols('hb_editor_slider_slides_lang', [
                    'id_hb_slide'  => $idSlide,
                    'id_lang'      => $idByIso[$iso],
                    'title'        => pSQL((string) ($lv['title'] ?? '')),
                    'description'  => pSQL((string) ($lv['description'] ?? ''), true),
                    'url'          => pSQL((string) ($lv['url'] ?? '')),
                    'image'        => pSQL((string) ($lv['image'] ?? '')),
                    'image_mobile' => pSQL((string) ($lv['image_mobile'] ?? '')),
                ]));
                if ($ok) { $stats['slide_lang']++; }
            }
        }
    }

    /** Non-lang columns of hb_editor_slider_slides that carry slide config. */
    private static function slideConfigColumns(): array
    {
        return [
            'position', 'active', 'active_mobile', 'text_position', 'show_text',
            'overlay_is_transparent', 'overlay_color', 'overlay_opacity',
            'cta_enabled', 'cta_text', 'cta_color', 'cta_bg', 'cta_size', 'cta_radius',
        ];
    }

    /**
     * Return files inside $dir whose name-stem is referenced anywhere in $blob
     * (catches originals, .webp twins and images embedded in HTML content).
     *
     * @return array<int,string>
     */
    private static function collectDirImages(string $dir, string $blob): array
    {
        $out = [];
        if (!is_dir($dir) || $blob === '') { return $out; }
        foreach ((array) @scandir($dir) as $f) {
            if ($f === '.' || $f === '..' || $f === 'index.php') { continue; }
            if (!is_file($dir . $f)) { continue; }
            $stem = (string) preg_replace('/\.[^.]+$/', '', $f);
            if ($stem !== '' && strpos($blob, $stem) !== false) {
                $out[] = $f;
            }
        }
        return $out;
    }

    /** Keep only keys that are real columns of $table (schema-drift safe import). */
    private static function onlyCols(string $table, array $data): array
    {
        if (!isset(self::$colCache[$table])) {
            $cols = [];
            foreach ((array) Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . bqSQL($table) . '`') as $r) {
                $cols[(string) $r['Field']] = true;
            }
            self::$colCache[$table] = $cols;
        }
        $known = self::$colCache[$table];
        $out   = [];
        foreach ($data as $k => $v) {
            if (isset($known[$k])) { $out[$k] = $v; }
        }
        return $out;
    }

    private static function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) { return round($bytes / 1048576, 1) . ' MB'; }
        if ($bytes >= 1024) { return round($bytes / 1024) . ' KB'; }
        return $bytes . ' B';
    }

    private static function isImageKey(string $name): bool
    {
        // Heuristic: HBE_ keys ending with IMAGE / IMG_n / _IMG / containing _IMG_
        return (bool) preg_match('/(_IMAGE$|_IMG$|_IMG_\d+$)/', $name);
    }

    private static function collectImageFilenames($value, array &$out): void
    {
        $value = (string) $value;
        if ($value === '') { return; }
        $clean = basename($value);
        // Only allow simple filenames (no slashes, no path traversal)
        if ($clean !== '' && $clean === $value && strpos($clean, '..') === false) {
            $out[$clean] = true;
        }
    }
}
