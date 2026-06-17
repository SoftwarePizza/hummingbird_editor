<?php
/**
 * 1.6.0 — wishlist preview drawer (Figma: Ulubione). No new hooks; the drawer
 * shell renders via displayAfterBodyOpeningTag and assets via
 * actionFrontControllerSetMedia, both already registered.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_6_0($module)
{
    if (Configuration::get('HBE_WISHLIST_PREVIEW_ENABLED') === false) {
        Configuration::updateValue('HBE_WISHLIST_PREVIEW_ENABLED', 1);
    }

    return true;
}
