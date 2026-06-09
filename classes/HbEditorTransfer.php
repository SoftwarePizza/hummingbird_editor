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
    private const IMG_DIR_REL    = 'hb_editor';

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
