<?php

/**
 * @package       VM Payment - Rave
 * @author        Rave
 * @copyright     Copyright (C) 2018 Oluwole Adebiyi. All rights reserved.
 * @version       1.0.0, March 2018
 * @license       GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die('Direct access to ' . basename(__FILE__) . ' is not allowed.');

if (!class_exists('vmPSPlugin'))
    require(JPATH_VM_PLUGINS . DIRECTORY_SEPARATOR . 'vmpsplugin.php');

class plgVmPaymentRave extends vmPSPlugin
{
    function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        $this->requeryCount = 0;

        $this->_loggable = true;
        $this->_tablepkey = 'id';
        $this->_tableId = 'id';

        $this->tableFields = array_keys($this->getTableSQLFields());

        $varsToPush = array(
            'test_mode' => array(
                1,
                'int'
            ), // rave.xml (test_mode)
            'live_secret_key' => array(
                '',
                'char'
            ), // rave.xml (live_secret_key)
            'live_public_key' => array(
                '',
                'char'
            ), // rave.xml (live_public_key)
            'test_secret_key' => array(
                '',
                'char'
            ), // rave.xml (test_secret_key)
            'test_public_key' => array(
                '',
                'char'
            ), // rave.xml (test_public_key)
            'logo' => array(
                '',
                'char'
            ), // rave.xml (logo)
            'title' => array(
                '',
                'char'
            ), // rave.xml (title)
            'description' => array(
                '',
                'char'
            ), // rave.xml (description)
            'metaname' => array(
                '',
                'char'
            ), // rave.xml (metaname)
            'metavalue' => array(
                '',
                'char'
            ), // rave.xml (metavalue)
            'country' => array(
                '',
                'char'
            ), // rave.xml (country)
            'payment_method' => array(
                '',
                'char'
            ), // rave.xml (payment_method)
            'status_pending' => array(
                '',
                'char'
            ),
            'status_success' => array(
                '',
                'char'
            ),
            'status_canceled' => array(
                '',
                'char'
            ),

            'min_amount' => array(
                0,
                'int'
            ),
            'max_amount' => array(
                0,
                'int'
            ),
            'cost_per_transaction' => array(
                0,
                'int'
            ),
            'cost_percent_total' => array(
                0,
                'int'
            ),
            'tax_id' => array(
                0,
                'int'
            )
        );

        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
    }

    public function getVmPluginCreateTableSQL()
    {
        return $this->createTableSQL('Payment Rave Table');
    }

    function getTableSQLFields()
    {
        $SQLfields = array(
            'id' => 'tinyint(1) unsigned NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id' => 'int(11) UNSIGNED DEFAULT NULL',
            'order_number' => 'char(32) DEFAULT NULL',
            'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED DEFAULT NULL',
            'payment_name' => 'char(255) NOT NULL DEFAULT \'\' ',
            'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
            'payment_currency' => 'char(3) ',
            'cost_per_transaction' => ' decimal(10,2) DEFAULT NULL ',
            'cost_percent_total' => ' decimal(10,2) DEFAULT NULL ',
            'tax_id' => 'smallint(11) DEFAULT NULL',
            'rave_transaction_reference' => 'char(32) DEFAULT NULL'
        );

        return $SQLfields;
    }

    function getRaveSettings($payment_method_id)
    {
        $rave_settings = $this->getPluginMethod($payment_method_id);

        if ($rave_settings->test_mode) {
            $baseUrl = 'https://ravesandboxapi.flutterwave.com';
            $apiLink = 'https://ravesandboxapi.flutterwave.com/';
            $secret_key = $rave_settings->test_secret_key;
            $public_key = $rave_settings->test_public_key;
        } else {
            $baseUrl = 'https://api.ravepay.co';
            $apiLink = 'https://api.ravepay.co/';
            $secret_key = $rave_settings->live_secret_key;
            $public_key = $rave_settings->live_public_key;
        }

        return array(
            'baseUrl' => $baseUrl,
            'apiLink' => $apiLink,
            'public_key' => $public_key,
            'secret_key' => $secret_key,
            'logo' => $rave_settings->logo,
            'title' => $rave_settings->title,
            'description' => $rave_settings->description,
            'metaname' => $rave_settings->metaname,
            'metavalue' => $rave_settings->metavalue,
            'country' => $rave_settings->country,
            'payment_method' => $rave_settings->payment_method
            
        );
    }

    function requeryRaveTransaction()
    {
        $transactionStatus = new stdClass();
        $transactionStatus->error = "";

        // Get Secret Key from settings
        $rave_settings = $this->getRaveSettings($_GET['pm']);

        $apiLink = $rave_settings['apiLink'];

        $txref = $reference;
        $this->requeryCount++;
        $data = array(
            'txref' => $_GET['txref'],
            'SECKEY' => $rave_settings['secret_key'],
            'last_attempt' => '1'
	        // 'only_successful' => '1'
        );
	    // make request to endpoint.
        $data_string = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiLink . 'flwv3-pug/getpaidx/api/v2/verify');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($ch);
        $resp = json_decode($response, false);

        if ($resp && $resp->status === "success") {
            if ($resp && $resp->data && $resp->data->status === "successful") {
                $transactionStatus = $resp->data;
            } elseif ($resp && $resp->data && $resp->data->status === "failed") {
                $transactionStatus->error = "Rave Error: " . $resp->data->chargemessage;
            } else {
                if ($this->requeryCount > 4) {
                    $transactionStatus->error = $transactionStatus->error . " : No response";
                } else {
                    sleep(3);
                    $this->requeryRaveTransaction();
                }
            }
        } else {
            if ($this->requeryCount > 4) {
                $transactionStatus->error = $transactionStatus->error . " : No response";
            } else {
                sleep(3);
                $this->requeryRaveTransaction();
            }
        }


        return $transactionStatus;

    }

    function plgVmConfirmedOrder($cart, $order)
    {   
        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null;
        }

        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }

        if (!class_exists('VirtueMartModelOrders'))
            require(JPATH_VM_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'orders.php');

        if (!class_exists('VirtueMartModelCurrency'))
            require(JPATH_VM_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'currency.php');

        // Get current order info
        $order_info = $order['details']['BT'];
        $country_code = ShopFunctions::getCountryByID($order_info->virtuemart_country_id, 'country_3_code');

        // Get payment currency
        $this->getPaymentCurrency($method);
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('currency_code_3');
        $query->from($db->quoteName('#__virtuemart_currencies'));
        $query->where($db->quoteName('virtuemart_currency_id')
                . ' = ' . $db->quote($method->payment_currency));
        $db->setQuery($query);
        $currency_code = $db->loadResult();

        // Get total amount for the current payment currency
        $totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total, $method->payment_currency);

        // Prepare data that should be stored in the database
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['payment_name'] = $this->renderPluginName($method, $order);
        $dbValues['virtuemart_paymentmethod_id'] = $order['details']['BT']->virtuemart_paymentmethod_id;
        $dbValues['cost_per_transaction'] = $method->cost_per_transaction;
        $dbValues['cost_percent_total'] = $method->cost_percent_total;
        $dbValues['payment_currency'] = $method->payment_currency;
        $dbValues['payment_order_total'] = $totalInPaymentCurrency;
        $dbValues['tax_id'] = $method->tax_id;
        $dbValues['rave_transaction_reference'] = $dbValues['order_number'] . '-' . date('YmdHis');

        $this->storePSPluginInternalData($dbValues);

        // Redirect URL - Requery Rave payment
        $redirect_url = JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id . '&Itemid=' . vRequest::getInt('Itemid') . '&lang=' . vRequest::getCmd('lang', '');

        // Rave Settings
        $payment_method_id = $dbValues['virtuemart_paymentmethod_id'];//vRequest::getInt('virtuemart_paymentmethod_id');
        $rave_settings = $this->getRaveSettings($payment_method_id);

        // Get the country
        switch ($currency_code) {
            case 'KES':
                $country = 'KE';
                break;
            case 'GHS':
                $country = 'GH';
                break;
            case 'ZAR':
                $country = 'ZA';
                break;
            
            default:
                $country = 'NG';
                break;
        }
        
        $postfields = array();
        $postfields['PBFPubKey'] = $rave_settings['public_key'];
        $postfields['customer_email'] = $order_info->email;
        $postfields['customer_firstname'] = $order_info->firstname;
        $postfields['custom_logo'] = $rave_settings['logo'];
        $postfields['custom_title'] = $rave_settings['title'];
        $postfields['custom_description'] = $rave_settings['description'];
        $postfields['customer_lastname'] = $order_info->lastname;
        $postfields['customer_phone'] = $order_info->phone;
        $postfields['country'] = $country; //$rave_settings['country'];
        $postfields['redirect_url'] = $redirect_url;
        $postfields['txref'] = $dbValues['rave_transaction_reference'];
        $postfields['payment_method'] = $rave_settings['payment_method'];
        $postfields['amount'] = $totalInPaymentCurrency['value'] + 0;
        $postfields['currency'] = $currency_code;
        $postfields['hosted_payment'] = 1;
        ksort($postfields);
        $stringToHash = "";
        foreach ($postfields as $key => $val) {
            $stringToHash .= $val;
        }
        $stringToHash .= $rave_settings['secret_key'];
        $hashedValue = hash('sha256', $stringToHash);
        $meta = array();
        array_push($meta, array('metaname' => $rave_settings['metaname'], 'metavalue' => $rave_settings['metavalue']));
        $transactionData = array_merge($postfields, array('integrity_hash' => $hashedValue));
        $json = json_encode($transactionData);
        
        // Rave Gateway HTML code
        $html = "
        <script type='text/javascript' src='" . $rave_settings['baseUrl'] . "/flwv3-pug/getpaidx/api/flwpbf-inline.js'></script>
        <script>
        document . addEventListener('DOMContentLoaded', function (event) {
        var data = JSON.parse('" . json_encode($transactionData = array_merge($postfields, array('integrity_hash' => $hashedValue), array('meta' => $meta))) . "');
        getpaidSetup(data);});
        </script>
        ";

        $cart->_confirmDone = false;
        $cart->_dataValidated = false;
        $cart->setCartIntoSession();

        vRequest::setVar('html', $html);
    }

    function plgVmOnPaymentResponseReceived(&$html)
    {
        if (!class_exists('VirtueMartCart')) {
            require(VMPATH_SITE . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cart.php');
        }
        if (!class_exists('shopFunctionsF')) {
            require(VMPATH_SITE . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'shopfunctionsf.php');
        }
        if (!class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'orders.php');
        }

        VmConfig::loadJLang('com_virtuemart_orders', true);
        $post_data = vRequest::getPost();

        // The payment itself should send the parameter needed.
        $virtuemart_paymentmethod_id = vRequest::getInt('pm', 0);

        $order_number = vRequest::getString('on', 0);
        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null;
        }

        if (!$this->selectedThisElement($method->payment_element)) {
            return null;
        }

        if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
            return null;
        }

        if (!($paymentTable = $this->getDataByOrderId($virtuemart_order_id))) {
            return '';
        }


        VmConfig::loadJLang('com_virtuemart');
        $orderModel = VmModel::getModel('orders');
        $order = $orderModel->getOrder($virtuemart_order_id);

        $payment_name = $this->renderPluginName($method);
        $html = '<table>' . "\n";
        $html .= $this->getHtmlRow('Payment Name', $payment_name);
        $html .= $this->getHtmlRow('Order Number', $order_number);
        $html .= $this->getHtmlRow('Transaction Reference', $_GET['txref']);

        $transData = $this->requeryRaveTransaction();


        if (!property_exists($transData, 'error') && property_exists($transData, 'status') && ($transData->status === 'successful') && (strpos($transData->txref, $order_number . "-") === 0)) {
            // Update order status - From pending to complete
            $order['order_status'] = 'C';
            $order['customer_notified'] = 1;
            $orderModel->updateStatusForOneOrder($order['details']['BT']->virtuemart_order_id, $order, true);

            $html .= $this->getHtmlRow('Total Amount', number_format($transData->amount, 2));
            $html .= $this->getHtmlRow('Status', $transData->status);
            $html .= '</table>' . "\n";
            // add order url
            $url = JRoute::_('index.php?option=com_virtuemart&view=orders&layout=details&order_number=' . $order_number, false);
            $html .= '<a href="' . JRoute::_('index.php?option=com_virtuemart&view=orders&layout=details&order_number=' . $order_number, false) . '" class="vm-button-correct">' . vmText::_('COM_VIRTUEMART_ORDER_VIEW_ORDER') . '</a>';

            // Empty cart
            $cart = VirtueMartCart::getCart();
            $cart->emptyCart();

            return true;
        } else if (property_exists($transData, 'error')) {
            die($transData->error);
        } else {
            $html .= $this->getHtmlRow('Total Amount', number_format($transData->amount, 2));
            $html .= $this->getHtmlRow('Status', $transData->status);
            $html .= '</table>' . "\n";
            $html .= '<a href="' . JRoute::_('index.php?option=com_virtuemart&view=cart', false) . '" class="vm-button-correct">' . vmText::_('CART_PAGE') . '</a>';

            // Update order status - From pending to canceled
            $order['order_status'] = 'X';
            $order['customer_notified'] = 1;
            $orderModel->updateStatusForOneOrder($order['details']['BT']->virtuemart_order_id, $order, true);
        }

        return false;
    }

    function plgVmOnUserPaymentCancel()
    {
        return true;
    }

    /**
     * Required functions by Joomla or VirtueMart. Removed code comments due to 'file length'.
     * All copyrights are (c) respective year of author or copyright holder, and/or the author.
     */
    function getCosts(VirtueMartCart $cart, $method, $cart_prices)
    {
        if (preg_match('/%$/', $method->cost_percent_total)) {
            $cost_percent_total = substr($method->cost_percent_total, 0, -1);
        } else {
            $cost_percent_total = $method->cost_percent_total;
        }
        return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
    }

    protected function checkConditions($cart, $method, $cart_prices)
    {
        $this->convert_condition_amount($method);
        $address = (($cart->ST == 0) ? $cart->BT : $cart->ST);
        $amount = $this->getCartAmount($cart_prices);
        $amount_cond = ($amount >= $method->min_amount and $amount <= $method->max_amount or ($method->min_amount <= $amount and ($method->max_amount == 0)));
        $countries = array();
        if (!empty($method->countries)) {
            if (!is_array($method->countries)) {
                $countries[0] = $method->countries;
            } else {
                $countries = $method->countries;
            }
        }
        if (!is_array($address)) {
            $address = array();
            $address['virtuemart_country_id'] = 0;
        }
        if (!isset($address['virtuemart_country_id'])) {
            $address['virtuemart_country_id'] = 0;
        }
        if (in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
            if ($amount_cond) {
                return true;
            }
        }
        return false;
    }

    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id)
    {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart)
    {
        return $this->OnSelectCheck($cart);
    }

    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn)
    {
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name)
    {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter)
    {
        return $this->onCheckAutomaticSelected($cart, $cart_prices, $paymentCounter);
    }

    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name)
    {
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    function plgVmonShowOrderPrintPayment($order_number, $method_id)
    {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPaymentVM3(&$data)
    {
        return $this->declarePluginParams('payment', $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table)
    {
        return $this->setOnTablePluginParams($name, $id, $table);
    }

}
