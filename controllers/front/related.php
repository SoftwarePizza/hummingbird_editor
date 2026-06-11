<?php
declare(strict_types=1);
/**
 * AJAX endpoint for the "Inni kupili również" carousel on the product page.
 * Returns JSON {html, count}; product data comes from ps_crossselling
 * (order-history based cross-selling), presented with the core listing presenter.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Hummingbird_editorRelatedModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $payload = ['html' => '', 'count' => 0];

        $idProduct = (int) Tools::getValue('id_product');
        if ($idProduct > 0) {
            $cross = Module::getInstanceByName('ps_crossselling');
            if ($cross && $cross->active) {
                $vars = $cross->getWidgetVariables('displayFooterProduct', [
                    'product' => ['id_product' => $idProduct],
                ]);
                if (!empty($vars['products'])) {
                    $this->context->smarty->assign([
                        'hbe_related_products' => $vars['products'],
                        'hbe_static_token'     => Tools::getToken(false),
                        'hbe_cart_url'         => $this->context->link->getPageLink('cart'),
                    ]);
                    $payload['html'] = $this->context->smarty->fetch(
                        _PS_MODULE_DIR_ . 'hummingbird_editor/views/templates/front/related-items.tpl'
                    );
                    $payload['count'] = count($vars['products']);
                }
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        $this->ajaxRender(json_encode($payload));
        exit;
    }
}
