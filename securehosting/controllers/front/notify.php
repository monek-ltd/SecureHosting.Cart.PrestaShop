<?php
/**
 * Package: securehosting
 * Author: Roman Ignatov ignatov.roman@gmail.com
 * Date: Date: 13.07.18
 */


/**
 * @since 1.5.0
 */
class securehostingnotifyModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    private $_debugmode = false;
    private $_logger = null;

    public function __construct()
    {
        $this->controller_type = 'modulefront';

        $this->module = Module::getInstanceByName(Tools::getValue('module'));
        if (! $this->module->active) {
            Tools::redirect('index');
        }
        $this->page_name = 'module-' . $this->module->name . '-' . Dispatcher::getInstance()->getController();

        parent::__construct();
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        try {
            $this->debug('SH validation started. Given params: ' . print_r($_GET, 1));
            parent::initContent();

            $errors = '';
            $securehosting = new securehosting();

            $cartId = intval(@$_GET['orderid']);
            $secureKey = @$_GET['securekey'];

            // Fill params
            $transactionamount = @$_GET["transactionamount"];
            $transaction_id = @$_GET["transactionnumber"];
            $transaction_currency = @$_GET['transactioncurrency'];

            $vars = $_GET;
            $vars['transaction_id'] = $transaction_id;
            $vars['trans_type'] = 'SecureHosting Payment';
            $vars['total'] = $transactionamount;
            $vars['buyer'] = @$_GET['cardholdersname'];

            if($vars['transaction_id'] > 0) {
                //Update If not Error
                $cart = new Cart((int)$cartId);
                if (!$cart->id) {
                    $errors = 'CartID is empty<br />';
                } else if ($order = Order::getByCartId((int)($cartId))) {

                    $cart_total_paid = (float)Tools::ps_round((float)$cart->getOrderTotal(true, Cart::BOTH), 2);
                    $languages = Language::getLanguages(true);


                    $paymentDetails = array(
                        'payment_method' => $securehosting->displayName,
                        'transaction_id' => $transaction_id,
                        'card_number' => urldecode(@$_GET['card_number']),
                        'card_brand' => @$_GET['cardtype'],
                        'card_expiration' => @$_GET['expmon'] . '/' . @$_GET['expyr'],
                        'card_holder' => urldecode($vars['buyer']),
                        'transactionamount' => $transactionamount,
                    );
//                    $current_order_state = $order->getCurrentOrderState();

                    $securehosting->addPaymentDetails($order,$paymentDetails);

                    // Set the order status
                    $orderstate = new OrderState(Configuration::get('SECUREHOSTING_PAYMENT_CONFIRMED'));
                    $new_history = new OrderHistory();
                    $new_history->id_order = (int)$order->id;
                    $new_history->changeIdOrderState($orderstate->id, (int)$order, true);
                    $new_history->addWithemail(true);



                    if ($this->_debugmode) {
                        if ($transactionamount != $cart_total_paid) {
                            $this->debug(sprintf('Warning: %s paid instead of %s', $transactionamount, $cart_total_paid));
                        }
                    }
//                    $this->debug('SH validation executed. No errors, should be good');

                }
            }else {
                if (!empty($errors)) {
                    $this->debug('SH validation executed. Done with errors: ' . $errors);
                    echo 'error';
                }
//                else if (!preg_match('/^[1-9][0-9]{1,10}$/', $transaction_id)) {
////                    $errors = 'Failed transaction. ' . @$_GET['failurereason'] . '<br>';
////                    Order::setCurrentState((int)$cartId);
//                }
            }
        } catch (Exception $e) {
            $this->debug('SH validation executed and got next Exception:' . print_r($e, 1));
        }

        exit;
    }

    private function debug($msg)
    {
        if ($this->_debugmode) {
            if (is_string($msg)) {
                $str = $msg;
            } else {
                $str = print_r($msg, 1);
            }
            echo $str . "\n\r<br>";
            $this->_logger->logDebug($str);
        }

    }
}
