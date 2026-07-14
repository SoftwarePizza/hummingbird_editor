<?php
/**
 * 1.10.0 — the cart's free-shipping bar gets a source, not just an amount.
 *
 * Until now the bar fell back to PS_SHIPPING_FREE_PRICE whenever no manual
 * amount was set. On this shop that setting had drifted to 500 while every
 * carrier (DHL, DPD, InPost) actually ships free from 250 — so the bar was
 * promising customers a threshold the checkout did not honour.
 *
 * HBE_CART_FREE_SHIPPING_MODE now says where the number comes from:
 * auto (read back from the carrier price ranges) / manual / shop / off.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_10_0($module)
{
    // Preserve intent: a shop that had typed a manual amount keeps using it.
    // Everything else switches to reading the carriers, which is the fix.
    $manual = (float) Configuration::get('HBE_CART_FREE_SHIPPING_THRESHOLD');
    $mode = $manual > 0
        ? Hummingbird_editor::FREE_SHIPPING_MODE_MANUAL
        : Hummingbird_editor::FREE_SHIPPING_MODE_AUTO;

    Configuration::updateValue('HBE_CART_FREE_SHIPPING_MODE', $mode);

    if (Configuration::get('HBE_CART_FREE_SHIPPING_THRESHOLD') === false) {
        Configuration::updateValue('HBE_CART_FREE_SHIPPING_THRESHOLD', 0);
    }

    return true;
}
