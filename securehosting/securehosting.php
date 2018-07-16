<?php
/**
 * Package: securehosting
 * Author: Roman Ignatov ignatov.roman@gmail.com
 * Date: Date: 26.03.12
 */
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
if (!defined('_PS_VERSION_')) exit;

class SecureHosting extends PaymentModule
{

    const SH_GATEWAY_URL = "https://secure-server-hosting.com/secutran/secuitems.php";
    const SH_CONF_SUBMIT_BTN = 'submitUpgConf';

    private $_html = '';
    private $_postErrors = array();

    private $account_ref = '';
    private $sh_checkcode = '';
    private $sh_template = 'prestashop_template.html';

    function __construct()
    {
        $this->name = 'securehosting';
        $this->tab = 'payments_gateways';
        $this->version = '0.9.6';
        $this->author = 'Secure Hosting';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $confFields = $this->getConfigFields();
        $config = Configuration::getMultiple($confFields);

        foreach ($confFields as $k => $v) {
            if ($config[$v] !== false) $this->$k = $config[$v];
        }

        parent::__construct();

        $this->displayName = $this->l('SecureHosting Payments Module');
        $this->description = $this->l('Accepts payments by credit cards with Secure Hosting ( http://www.securehosting.com )');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (empty($this->account_ref) || empty($this->sh_checkcode))
            $this->warning = $this->l('Account number and check code must be configured to use this module correctly.');

        if (!count(Currency::checkPaymentCurrencies($this->id)))
            $this->warning = $this->l('No currency set for this module');
    }

    public function getConfigFields($type = 'sysconf')
    {
        $fields = array(
            'account_ref' => array(
                'sysconf' => 'SECUREHOSTING_ACCOUNT_REF',
                'title' => 'SecureHosting account reference',
            ),
            'sh_checkcode' => array(
                'sysconf' => 'SECUREHOSTING_SH_CHECKCODE',
                'title' => 'Second level security checkcode',
            ),
            'sh_template' => array(
                'sysconf' => 'SECUREHOSTING_TEMPLATE',
                'title' => 'SecureHosting template',
            ),
            'sh_secuphrase' => array(
                'sysconf' => 'SECUREHOSTING_ADV_SECU_PHRASE',
                'title' => 'SecureHosting Advanced Secuphrase',
            ),
        );
        $res = array();
        foreach ($fields as $k => $v) {
            $res[$k] = (isset($v[$type])) ? $v[$type] : '';
        }
        return $res;
    }

    private function _postValidation()
    {
        if (Tools::isSubmit(self::SH_CONF_SUBMIT_BTN)) {
            if (!Tools::getValue('account_ref'))
                $this->_postErrors[] = $this->l('Account details are required.');
            elseif (!Tools::getValue('sh_checkcode'))
                $this->_postErrors[] = $this->l('SecureHosting CheckCode is required.');
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit(self::SH_CONF_SUBMIT_BTN)) {
            $confFields = $this->getConfigFields();
            foreach ($confFields as $k => $v) {
                Configuration::updateValue($v, Tools::getValue($k));
            }
        }
        $this->_html .= '<div class="conf confirm"><img src="../img/admin/enabled.gif" alt="' . $this->l('ok') . '" /> ' . $this->l('Settings updated') . '</div>';
    }


    /**
     *       HOOKS HERE
     */

    public function hookPayment($params)
    {
        if (!$this->active) return;

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $payment_options = [
            $this->getExternalPaymentOption()
        ];
        return $payment_options;
    }

    public function getExternalPaymentOption()
    {
        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->trans('Pay with Secure Hosting'))
            ->setModuleName($this->name)
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', array(), true))
            ->setAdditionalInformation($this->fetch('module:securehosting/views/templates/hook/payment.tpl'));
        return $externalOption;
    }

    /**
     * DIFFERENT METHODS
     */


    public function validateOrder($id_cart, $id_order_state, $amountPaid, $paymentMethod = 'Unknown', $message = NULL, $extraVars = array(), $currency_special = NULL, $dont_touch_amount = false, $secure_key = false, $paymentDetails)
    {
        if (!$this->active)
            return;
        $res = parent::validateOrder($id_cart, $id_order_state, $amountPaid, $paymentMethod, $message, $extraVars, $currency_special, $dont_touch_amount, $secure_key,null);

        $this->_saveTransaction($id_cart, $extraVars);
        if ($res && $this->currentOrder && !empty($paymentDetails)) {
            $paymentId = Db::getInstance()->getValue('SELECT `id_order_payment` FROM `' . _DB_PREFIX_ . 'order_payment` WHERE `id_order` = ' . (int)$this->currentOrder);
            if ($paymentId) {

                $sql = "UPDATE `" . _DB_PREFIX_ . "order_payment` SET " .
                    sprintf("payment_method='%s', transaction_id='%s', card_number='%s', card_brand='%s', card_expiration='%s', card_holder='%s'",
                        $paymentDetails['payment_method'], $paymentDetails['transaction_id'], $paymentDetails['card_number'],
                        $paymentDetails['card_brand'], $paymentDetails['card_expiration'], $paymentDetails['card_holder']

                    ) . ' WHERE id_order_payment=' . $paymentId;
                Db::getInstance()->Execute($sql);
            }
        }
    }
    public function createOrder($id_cart,$id_order_state,$amountPaid,$paymentMethod,$message = null,$secureKey){
        if(!$this->active)
            return;
        $res = parent::validateOrder($id_cart,$id_order_state,$amountPaid,$paymentMethod,$message,null,null,true,$secureKey,null);

    }
    public function addPaymentDetails($order,$paymentDetails){

        echo "Callback run successful<br/>";
        if(!$order->getOrderPayments()){
            $order->addOrderPayment($paymentDetails['transactionamount'],$this->displayName,$paymentDetails['transaction_id'],null,null,null);
            echo "Payment added<br/>";
        }else{
            echo "Payment already exists<br/>";
        }

    }
    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getContent()
    {
        $this->_html = '<h2>' . $this->displayName . '</h2>';

        if (Tools::isSubmit(self::SH_CONF_SUBMIT_BTN)) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err)
                    $this->_html .= '<div class="alert error">' . $err . '</div>';
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_displayForm();
        return $this->_html;
    }

    private function _displayForm()
    {
        $this->_html .=
            '<form action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data">
                <fieldset>
                    <legend>' . $this->l('SecureHosting module configuration') . '</legend><br/>';

        $confFields = $this->getConfigFields('title');
        foreach ($confFields as $k => $v) {
            $this->_html .= "<label for=\"{$k}\">{$this->l($v)}&nbsp;&nbsp;</label>
                            <input id=\"{$k}\" type=\"text\" name=\"{$k}\" value=\"" . Tools::getValue($k, $this->$k) . "\" /><br class=\"clear\"/><br/>";
        }

        $this->_html .= '<input class="button" type="submit" name="' . self::SH_CONF_SUBMIT_BTN . '" value="' . $this->l('Save') . '" style="margin-left: 200px;"/>
                </fieldset>
            </form>';
    }


    private function _saveTransaction($id_cart, $extraVars)
    {
        $cart = new Cart((int)($id_cart));
        if (Validate::isLoadedObject($cart) AND $cart->OrderExists()) {
            $responsedata = json_encode($extraVars);
            $id_order = Db::getInstance()->getValue('SELECT `id_order` FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_cart` = ' . (int)$cart->id);
            Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . 'securehosting_order` (`id_order`, `id_transaction`, `payment_method`, `payment_status`, response_data) VALUES (' . (int)$id_order . ', \'' . pSQL($extraVars['transaction_id']) . '\', \'' . pSQL($extraVars['trans_type']) . '\', \'' . pSQL($extraVars['payment_status']) . '\',\'' . $responsedata . '\')');
        }
    }


    /**
     * INSTALLATION  / DEINSTALLATION
     */

    public function install()
    {
        if (!parent::install()
            OR !$this->registerHook('paymentOptions')
            OR !$this->registerHook('payment')
            OR !$this->registerHook('paymentReturn')
            OR !$this->registerHook('shoppingCartExtra')
            OR !$this->registerHook('backBeforePayment')
            OR !$this->registerHook('rightColumn')
            OR !$this->registerHook('displayPayment')
            OR !$this->registerHook('cancelProduct')
            OR !$this->registerHook('adminOrder')
        )
            return false;

        $this->installOrderStates();

        Db::getInstance()->execute("
CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "securehosting_order` (
  `id_order` int(10) unsigned NOT NULL,
  `id_transaction` varchar(255) NOT NULL,
  `payment_method` int(10) unsigned NOT NULL,
  `payment_status` varchar(255) NOT NULL,
  `capture` int(10) unsigned NOT NULL,
   `response_data` TEXT,
  PRIMARY KEY (`id_order`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Paygate SecureHosting module debug data'");

        // Set The Configuration
        Configuration::updateValue('SECUREHOSTING_ACCOUNT_REF', 'SH22____');
        Configuration::updateValue('SECUREHOSTING_SH_CHECKCODE', '______');
        Configuration::updateValue('SECUREHOSTING_ADV_SECU_PHRASE', '');
        Configuration::updateValue('SECUREHOSTING_TEMPLATE', 'prestashop_template.html');

        return true;
    }
    protected function installOrderStates()
    {
        $values_to_insert = array(
            'invoice' => 0,
            'send_email' => 0,
            'module_name' => pSQL($this->name),
            'color' => '#4169E1',
            'unremovable' => 0,
            'hidden' => 0,
            'logable' => 0,
            'delivery' => 0,
            'shipped' => 0,
            'paid' => 0,
            'deleted' => 0,
        );
        if (!Db::getInstance()->insert('order_state', $values_to_insert)) {
            return false;
        }
        $id_order_state = (int) Db::getInstance()->Insert_ID();
        Configuration::updateValue('SECUREHOSTING_PAYMENT_STATUS', $id_order_state);
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            Db::getInstance()->insert('order_state_lang', array(
                'id_order_state' => $id_order_state,
                'id_lang' => (int)$language['id_lang'],
                'name' => pSQL($this->l('Secure Hosting - Awaiting callback for payment confirmation.')),
                'template' => 'preparation',
            ));
        }


        unset($id_order_state);

        $values_to_insert = array(
            'invoice' => 0,
            'send_email' => 0,
            'module_name' => pSQL($this->name),
            'color' => '#32CD32',
            'unremovable' => 0,
            'hidden' => 0,
            'logable' => 1,
            'delivery' => 0,
            'shipped' => 0,
            'paid' => 1,
            'deleted' => 0,
        );
        if (!Db::getInstance()->insert('order_state', $values_to_insert)) {
            return false;
        }
        $id_order_state = (int) Db::getInstance()->Insert_ID();
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            Db::getInstance()->insert('order_state_lang', array(
                'id_order_state' => $id_order_state,
                'id_lang' => (int)$language['id_lang'],
                'name' => pSQL($this->l('Secure Hosting - Callback complete, payment confirmed.')),
                'template' => 'payment',
            ));
        }
        Configuration::updateValue('SECUREHOSTING_PAYMENT_CONFIRMED', $id_order_state);
        unset($id_order_state);
    }
    public function uninstall()
    {
        $dbPref = _DB_PREFIX_;
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "securehosting_order`";
        Db::getInstance()->Execute($sql);

        /* Delete all configurations */
        Configuration::deleteByName('SECUREHOSTING_ACCOUNT_REF');
        Configuration::deleteByName('SECUREHOSTING_SH_CHECKCODE');
        Configuration::deleteByName('SECUREHOSTING_ADV_SECU_PHRASE', '');
        Configuration::deleteByName('SECUREHOSTING_ADV_SECUITEMS', '');
        Configuration::deleteByName('SECUREHOSTING_PAYMENT_STATUS', '');
        Configuration::deleteByName('SECUREHOSTING_PAYMENT_CONFIRMED', '');
        Configuration::deleteByName('SECUREHOSTING_TEMPLATE', '');

        return parent::uninstall();
    }
}
