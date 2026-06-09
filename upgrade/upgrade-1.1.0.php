<?php
/**
 * Upgrade 1.0.0 → 1.1.0
 * Adds the slider (ported from bemo_slider): creates tables, default settings
 * and migrates existing bemo_slider data + images when present.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    // 1) Slider tables
    if (!$module->ensureSliderSchema()) {
        return false;
    }

    // 2) Module images dir for slides
    $sliderPath = _PS_MODULE_DIR_ . 'hummingbird_editor/images/';
    if (!is_dir($sliderPath)) {
        @mkdir($sliderPath, 0755, true);
    }

    // 3) Default global settings (only if missing)
    $defaults = [
        'HBE_SLIDER_SPEED'          => 5000,
        'HBE_SLIDER_AUTOPLAY'       => 1,
        'HBE_SLIDER_PAUSE_ON_HOVER' => 1,
        'HBE_SLIDER_SHOW_ARROWS'    => 0,
        'HBE_SLIDER_SHOW_DOTS'      => 1,
    ];
    foreach ($defaults as $key => $val) {
        if (Configuration::get($key) === false) {
            Configuration::updateValue($key, $val);
        }
    }

    // 4) One-time data/image migration from the legacy bemo_slider (idempotent)
    if (method_exists($module, 'migrateFromBemoSlider')) {
        $module->migrateFromBemoSlider();
    }

    return true;
}
