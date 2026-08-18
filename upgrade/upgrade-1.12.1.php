<?php
/**
 * 1.12.1 — schema catch-up for shops installed from older builds.
 *
 * install() calls HbEditorBlock::upgradeSchema() before the tables exist, so a
 * fresh shop died on "Table ... hb_editor_block doesn't exist". The helper is
 * now guarded; this upgrade re-runs it (plus the slider tables) so installs
 * that were patched by hand end up with the same schema as a clean install —
 * including the idx_hook_active index, which tables created before 1.2.0 lack.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_12_1($module)
{
    require_once _PS_MODULE_DIR_ . 'hummingbird_editor/classes/HbEditorBlock.php';

    HbEditorBlock::upgradeSchema();

    return (bool) $module->ensureSliderSchema();
}
