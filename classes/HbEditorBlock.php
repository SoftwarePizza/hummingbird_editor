<?php
declare(strict_types=1);
/**
 * Hummingbird Editor – block data class
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class HbEditorBlock
{
    /* ── Generic block types ──────────────────────────────────────────────── */
    const TYPE_TEXT  = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_HTML  = 'html';

    /* ── Section block types (duplicatable static sections) ──────────────── */
    const STYPE_INFOBAR    = 'infobar';
    const STYPE_IMGHERO    = 'imghero';
    const STYPE_COLS3      = 'cols3';
    const STYPE_COLS3DESC  = 'cols3desc';
    const STYPE_TAGLINE    = 'tagline';
    const STYPE_KATCOLS    = 'katcols';
    const STYPE_SPLITBLOCK = 'splitblock';
    const STYPE_ICONS4     = 'icons4';
    const STYPE_BRANDS     = 'brands';

    public static function getTypes(): array
    {
        return [self::TYPE_TEXT, self::TYPE_IMAGE, self::TYPE_HTML];
    }

    public static function getSectionTypes(): array
    {
        return [
            self::STYPE_INFOBAR,
            self::STYPE_IMGHERO,
            self::STYPE_COLS3,
            self::STYPE_COLS3DESC,
            self::STYPE_TAGLINE,
            self::STYPE_KATCOLS,
            self::STYPE_SPLITBLOCK,
            self::STYPE_ICONS4,
            self::STYPE_BRANDS,
        ];
    }

    /* ── CRUD ─────────────────────────────────────────────────────────────── */

    public static function getById(int $idBlock): ?array
    {
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'hb_editor_block`
             WHERE id_block = ' . (int) $idBlock
        );
        return $row ?: null;
    }

    /**
     * Returns all blocks (with all lang data) for the admin list.
     */
    public static function getAllForAdmin(): array
    {
        return (array) Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'hb_editor_block`
             ORDER BY hook_name ASC, position ASC'
        );
    }

    /**
     * Returns blocks for a specific hook + shop + lang for frontend rendering.
     */
    public static function getByHook(string $hookName, int $idShop, int $idLang): array
    {
        return (array) Db::getInstance()->executeS(
            'SELECT b.*, bl.content_desktop, bl.content_mobile, bl.link_desktop, bl.link_mobile
             FROM `' . _DB_PREFIX_ . 'hb_editor_block` b
             INNER JOIN `' . _DB_PREFIX_ . 'hb_editor_block_shop` bs
                 ON bs.id_block = b.id_block AND bs.id_shop = ' . (int) $idShop . '
             LEFT JOIN `' . _DB_PREFIX_ . 'hb_editor_block_lang` bl
                 ON bl.id_block = b.id_block AND bl.id_lang = ' . (int) $idLang . '
             WHERE b.hook_name = "' . pSQL($hookName) . '" AND b.active = 1
             ORDER BY b.position ASC'
        );
    }

    /**
     * Returns all lang rows for one block (keyed by id_lang).
     */
    public static function getLangData(int $idBlock): array
    {
        $rows = (array) Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'hb_editor_block_lang`
             WHERE id_block = ' . (int) $idBlock
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['id_lang']] = $r;
        }
        return $out;
    }

    /**
     * Returns shop IDs assigned to a block.
     */
    public static function getShopIds(int $idBlock): array
    {
        $rows = (array) Db::getInstance()->executeS(
            'SELECT id_shop FROM `' . _DB_PREFIX_ . 'hb_editor_block_shop`
             WHERE id_block = ' . (int) $idBlock
        );
        return array_column($rows, 'id_shop');
    }

    /**
     * Create a new block. Returns new id_block or false.
     */
    public static function create(array $data): int
    {
        $db = Db::getInstance();
        $db->insert('hb_editor_block', [
            'hook_name'        => pSQL($data['hook_name']),
            'type'             => pSQL($data['type'] ?? 'text'),
            'section_type'     => pSQL($data['section_type'] ?? ''),
            'section_data'     => isset($data['section_data']) ? pSQL($data['section_data']) : null,
            'position'         => (int) ($data['position'] ?? 0),
            'active'           => (int) ($data['active'] ?? 1),
            'mobile_different' => (int) ($data['mobile_different'] ?? 0),
            'image_desktop'    => isset($data['image_desktop']) ? pSQL($data['image_desktop']) : null,
            'image_mobile'     => isset($data['image_mobile']) ? pSQL($data['image_mobile']) : null,
            'date_add'         => date('Y-m-d H:i:s'),
            'date_upd'         => date('Y-m-d H:i:s'),
        ]);
        return (int) $db->Insert_ID();
    }

    /**
     * Update non-lang fields of a block.
     */
    public static function update(int $idBlock, array $data): bool
    {
        $fields = [
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        if (array_key_exists('hook_name', $data)) {
            $fields['hook_name'] = pSQL($data['hook_name']);
        }
        if (array_key_exists('type', $data)) {
            $fields['type'] = pSQL($data['type']);
        }
        if (array_key_exists('section_type', $data)) {
            $fields['section_type'] = pSQL($data['section_type']);
        }
        if (array_key_exists('section_data', $data)) {
            $fields['section_data'] = $data['section_data'] !== null ? pSQL($data['section_data']) : null;
        }
        if (array_key_exists('active', $data)) {
            $fields['active'] = (int) $data['active'];
        }
        if (array_key_exists('mobile_different', $data)) {
            $fields['mobile_different'] = (int) $data['mobile_different'];
        }
        if (array_key_exists('image_desktop', $data)) {
            $fields['image_desktop'] = $data['image_desktop'] ? pSQL($data['image_desktop']) : null;
        }
        if (array_key_exists('image_mobile', $data)) {
            $fields['image_mobile'] = $data['image_mobile'] ? pSQL($data['image_mobile']) : null;
        }
        return Db::getInstance()->update('hb_editor_block', $fields, 'id_block = ' . (int) $idBlock);
    }

    /**
     * Save lang data for a block (upsert for all languages).
     */
    public static function saveLang(int $idBlock, array $langData): void
    {
        $db = Db::getInstance();
        foreach ($langData as $idLang => $fields) {
            $db->delete(
                'hb_editor_block_lang',
                'id_block = ' . (int) $idBlock . ' AND id_lang = ' . (int) $idLang
            );
            $db->insert('hb_editor_block_lang', [
                'id_block'        => (int) $idBlock,
                'id_lang'         => (int) $idLang,
                'content_desktop' => $fields['content_desktop'] ?? null,
                'content_mobile'  => $fields['content_mobile'] ?? null,
                'link_desktop'    => $fields['link_desktop'] ?? null,
                'link_mobile'     => $fields['link_mobile'] ?? null,
            ]);
        }
    }

    /**
     * Assign block to shops.
     */
    public static function setShops(int $idBlock, array $shopIds): void
    {
        $db = Db::getInstance();
        $db->delete('hb_editor_block_shop', 'id_block = ' . (int) $idBlock);
        foreach (array_unique(array_map('intval', $shopIds)) as $idShop) {
            $db->insert('hb_editor_block_shop', [
                'id_block' => (int) $idBlock,
                'id_shop'  => $idShop,
            ]);
        }
    }

    /**
     * Delete a block and all associated lang/shop data.
     */
    public static function delete(int $idBlock): bool
    {
        $db = Db::getInstance();
        $db->delete('hb_editor_block_lang', 'id_block = ' . (int) $idBlock);
        $db->delete('hb_editor_block_shop', 'id_block = ' . (int) $idBlock);
        return $db->delete('hb_editor_block', 'id_block = ' . (int) $idBlock);
    }

    /**
     * Duplicate a block — copies all fields, images and lang data.
     * Returns the new id_block on success, 0 on failure.
     */
    public static function duplicate(int $idBlock): int
    {
        $src = self::getById($idBlock);
        if (!$src) {
            return 0;
        }

        $imgDir  = _PS_IMG_DIR_ . 'hb_editor/';
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

        // Copy generic block images
        $newDesktop = $copyImg($src['image_desktop'] ?? null);
        $newMobile  = $copyImg($src['image_mobile']  ?? null);

        // Deep-copy images embedded in section_data JSON
        $newSectionData = null;
        if (!empty($src['section_data'])) {
            $sd = json_decode((string) $src['section_data'], true);
            if (is_array($sd)) {
                foreach (['image', 'image_mobile', 'l_image', 'l_image_mobile',
                          'r_image', 'r_image_mobile', 'm_image', 'm_image_mobile'] as $imgKey) {
                    if (!empty($sd[$imgKey])) {
                        $copied = $copyImg($sd[$imgKey]);
                        if ($copied) {
                            $sd[$imgKey] = $copied;
                        }
                    }
                }
                // imgs array (icons4: keys d/m; brands: key img)
                if (!empty($sd['imgs']) && is_array($sd['imgs'])) {
                    foreach ($sd['imgs'] as &$imgEntry) {
                        foreach (['d', 'm', 'img'] as $k) {
                            if (!empty($imgEntry[$k])) {
                                $copied = $copyImg($imgEntry[$k]);
                                if ($copied) {
                                    $imgEntry[$k] = $copied;
                                }
                            }
                        }
                    }
                    unset($imgEntry);
                }
                // per-lang images (ml mode)
                if (!empty($sd['img_langs']) && is_array($sd['img_langs'])) {
                    foreach ($sd['img_langs'] as &$langImgs) {
                        foreach (['desktop', 'mobile'] as $k) {
                            if (!empty($langImgs[$k])) {
                                $copied = $copyImg($langImgs[$k]);
                                if ($copied) {
                                    $langImgs[$k] = $copied;
                                }
                            }
                        }
                    }
                    unset($langImgs);
                }
                $newSectionData = json_encode($sd, JSON_UNESCAPED_UNICODE);
            }
        }

        $position = self::getNextPosition($src['hook_name']);
        $newId    = self::create([
            'hook_name'        => $src['hook_name'],
            'type'             => $src['type'],
            'section_type'     => $src['section_type'] ?? '',
            'section_data'     => $newSectionData,
            'active'           => (int) $src['active'],
            'mobile_different' => (int) $src['mobile_different'],
            'position'         => $position,
            'image_desktop'    => $newDesktop,
            'image_mobile'     => $newMobile,
        ]);

        if (!$newId) {
            return 0;
        }

        // Copy lang data (generic blocks)
        $langData = self::getLangData($idBlock);
        if ($langData) {
            $copyLang = [];
            foreach ($langData as $lid => $row) {
                $copyLang[(int) $lid] = [
                    'content_desktop' => $row['content_desktop'] ?? '',
                    'content_mobile'  => $row['content_mobile']  ?? '',
                    'link_desktop'    => $row['link_desktop']     ?? '',
                    'link_mobile'     => $row['link_mobile']      ?? '',
                ];
            }
            self::saveLang($newId, $copyLang);
        }

        // Copy shop assignments
        $shopIds = self::getShopIds($idBlock);
        if (!$shopIds) {
            $shopIds = [(int) Context::getContext()->shop->id];
        }
        self::setShops($newId, $shopIds);

        return $newId;
    }

    /**
     * Update positions. $positions = [[id_block => N, position => P], ...]
     */
    public static function updatePositions(array $positions): void
    {
        foreach ($positions as $p) {
            Db::getInstance()->update(
                'hb_editor_block',
                ['position' => (int) $p['position']],
                'id_block = ' . (int) $p['id_block']
            );
        }
    }

    /**
     * Toggle active status.
     */
    public static function toggleActive(int $idBlock, bool $active): bool
    {
        return Db::getInstance()->update(
            'hb_editor_block',
            ['active' => (int) $active, 'date_upd' => date('Y-m-d H:i:s')],
            'id_block = ' . (int) $idBlock
        );
    }

    /**
     * Returns list of unique hook names currently used.
     */
    public static function getUsedHookNames(): array
    {
        $rows = (array) Db::getInstance()->executeS(
            'SELECT DISTINCT hook_name FROM `' . _DB_PREFIX_ . 'hb_editor_block` ORDER BY hook_name'
        );
        return array_column($rows, 'hook_name');
    }

    /**
     * Returns next available position for a hook.
     */
    public static function getNextPosition(string $hookName): int
    {
        $max = Db::getInstance()->getValue(
            'SELECT MAX(position) FROM `' . _DB_PREFIX_ . 'hb_editor_block`
             WHERE hook_name = "' . pSQL($hookName) . '"'
        );
        return (int) $max + 1;
    }

    /**
     * Ensure schema has the section_type and section_data columns (upgrade helper).
     */
    public static function upgradeSchema(): void
    {
        $db = Db::getInstance();
        $p  = _DB_PREFIX_;

        $cols = (array) $db->executeS("SHOW COLUMNS FROM `{$p}hb_editor_block`");
        $existing = array_column($cols, 'Field');

        if (!in_array('section_type', $existing, true)) {
            $db->execute(
                "ALTER TABLE `{$p}hb_editor_block`
                 ADD COLUMN `section_type` VARCHAR(50) NOT NULL DEFAULT '' AFTER `type`"
            );
        }
        if (!in_array('section_data', $existing, true)) {
            $db->execute(
                "ALTER TABLE `{$p}hb_editor_block`
                 ADD COLUMN `section_data` MEDIUMTEXT DEFAULT NULL AFTER `section_type`"
            );
        }
    }
}
