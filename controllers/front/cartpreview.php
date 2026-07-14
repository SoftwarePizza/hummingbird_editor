<?php
declare(strict_types=1);
/**
 * AJAX endpoint returning the cart preview (cart-preview.tpl) freshly rendered
 * from the current cart. Returns JSON {html, products_count}.
 *
 * After a +/- or remove click, ps_shoppingcart.js re-renders the header
 * .blockcart, but not the add-to-cart modal, which keeps its stale quantities
 * and totals. cart-preview.js calls this endpoint to swap the modal content.
 * Rendering server-side keeps the free-shipping bar (a PHP computation) and the
 * modal in sync instead of recomputing prices in JavaScript.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Hummingbird_editorCartpreviewModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        // Assigns the presented $cart, $urls and $configuration the template expects.
        $this->assignGeneralPurposeVariables();

        $this->context->smarty->assign([
            'cart_url'               => $this->context->link->getPageLink('cart', true),
            'hbe_cart_free_shipping' => $this->module->getCartFreeShippingData(),
        ]);

        $cart = $this->context->cart;

        $payload = [
            'html'           => $this->context->smarty->fetch('module:ps_shoppingcart/cart-preview.tpl'),
            'products_count' => Validate::isLoadedObject($cart) ? (int) $cart->nbProducts() : 0,
        ];

        header('Content-Type: application/json; charset=utf-8');
        $this->ajaxRender(json_encode($payload));
        exit;
    }
}
