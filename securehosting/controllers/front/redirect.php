<?php
/**
 * Package: securehosting
 * Author: Roman Ignatov ignatov.roman@gmail.com
 * Date: Date: 26.03.12
 */


/**
 * @since 1.5.0
 */
class securehostingredirectModuleFrontController extends ModuleFrontController
{
	public $display_column_left = false;
	public $ssl = true;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		$cart = $this->context->cart;
        $shreference = Configuration::get('SECUREHOSTING_ACCOUNT_REF');
        $sh_checkcode = Configuration::get('SECUREHOSTING_SH_CHECKCODE');
        if (empty($shreference) OR empty($sh_checkcode)) die('SecureHosting error: (Securehosting module not configured)');

        // Billing address
        $billingAddress = new Address((int)($cart->id_address_invoice));
        $billingCountry = new Country((int)($billingAddress->id_country));
        $billingState = NULL;
        if ($billingAddress->id_state) $billingState = new State((int)($billingAddress->id_state));

        $customer = new Customer((int)($cart->id_customer));

        $currency_module = new Currency((int)($cart->id_currency));
        $productsInCart = $cart->getProducts();
        $productTotal = $cart->getOrderTotal(false);
        $productTotal = sprintf("%01.2f", (float)$productTotal);
        $itemsData = '';
        foreach ($productsInCart as $prod) {
            $_p = array($prod['id_product'], '', $prod['name'], sprintf("%01.2f", $prod['price']), $prod['quantity'], sprintf("%01.2f",$prod['total']));
            $itemsData .= '['.implode('|', $_p).']';
        }
        $callbackurl = Context::getContext()->link->getModuleLink('securehosting', 'notify', array(), true);
        $callbackurl = str_replace("&","&amp;",$callbackurl);
        $shippingAmmount = $cart->getTotalShippingCost();
        $total_tax = $cart->getOrderTotal() - $cart->getOrderTotal(false);
        $secuphrase = Configuration::get('SECUREHOSTING_ADV_SECU_PHRASE');
        if($secuphrase != ""){
            $secustring = $this->getSecustring($shreference, $itemsData,$productTotal,$secuphrase);
        }

		$data = array(
            'redirect_text' => 'Please wait, you will be redirected to SecureHosting website ... Thanks.',
            'securehosting_url' => Securehosting::SH_GATEWAY_URL,
            'shreference' => $shreference,
            'checkcode' => $sh_checkcode,
            'cart_id' => (int)($cart->id),
            'cart_secure_key' => pSQL($cart->secure_key),

            'billing_address' => $billingAddress,
            'billing_country' => $billingCountry,
            'billing_state' => (is_object($billingState)? $billingState->iso_code : ''),
            'email' => $customer->email,

            'amount' => (float)$cart->getOrderTotal(),
            'products_amount' => $productTotal,
            'transactionamount' => $productTotal,
            'tax' => sprintf("%01.2f", $total_tax),
            'shipping' => sprintf("%01.2f", $shippingAmmount),

            'customer' => $customer,
            'discount' => $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS),
            'currency' => $currency_module->iso_code,
            'products' => $itemsData,
            'success_url' => Context::getContext()->link->getModuleLink('securehosting', 'success',['orderid'=>$cart->id,'securekey'=>$cart->secure_key]),
            'failure_url' => Context::getContext()->link->getModuleLink('securehosting', 'failure'),
            'template' => $shreference. '/' . Configuration::get('SECUREHOSTING_TEMPLATE'),
            'callbackurl' => $callbackurl,
			'callbackdata' => "cardholdersname|#cardholdersname|orderid|#orderid|transactionamount|#transactionamount|fc|module|module|securehosting|controller|notify|cartid|{$cart->id}|securekey|{$cart->secure_key}|card_number|#cardnumber|expmon|#cardexpiremonth|expyr|#cardexpireyear|transactioncurrency|#transactioncurrency",
            'secuString' => $secustring,

			'nbProducts' => $cart->nbProducts(),
			'cust_currency' => $cart->id_currency,
			'currencies' => $this->module->getCurrency((int)$cart->id_currency),
			'total' => $cart->getOrderTotal(true, Cart::BOTH),
			'this_path' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
		);

        $this->context->smarty->assign($data);
        //print_r($data);
		$this->setTemplate('module:securehosting/views/templates/front/redirect.tpl');
        $this->context->smarty->display($this->template);
        exit;
	}
	public function getSecustring($shrefernce,$secuitems,$transactionamount,$secuphrase){
        $postdata = "shreference=$shrefernce";
        $postdata .= "&secuitems=$secuitems";
        $postdata .= "&secuphrase=$secuphrase";
        $postdata .= "&transactionamount=$transactionamount";
//        return $postdata;
        return md5($secuitems . $transactionamount . $secuphrase);
    }


}


