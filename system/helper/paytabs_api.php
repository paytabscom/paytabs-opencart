<?php

define('PAYTABS_PAYPAGE_VERSION', '1.1.0');
define('PAYTABS_DEBUG_FILE', 'debug_paytabs.log');

require_once DIR_SYSTEM . '/helper/paytabs_core.php';

class PaytabsController
{
    private $controller;

    private $keys = PaytabsAdapter::KEYS;

    private $userToken_str = '';

    private $urlExtensions = 'marketplace/extension';
    // private $urlExtensions = 'extension/extension'; // OpenCart 2.3

    private $settingsKey = '';

    //

    function __construct($controller)
    {
        $this->controller = $controller;

        $this->controller->load->helper('paytabs_api');

        $this->controller->load->language("extension/payment/paytabs_strings");
        $this->controller->load->model('setting/setting');

        $token_str = 'user_token';
        // $token_str = 'token'; // OpenCart 2.3
        $this->controller->userToken = $this->controller->session->data[$token_str];

        $this->controller->document->setTitle($this->controller->language->get("{$this->controller->_code}_heading_title"));


        foreach ($this->keys as $key => &$value) {
            $value['configKey'] = PaytabsAdapter::KEY_PREFIX . str_replace('_{PAYMENTMETHOD}_', "_{$this->controller->_code}_", $value['configKey']);
        }

        $this->userToken_str = "user_token={$this->controller->userToken}";
        // $this->userToken_str = "token={$this->controller->userToken}"; // OpenCart 2.3

        $this->settingsKey = "payment_paytabs_{$this->controller->_code}";
        // $this->settingsKey = "paytabs_{$this->controller->_code}"; // OpenCart 2.3
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
            'merchant_email',
            'merchant_secret_key'
        ], $data);


        /** Fill values */

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
            PaytabsAdapter::_key('sort_order', $this->controller->_code) => 80
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


        $values = $this->prepareOrder();

        $paypage = $this->ptApi->create_pay_page($values);

        if ($paypage->success) {
            $data['paypage'] = true;
            $data['payment_url'] = $paypage->payment_url;
        } else {
            $data['paypage'] = false;
            $data['paypage_msg'] = $paypage->result;

            $_logResult = (json_encode($paypage));
            $_logData = json_encode($values);
            PaytabsHelper::log("callback failed, Data [{$_logData}], response [{$_logResult}]", 3);
        }

        return $this->controller->load->view("extension/payment/paytabs_view", $data);
    }


    public function callback()
    {
        $transactionId =
            isset($this->controller->request->post['payment_reference'])
            ? $this->controller->request->post['payment_reference']
            : false;
        if (!$transactionId) {
            return $this->callbackFailure('Transaction ID is missing');
        }

        $this->controller->load->model("extension/payment/paytabs_{$this->controller->_code}");
        $result = $this->ptApi->verify_payment($transactionId);

        if (in_array($result->response_code, [100, 481, 482])) { //check successed
            //TODO: Check here if the result is tempered
            $this->controller->load->model('checkout/order');

            PaytabsHelper::log("PayTabs {$this->controller->_code} checkout successed");

            $order_id = $result->reference_no;
            $successStatus = $this->controller->config->get(PaytabsAdapter::_key('order_status_id', $this->controller->_code));

            $this->controller->model_checkout_order->addOrderHistory($order_id, $successStatus, $result->result);
            $this->controller->response->redirect($this->controller->url->link('checkout/success'));
        } else {
            $_logVerify = (json_encode($result));
            PaytabsHelper::log("callback failed, response [{$_logVerify}]", 3);

            //Redirect to failed method
            // $this->controller->response->redirect($this->controller->url->link('checkout/failure'));

            $this->callbackFailure($result->result);
        }
    }


    private function callbackFailure($message)
    {
        $this->controller->load->language('checkout/failure');

        $this->controller->document->setTitle($this->controller->language->get('heading_title'));

        $data['breadcrumbs'] = [
            [
                'text' => $this->controller->language->get('text_home'),
                'href' => $this->controller->url->link('common/home')
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
                'href' => $this->controller->url->link('checkout/failure')
            ]
        ];

        $data['text_message'] = sprintf($this->controller->language->get('text_message'), $this->controller->url->link('information/contact'));

        $data['continue'] = $this->controller->url->link('common/home');

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

        $siteUrl = $this->controller->config->get('config_url');
        $return_url = $this->controller->url->link("extension/payment/paytabs_{$this->controller->_code}/callback");


        $cost = $this->controller->session->data['shipping_method']['cost'];
        $subtotal = $this->controller->cart->getSubTotal();
        $discount = $subtotal + $cost - $order_info['total'];
        $price1 = $subtotal + $cost;
        $amount = $this->getPrice($price1, $order_info);

        //

        $products = $this->controller->cart->getProducts();

        $items_arr = array_map(function ($p) use ($order_info) {
            return [
                'name' => $p['name'],
                'quantity' => $p['quantity'],
                'price' => round($this->getPrice($p['price'], $order_info), 2)
            ];
        }, $products);


        $cdetails = PaytabsHelper::getCountryDetails($order_info['payment_iso_code_2']);
        $phoneext = $cdetails['phone'];
        $telephone = $order_info['telephone'];

        $address_billing = trim($order_info['payment_address_1'] . ' ' . $order_info['payment_address_2']);
        $address_shipping = trim($order_info['shipping_address_1'] . ' ' . $order_info['shipping_address_2']);

        $zone_billing = PaytabsHelper::getNonEmpty($order_info['payment_zone'], $order_info['payment_city']);
        $zone_shipping = PaytabsHelper::getNonEmpty($order_info['shipping_zone'], $order_info['shipping_city'], $zone_billing);

        $lang_code = $this->controller->language->get('code');
        $lang = ($lang_code == 'ar') ? 'Arabic' : 'English';


        $holder = new PaytabsHolder();
        $holder
            ->set01PaymentCode($this->controller->_code)
            ->set02ReferenceNum($orderId)
            ->set03InvoiceInfo($order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'], $lang)
            ->set04Payment(
                $order_info['currency_code'],
                $amount,
                $this->getPrice($cost, $order_info),
                $this->getPrice($discount, $order_info)
            )
            ->set05Products($items_arr)
            ->set06CustomerInfo(
                $order_info['payment_firstname'],
                $order_info['payment_lastname'],
                $phoneext,
                $telephone,
                $order_info['email']
            )
            ->set07Billing(
                $address_billing,
                $zone_billing,
                $order_info['payment_city'],
                $order_info['payment_postcode'],
                $order_info['payment_iso_code_3']
            )
            ->set08Shipping(
                $order_info['shipping_firstname'],
                $order_info['shipping_lastname'],
                $address_shipping,
                $zone_shipping,
                $order_info['shipping_city'],
                $order_info['shipping_postcode'],
                $order_info['shipping_iso_code_3']
            )
            ->set09URLs(
                $siteUrl,
                $return_url
            )
            ->set10CMSVersion('OpenCart ' . VERSION)
            ->set11IPCustomer('');

        $post_arr = $holder->pt_build(true);

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
        'merchant_email' => [
            'key' => 'payment_paytabs_merchant_email',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_merchant_email',
            'required' => true,
        ],
        'merchant_secret_key' => [
            'key' => 'payment_paytabs_merchant_secret_key',
            'configKey' => 'paytabs_{PAYMENTMETHOD}_merchant_secret_key',
            'required' => true,
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
    const KEY_PREFIX = 'payment_';
    // const KEY_PREFIX = ''; // OpenCart 2.3

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
        $merchant_email = $this->config->get(PaytabsAdapter::_key('merchant_email', $this->paymentMethod));
        $secretKey = $this->config->get(PaytabsAdapter::_key('merchant_secret_key', $this->paymentMethod));

        $pt = PaytabsApi::getInstance($merchant_email, $secretKey);

        return $pt;
    }
}

function paytabs_error_log($message, $severity = 1)
{
    $log = new Log(PAYTABS_DEBUG_FILE);

    $_prefix = "[{$severity}] ";
    $log->write($_prefix . $message . PHP_EOL);
}
