<?php
/**
 * 1.5.0 — move FAQ + related carousel to the custom displayProductSections
 * hook. displayProductButtons is an alias of displayProductAdditionalInfo,
 * so the standard hook fired twice on the product page (once per template
 * call site) and duplicated the sections.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_5_0($module)
{
    return $module->registerHook('displayProductSections')
        && $module->unregisterHook('displayProductButtons');
}
