<?php
declare(strict_types=1);
/**
 * Progi rabatowe — aktywacja kodu jednym kliknięciem i odświeżenie paska.
 *
 * POST code + token  → dokłada do koszyka regułę o tym kodzie (z listy progów
 *                      z panelu), zdejmując wcześniej niższy próg, jeśli klient
 *                      go miał (reguły progowe nie łączą się ze sobą — rdzeń
 *                      odrzuciłby „1000” przy wpiętym „500”). Walidację robi
 *                      sam rdzeń (CartRule::checkValidity), więc próg, grupa,
 *                      limity użyć i kolizje z innymi kuponami są te same co przy
 *                      wpisaniu kodu ręcznie. Potem przekierowanie do koszyka z
 *                      komunikatem (PRG — odświeżenie strony nie powtórzy POST-a).
 *
 * GET ?ctx=product   → JSON {html} z paskiem dla bieżącego koszyka; karta
 *                      produktu podmienia nim swój blok po dodaniu do koszyka
 *                      (tiers.js), bo strona się wtedy nie przeładowuje.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Hummingbird_editorTiersModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ssl = true;

    public function postProcess()
    {
        $code = trim((string) Tools::getValue('code', ''));
        if ($code === '' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $cartUrl = $this->context->link->getPageLink('cart', true, null, ['action' => 'show']);

        if ((string) Tools::getValue('token') !== Tools::getToken(false)) {
            $this->errors[] = $this->trans('Invalid token', [], 'Shop.Notifications.Error');
            $this->redirectWithNotifications($cartUrl);
        }

        /** @var Hummingbird_editor $module */
        $module = $this->module;
        $tiers = new HbEditorDiscountTiers($module, $this->context);
        $tier = HbEditorDiscountTiers::isEnabled() ? $tiers->getTierByCode($code) : null;
        $cart = $this->context->cart;

        if ($tier === null || !Validate::isLoadedObject($cart) || !$cart->nbProducts()) {
            $this->errors[] = $this->trans('This voucher does not exist.', [], 'Shop.Notifications.Error');
            $this->redirectWithNotifications($cartUrl);
        }

        // Niższy (albo inny) próg już w koszyku — zdejmujemy, bo rdzeń uznałby
        // nowy kod za „niełączący się z kuponem w koszyku”. Zapamiętujemy, żeby
        // przywrócić, gdyby nowy jednak nie przeszedł.
        $tierIds = array_column($tiers->getTiers(), 'id');
        $removed = [];
        foreach ((array) $cart->getCartRules(CartRule::FILTER_ACTION_ALL, false) as $rule) {
            $idRule = (int) $rule['id_cart_rule'];
            if ($idRule !== $tier['id'] && in_array($idRule, $tierIds, true)) {
                $cart->removeCartRule($idRule);
                $removed[] = $idRule;
            }
        }

        $cartRule = new CartRule($tier['id'], (int) $this->context->language->id);
        $error = Validate::isLoadedObject($cartRule) ? $cartRule->checkValidity($this->context, false, true) : true;

        if ($error) {
            foreach ($removed as $idRule) {
                $cart->addCartRule($idRule);
            }
            $this->errors[] = is_string($error)
                ? $error
                : $this->trans('This voucher does not exist.', [], 'Shop.Notifications.Error');
            $this->redirectWithNotifications($cartUrl);
        }

        $cart->addCartRule($tier['id']);
        HbEditorDiscountTiers::reset();

        $this->success[] = $tiers->label('success', [
            '%percent%' => $tier['percent_label'],
            '%code%'    => $tier['code'],
        ]);
        $this->redirectWithNotifications($cartUrl);
    }

    public function initContent()
    {
        /** @var Hummingbird_editor $module */
        $module = $this->module;
        $ctx = (string) Tools::getValue('ctx', HbEditorDiscountTiers::CTX_PRODUCT);
        if (!in_array($ctx, [HbEditorDiscountTiers::CTX_PRODUCT, HbEditorDiscountTiers::CTX_PREVIEW, HbEditorDiscountTiers::CTX_CART], true)) {
            $ctx = HbEditorDiscountTiers::CTX_PRODUCT;
        }

        // Szablon paska nie potrzebuje zmiennych strony, ale $urls/$static_token
        // nie zaszkodzą i trzymają parytet z cartpreview.php.
        $this->assignGeneralPurposeVariables();

        $tiers = new HbEditorDiscountTiers($module, $this->context);

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $this->ajaxRender(json_encode(['html' => $tiers->renderBar($ctx)]));
        exit;
    }
}
