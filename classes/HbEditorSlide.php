<?php
/**
 * Hummingbird Editor — Slide Class
 *
 * Ported 1:1 from the standalone bemo_slider module (BemoSlide), relocated into
 * hummingbird_editor. Tables renamed to hb_editor_slider* to avoid collision
 * with the legacy module and to allow safe removal of bemo_slider afterwards.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class HbEditorSlide extends ObjectModel
{
    public $title;
    public $description;
    public $url;
    public $image;
    public $image_mobile;
    public $active;
    public $active_mobile;
    public $position;
    public $id_shop;

    // Custom fields
    public $text_position;
    public $show_text;
    public $overlay_is_transparent;
    public $overlay_color;
    public $overlay_opacity;

    // Per-slide CTA
    public $cta_enabled;
    public $cta_text;
    public $cta_color;
    public $cta_bg;
    public $cta_size;
    public $cta_radius;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'hb_editor_slider_slides',
        'primary' => 'id_hb_slide',
        'multilang' => true,
        'fields' => [
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'active_mobile' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true],

            'text_position' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => false],
            'show_text' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
            'overlay_is_transparent' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
            'overlay_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7, 'required' => false],
            'overlay_opacity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],

            // Per-slide CTA
            'cta_enabled' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false],
            'cta_text' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 100, 'required' => false],
            'cta_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7, 'required' => false],
            'cta_bg' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 7, 'required' => false],
            'cta_size' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 2, 'required' => false],
            'cta_radius' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],

            // Lang fields
            'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 4000],
            'title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'url' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'size' => 255],
            'image' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'image_mobile' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
        ],
    ];

    /**
     * Add slide
     */
    public function add($autodate = true, $null_values = false)
    {
        $context = Context::getContext();
        $id_shop = $context->shop->id;

        $res = parent::add($autodate, $null_values);

        // Calculate max position for this shop
        $max = Db::getInstance()->getValue('
            SELECT MAX(position) FROM `' . _DB_PREFIX_ . 'hb_editor_slider`
            WHERE `id_shop` = ' . (int) $id_shop
        );
        $position = (int) $max + 1;

        $res &= Db::getInstance()->execute('
            INSERT INTO `' . _DB_PREFIX_ . 'hb_editor_slider` (`id_shop`, `id_hb_slide`, `position`)
            VALUES(' . (int) $id_shop . ', ' . (int) $this->id . ', ' . (int) $position . ')'
        );

        return $res;
    }

    /**
     * Delete slide
     */
    public function delete()
    {
        $res = true;

        // Delete desktop images
        $images = $this->image;
        foreach ($images as $image) {
            if (preg_match('/sample/', $image) === 0) {
                if ($image && file_exists(__DIR__ . '/../images/' . $image)) {
                    $res &= @unlink(__DIR__ . '/../images/' . $image);
                }
            }
        }

        // Delete mobile images
        $images_mobile = $this->image_mobile;
        foreach ($images_mobile as $image) {
            if (preg_match('/sample/', $image) === 0) {
                if ($image && file_exists(__DIR__ . '/../images/' . $image)) {
                    $res &= @unlink(__DIR__ . '/../images/' . $image);
                }
            }
        }

        $res &= $this->reOrderPositions();

        $res &= Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_slider`
            WHERE `id_hb_slide` = ' . (int) $this->id
        );

        $res &= parent::delete();

        return $res;
    }

    /**
     * Update shop associations
     */
    public function updateShops($shop_ids)
    {
        if (empty($shop_ids)) {
            return false;
        }

        // Backup positions
        $positions = [];
        $rows = Db::getInstance()->executeS('SELECT id_shop, position FROM ' . _DB_PREFIX_ . 'hb_editor_slider WHERE id_hb_slide = ' . (int) $this->id);
        if ($rows) {
            foreach ($rows as $r) {
                $positions[$r['id_shop']] = $r['position'];
            }
        }

        // Remove existing associations
        Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'hb_editor_slider`
            WHERE `id_hb_slide` = ' . (int) $this->id
        );

        // Add new associations
        $values = [];
        foreach ($shop_ids as $id_shop) {
            $pos = isset($positions[$id_shop]) ? $positions[$id_shop] : 0;
            if ($pos == 0) {
                // New shop, get max position
                $max = Db::getInstance()->getValue('SELECT MAX(position) FROM ' . _DB_PREFIX_ . 'hb_editor_slider WHERE id_shop=' . (int) $id_shop);
                $pos = (int) $max + 1;
            }
            $values[] = '(' . (int) $id_shop . ', ' . (int) $this->id . ', ' . (int) $pos . ')';
        }

        if (!empty($values)) {
            return Db::getInstance()->execute('
                INSERT INTO `' . _DB_PREFIX_ . 'hb_editor_slider` (`id_shop`, `id_hb_slide`, `position`)
                VALUES ' . implode(', ', $values)
            );
        }

        return true;
    }

    /**
     * Reorder positions after deletion
     */
    public function reOrderPositions()
    {
        $shops = self::getAssociatedIdsShop($this->id);
        if (!$shops) {
            return true;
        }

        foreach ($shops as $id_shop) {
            $pos = Db::getInstance()->getValue('SELECT position FROM ' . _DB_PREFIX_ . 'hb_editor_slider WHERE id_hb_slide=' . (int) $this->id . ' AND id_shop=' . (int) $id_shop);
            if ($pos) {
                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'hb_editor_slider SET position = position - 1 WHERE id_shop=' . (int) $id_shop . ' AND position > ' . (int) $pos);
            }
        }

        return true;
    }

    /**
     * Next position for the given shop (ported from bemo_slider::getNextPosition).
     */
    public static function getNextPosition($id_shop)
    {
        $row = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->getRow(
            'SELECT MAX(bs.`position`) AS `next_position`
            FROM `' . _DB_PREFIX_ . 'hb_editor_slider_slides` bs, `' . _DB_PREFIX_ . 'hb_editor_slider` b
            WHERE bs.`id_hb_slide` = b.`id_hb_slide` AND b.`id_shop` = ' . (int) $id_shop
        );

        return ++$row['next_position'];
    }

    /**
     * Generate a sibling .webp variant for an uploaded raster image.
     * Ported from bemo_slider::generateWebpVariant().
     *
     * @param string $sourcePath absolute path to the source image
     * @return string|false webp path on success, false otherwise
     */
    public static function generateWebpVariant($sourcePath)
    {
        if (!is_string($sourcePath) || !is_file($sourcePath)) {
            return false;
        }
        if (!function_exists('imagewebp')) {
            return false;
        }
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['webp', 'svg'], true)) {
            return false;
        }
        $info = @getimagesize($sourcePath);
        if (!$info) {
            return false;
        }
        $img = null;
        switch ($info[2]) {
            case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg($sourcePath); break;
            case IMAGETYPE_PNG:  $img = @imagecreatefrompng($sourcePath);
                if ($img) { imagepalettetotruecolor($img); imagealphablending($img, true); imagesavealpha($img, true); }
                break;
            case IMAGETYPE_GIF:  $img = @imagecreatefromgif($sourcePath); break;
            default: return false;
        }
        if (!$img) {
            return false;
        }
        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $sourcePath);
        $ok = @imagewebp($img, $webpPath, 82);
        imagedestroy($img);
        return $ok ? $webpPath : false;
    }

    /**
     * Get associated shop IDs
     */
    public static function getAssociatedIdsShop($id_slide)
    {
        $result = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS('
            SELECT b.`id_shop`
            FROM `' . _DB_PREFIX_ . 'hb_editor_slider` b
            WHERE b.`id_hb_slide` = ' . (int) $id_slide
        );

        if (!is_array($result)) {
            return false;
        }

        $return = [];

        foreach ($result as $id_shop) {
            $return[] = (int) $id_shop['id_shop'];
        }

        return $return;
    }
}
