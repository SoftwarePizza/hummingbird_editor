<?php
/**
 * Darmowy odbior osobisty zamiast golego "Za darmo!".
 *
 * DeliveryOptionsFinder zasila dwa widoki kasy naraz — liste przewoznikow w
 * kroku "Przesylka" i wybrana opcje w podsumowaniu zamowienia — a rdzen nie
 * daje w nim zadnego hooka, stad override. Cala decyzja siedzi w module:
 * bez hummingbird_editor (albo z pusta lista przewoznikow odbioru osobistego
 * w BO -> Hummingbird -> Kasa) ten plik nie zmienia niczego.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DeliveryOptionsFinder extends DeliveryOptionsFinderCore
{
    public function getDeliveryOptions()
    {
        $options = parent::getDeliveryOptions();

        if (!is_array($options) || !$options) {
            return $options;
        }

        $module = Module::getInstanceByName('hummingbird_editor');
        if (!$module || !$module->active || !method_exists($module, 'relabelFreePickupOptions')) {
            return $options;
        }

        return $module->relabelFreePickupOptions($options);
    }
}
