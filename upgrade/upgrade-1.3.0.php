<?php
/**
 * 1.3.0 — image + text section on the product page (displayFooterProduct).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_0($module)
{
    return $module->registerHook('displayFooterProduct');
}
