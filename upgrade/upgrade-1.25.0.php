<?php
/**
 * 1.25.0 — sekcje motywu (section_type=theme, schemat JSON w themes/<motyw>/hbe/sections)
 * i zakładka „Treści motywu” (HBE_THEME_FIELDS). Bez zmian w bazie.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_25_0($module)
{
    return true;
}
