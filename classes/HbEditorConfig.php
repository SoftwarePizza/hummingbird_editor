<?php
/**
 * Hummingbird Editor — Configuration access helper (multishop-aware).
 *
 * Single entry point for reading/writing the module's `HBE_*` settings so that
 * values resolve against the CURRENT shop context the same way on both sides
 * (read == write). PrestaShop's Configuration is already shop-context aware;
 * this wrapper makes the behaviour explicit and self-documenting, removes the
 * former slider asymmetry (read used an explicit shop id while writes relied on
 * the implicit context), and adds a lang -> non-lang fallback for translations.
 *
 * Scope rules (matching PrestaShop conventions):
 *  - single-shop admin context  -> read/write a per-shop override
 *  - "All shops" / unset context -> read/write the global default inherited by
 *    every shop
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class HbEditorConfig
{
    /**
     * Read an `HBE_*` value for the active shop context.
     *
     * @param string   $key
     * @param int|null $idLang when set, reads the translated value and falls
     *                         back to the non-lang value if it is empty
     *
     * @return mixed configuration value (false when the key is unknown)
     */
    public static function get(string $key, ?int $idLang = null)
    {
        $idShopGroup = Shop::getContextShopGroupID(true);
        $idShop = Shop::getContextShopID(true);

        $value = Configuration::get($key, $idLang, $idShopGroup, $idShop);

        if (($value === false || $value === null || $value === '') && $idLang !== null) {
            // Translated value missing for this shop: fall back to the
            // context value without a language (Configuration then resolves
            // shop -> group -> global on its own).
            $value = Configuration::get($key, null, $idShopGroup, $idShop);
        }

        return $value;
    }

    /**
     * Write an `HBE_*` value into the active shop context.
     *
     * @param string                     $key
     * @param array<int,string>|string|int|float $value scalar, or array<idLang,string> for multilang
     * @param bool                       $html
     *
     * @return bool
     */
    public static function set(string $key, $value, bool $html = false): bool
    {
        $idShopGroup = Shop::getContextShopGroupID(true);
        $idShop = Shop::getContextShopID(true);

        return (bool) Configuration::updateValue($key, $value, $html, $idShopGroup, $idShop);
    }
}
