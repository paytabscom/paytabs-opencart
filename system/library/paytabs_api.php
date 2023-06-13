<?php

namespace Opencart\System\Library;

define('PAYTABS_PAYPAGE_VERSION', '4.4.0');

define('PAYTABS_OPENCART_2_3', substr(VERSION, 0, 3) == '2.3');

// require_once DIR_SYSTEM . '/library/paytabs_core.php';
require_once DIR_EXTENSION . 'paytabs/system/library/paytabs_core.php';

class paytabs_api
{
}

abstract class PaytabsAdminController extends \Opencart\System\Engine\Controller
{
    // private $controller;

    public $_code = '_';
    public $error = array();
    public $userToken;

    //

    private $keys = PaytabsAdapter::KEYS;

    private $userToken_str = '';

    private $urlExtensions = '';

    private $settingsKey = '';

    //

    function init()
    {
        // $this->controller = $controller;

        // $this->controller->load->library('paytabs_api');

        $this->load->language("extension/paytabs/payment/paytabs_strings");
        $this->load->model('setting/setting');

        $this->document->setTitle($this->language->get("{$this->_code}_heading_title"));

        $this->keys = array_filter($this->keys, function ($values) {
            if (key_exists('methods', $values) && !in_array($this->_code, $values['methods'])) return false;
            return true;
        });

        foreach ($this->keys as $key => &$value) {
            $value['configKey'] = PaytabsAdapter::KEY_PREFIX . str_replace('_{PAYMENTMETHOD}_', "_{$this->_code}_", $value['configKey']);
        }

        if (PAYTABS_OPENCART_2_3) {
            $this->urlExtensions = 'extension/extension'; // OpenCart 2.3

            $token_str = 'token'; // OpenCart 2.3

            $this->userToken = $this->session->data[$token_str];
            $this->userToken_str = "token={$this->userToken}"; // OpenCart 2.3

            $this->settingsKey = "paytabs_{$this->_code}"; // OpenCart 2.3

        } else {
            $this->urlExtensions = 'marketplace/extension'; // OpenCart 3.x

            $token_str = 'user_token'; // OpenCart 3.x

            $this->userToken = $this->session->data[$token_str];
            $this->userToken_str = "user_token={$this->userToken}"; // OpenCart 3.x

            $this->settingsKey = "payment_paytabs_{$this->_code}"; // OpenCart 3.x
        }
    }


    public function index()
    {
        $this->init();

        /** Save request Handling */

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->save();
        }


        /** Error messages Handling */

        PaytabsAdminController::paytabs_errorList($this->error, [
            'warning',
            'profile_id',
            'endpoint',
            'server_key',
            'valu_product_id'
        ], $data);


        /** Fill values */

        $data['endpoints'] = PaytabsApi::getEndpoints();
        $data['is_card_payment'] = PaytabsHelper::isCardPayment($this->_code);
        $data['support_iframe'] =  PaytabsHelper::supportIframe($this->_code);

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        PaytabsAdminController::paytabs_fillVars(
            $this->keys,
            $this->request->post,
            $this->config,
            $data
        );


        /** Breadcrumb building */

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', "{$this->userToken_str}", true)
            ],
            [
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link($this->urlExtensions, "{$this->userToken_str}&type=payment", true)
            ],
            [
                'text' => $this->language->get("{$this->_code}_heading_title"),
                'href' => $this->url->link("extension/paytabs/payment/paytabs_{$this->_code}", "{$this->userToken_str}", true)
            ]
        ];


        /** Actions */

        $data['action'] = $this->url->link("extension/paytabs/payment/paytabs_{$this->_code}", "{$this->userToken_str}", true);
        $data['cancel'] = $this->url->link($this->urlExtensions, "{$this->userToken_str}&type=payment", true);


        /** Fetch page parts */

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');


        if (PAYTABS_OPENCART_2_3) {
            /** Strings */ // OpenCart 2.3

            $this->load->language("extension/paytabs/payment/paytabs_strings");
            $strings = $this->language->all();
            foreach ($strings as $key => $value) {
                if (substr($key, 0, 5) === "error") continue;
                $data[$key] = $value;
            }
        }


        /** Response */
        if (VERSION >= '4.0.2.0')
        {
            $data['method'] = "paytabs_{$this->_code}".".paytabs_{$this->_code}";
        }
        else
        {
            $data['method'] = $this->_code;
        }

        
        $data['title'] = $this->language->get("{$this->_code}_heading_title");
        $this->response->setOutput($this->load->view("extension/paytabs/payment/paytabs_view", $data));
    }


    public function save()
    {
        $values = [];
        foreach ($this->keys as $option => $value) {
            $postKey = $value['key'];
            $configKey = $value['configKey'];

            $post_value = $this->request->post[$postKey];

            if (!is_null($post_value)) {
                $values[$configKey] = $post_value;
            }
        }

        $this->model_setting_setting->editSetting($this->settingsKey, $values);

        $this->session->data['success'] = $this->language->get('text_success');

        $this->response->redirect($this->url->link($this->urlExtensions, "{$this->userToken_str}&type=payment", true));
    }


    public function validate()
    {
        if (!$this->user->hasPermission('modify', "extension/paytabs/payment/paytabs_{$this->_code}")) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->keys as $option => $value) {
            $key = $value['key'];
            $required = $value['required'];

            if (!$required) continue;

            if (!$this->request->post[$key]) {
                $this->error[$option] = $this->language->get("error_{$option}");
            }
        }

        return !$this->error;
    }


    public function install()
    {
        $this->load->model('setting/setting');

        $defaults = [
            PaytabsAdapter::_key('sort_order', $this->_code) => ($this->_code == 'mada') ? 1 : 80,
            PaytabsAdapter::_key('order_status_id',       $this->_code) => 2, // Processing
            PaytabsAdapter::_key('order_fraud_status_id', $this->_code) => 8, // Denied
        ];

        if (PaytabsHelper::isCardPayment($this->_code)) {
            $allow_associated_methods = true;
            if ($this->_code == 'knet') {
                $allow_associated_methods = false;
            }
            $defaults[PaytabsAdapter::_key('allow_associated_methods', $this->_code)] = $allow_associated_methods;
        }

        $this->model_setting_setting->editSetting($this->settingsKey, $defaults);
        
         $this->model_setting_event->addEvent(
			[
                "code"=>'paytabs_refund', 
			    "status"=> true,
			    "sort_order"=> 80,
			    "description"=>'paytabs refund event ',
			    "trigger"=>'admin/view/sale/order_info/before',
			    "action"=>'extension/paytabs/payment/order.info'
            ]);
    }

    //

    public static function paytabs_errorList($error, $keys, &$data, $prefix = 'error_')
    {
        foreach ($keys as $key) {
            $data["{$prefix}{$key}"] = isset($error[$key]) ? $error[$key] : '';
        }
    }

    public static function paytabs_fillVars($keys, $arrData, $configs, &$data)
    {
        foreach ($keys as $option => $value) {
            $htmlKey = $value['key'];
            $configKey = $value['configKey'];

            $data[$htmlKey] = isset($arrData[$htmlKey]) ? $arrData[$htmlKey] : $configs->get($configKey);
        }
    }
}


abstract class PaytabsCatalogController extends \Opencart\System\Engine\Controller
{
    public $_code = '';

    // private $controller;
    private $ptApi;

    function init()
    {
        // $this->controller = $controller;

        $this->ptApi = (new PaytabsAdapter($this->config, $this->_code))->pt();
    }


    public function index()
    {
        $this->init();

        $data['button_confirm'] = $this->language->get('button_confirm');

        $orderId = $this->session->data['order_id'];

        $data['order_id'] = $orderId;
        $data['iframe_mode'] = (bool) $this->config->get(PaytabsAdapter::_key('iframe', $this->_code));
        $data['url_confirm'] = $this->url->link("extension/paytabs/payment/paytabs_{$this->_code}|confirm", '', true);

        return $this->load->view("extension/paytabs/payment/paytabs_view", $data);
    }


    public function confirm()
    {
        $this->init();

        $order_id = $this->request->post['order'];
        $order_session_id = $this->session->data['order_id'];
        $order_session_payment = $this->session->data['payment_method'];

        if ($order_id != $order_session_id) {
            $this->_re_checkout('The Order has been changed');
            return;
        }
        if (VERSION >= '4.0.2.0')
        {
            if ($order_session_payment['code'] != "paytabs_{$this->_code}".".paytabs_{$this->_code}") {
                $this->_re_checkout('Payment method is required');
                return;
            }
        }
        else
        {
            if ($order_session_payment != "paytabs_{$this->_code}") {
                $this->_re_checkout('Payment method is required');
                return;
            }
        }
      

        //

        $values = $this->prepareOrder();

        $paypage = $this->ptApi->create_pay_page($values);

        $iframe = (bool) $this->config->get(PaytabsAdapter::_key('iframe', $this->_code));

        if ($paypage->success) {
            $payment_url = $paypage->payment_url;

            if ($iframe) {
                $data['payment_url'] = $payment_url;

                $pnl_iFrame = $this->load->view("extension/paytabs/payment/paytabs_framed", $data);
                $this->response->setOutput($pnl_iFrame);
                return;
            } else {
                $this->response->redirect($payment_url);
            }
        } else {
            $paypage_msg = $paypage->message;

            $_logResult = json_encode($paypage);
            $_logData = json_encode($values);
            PaytabsHelper::log("callback failed, Data [{$_logData}], response [{$_logResult}]", 3);

            if ($iframe) {
                $this->response->setOutput($paypage_msg);
            } else {
                $this->_re_checkout($paypage_msg);
            }
        }
    }


    private function _re_checkout($msg)
    {
        $this->session->data['error'] = $msg;
        $this->response->redirect($this->url->link('checkout/checkout', '', true));
    }


    public function callback()
    {
        $this->init();

        $response_data = $this->ptApi->read_response(true);
        if (!$response_data) {
            return;
        }

        $transactionId =
            isset($response_data->transaction_id)
            ? $response_data->transaction_id
            : false;
        if (!$transactionId) {
            return 'Transaction ID is missing';
        }

        $this->load->model('checkout/order');
        $this->load->model("extension/paytabs/payment/paytabs_{$this->_code}");

        $success = $response_data->success;
        $fraud = false;
        $res_msg = $response_data->message;
        $order_id = @$response_data->reference_no;
        $cart_amount = @$response_data->cart_amount;
        $cart_currency = $response_data->cart_currency;

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            PaytabsHelper::log("callback failed, No Order found [{$order_id}]", 3);
            return;
        }


        if ($success) {
            // Check here if the result is tempered

            if (!$this->_confirmAmountPaid($order_info, $cart_amount, $cart_currency)) {
                $res_msg = "The Order has been altered, {$order_id}";
                $success = false;
                $fraud = true;
            } else {
                PaytabsHelper::log("PayTabs {$this->_code} checkout succeeded");

                $successStatus = $this->config->get(PaytabsAdapter::_key('order_status_id', $this->_code));

                //save paytabs transaction refrence.
                $sql = "UPDATE " . DB_PREFIX . "order SET transaction_id = '" . $transactionId . "' WHERE order_id = '" . (int)$order_id . "'";
                $this->db->query($sql)->row;

                $this->model_checkout_order->addHistory($order_id, $successStatus, $res_msg);
            }
        } else if ($response_data->is_on_hold) {

        } else if ($response_data->is_pending) {
            
        }

        if (!$success) {
            $_logVerify = (json_encode($response_data));
            PaytabsHelper::log("callback failed, response [{$_logVerify}]", 3);

            if ($fraud) {
                $fraudStatus = $this->config->get(PaytabsAdapter::_key('order_fraud_status_id', $this->_code));
                $this->model_checkout_order->addHistory($order_id, $fraudStatus, $res_msg);
            } else {
                $failedStatus = $this->config->get(PaytabsAdapter::_key('order_failed_status_id', $this->_code));
                if ($failedStatus) {
                    $this->model_checkout_order->addHistory($order_id, $failedStatus, $res_msg);
                }
            }

            // $this->callbackFailure($res_msg);
        }

        return $success;
    }

    public function refund()
    {
        $this->init();
        
        if (isset($this->controller->request->get['order_id'])) {
            $order_id = $this->controller->request->get['order_id'];
        } 


        $payment_refrence =  $this->db->query("SELECT transaction_id FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'")->row;
        
        $order_amount = $this->db->query("SELECT total FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'")->row;
        $order_currency = $this->db->query("SELECT currency_code FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'")->row;
       

        $values = [
            "tran_type" => "refund",
            "tran_class" => "ecom",
            "cart_id"=> $order_id,
            "cart_currency"=> implode(" ",$order_currency),
            "cart_amount"=> implode(" ",$order_amount),
            "cart_description"=> "Refunded from opencart",
            "tran_ref"=> implode(" ",$payment_refrence)
        ];

        $refund_request = $this->ptApi->request_followup($values);

        $tran_ref = @$refund_request->tran_ref;
        $success = $refund_request->success;
        $message = $refund_request->message;

        if ($success) {
            $order_status_id = 11; //refunded status id in opencart 3
            $sql = "UPDATE " . DB_PREFIX . "order SET order_status_id = '" . (int)$order_status_id . "' WHERE order_id = '" . (int)$order_id . "'";
            $this->db->query($sql)->row;
            PaytabsHelper::log("refund success, order  [{$order_id} - {$message}]");


        } else {
            PaytabsHelper::log("refund failed, {$order_id} - {$message}", 3);
        }

        $this->controller->response->redirect($this->controller->url->link('sale/order/info', 'user_token=' . $this->controller->session->data['user_token'] . '&order_id=' . $order_id, true));
    
    }



    public function redirectAfterPayment()
    {
        $transactionId =
            isset($this->request->post['tranRef'])
            ? $this->request->post['tranRef']
            : false;
        if (!$transactionId) {
            return $this->callbackFailure('Transaction ID is missing');
        }

        $this->init();

        $this->load->model('checkout/order');
        $this->load->model("extension/paytabs/payment/paytabs_{$this->_code}");

        $is_valid_req = $this->ptApi->is_valid_redirect($this->request->post);
        if (!$is_valid_req) {
            $_logVerify = json_encode($this->request->request);
            PaytabsHelper::log("return failed, Fraud request [{$_logVerify}]", 3);
            return;
        }

        $verify_response = $this->ptApi->verify_payment($transactionId);

        $success = $verify_response->success;
        $fraud = false;
        $res_msg = $verify_response->message;
        $order_id = @$verify_response->reference_no;
        $cart_amount = @$verify_response->cart_amount;
        $cart_currency = @$verify_response->cart_currency;

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            PaytabsHelper::log("return failed, No Order found [{$order_id}]", 3);
            return;
        }


        if ($success) {
            // Check here if the result is tempered

            if (!$this->_confirmAmountPaid($order_info, $cart_amount, $cart_currency)) {
                $res_msg = 'The Order has been altered';
                $success = false;
                $fraud = true;
            } else {
                PaytabsHelper::log("PayTabs {$this->_code} checkout succeeded");

                $this->response->redirect($this->url->link('checkout/success', '', true));
            }
        }

        if (!$success) {
            $_logVerify = (json_encode($verify_response));
            PaytabsHelper::log("return failed, response [{$_logVerify}]", 3);
            $this->callbackFailure($res_msg);
        }
    }


    private function _confirmAmountPaid($order_info, $online_amount, $online_currency)
    {
        $total = $order_info['total'];
        $online_amount = (float)$online_amount;
        $order_amount = $this->getPrice($total, $order_info);
        $order_currency = $order_info['currency_code'];

        if (strcasecmp($order_currency, $online_currency) != 0) {
            return false;
        }

        if (abs($order_amount - $online_amount) > 0.0001) {
            return false;
        }

        return true;
    }


    private function callbackFailure($message)
    {
        $this->load->language('checkout/failure');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home', '', true)
            ],
            [
                'text' => $this->language->get('text_basket'),
                'href' => $this->url->link('checkout/cart')
            ],
            [
                'text' => $this->language->get('text_checkout'),
                'href' => $this->url->link('checkout/checkout', '', true)
            ],
            [
                'text' => $this->language->get('text_failure'),
                'href' => $this->url->link('checkout/failure', '', true)
            ]
        ];

        $data['text_message'] = sprintf($this->language->get('text_message'), $this->url->link('information/contact', '', true));

        $data['continue'] = $this->url->link('common/home', '', true);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $data['paytabs_error'] = $message;
        $this->response->setOutput($this->load->view("extension/paytabs/payment/paytabs_error", $data));
    }


    //

    private function getPrice($value, $order_info)
    {
        return $this->currency->format($value, $order_info['currency_code'], $order_info['currency_value'], false);
    }

    /**
     * Extract required parameters from the Order, to Pass to create_page API
     * -Client information
     * -Shipping address
     * -Products
     * @return Array of values to pass to create_paypage API
     */
    private function prepareOrder()
    {
        $this->load->model('checkout/order');

        $orderId = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($orderId);
        $cart = $this->cart;

       


        // $siteUrl = $this->config->get('config_url');
        $return_url = $this->url->link("extension/paytabs/payment/paytabs_{$this->_code}|redirectAfterPayment", '', true);
        $callback_url = $this->url->link("extension/paytabs/payment/paytabs_{$this->_code}|callback", '', true);


        //

        $vouchers_arr = [];
        if (isset($this->session->data["vouchers"])) {
            $vouchers = $this->session->data["vouchers"];

            $vouchers_arr = array_map(function ($p) use ($order_info) {
                $name = $p['description'];
                // $price = $this->getPrice($p['amount'], $order_info);
                return "$name";
            }, $vouchers);
        }

        // $cost = $this->session->data['shipping_method']['cost'];
        // $subtotal = $this->cart->getSubTotal();
        // $discount = $subtotal + $cost - $order_info['total'];
        // $total = $subtotal + $cost;
        $total = $order_info['total'];
        $amount = $this->getPrice($total, $order_info);

        //

        $products = $cart->getProducts();

        $items_arr = array_map(function ($p) {
            $name = $p['name'];
            $qty = $p['quantity'];
            $qty_str = $qty > 1 ? "({$qty})" : '';
            return "{$name} $qty_str";
        }, $products);

        $items_arr = array_merge($items_arr, $vouchers_arr);
        $cart_desc = implode(', ', $items_arr);

        //

        // $cdetails = PaytabsHelper::getCountryDetails($order_info['payment_iso_code_2']);
        // $phoneext = $cdetails['phone'];
        $telephone = $order_info['telephone'];

        $address_billing = trim($order_info['payment_address_1'] . ' ' . $order_info['payment_address_2']);
        $address_shipping = trim($order_info['shipping_address_1'] . ' ' . $order_info['shipping_address_2']);

        $zone_billing = PaytabsHelper::getNonEmpty($order_info['payment_zone'], $order_info['payment_city']);
        $zone_shipping = PaytabsHelper::getNonEmpty($order_info['shipping_zone'], $order_info['shipping_city'], $zone_billing);

        $lang_code = $this->language->get('code');
        // $lang = ($lang_code == 'ar') ? 'Arabic' : 'English';

        //

        $hide_shipping = (bool) $this->config->get(PaytabsAdapter::_key('hide_shipping', $this->_code));
        $iframe = (bool) $this->config->get(PaytabsAdapter::_key('iframe', $this->_code));
        $allow_associated_methods = (bool) $this->config->get(PaytabsAdapter::_key('allow_associated_methods', $this->_code));

        //

        $holder = new PaytabsRequestHolder();
        $holder
            ->set01PaymentCode($this->_code, $allow_associated_methods, $order_info['currency_code'])
            ->set02Transaction(PaytabsEnum::TRAN_TYPE_SALE, PaytabsEnum::TRAN_CLASS_ECOM)
            ->set03Cart(
                $orderId,
                $order_info['currency_code'],
                $amount,
                $cart_desc
            )
            ->set04CustomerDetails(
                $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
                $order_info['email'],
                $telephone,
                $address_billing,
                $order_info['payment_city'],
                $zone_billing,
                $order_info['payment_iso_code_3'],
                $order_info['payment_postcode'],
                null
            )
            ->set05ShippingDetails(
                false,
                $order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'],
                $order_info['email'],
                null,
                $address_shipping,
                $order_info['shipping_city'],
                $zone_shipping,
                $order_info['shipping_iso_code_3'],
                $order_info['shipping_postcode'],
                null
            )
            ->set06HideShipping($hide_shipping)
            ->set07URLs($return_url, $callback_url)
            ->set08Lang($lang_code)
            ->set09Framed($iframe, 'top')
            ->set99PluginInfo('OpenCart', VERSION, PAYTABS_PAYPAGE_VERSION);

        if ($this->_code === 'valu') {
            $valu_product_id = $this->config->get(PaytabsAdapter::_key('valu_product_id', $this->_code));
            // $holder->set20ValuParams($valu_product_id, 0);
        }

        $post_arr = $holder->pt_build();

        return $post_arr;
    }
}


abstract class PaytabsCatalogModel extends \Opencart\System\Engine\Model
{
    public $_code = '';

    // private $controller;


    function init()
    {
        // $this->controller = $controller;

        $this->load->language("extension/paytabs/payment/paytabs_strings");
    }


    public function getMethod($address)
    {
        $this->init();

        /** Read params */

        $currencyCode = $this->session->data['currency'];

        /** Confirm the availability of the payment method */

        $status = true;

        if ($this->cart->hasSubscription()) {
            $status = false;
        } elseif (!$this->isAvailableForAddress($address)) {
            $status = false;
        } elseif (!PaytabsHelper::paymentAllowed($this->_code, $currencyCode)) {
            $status = false;
        }


        /** Prepare the payment method */

        $method_data = array();

        if ($status) {
            if (VERSION >= '4.0.2.0')
            {
                $method_data = array(
                    'code'       => "paytabs_{$this->_code}",
                    'name'       => $this->language->get("{$this->_code}_text_title"),
                    'option'     => '',
                    'sort_order' => $this->config->get(PaytabsAdapter::_key('sort_order', $this->_code))
                );
            }
            else
            {
                $method_data = array(
                    'code'       => "paytabs_{$this->_code}",
                    'title'      => $this->language->get("{$this->_code}_text_title"),
                    'terms'      => '',
                    'sort_order' => $this->config->get(PaytabsAdapter::_key('sort_order', $this->_code))
                );
            }
           
        }

        return $method_data;
    }

    public function getMethods(array $address = []): array 
    {
        $this->init();

        /** Read params */

        $currencyCode = $this->session->data['currency'];

        /** Confirm the availability of the payment method */

        $status = true;

        if ($this->cart->hasSubscription()) {
            $status = false;
        } elseif (!$this->isAvailableForAddress($address)) {
            $status = false;
        } elseif (!PaytabsHelper::paymentAllowed($this->_code, $currencyCode)) {
            $status = false;
        }
    

        /** Prepare the payment method */

        $method_data = [];

        if ($status) {
            if (VERSION >= '4.0.2.0')
            {
                $option_data["paytabs_{$this->_code}"] = [
                    'code' => "paytabs_{$this->_code}".".paytabs_{$this->_code}",
                    'name' => $this->language->get("{$this->_code}_text_title"),
                ];
                
                $method_data = [
                    'code'       => "paytabs_{$this->_code}",
                    'name'       => $this->language->get("{$this->_code}_text_title"),
                    'option'     => $option_data,
                    'sort_order' => $this->config->get(PaytabsAdapter::_key('sort_order', $this->_code))
                ];
            }
            else
            {
                $method_data = array(
                    'code'       => "paytabs_{$this->_code}",
                    'title'      => $this->language->get("{$this->_code}_text_title"),
                    'terms'      => '',
                    'sort_order' => $this->config->get(PaytabsAdapter::_key('sort_order', $this->_code))
                );
            }
           
        }

        return $method_data;
    }


    private function isAvailableForAddress($address)
    {
        $geoZoneId = (int) $this->config->get(PaytabsAdapter::_key('geo_zone_id', $this->_code));

        if (!$geoZoneId) {
            return true;
        }

        $table = DB_PREFIX . "zone_to_geo_zone";
        $query = $this->db->query(
            "SELECT * FROM ${table} WHERE geo_zone_id = '{$geoZoneId}' AND country_id =". (int)$address['country_id']." AND (zone_id =". (int)$address['zone_id']." OR zone_id = '0')"
        );

        if ($query->num_rows) {
            return true;
        }

        return false;
    }
}


class PaytabsAdapter
{
    private $config;
    private $paymentMethod;

    /**
     * Main keys foreach payment method
     * key: used in HTML forms
     * configKey: used for Database store, each payment method has different configKey value
     * required: used in validate() function when saving the payment settings form
     */
    const KEYS = [
        'status' => [
            'key' => 'payment_paytabs_status',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_status',
            'required' => false,
        ],
        'endpoint' => [
            'key' => 'payment_paytabs_endpoint',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_endpoint',
            'required' => true,
        ],
        'profile_id' => [
            'key' => 'payment_paytabs_profile_id',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_profile_id',
            'required' => true,
        ],
        'server_key' => [
            'key' => 'payment_paytabs_server_key',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_server_key',
            'required' => true,
        ],
        'valu_product_id' => [
            'key' => 'payment_paytabs_valu_product_id',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_valu_product_id',
            'required' => true,
            'methods' => ['valu']
        ],
        'order_status_id' => [
            'key' => 'payment_paytabs_order_status_id',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_order_status_id',
            'required' => false,
        ],
        'order_failed_status_id' => [
            'key' => 'payment_paytabs_order_failed_status_id',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_order_failed_status_id',
            'required' => false,
        ],
        'order_fraud_status_id' => [
            'key' => 'payment_paytabs_order_fraud_status_id',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_order_fraud_status_id',
            'required' => false,
        ],
        'hide_shipping' => [
            'key' => 'payment_paytabs_hide_shipping',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_hide_shipping',
            'required' => false,
        ],
        'iframe' => [
            'key' => 'payment_paytabs_iframe',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_iframe',
            'required' => false,
        ],
        'geo_zone_id' => [
            'key' => 'payment_paytabs_geo_zone_id',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_geo_zone_id',
            'required' => false,
        ],
        'sort_order' => [
            'key' => 'payment_paytabs_sort_order',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_sort_order',
            'required' => false,
        ],
        'allow_associated_methods' => [
            'key' => 'payment_paytabs_allow_associated_methods',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_allow_associated_methods',
            'required' => false,
        ],
    ];

    const KEY_PREFIX = PAYTABS_OPENCART_2_3 ? '' : 'payment_'; // OpenCart 2.3

    static function _key($key, $payment_code)
    {
        return self::KEY_PREFIX . str_replace('_{PAYMENTMETHOD}_', "_{$payment_code}_", self::KEYS[$key]['configKey']);
    }


    public function __construct($config, $paymentMethod)
    {
        $this->config = $config;
        $this->paymentMethod = $paymentMethod;
    }

    public function pt()
    {
        $endpoint = $this->config->get(PaytabsAdapter::_key('endpoint', $this->paymentMethod));

        $merchant_id = $this->config->get(PaytabsAdapter::_key('profile_id', $this->paymentMethod));
        $merchant_key = $this->config->get(PaytabsAdapter::_key('server_key', $this->paymentMethod));

        $pt = PaytabsApi::getInstance($endpoint, $merchant_id, $merchant_key);

        return $pt;
    }
}

function paytabs_error_log($message, $severity = 1)
{
    $log = new Log(PAYTABS_DEBUG_FILE_NAME);

    $severity_str = $severity == 1 ? 'Info' : ($severity == 2 ? 'Warning' : 'Error');
    $_prefix = "[{$severity_str}] ";

    $log->write($_prefix . $message);
}
