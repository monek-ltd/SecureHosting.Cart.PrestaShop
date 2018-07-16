<?php
/**
 * Created by PhpStorm.
 * User: jonm
 * Date: 16/04/2018
 * Time: 15:05
 */
class SecureHostingFailureModuleFrontController extends ModuleFrontController
{
//	public $display_column_left = false;
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

            $this->setTemplate('module:securehosting/views/templates/front/failure.tpl');
            $this->context->smarty->display($this->template);
    }


}


