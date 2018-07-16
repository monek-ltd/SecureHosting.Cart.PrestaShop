<?php
/**
 * Package: securehosting
 * Author: Roman Ignatov ignatov.roman@gmail.com
 * Date: Date: 26.03.12
 */


/**
 * @since 1.5.0
 */
class securehostingsuccessModuleFrontController extends ModuleFrontController
{
//	public $display_column_left = false;
	public $ssl = true;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
        parent::initContent();

        $securehosting = new securehosting();

        $cartId = intval(@$_GET['orderid']);
        $secureKey = @$_GET['securekey'];
        $cart = new Cart((int)$cartId);
        if (!$cart->id) {
            $errors = 'CartID is empty<br />';
        } else if (Order::getIdByCartId((int)($cartId))) {
            $errors = 'Order already exists for this cart<br />';
        } else {
            $securehosting->createOrder($cartId, Configuration::get('SECUREHOSTING_PAYMENT_STATUS'), null, $securehosting->displayName, 'payment completed on secure hosting',$secureKey);
        }

        $this->setTemplate('module:securehosting/views/templates/front/success.tpl');
        $this->context->smarty->display($this->template);
	}


}


