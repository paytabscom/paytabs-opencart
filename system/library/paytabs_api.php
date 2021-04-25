<?php

define('PAYTABS_PAYPAGE_VERSION', '3.2.0');
define('PAYTABS_DEBUG_FILE', 'debug_paytabs.log');

define('PAYTABS_OPENCART_2_3', substr(VERSION, 0, 3) == '2.3');

require_once DIR_SYSTEM . '/library/paytabs_core.php';

class paytabs_api
{
}

class PaytabsController
{
    private $controller;

    private $keys = PaytabsAdapter::KEYS;

    private $userToken_str = '';

    private $urlExtensions = '';

    private $settingsKey = '';

    //

    function __construct($controller)
    {
        $this->controller = $controller;

        $this->controller->load->library('paytabs_api');

        $this->controller->load->language("extension/payment/paytabs_strings");
        $this->controller->load->model('setting/setting');

        $this->controller->document->setTitle($this->controller->language->get("{$this->controller->_code}_heading_title"));

        $this->keys = array_filter($this->keys, function ($values) {
            if (key_exists('methods', $values) && !in_array($this->controller->_code, $values['methods'])) return false;
            return true;
        });

        foreach ($this->keys as $key => &$value) {
            $value['configKey'] = PaytabsAdapter::KEY_PREFIX . str_replace('_{PAYMENTMETHOD}_', "_{$this->controller->_code}_", $value['configKey']);
        }

        if (PAYTABS_OPENCART_2_3) {
            $this->urlExtensions = 'extension/extension'; // OpenCart 2.3

            $token_str = 'token'; // OpenCart 2.3

            $this->controller->userToken = $this->controller->session->data[$token_str];
            $this->userToken_str = "token={$this->controller->userToken}"; // OpenCart 2.3

            $this->settingsKey = "paytabs_{$this->controller->_code}"; // OpenCart 2.3

        } else {
            $this->urlExtensions = 'marketplace/extension'; // OpenCart 3.x

            $token_str = 'user_token'; // OpenCart 3.x

            $this->controller->userToken = $this->controller->session->data[$token_str];
            $this->userToken_str = "user_token={$this->controller->userToken}"; // OpenCart 3.x

            $this->settingsKey = "payment_paytabs_{$this->controller->_code}"; // OpenCart 3.x
        }
    }


    public function index(&$data)
    {
        /** Save request Handling */

        if (($this->controller->request->server['REQUEST_METHOD'] == 'POST') && $this->controller->validate()) {
            $this->controller->save();
        }


        /** Error messages Handling */

        PaytabsController::paytabs_errorList($this->controller->error, [
            'warning',
            'profile_id',
            'endpoint',
            'server_key',
            'valu_product_id'
        ], $data);


        /** Fill values */

        $data['endpoints'] = PaytabsApi::getEndpoints();

        $this->controller->load->model('localisation/order_status');
        $data['order_statuses'] = $this->controller->model_localisation_order_status->getOrderStatuses();

        $this->controller->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->controller->model_localisation_geo_zone->getGeoZones();

        PaytabsController::paytabs_fillVars(
            $this->keys,
            $this->controller->request->post,
            $this->controller->config,
            $data
        );


        /** Breadcrumb building */

        $data['breadcrumbs'] = [
            [
                'text' => $this->controller->language->get('text_home'),
                'href' => $this->controller->url->link('common/dashboard', "{$this->userToken_str}", true)
            ],
            [
                'text' => $this->controller->language->get('text_extension'),
                'href' => $this->controller->url->link($this->urlExtensions, "{$this->userToken_str}&type=payment", true)
            ],
            [
                'text' => $this->controller->language->get("{$this->controller->_code}_heading_title"),
                'href' => $this->controller->url->link("extension/payment/paytabs_{$this->controller->_code}", "{$this->userToken_str}", true)
            ]
        ];


        /** Actions */

        $data['action'] = $this->controller->url->link("extension/payment/paytabs_{$this->controller->_code}", "{$this->userToken_str}", true);
        $data['cancel'] = $this->controller->url->link($this->urlExtensions, "{$this->userToken_str}&type=payment", true);


        /** Fetch page parts */

        $data['header'] = $this->controller->load->controller('common/header');
        $data['column_left'] = $this->controller->load->controller('common/column_left');
        $data['footer'] = $this->controller->load->controller('common/footer');


        if (PAYTABS_OPENCART_2_3) {
            /** Strings */ // OpenCart 2.3

            $this->controller->load->language("extension/payment/paytabs_strings");
            $strings = $this->controller->language->all();
            foreach ($strings as $key => $value) {
                if (substr($key, 0, 5) === "error") continue;
                $data[$key] = $value;
            }
        }


        /** Response */

        $data['method'] = $this->controller->_code;
        $data['title'] = $this->controller->language->get("{$this->controller->_code}_heading_title");
        $this->controller->response->setOutput($this->controller->load->view("extension/payment/paytabs_view", $data));
    }


    public function save()
    {
        $values = [];
        foreach ($this->keys as $option => $value) {
            $postKey = $value['key'];
            $configKey = $value['configKey'];

            $values[$configKey] = $this->controller->request->post[$postKey];
        }

        $this->controller->model_setting_setting->editSetting($this->settingsKey, $values);

        $this->controller->session->data['success'] = $this->controller->language->get('text_success');

        $this->controller->response->redirect($this->controller->url->link($this->urlExtensions, "{$this->userToken_str}&type=payment", true));
    }


    public function validate()
    {
        if (!$this->controller->user->hasPermission('modify', "extension/payment/paytabs_{$this->controller->_code}")) {
            $this->controller->error['warning'] = $this->controller->language->get('error_permission');
        }

        foreach ($this->keys as $option => $value) {
            $key = $value['key'];
            $required = $value['required'];

            if (!$required) continue;

            if (!$this->controller->request->post[$key]) {
                $this->controller->error[$option] = $this->controller->language->get("error_{$option}");
            }
        }

        return !$this->controller->error;
    }


    public function install()
    {
        $this->controller->load->model('setting/setting');

        $defaults = [
            PaytabsAdapter::_key('sort_order', $this->controller->_code) => 80,
            PaytabsAdapter::_key('order_status_id',       $this->controller->_code) => 2, // Processing
            PaytabsAdapter::_key('order_fraud_status_id', $this->controller->_code) => 8, // Denied
        ];

        $this->controller->model_setting_setting->editSetting($this->settingsKey, $defaults);
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


class PaytabsCatalogController
{
    private $controller;
    private $ptApi;

    function __construct($controller)
    {
        $this->controller = $controller;

        $this->ptApi = (new PaytabsAdapter($this->controller->config, $this->controller->_code))->pt();
    }


    public function index(&$data)
    {
        $data['button_confirm'] = $this->controller->language->get('button_confirm');

        $orderId = $this->controller->session->data['order_id'];

        $data['order_id'] = $orderId;
        $data['url_confirm'] = $this->controller->url->link("extension/payment/paytabs_{$this->controller->_code}/confirm", '', true);

        return $this->controller->load->view("extension/payment/paytabs_view", $data);
    }

    public function confirm(&$data)
    {
        $order_id = $this->controller->request->post['order'];
        $order_session_id = $this->controller->session->data['order_id'];
        if ($order_id != $order_session_id) {
            $this->_re_checkout('The Order has been changed');
            return;
        }

        $values = $this->prepareOrder();

        $paypage = $this->ptApi->create_pay_page($values);

        if ($paypage->success) {
            $payment_url = $paypage->payment_url;

            $this->controller->response->redirect($payment_url);
        } else {
            $paypage_msg = $paypage->message;

            $_logResult = json_encode($paypage);
            $_logData = json_encode($values);
            PaytabsHelper::log("callback failed, Data [{$_logData}], response [{$_logResult}]", 3);

            $this->_re_checkout($paypage_msg);
        }
    }


    private function _re_checkout($msg)
    {
        $this->controller->session->data['error'] = $msg;
        $this->controller->response->redirect($this->controller->url->link('checkout/checkout', '', true));
    }


    public function callback()
    {
        $transactionId =
            isset($this->controller->request->post['tranRef'])
            ? $this->controller->request->post['tranRef']
            : false;
        if (!$transactionId) {
            return $this->callbackFailure('Transaction ID is missing');
        }

        $this->controller->load->model('checkout/order');
        $this->controller->load->model("extension/payment/paytabs_{$this->controller->_code}");

        $is_valid_req = $this->ptApi->is_valid_redirect($this->controller->request->post);
        if (!$is_valid_req) {
            $_logVerify = json_encode($this->controller->request->request);
            PaytabsHelper::log("callback failed, Fraud request [{$_logVerify}]", 3);
            return;
        }

        $verify_response = $this->ptApi->verify_payment($transactionId);

        $success = $verify_response->success;
        $fraud = false;
        $res_msg = $verify_response->message;
        $order_id = @$verify_response->reference_no;
        $cart_amount = @$verify_response->cart_amount;
        $cart_currency = @$verify_response->cart_currency;

        $order_info = $this->controller->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            PaytabsHelper::log("callback failed, No Order found [{$order_id}]", 3);
            return;
        }


        if ($success) {
            // Check here if the result is tempered

            if (!$this->_confirmAmountPaid($order_info, $cart_amount, $cart_currency)) {
                $res_msg = 'The Order has been altered';
                $success = false;
                $fraud = true;
            } else {
                PaytabsHelper::log("PayTabs {$this->controller->_code} checkout successed");

                $successStatus = $this->controller->config->get(PaytabsAdapter::_key('order_status_id', $this->controller->_code));

                $this->controller->model_checkout_order->addOrderHistory($order_id, $successStatus, $res_msg);
                $this->controller->response->redirect($this->controller->url->link('checkout/success', '', true));
            }
        }

        if (!$success) {
            $_logVerify = (json_encode($verify_response));
            PaytabsHelper::log("callback failed, response [{$_logVerify}]", 3);

            // Redirect to failed method
            // $this->controller->response->redirect($this->controller->url->link('checkout/failure'));

            if ($fraud) {
                $fraudStatus = $this->controller->config->get(PaytabsAdapter::_key('order_fraud_status_id', $this->controller->_code));
                $this->controller->model_checkout_order->addOrderHistory($order_id, $fraudStatus, $res_msg);
            } else {
                $failedStatus = $this->controller->config->get(PaytabsAdapter::_key('order_failed_status_id', $this->controller->_code));
                if ($failedStatus) {
                    $this->controller->model_checkout_order->addOrderHistory($order_id, $failedStatus, $res_msg);
                }
            }

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
        $this->controller->load->language('checkout/failure');

        $this->controller->document->setTitle($this->controller->language->get('heading_title'));

        $data['breadcrumbs'] = [
            [
                'text' => $this->controller->language->get('text_home'),
                'href' => $this->controller->url->link('common/home', '', true)
            ],
            [
                'text' => $this->controller->language->get('text_basket'),
                'href' => $this->controller->url->link('checkout/cart')
            ],
            [
                'text' => $this->controller->language->get('text_checkout'),
                'href' => $this->controller->url->link('checkout/checkout', '', true)
            ],
            [
                'text' => $this->controller->language->get('text_failure'),
                'href' => $this->controller->url->link('checkout/failure', '', true)
            ]
        ];

        $data['text_message'] = sprintf($this->controller->language->get('text_message'), $this->controller->url->link('information/contact', '', true));

        $data['continue'] = $this->controller->url->link('common/home', '', true);

        $data['column_left'] = $this->controller->load->controller('common/column_left');
        $data['column_right'] = $this->controller->load->controller('common/column_right');
        $data['content_top'] = $this->controller->load->controller('common/content_top');
        $data['content_bottom'] = $this->controller->load->controller('common/content_bottom');
        $data['footer'] = $this->controller->load->controller('common/footer');
        $data['header'] = $this->controller->load->controller('common/header');

        $data['paytabs_error'] = $message;
        $this->controller->response->setOutput($this->controller->load->view("extension/payment/paytabs_error", $data));
    }


    //

    private function getPrice($value, $order_info)
    {
        return $this->controller->currency->format($value, $order_info['currency_code'], $order_info['currency_value'], false);
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
        $this->controller->load->model('checkout/order');

        $orderId = $this->controller->session->data['order_id'];
        $order_info = $this->controller->model_checkout_order->getOrder($orderId);
        $cart = $this->controller->cart;

        // $siteUrl = $this->controller->config->get('config_url');
        $return_url = $this->controller->url->link("extension/payment/paytabs_{$this->controller->_code}/callback", '', true);

        //

        $vouchers_arr = [];
        if (isset($this->controller->session->data["vouchers"])) {
            $vouchers = $this->controller->session->data["vouchers"];

            $vouchers_arr = array_map(function ($p) use ($order_info) {
                $name = $p['description'];
                // $price = $this->getPrice($p['amount'], $order_info);
                return "$name";
            }, $vouchers);
        }

        // $cost = $this->controller->session->data['shipping_method']['cost'];
        // $subtotal = $this->controller->cart->getSubTotal();
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

        $lang_code = $this->controller->language->get('code');
        // $lang = ($lang_code == 'ar') ? 'Arabic' : 'English';

        //

        $hide_shipping = (bool) $this->controller->config->get(PaytabsAdapter::_key('hide_shipping', $this->controller->_code));

        //

        $holder = new PaytabsRequestHolder();
        $holder
            ->set01PaymentCode($this->controller->_code)
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
            ->set07URLs($return_url, null)
            ->set08Lang($lang_code)
            ->set99PluginInfo('OpenCart', VERSION, PAYTABS_PAYPAGE_VERSION);

        if ($this->controller->_code === 'valu') {
            $valu_product_id = $this->controller->config->get(PaytabsAdapter::_key('valu_product_id', $this->controller->_code));
            // $holder->set20ValuParams($valu_product_id, 0);
        }

        $post_arr = $holder->pt_build();

        return $post_arr;
    }
}


class PaytabsCatalogModel
{
    private $controller;


    function __construct($controller)
    {
        $this->controller = $controller;

        $this->controller->load->language("extension/payment/paytabs_strings");
    }


    public function getMethod($address, $total)
    {
        /** Read params */

        $currencyCode = $this->controller->session->data['currency'];
        $ptTotal = (float) $this->controller->config->get(PaytabsAdapter::_key('total', $this->controller->_code));

        $total1 = $this->controller->currency->format($total, $currencyCode, null, false);

        /** Confirm the availability of the payment method */

        $status = true;

        if ($ptTotal > 0 && $total1 < $ptTotal) {
            $status = false;
        } elseif (!$this->isAvailableForAddress($address)) {
            $status = false;
        } elseif (!PaytabsHelper::paymentAllowed($this->controller->_code, $currencyCode)) {
            $status = false;
        }


        /** Prepare the payment method */

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => "paytabs_{$this->controller->_code}",
                'title'      => $this->controller->language->get("{$this->controller->_code}_text_title"),
                'terms'      => '',
                'sort_order' => $this->controller->config->get(PaytabsAdapter::_key('sort_order', $this->controller->_code))
            );
        }

        return $method_data;
    }


    private function isAvailableForAddress($address)
    {
        $geoZoneId = (int) $this->controller->config->get(PaytabsAdapter::_key('geo_zone_id', $this->controller->_code));
        $countryId = (int) $address['country_id'];
        $zoneId = (int) $address['zone_id'];

        if (!$geoZoneId) {
            return true;
        }

        $table = DB_PREFIX . "zone_to_geo_zone";
        $query = $this->controller->db->query(
            "SELECT * FROM ${table} WHERE geo_zone_id = '{$geoZoneId}' AND country_id = '{$countryId}' AND (zone_id = '{$zoneId}' OR zone_id = '0')"
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
        'total' => [
            'key' => 'payment_paytabs_total',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_total',
            'required' => false,
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
    $log = new Log(PAYTABS_DEBUG_FILE);

    $_prefix = "[{$severity}] ";
    $log->write($_prefix . $message);
}
