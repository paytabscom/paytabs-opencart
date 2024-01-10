<?php

namespace Opencart\System\Library;

define('CLICKPAY_PAYPAGE_VERSION', '4.9.0');

define('CLICKPAY_OPENCART_2_3', substr(VERSION, 0, 3) == '2.3');

// require_once DIR_SYSTEM . '/library/clickpay_core.php';
require_once DIR_EXTENSION . 'clickpay/system/library/clickpay_core.php';

class clickpay_api
{
}

abstract class ClickpayAdminController extends \Opencart\System\Engine\Controller
{
    // private $controller;

    public $_code = '_';
    public $error = array();
    public $userToken;

    //

    private $keys = ClickpayAdapter::KEYS;

    private $userToken_str = '';

    private $urlExtensions = '';

    private $settingsKey = '';

    //

    function init()
    {
        // $this->controller = $controller;

        // $this->controller->load->library('clickpay_api');

        $this->load->language("extension/clickpay/payment/clickpay_strings");
        $this->load->model('setting/setting');

        $this->document->setTitle($this->language->get("{$this->_code}_heading_title"));

        $this->keys = array_filter($this->keys, function ($values) {
            if (key_exists('methods', $values) && !in_array($this->_code, $values['methods'])) return false;
            return true;
        });

        foreach ($this->keys as $key => &$value) {
            $value['configKey'] = ClickpayAdapter::KEY_PREFIX . str_replace('_{PAYMENTMETHOD}_', "_{$this->_code}_", $value['configKey']);
        }

        if (CLICKPAY_OPENCART_2_3) {
            $this->urlExtensions = 'extension/extension'; // OpenCart 2.3

            $token_str = 'token'; // OpenCart 2.3

            $this->userToken = $this->session->data[$token_str];
            $this->userToken_str = "token={$this->userToken}"; // OpenCart 2.3

            $this->settingsKey = "clickpay_{$this->_code}"; // OpenCart 2.3

        } else {
            $this->urlExtensions = 'marketplace/extension'; // OpenCart 3.x

            $token_str = 'user_token'; // OpenCart 3.x

            $this->userToken = $this->session->data[$token_str];
            $this->userToken_str = "user_token={$this->userToken}"; // OpenCart 3.x

            $this->settingsKey = "payment_clickpay_{$this->_code}"; // OpenCart 3.x
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

        ClickpayAdminController::clickpay_errorList($this->error, [
            'warning',
            'profile_id',
            'endpoint',
            'server_key',
            'valu_product_id'
        ], $data);


        /** Fill values */

        $data['endpoints'] = ClickpayApi::getEndpoints();
        $data['is_card_payment'] = ClickpayHelper::isCardPayment($this->_code);
        $data['support_iframe'] =  ClickpayHelper::supportIframe($this->_code);

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        ClickpayAdminController::clickpay_fillVars(
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
                'href' => $this->url->link("extension/clickpay/payment/clickpay_{$this->_code}", "{$this->userToken_str}", true)
            ]
        ];


        /** Actions */

        $data['action'] = $this->url->link("extension/clickpay/payment/clickpay_{$this->_code}", "{$this->userToken_str}", true);
        $data['cancel'] = $this->url->link($this->urlExtensions, "{$this->userToken_str}&type=payment", true);


        /** Fetch page parts */

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');


        if (CLICKPAY_OPENCART_2_3) {
            /** Strings */ // OpenCart 2.3

            $this->load->language("extension/clickpay/payment/clickpay_strings");
            $strings = $this->language->all();
            foreach ($strings as $key => $value) {
                if (substr($key, 0, 5) === "error") continue;
                $data[$key] = $value;
            }
        }


        /** Response */
        if (VERSION >= '4.0.2.0')
        {
            $data['method'] = "clickpay_{$this->_code}".".clickpay_{$this->_code}";
        }
        else
        {
            $data['method'] = $this->_code;
        }

        
        $data['title'] = $this->language->get("{$this->_code}_heading_title");
        $this->response->setOutput($this->load->view("extension/clickpay/payment/clickpay_view", $data));
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
        if (!$this->user->hasPermission('modify', "extension/clickpay/payment/clickpay_{$this->_code}")) {
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
            ClickpayAdapter::_key('sort_order', $this->_code) => ($this->_code == 'mada') ? 1 : 80,
            ClickpayAdapter::_key('order_status_id',       $this->_code) => 2, // Processing
            ClickpayAdapter::_key('order_fraud_status_id', $this->_code) => 8, // Denied
        ];

        if (ClickpayHelper::isCardPayment($this->_code)) {
            $allow_associated_methods = true;
            if ($this->_code == 'knet') {
                $allow_associated_methods = false;
            }
            $defaults[ClickpayAdapter::_key('allow_associated_methods', $this->_code)] = $allow_associated_methods;
        }

        $this->model_setting_setting->editSetting($this->settingsKey, $defaults);
    }

    //

    public static function clickpay_errorList($error, $keys, &$data, $prefix = 'error_')
    {
        foreach ($keys as $key) {
            $data["{$prefix}{$key}"] = isset($error[$key]) ? $error[$key] : '';
        }
    }

    public static function clickpay_fillVars($keys, $arrData, $configs, &$data)
    {
        foreach ($keys as $option => $value) {
            $htmlKey = $value['key'];
            $configKey = $value['configKey'];

            $data[$htmlKey] = isset($arrData[$htmlKey]) ? $arrData[$htmlKey] : $configs->get($configKey);
        }
    }
}


abstract class ClickpayCatalogController extends \Opencart\System\Engine\Controller
{
    public $_code = '';

    // private $controller;
    private $ptApi;

    function init()
    {
        // $this->controller = $controller;

        $this->ptApi = (new ClickpayAdapter($this->config, $this->_code))->pt();
    }


    public function index()
    {
        $this->init();

        $data['button_confirm'] = $this->language->get('button_confirm');

        $orderId = $this->session->data['order_id'];

        $data['order_id'] = $orderId;
        $data['iframe_mode'] = (bool) $this->config->get(ClickpayAdapter::_key('iframe', $this->_code));
        $data['url_confirm'] = $this->url->link("extension/clickpay/payment/clickpay_{$this->_code}|confirm", '', true);

        return $this->load->view("extension/clickpay/payment/clickpay_view", $data);
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
            if ($order_session_payment['code'] != "clickpay_{$this->_code}".".clickpay_{$this->_code}") {
                $this->_re_checkout('Payment method is required');
                return;
            }
        }
        else
        {
            if ($order_session_payment != "clickpay_{$this->_code}") {
                $this->_re_checkout('Payment method is required');
                return;
            }
        }
      

        //

        $values = $this->prepareOrder();

        $paypage = $this->ptApi->create_pay_page($values);

        $iframe = (bool) $this->config->get(ClickpayAdapter::_key('iframe', $this->_code));

        if ($paypage->success) {
            $payment_url = $paypage->payment_url;

            if ($iframe) {
                $data['payment_url'] = $payment_url;

                $pnl_iFrame = $this->load->view("extension/clickpay/payment/clickpay_framed", $data);
                $this->response->setOutput($pnl_iFrame);
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(["redirect_url" => $payment_url, "status" => true]));
            }
        } else {
            $paypage_msg = $paypage->message;

            $_logResult = json_encode($paypage);
            $_logData = json_encode($values);
            ClickpayHelper::log("callback failed, Data [{$_logData}], response [{$_logResult}]", 3);

            if ($iframe) {
                $this->response->setOutput($paypage_msg);
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(["message" => $paypage_msg, "status" => false]));
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
        $this->load->model("extension/clickpay/payment/clickpay_{$this->_code}");

        $success = $response_data->success;
        $fraud = false;
        $res_msg = $response_data->message;
        $order_id = @$response_data->reference_no;
        $cart_amount = @$response_data->cart_amount;
        $cart_currency = $response_data->cart_currency;

        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            ClickpayHelper::log("callback failed, No Order found [{$order_id}]", 3);
            return;
        }


        if ($success) {
            // Check here if the result is tempered

            if (!$this->_confirmAmountPaid($order_info, $cart_amount, $cart_currency)) {
                $res_msg = "The Order has been altered, {$order_id}";
                $success = false;
                $fraud = true;
            } else {
                ClickpayHelper::log("Clickpay {$this->_code} checkout succeeded");

                $successStatus = $this->config->get(ClickpayAdapter::_key('order_status_id', $this->_code));

                $this->model_checkout_order->addHistory($order_id, $successStatus, $res_msg);
            }
        } else if ($response_data->is_on_hold) {

        } else if ($response_data->is_pending) {
            
        }

        if (!$success) {
            $_logVerify = (json_encode($response_data));
            ClickpayHelper::log("callback failed, response [{$_logVerify}]", 3);

            if ($fraud) {
                $fraudStatus = $this->config->get(ClickpayAdapter::_key('order_fraud_status_id', $this->_code));
                $this->model_checkout_order->addHistory($order_id, $fraudStatus, $res_msg);
            } else {
                $failedStatus = $this->config->get(ClickpayAdapter::_key('order_failed_status_id', $this->_code));
                if ($failedStatus) {
                    $this->model_checkout_order->addHistory($order_id, $failedStatus, $res_msg);
                }
            }

            // $this->callbackFailure($res_msg);
        }

        return $success;
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
        $this->load->model("extension/clickpay/payment/clickpay_{$this->_code}");

        $is_valid_req = $this->ptApi->is_valid_redirect($this->request->post);
        if (!$is_valid_req) {
            $_logVerify = json_encode($this->request->request);
            ClickpayHelper::log("return failed, Fraud request [{$_logVerify}]", 3);
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
            ClickpayHelper::log("return failed, No Order found [{$order_id}]", 3);
            return;
        }


        if ($success) {
            // Check here if the result is tempered

            if (!$this->_confirmAmountPaid($order_info, $cart_amount, $cart_currency)) {
                $res_msg = 'The Order has been altered';
                $success = false;
                $fraud = true;
            } else {
                ClickpayHelper::log("Clickpay {$this->_code} checkout succeeded");

                $this->response->redirect($this->url->link('checkout/success', '', true));
            }
        }

        if (!$success) {
            $_logVerify = (json_encode($verify_response));
            ClickpayHelper::log("return failed, response [{$_logVerify}]", 3);
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

        $data['clickpay_error'] = $message;
        $this->response->setOutput($this->load->view("extension/clickpay/payment/clickpay_error", $data));
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
        $return_url = $this->url->link("extension/clickpay/payment/clickpay_{$this->_code}|redirectAfterPayment", '', true);
        $callback_url = $this->url->link("extension/clickpay/payment/clickpay_{$this->_code}|callback", '', true);


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

        // $cdetails = ClickpayHelper::getCountryDetails($order_info['payment_iso_code_2']);
        // $phoneext = $cdetails['phone'];
        $telephone = $order_info['telephone'];

        $address_billing = trim($order_info['payment_address_1'] . ' ' . $order_info['payment_address_2']);
        $address_shipping = trim($order_info['shipping_address_1'] . ' ' . $order_info['shipping_address_2']);

        $zone_billing = ClickpayHelper::getNonEmpty($order_info['payment_zone'], $order_info['payment_city']);
        $zone_shipping = ClickpayHelper::getNonEmpty($order_info['shipping_zone'], $order_info['shipping_city'], $zone_billing);

        $lang_code = $this->language->get('code');
        // $lang = ($lang_code == 'ar') ? 'Arabic' : 'English';

        //

        $hide_shipping = (bool) $this->config->get(ClickpayAdapter::_key('hide_shipping', $this->_code));
        $iframe = (bool) $this->config->get(ClickpayAdapter::_key('iframe', $this->_code));
        $allow_associated_methods = (bool) $this->config->get(ClickpayAdapter::_key('allow_associated_methods', $this->_code));
        $theme_config_id = $this->config->get(ClickpayAdapter::_key('config_id', $this->_code));
        $alt_currency = $this->config->get(ClickpayAdapter::_key('alt_currency', $this->_code));

        //

        $holder = new ClickpayRequestHolder();
        $holder
            ->set01PaymentCode($this->_code, $allow_associated_methods, $order_info['currency_code'])
            ->set02Transaction(ClickpayEnum::TRAN_TYPE_SALE, ClickpayEnum::TRAN_CLASS_ECOM)
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
            ->set11ThemeConfigId($theme_config_id)
            ->set12AltCurrency($alt_currency)
            ->set99PluginInfo('OpenCart', VERSION, CLICKPAY_PAYPAGE_VERSION);

        if ($this->_code === 'valu') {
            $valu_product_id = $this->config->get(ClickpayAdapter::_key('valu_product_id', $this->_code));
            // $holder->set20ValuParams($valu_product_id, 0);
        }

        $post_arr = $holder->pt_build();

        return $post_arr;
    }
}


abstract class ClickpayCatalogModel extends \Opencart\System\Engine\Model
{
    public $_code = '';

    // private $controller;


    function init()
    {
        // $this->controller = $controller;

        $this->load->language("extension/clickpay/payment/clickpay_strings");
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
        } elseif (!ClickpayHelper::paymentAllowed($this->_code, $currencyCode)) {
            $status = false;
        }


        /** Prepare the payment method */

        $method_data = array();

        if ($status) {
            if (VERSION >= '4.0.2.0')
            {
                $method_data = array(
                    'code'       => "clickpay_{$this->_code}",
                    'name'       => $this->language->get("{$this->_code}_text_title"),
                    'option'     => '',
                    'sort_order' => $this->config->get(ClickpayAdapter::_key('sort_order', $this->_code))
                );
            }
            else
            {
                $method_data = array(
                    'code'       => "clickpay_{$this->_code}",
                    'title'      => $this->language->get("{$this->_code}_text_title"),
                    'terms'      => '',
                    'sort_order' => $this->config->get(ClickpayAdapter::_key('sort_order', $this->_code))
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
        } elseif (!ClickpayHelper::paymentAllowed($this->_code, $currencyCode)) {
            $status = false;
        }
    

        /** Prepare the payment method */

        $method_data = [];

        if ($status) {
            if (VERSION >= '4.0.2.0')
            {
                $option_data["clickpay_{$this->_code}"] = [
                    'code' => "clickpay_{$this->_code}".".clickpay_{$this->_code}",
                    'name' => $this->language->get("{$this->_code}_text_title"),
                ];
                
                $method_data = [
                    'code'       => "clickpay_{$this->_code}",
                    'name'       => $this->language->get("{$this->_code}_text_title"),
                    'option'     => $option_data,
                    'sort_order' => $this->config->get(ClickpayAdapter::_key('sort_order', $this->_code))
                ];
            }
            else
            {
                $method_data = array(
                    'code'       => "clickpay_{$this->_code}",
                    'title'      => $this->language->get("{$this->_code}_text_title"),
                    'terms'      => '',
                    'sort_order' => $this->config->get(ClickpayAdapter::_key('sort_order', $this->_code))
                );
            }
           
        }

        return $method_data;
    }


    private function isAvailableForAddress($address)
    {
        $geoZoneId = (int) $this->config->get(ClickpayAdapter::_key('geo_zone_id', $this->_code));

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


class ClickpayAdapter
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
            'key' => 'payment_clickpay_status',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_status',
            'required' => false,
        ],
        'endpoint' => [
            'key' => 'payment_clickpay_endpoint',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_endpoint',
            'required' => true,
        ],
        'profile_id' => [
            'key' => 'payment_clickpay_profile_id',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_profile_id',
            'required' => true,
        ],
        'server_key' => [
            'key' => 'payment_clickpay_server_key',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_server_key',
            'required' => true,
        ],
        'valu_product_id' => [
            'key' => 'payment_clickpay_valu_product_id',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_valu_product_id',
            'required' => true,
            'methods' => ['valu']
        ],
        'order_status_id' => [
            'key' => 'payment_clickpay_order_status_id',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_order_status_id',
            'required' => false,
        ],
        'order_failed_status_id' => [
            'key' => 'payment_clickpay_order_failed_status_id',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_order_failed_status_id',
            'required' => false,
        ],
        'order_fraud_status_id' => [
            'key' => 'payment_clickpay_order_fraud_status_id',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_order_fraud_status_id',
            'required' => false,
        ],
        'hide_shipping' => [
            'key' => 'payment_clickpay_hide_shipping',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_hide_shipping',
            'required' => false,
        ],
        'iframe' => [
            'key' => 'payment_clickpay_iframe',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_iframe',
            'required' => false,
        ],
        'geo_zone_id' => [
            'key' => 'payment_clickpay_geo_zone_id',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_geo_zone_id',
            'required' => false,
        ],
        'sort_order' => [
            'key' => 'payment_clickpay_sort_order',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_sort_order',
            'required' => false,
        ],
        'allow_associated_methods' => [
            'key' => 'payment_clickpay_allow_associated_methods',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_allow_associated_methods',
            'required' => false,
        ],
        'config_id' => [
            'key' => 'payment_clickpay_config_id',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_config_id',
            'required' => false,
        ],
        'alt_currency' => [
            'key' => 'payment_clickpay_alt_currency',
            'configKey' => 'clickpay_{PAYMENTMETHOD}_alt_currency',
            'required' => false,
        ],
    ];

    const KEY_PREFIX = CLICKPAY_OPENCART_2_3 ? '' : 'payment_'; // OpenCart 2.3

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
        $endpoint = $this->config->get(ClickpayAdapter::_key('endpoint', $this->paymentMethod));

        $merchant_id = $this->config->get(ClickpayAdapter::_key('profile_id', $this->paymentMethod));
        $merchant_key = $this->config->get(ClickpayAdapter::_key('server_key', $this->paymentMethod));

        $pt = ClickpayApi::getInstance($endpoint, $merchant_id, $merchant_key);

        return $pt;
    }
}

function clickpay_error_log($message, $severity = 1)
{
    $log = new Log(CLICKPAY_DEBUG_FILE_NAME);

    $severity_str = $severity == 1 ? 'Info' : ($severity == 2 ? 'Warning' : 'Error');
    $_prefix = "[{$severity_str}] ";

    $log->write($_prefix . $message);
}
