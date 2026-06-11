<?php
/**
 * 1.4.0 — listing banners on category pages (displayListingBanner).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_0($module)
{
    return $module->registerHook('displayListingBanner');
}
