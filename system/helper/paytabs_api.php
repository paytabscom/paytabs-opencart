<?php

define('PAYTABS_PAYPAGE_VERSION', '1.0.2');
class PaytabsController
{
    private $controller;

    private $keys = PaytabsHelper::KEYS;

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
            $value['configKey'] = PaytabsHelper::KEY_PREFIX . str_replace('_{PAYMENTMETHOD}_', "_{$this->controller->_code}_", $value['configKey']);
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
            PaytabsHelper::_key('sort_order', $this->controller->_code) => 80
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

        $this->ptApi = (new PaytabsHelper($this->controller->config, $this->controller->_code))->pt();
    }


    public function index(&$data)
    {
        $data['button_confirm'] = $this->controller->language->get('button_confirm');


        $values = $this->prepareOrder();

        $paypage = $this->ptApi->create_pay_page($values);

        if ($paypage && $paypage->response_code == 4012) {
            $data['paypage'] = true;
            $data['payment_url'] = $paypage->payment_url;
        } else {
            $data['paypage'] = false;
            $data['paypage_msg'] = PaytabsHelper::getNonEmpty($paypage->details, $paypage->result);
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

            $this->controller->log->write("PayTabs {$this->controller->_code} checkout successed");

            $order_id = $result->reference_no;
            $successStatus = $this->controller->config->get(PaytabsHelper::_key('order_status_id', $this->controller->_code));

            $this->controller->model_checkout_order->addOrderHistory($order_id, $successStatus, $result->result);
            $this->controller->response->redirect($this->controller->url->link('checkout/success'));
        } else {
            $this->controller->log->write("PayTabs {$this->controller->_code} checkout check failed");
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

        $products_arr = PaytabsHelper::prepare_products($items_arr);


        $cdetails = PaytabsHelper::getCountryDetails($order_info['payment_iso_code_2']);
        $phoneext = $cdetails['phone'];
        $telephone = $order_info['telephone'];

        // Remove country_code from phone_number if it is same as the user's Country code
        $telephone = preg_replace("/^[\+|00]+{$phoneext}/", '', $telephone);

        $postalCode = PaytabsHelper::getNonEmpty($order_info['payment_postcode'], 11111);
        $postalCodeShipping = PaytabsHelper::getNonEmpty($order_info['shipping_postcode'], $postalCode);

        $address_billing = trim($order_info['payment_address_1'] . ' ' . $order_info['payment_address_2']);
        $address_shipping = PaytabsHelper::getNonEmpty(trim($order_info['shipping_address_1'] . ' ' . $order_info['shipping_address_2']), $address_billing);

        $zone_billing = PaytabsHelper::getNonEmpty($order_info['payment_zone'], $order_info['payment_city']);
        $zone_shipping = PaytabsHelper::getNonEmpty($order_info['shipping_zone'], $order_info['shipping_city'], $zone_billing);

        $lang_code = $this->controller->language->get('code');
        $lang = ($lang_code == 'ar') ? 'Arabic' : 'English';

        $params = [
            'payment_type'         => $this->controller->_code,

            'title'                => $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],

            'currency'             => $order_info['currency_code'],
            'amount'               => $amount,
            'other_charges'        => $this->getPrice($cost, $order_info),
            'discount'             => $this->getPrice($discount, $order_info),

            'reference_no'         => $orderId,

            'cc_first_name'        => $order_info['payment_firstname'],
            'cc_last_name'         => $order_info['payment_lastname'],
            'cc_phone_number'      => $phoneext,
            'phone_number'         => $telephone,
            'email'                => $order_info['email'],

            'billing_address'      => $address_billing,
            'state'                => $zone_billing,
            'city'                 => $order_info['payment_city'],
            'postal_code'          => $postalCode,
            'country'              => $order_info['payment_iso_code_3'],

            'shipping_firstname'   => PaytabsHelper::getNonEmpty($order_info['shipping_firstname'], $order_info['payment_firstname']),
            'shipping_lastname'    => PaytabsHelper::getNonEmpty($order_info['shipping_lastname'], $order_info['payment_lastname']),
            'address_shipping'     => $address_shipping,
            'city_shipping'        => PaytabsHelper::getNonEmpty($order_info['shipping_city'], $order_info['payment_city']),
            'state_shipping'       => $zone_shipping,
            'postal_code_shipping' => $postalCodeShipping,
            'country_shipping'     => PaytabsHelper::getNonEmpty($order_info['shipping_iso_code_3'], $order_info['payment_iso_code_3']),

            'site_url'             => $siteUrl,
            'return_url'           => $return_url,

            'msg_lang'             => $lang,
            'cms_with_version'     => 'OpenCart ' . VERSION,

            'ip_customer'          => '',
        ];

        $post_arr = array_merge($params, $products_arr);

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
        $ptTotal = (float) $this->controller->config->get(PaytabsHelper::_key('total', $this->controller->_code));

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
                'sort_order' => $this->controller->config->get(PaytabsHelper::_key('sort_order', $this->controller->_code))
            );
        }

        return $method_data;
    }


    private function isAvailableForAddress($address)
    {
        $geoZoneId = (int) $this->controller->config->get(PaytabsHelper::_key('geo_zone_id', $this->controller->_code));
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


class PaytabsHelper
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
        $Email = $this->config->get(PaytabsHelper::_key('merchant_email', $this->paymentMethod));
        $secretKey = $this->config->get(PaytabsHelper::_key('merchant_secret_key', $this->paymentMethod));

        $pt = new PaytabsApi($Email, $secretKey);

        return $pt;
    }

    static function paymentType($key)
    {
        return PaytabsApi::PAYMENT_TYPES[$key]['name'];
    }

    static function paymentAllowed($code, $currencyCode)
    {
        $row = null;
        foreach (PaytabsApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                $row = $value;
                break;
            }
        }
        if (!$row) {
            return false;
        }
        $list = $row['currencies'];
        if ($list == null) {
            return true;
        }

        $currencyCode = strtoupper($currencyCode);

        return in_array($currencyCode, $list);
    }

    public static function getCountryDetails($iso_2)
    {
        $countryPhoneList = array(
            'AD' => array('name' => 'ANDORRA', 'code' => '376'),
            'AE' => array('name' => 'UNITED ARAB EMIRATES', 'code' => '971'),
            'AF' => array('name' => 'AFGHANISTAN', 'code' => '93'),
            'AG' => array('name' => 'ANTIGUA AND BARBUDA', 'code' => '1268'),
            'AI' => array('name' => 'ANGUILLA', 'code' => '1264'),
            'AL' => array('name' => 'ALBANIA', 'code' => '355'),
            'AM' => array('name' => 'ARMENIA', 'code' => '374'),
            'AN' => array('name' => 'NETHERLANDS ANTILLES', 'code' => '599'),
            'AO' => array('name' => 'ANGOLA', 'code' => '244'),
            'AQ' => array('name' => 'ANTARCTICA', 'code' => '672'),
            'AR' => array('name' => 'ARGENTINA', 'code' => '54'),
            'AS' => array('name' => 'AMERICAN SAMOA', 'code' => '1684'),
            'AT' => array('name' => 'AUSTRIA', 'code' => '43'),
            'AU' => array('name' => 'AUSTRALIA', 'code' => '61'),
            'AW' => array('name' => 'ARUBA', 'code' => '297'),
            'AZ' => array('name' => 'AZERBAIJAN', 'code' => '994'),
            'BA' => array('name' => 'BOSNIA AND HERZEGOVINA', 'code' => '387'),
            'BB' => array('name' => 'BARBADOS', 'code' => '1246'),
            'BD' => array('name' => 'BANGLADESH', 'code' => '880'),
            'BE' => array('name' => 'BELGIUM', 'code' => '32'),
            'BF' => array('name' => 'BURKINA FASO', 'code' => '226'),
            'BG' => array('name' => 'BULGARIA', 'code' => '359'),
            'BH' => array('name' => 'BAHRAIN', 'code' => '973'),
            'BI' => array('name' => 'BURUNDI', 'code' => '257'),
            'BJ' => array('name' => 'BENIN', 'code' => '229'),
            'BL' => array('name' => 'SAINT BARTHELEMY', 'code' => '590'),
            'BM' => array('name' => 'BERMUDA', 'code' => '1441'),
            'BN' => array('name' => 'BRUNEI DARUSSALAM', 'code' => '673'),
            'BO' => array('name' => 'BOLIVIA', 'code' => '591'),
            'BR' => array('name' => 'BRAZIL', 'code' => '55'),
            'BS' => array('name' => 'BAHAMAS', 'code' => '1242'),
            'BT' => array('name' => 'BHUTAN', 'code' => '975'),
            'BW' => array('name' => 'BOTSWANA', 'code' => '267'),
            'BY' => array('name' => 'BELARUS', 'code' => '375'),
            'BZ' => array('name' => 'BELIZE', 'code' => '501'),
            'CA' => array('name' => 'CANADA', 'code' => '1'),
            'CC' => array('name' => 'COCOS (KEELING) ISLANDS', 'code' => '61'),
            'CD' => array('name' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE', 'code' => '243'),
            'CF' => array('name' => 'CENTRAL AFRICAN REPUBLIC', 'code' => '236'),
            'CG' => array('name' => 'CONGO', 'code' => '242'),
            'CH' => array('name' => 'SWITZERLAND', 'code' => '41'),
            'CI' => array('name' => 'COTE D IVOIRE', 'code' => '225'),
            'CK' => array('name' => 'COOK ISLANDS', 'code' => '682'),
            'CL' => array('name' => 'CHILE', 'code' => '56'),
            'CM' => array('name' => 'CAMEROON', 'code' => '237'),
            'CN' => array('name' => 'CHINA', 'code' => '86'),
            'CO' => array('name' => 'COLOMBIA', 'code' => '57'),
            'CR' => array('name' => 'COSTA RICA', 'code' => '506'),
            'CU' => array('name' => 'CUBA', 'code' => '53'),
            'CV' => array('name' => 'CAPE VERDE', 'code' => '238'),
            'CX' => array('name' => 'CHRISTMAS ISLAND', 'code' => '61'),
            'CY' => array('name' => 'CYPRUS', 'code' => '357'),
            'CZ' => array('name' => 'CZECH REPUBLIC', 'code' => '420'),
            'DE' => array('name' => 'GERMANY', 'code' => '49'),
            'DJ' => array('name' => 'DJIBOUTI', 'code' => '253'),
            'DK' => array('name' => 'DENMARK', 'code' => '45'),
            'DM' => array('name' => 'DOMINICA', 'code' => '1767'),
            'DO' => array('name' => 'DOMINICAN REPUBLIC', 'code' => '1809'),
            'DZ' => array('name' => 'ALGERIA', 'code' => '213'),
            'EC' => array('name' => 'ECUADOR', 'code' => '593'),
            'EE' => array('name' => 'ESTONIA', 'code' => '372'),
            'EG' => array('name' => 'EGYPT', 'code' => '20'),
            'ER' => array('name' => 'ERITREA', 'code' => '291'),
            'ES' => array('name' => 'SPAIN', 'code' => '34'),
            'ET' => array('name' => 'ETHIOPIA', 'code' => '251'),
            'FI' => array('name' => 'FINLAND', 'code' => '358'),
            'FJ' => array('name' => 'FIJI', 'code' => '679'),
            'FK' => array('name' => 'FALKLAND ISLANDS (MALVINAS)', 'code' => '500'),
            'FM' => array('name' => 'MICRONESIA, FEDERATED STATES OF', 'code' => '691'),
            'FO' => array('name' => 'FAROE ISLANDS', 'code' => '298'),
            'FR' => array('name' => 'FRANCE', 'code' => '33'),
            'GA' => array('name' => 'GABON', 'code' => '241'),
            'GB' => array('name' => 'UNITED KINGDOM', 'code' => '44'),
            'GD' => array('name' => 'GRENADA', 'code' => '1473'),
            'GE' => array('name' => 'GEORGIA', 'code' => '995'),
            'GH' => array('name' => 'GHANA', 'code' => '233'),
            'GI' => array('name' => 'GIBRALTAR', 'code' => '350'),
            'GL' => array('name' => 'GREENLAND', 'code' => '299'),
            'GM' => array('name' => 'GAMBIA', 'code' => '220'),
            'GN' => array('name' => 'GUINEA', 'code' => '224'),
            'GQ' => array('name' => 'EQUATORIAL GUINEA', 'code' => '240'),
            'GR' => array('name' => 'GREECE', 'code' => '30'),
            'GT' => array('name' => 'GUATEMALA', 'code' => '502'),
            'GU' => array('name' => 'GUAM', 'code' => '1671'),
            'GW' => array('name' => 'GUINEA-BISSAU', 'code' => '245'),
            'GY' => array('name' => 'GUYANA', 'code' => '592'),
            'HK' => array('name' => 'HONG KONG', 'code' => '852'),
            'HN' => array('name' => 'HONDURAS', 'code' => '504'),
            'HR' => array('name' => 'CROATIA', 'code' => '385'),
            'HT' => array('name' => 'HAITI', 'code' => '509'),
            'HU' => array('name' => 'HUNGARY', 'code' => '36'),
            'ID' => array('name' => 'INDONESIA', 'code' => '62'),
            'IE' => array('name' => 'IRELAND', 'code' => '353'),
            'IL' => array('name' => 'ISRAEL', 'code' => '972'),
            'IM' => array('name' => 'ISLE OF MAN', 'code' => '44'),
            'IN' => array('name' => 'INDIA', 'code' => '91'),
            'IQ' => array('name' => 'IRAQ', 'code' => '964'),
            'IR' => array('name' => 'IRAN, ISLAMIC REPUBLIC OF', 'code' => '98'),
            'IS' => array('name' => 'ICELAND', 'code' => '354'),
            'IT' => array('name' => 'ITALY', 'code' => '39'),
            'JM' => array('name' => 'JAMAICA', 'code' => '1876'),
            'JO' => array('name' => 'JORDAN', 'code' => '962'),
            'JP' => array('name' => 'JAPAN', 'code' => '81'),
            'KE' => array('name' => 'KENYA', 'code' => '254'),
            'KG' => array('name' => 'KYRGYZSTAN', 'code' => '996'),
            'KH' => array('name' => 'CAMBODIA', 'code' => '855'),
            'KI' => array('name' => 'KIRIBATI', 'code' => '686'),
            'KM' => array('name' => 'COMOROS', 'code' => '269'),
            'KN' => array('name' => 'SAINT KITTS AND NEVIS', 'code' => '1869'),
            'KP' => array('name' => 'KOREA DEMOCRATIC PEOPLES REPUBLIC OF', 'code' => '850'),
            'KR' => array('name' => 'KOREA REPUBLIC OF', 'code' => '82'),
            'KW' => array('name' => 'KUWAIT', 'code' => '965'),
            'KY' => array('name' => 'CAYMAN ISLANDS', 'code' => '1345'),
            'KZ' => array('name' => 'KAZAKSTAN', 'code' => '7'),
            'LA' => array('name' => 'LAO PEOPLES DEMOCRATIC REPUBLIC', 'code' => '856'),
            'LB' => array('name' => 'LEBANON', 'code' => '961'),
            'LC' => array('name' => 'SAINT LUCIA', 'code' => '1758'),
            'LI' => array('name' => 'LIECHTENSTEIN', 'code' => '423'),
            'LK' => array('name' => 'SRI LANKA', 'code' => '94'),
            'LR' => array('name' => 'LIBERIA', 'code' => '231'),
            'LS' => array('name' => 'LESOTHO', 'code' => '266'),
            'LT' => array('name' => 'LITHUANIA', 'code' => '370'),
            'LU' => array('name' => 'LUXEMBOURG', 'code' => '352'),
            'LV' => array('name' => 'LATVIA', 'code' => '371'),
            'LY' => array('name' => 'LIBYAN ARAB JAMAHIRIYA', 'code' => '218'),
            'MA' => array('name' => 'MOROCCO', 'code' => '212'),
            'MC' => array('name' => 'MONACO', 'code' => '377'),
            'MD' => array('name' => 'MOLDOVA, REPUBLIC OF', 'code' => '373'),
            'ME' => array('name' => 'MONTENEGRO', 'code' => '382'),
            'MF' => array('name' => 'SAINT MARTIN', 'code' => '1599'),
            'MG' => array('name' => 'MADAGASCAR', 'code' => '261'),
            'MH' => array('name' => 'MARSHALL ISLANDS', 'code' => '692'),
            'MK' => array('name' => 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF', 'code' => '389'),
            'ML' => array('name' => 'MALI', 'code' => '223'),
            'MM' => array('name' => 'MYANMAR', 'code' => '95'),
            'MN' => array('name' => 'MONGOLIA', 'code' => '976'),
            'MO' => array('name' => 'MACAU', 'code' => '853'),
            'MP' => array('name' => 'NORTHERN MARIANA ISLANDS', 'code' => '1670'),
            'MR' => array('name' => 'MAURITANIA', 'code' => '222'),
            'MS' => array('name' => 'MONTSERRAT', 'code' => '1664'),
            'MT' => array('name' => 'MALTA', 'code' => '356'),
            'MU' => array('name' => 'MAURITIUS', 'code' => '230'),
            'MV' => array('name' => 'MALDIVES', 'code' => '960'),
            'MW' => array('name' => 'MALAWI', 'code' => '265'),
            'MX' => array('name' => 'MEXICO', 'code' => '52'),
            'MY' => array('name' => 'MALAYSIA', 'code' => '60'),
            'MZ' => array('name' => 'MOZAMBIQUE', 'code' => '258'),
            'NA' => array('name' => 'NAMIBIA', 'code' => '264'),
            'NC' => array('name' => 'NEW CALEDONIA', 'code' => '687'),
            'NE' => array('name' => 'NIGER', 'code' => '227'),
            'NG' => array('name' => 'NIGERIA', 'code' => '234'),
            'NI' => array('name' => 'NICARAGUA', 'code' => '505'),
            'NL' => array('name' => 'NETHERLANDS', 'code' => '31'),
            'NO' => array('name' => 'NORWAY', 'code' => '47'),
            'NP' => array('name' => 'NEPAL', 'code' => '977'),
            'NR' => array('name' => 'NAURU', 'code' => '674'),
            'NU' => array('name' => 'NIUE', 'code' => '683'),
            'NZ' => array('name' => 'NEW ZEALAND', 'code' => '64'),
            'OM' => array('name' => 'OMAN', 'code' => '968'),
            'PA' => array('name' => 'PANAMA', 'code' => '507'),
            'PE' => array('name' => 'PERU', 'code' => '51'),
            'PF' => array('name' => 'FRENCH POLYNESIA', 'code' => '689'),
            'PG' => array('name' => 'PAPUA NEW GUINEA', 'code' => '675'),
            'PH' => array('name' => 'PHILIPPINES', 'code' => '63'),
            'PK' => array('name' => 'PAKISTAN', 'code' => '92'),
            'PL' => array('name' => 'POLAND', 'code' => '48'),
            'PM' => array('name' => 'SAINT PIERRE AND MIQUELON', 'code' => '508'),
            'PN' => array('name' => 'PITCAIRN', 'code' => '870'),
            'PR' => array('name' => 'PUERTO RICO', 'code' => '1'),
            'PS' => array('name' => 'PALESTINE', 'code' => '970'),
            'PT' => array('name' => 'PORTUGAL', 'code' => '351'),
            'PW' => array('name' => 'PALAU', 'code' => '680'),
            'PY' => array('name' => 'PARAGUAY', 'code' => '595'),
            'QA' => array('name' => 'QATAR', 'code' => '974'),
            'RO' => array('name' => 'ROMANIA', 'code' => '40'),
            'RS' => array('name' => 'SERBIA', 'code' => '381'),
            'RU' => array('name' => 'RUSSIAN FEDERATION', 'code' => '7'),
            'RW' => array('name' => 'RWANDA', 'code' => '250'),
            'SA' => array('name' => 'SAUDI ARABIA', 'code' => '966'),
            'SB' => array('name' => 'SOLOMON ISLANDS', 'code' => '677'),
            'SC' => array('name' => 'SEYCHELLES', 'code' => '248'),
            'SD' => array('name' => 'SUDAN', 'code' => '249'),
            'SE' => array('name' => 'SWEDEN', 'code' => '46'),
            'SG' => array('name' => 'SINGAPORE', 'code' => '65'),
            'SH' => array('name' => 'SAINT HELENA', 'code' => '290'),
            'SI' => array('name' => 'SLOVENIA', 'code' => '386'),
            'SK' => array('name' => 'SLOVAKIA', 'code' => '421'),
            'SL' => array('name' => 'SIERRA LEONE', 'code' => '232'),
            'SM' => array('name' => 'SAN MARINO', 'code' => '378'),
            'SN' => array('name' => 'SENEGAL', 'code' => '221'),
            'SO' => array('name' => 'SOMALIA', 'code' => '252'),
            'SR' => array('name' => 'SURINAME', 'code' => '597'),
            'ST' => array('name' => 'SAO TOME AND PRINCIPE', 'code' => '239'),
            'SV' => array('name' => 'EL SALVADOR', 'code' => '503'),
            'SY' => array('name' => 'SYRIAN ARAB REPUBLIC', 'code' => '963'),
            'SZ' => array('name' => 'SWAZILAND', 'code' => '268'),
            'TC' => array('name' => 'TURKS AND CAICOS ISLANDS', 'code' => '1649'),
            'TD' => array('name' => 'CHAD', 'code' => '235'),
            'TG' => array('name' => 'TOGO', 'code' => '228'),
            'TH' => array('name' => 'THAILAND', 'code' => '66'),
            'TJ' => array('name' => 'TAJIKISTAN', 'code' => '992'),
            'TK' => array('name' => 'TOKELAU', 'code' => '690'),
            'TL' => array('name' => 'TIMOR-LESTE', 'code' => '670'),
            'TM' => array('name' => 'TURKMENISTAN', 'code' => '993'),
            'TN' => array('name' => 'TUNISIA', 'code' => '216'),
            'TO' => array('name' => 'TONGA', 'code' => '676'),
            'TR' => array('name' => 'TURKEY', 'code' => '90'),
            'TT' => array('name' => 'TRINIDAD AND TOBAGO', 'code' => '1868'),
            'TV' => array('name' => 'TUVALU', 'code' => '688'),
            'TW' => array('name' => 'TAIWAN, PROVINCE OF CHINA', 'code' => '886'),
            'TZ' => array('name' => 'TANZANIA, UNITED REPUBLIC OF', 'code' => '255'),
            'UA' => array('name' => 'UKRAINE', 'code' => '380'),
            'UG' => array('name' => 'UGANDA', 'code' => '256'),
            'US' => array('name' => 'UNITED STATES', 'code' => '1'),
            'UY' => array('name' => 'URUGUAY', 'code' => '598'),
            'UZ' => array('name' => 'UZBEKISTAN', 'code' => '998'),
            'VA' => array('name' => 'HOLY SEE (VATICAN CITY STATE)', 'code' => '39'),
            'VC' => array('name' => 'SAINT VINCENT AND THE GRENADINES', 'code' => '1784'),
            'VE' => array('name' => 'VENEZUELA', 'code' => '58'),
            'VG' => array('name' => 'VIRGIN ISLANDS, BRITISH', 'code' => '1284'),
            'VI' => array('name' => 'VIRGIN ISLANDS, U.S.', 'code' => '1340'),
            'VN' => array('name' => 'VIET NAM', 'code' => '84'),
            'VU' => array('name' => 'VANUATU', 'code' => '678'),
            'WF' => array('name' => 'WALLIS AND FUTUNA', 'code' => '681'),
            'WS' => array('name' => 'SAMOA', 'code' => '685'),
            'XK' => array('name' => 'KOSOVO', 'code' => '381'),
            'YE' => array('name' => 'YEMEN', 'code' => '967'),
            'YT' => array('name' => 'MAYOTTE', 'code' => '262'),
            'ZA' => array('name' => 'SOUTH AFRICA', 'code' => '27'),
            'ZM' => array('name' => 'ZAMBIA', 'code' => '260'),
            'ZW' => array('name' => 'ZIMBABWE', 'code' => '263')
        );

        $arr = array();

        if (isset($countryPhoneList[$iso_2])) {
            $phcountry = $countryPhoneList[$iso_2];
            $arr['phone'] = $phcountry['code'];
            $arr['country'] = $phcountry['name'];
        }

        return $arr;
    }

    public static function countryGetiso3($iso_2)
    {
        $iso = array(
            'AND' => 'AD',
            'ARE' => 'AE',
            'AFG' => 'AF',
            'ATG' => 'AG',
            'AIA' => 'AI',
            'ALB' => 'AL',
            'ARM' => 'AM',
            'AGO' => 'AO',
            'ATA' => 'AQ',
            'ARG' => 'AR',
            'ASM' => 'AS',
            'AUT' => 'AT',
            'AUS' => 'AU',
            'ABW' => 'AW',
            'ALA' => 'AX',
            'AZE' => 'AZ',
            'BIH' => 'BA',
            'BRB' => 'BB',
            'BGD' => 'BD',
            'BEL' => 'BE',
            'BFA' => 'BF',
            'BGR' => 'BG',
            'BHR' => 'BH',
            'BDI' => 'BI',
            'BEN' => 'BJ',
            'BLM' => 'BL',
            'BMU' => 'BM',
            'BRN' => 'BN',
            'BOL' => 'BO',
            'BES' => 'BQ',
            'BRA' => 'BR',
            'BHS' => 'BS',
            'BTN' => 'BT',
            'BVT' => 'BV',
            'BWA' => 'BW',
            'BLR' => 'BY',
            'BLZ' => 'BZ',
            'CAN' => 'CA',
            'CCK' => 'CC',
            'COD' => 'CD',
            'CAF' => 'CF',
            'COG' => 'CG',
            'CHE' => 'CH',
            'CIV' => 'CI',
            'COK' => 'CK',
            'CHL' => 'CL',
            'CMR' => 'CM',
            'CHN' => 'CN',
            'COL' => 'CO',
            'CRI' => 'CR',
            'CUB' => 'CU',
            'CPV' => 'CV',
            'CUW' => 'CW',
            'CXR' => 'CX',
            'CYP' => 'CY',
            'CZE' => 'CZ',
            'DEU' => 'DE',
            'DJI' => 'DJ',
            'DNK' => 'DK',
            'DMA' => 'DM',
            'DOM' => 'DO',
            'DZA' => 'DZ',
            'ECU' => 'EC',
            'EST' => 'EE',
            'EGY' => 'EG',
            'ESH' => 'EH',
            'ERI' => 'ER',
            'ESP' => 'ES',
            'ETH' => 'ET',
            'FIN' => 'FI',
            'FJI' => 'FJ',
            'FLK' => 'FK',
            'FSM' => 'FM',
            'FRO' => 'FO',
            'FRA' => 'FR',
            'GAB' => 'GA',
            'GBR' => 'GB',
            'GRD' => 'GD',
            'GEO' => 'GE',
            'GUF' => 'GF',
            'GGY' => 'GG',
            'GHA' => 'GH',
            'GIB' => 'GI',
            'GRL' => 'GL',
            'GMB' => 'GM',
            'GIN' => 'GN',
            'GLP' => 'GP',
            'GNQ' => 'GQ',
            'GRC' => 'GR',
            'SGS' => 'GS',
            'GTM' => 'GT',
            'GUM' => 'GU',
            'GNB' => 'GW',
            'GUY' => 'GY',
            'HKG' => 'HK',
            'HMD' => 'HM',
            'HND' => 'HN',
            'HRV' => 'HR',
            'HTI' => 'HT',
            'HUN' => 'HU',
            'IDN' => 'ID',
            'IRL' => 'IE',
            'ISR' => 'IL',
            'IMN' => 'IM',
            'IND' => 'IN',
            'IOT' => 'IO',
            'IRQ' => 'IQ',
            'IRN' => 'IR',
            'ISL' => 'IS',
            'ITA' => 'IT',
            'JEY' => 'JE',
            'JAM' => 'JM',
            'JOR' => 'JO',
            'JPN' => 'JP',
            'KEN' => 'KE',
            'KGZ' => 'KG',
            'KHM' => 'KH',
            'KIR' => 'KI',
            'COM' => 'KM',
            'KNA' => 'KN',
            'PRK' => 'KP',
            'KOR' => 'KR',
            'XKX' => 'XK',
            'KWT' => 'KW',
            'CYM' => 'KY',
            'KAZ' => 'KZ',
            'LAO' => 'LA',
            'LBN' => 'LB',
            'LCA' => 'LC',
            'LIE' => 'LI',
            'LKA' => 'LK',
            'LBR' => 'LR',
            'LSO' => 'LS',
            'LTU' => 'LT',
            'LUX' => 'LU',
            'LVA' => 'LV',
            'LBY' => 'LY',
            'MAR' => 'MA',
            'MCO' => 'MC',
            'MDA' => 'MD',
            'MNE' => 'ME',
            'MAF' => 'MF',
            'MDG' => 'MG',
            'MHL' => 'MH',
            'MKD' => 'MK',
            'MLI' => 'ML',
            'MMR' => 'MM',
            'MNG' => 'MN',
            'MAC' => 'MO',
            'MNP' => 'MP',
            'MTQ' => 'MQ',
            'MRT' => 'MR',
            'MSR' => 'MS',
            'MLT' => 'MT',
            'MUS' => 'MU',
            'MDV' => 'MV',
            'MWI' => 'MW',
            'MEX' => 'MX',
            'MYS' => 'MY',
            'MOZ' => 'MZ',
            'NAM' => 'NA',
            'NCL' => 'NC',
            'NER' => 'NE',
            'NFK' => 'NF',
            'NGA' => 'NG',
            'NIC' => 'NI',
            'NLD' => 'NL',
            'NOR' => 'NO',
            'NPL' => 'NP',
            'NRU' => 'NR',
            'NIU' => 'NU',
            'NZL' => 'NZ',
            'OMN' => 'OM',
            'PAN' => 'PA',
            'PER' => 'PE',
            'PYF' => 'PF',
            'PNG' => 'PG',
            'PHL' => 'PH',
            'PAK' => 'PK',
            'POL' => 'PL',
            'SPM' => 'PM',
            'PCN' => 'PN',
            'PRI' => 'PR',
            'PSE' => 'PS',
            'PRT' => 'PT',
            'PLW' => 'PW',
            'PRY' => 'PY',
            'QAT' => 'QA',
            'REU' => 'RE',
            'ROU' => 'RO',
            'SRB' => 'RS',
            'RUS' => 'RU',
            'RWA' => 'RW',
            'SAU' => 'SA',
            'SLB' => 'SB',
            'SYC' => 'SC',
            'SDN' => 'SD',
            'SSD' => 'SS',
            'SWE' => 'SE',
            'SGP' => 'SG',
            'SHN' => 'SH',
            'SVN' => 'SI',
            'SJM' => 'SJ',
            'SVK' => 'SK',
            'SLE' => 'SL',
            'SMR' => 'SM',
            'SEN' => 'SN',
            'SOM' => 'SO',
            'SUR' => 'SR',
            'STP' => 'ST',
            'SLV' => 'SV',
            'SXM' => 'SX',
            'SYR' => 'SY',
            'SWZ' => 'SZ',
            'TCA' => 'TC',
            'TCD' => 'TD',
            'ATF' => 'TF',
            'TGO' => 'TG',
            'THA' => 'TH',
            'TJK' => 'TJ',
            'TKL' => 'TK',
            'TLS' => 'TL',
            'TKM' => 'TM',
            'TUN' => 'TN',
            'TON' => 'TO',
            'TUR' => 'TR',
            'TTO' => 'TT',
            'TUV' => 'TV',
            'TWN' => 'TW',
            'TZA' => 'TZ',
            'UKR' => 'UA',
            'UGA' => 'UG',
            'UMI' => 'UM',
            'USA' => 'US',
            'URY' => 'UY',
            'UZB' => 'UZ',
            'VAT' => 'VA',
            'VCT' => 'VC',
            'VEN' => 'VE',
            'VGB' => 'VG',
            'VIR' => 'VI',
            'VNM' => 'VN',
            'VUT' => 'VU',
            'WLF' => 'WF',
            'WSM' => 'WS',
            'YEM' => 'YE',
            'MYT' => 'YT',
            'ZAF' => 'ZA',
            'ZMB' => 'ZM',
            'ZWE' => 'ZW',
            'SCG' => 'CS',
            'ANT' => 'AN',
        );

        $iso_3 = "";

        foreach ($iso as $key => $val) {
            if ($val == $iso_2) {
                $iso_3 = $key;
                break;
            }
        }

        return $iso_3;
    }


    public static function getNonEmpty(...$vars)
    {
        foreach ($vars as $var) {
            if (!empty($var)) return $var;
        }
        return false;
    }

    /**
     * @param $items: array of the products, each product has the format ['name' => xx, 'quantity' => x, 'price' =>x]
     * @return array to pass to paypage API in the format ['products_per_title' => 'xx || xx ', 'quantity' => 'xx || xx', 'unit_price' => 'xx || xx']
     */
    public static function prepare_products(array $items)
    {
        $glue = ' || ';

        $products_str = implode($glue, array_map(function ($p) {
            $name = str_replace('||', '/', $p['name']);
            return $name;
        }, $items));

        $quantity = implode($glue, array_map(function ($p) {
            return $p['quantity'];
        }, $items));

        $unit_price = implode($glue, array_map(function ($p) {
            return $p['price'];
        }, $items));


        return [
            'products_per_title' => $products_str,
            'quantity'           => $quantity,
            'unit_price'         => $unit_price,
        ];
    }
}


class PaytabsApi
{
    const PAYMENT_TYPES = [
        '1' => ['name' => 'stcpay', 'currencies' => ['SAR']],
        '2' => ['name' => 'stcpayqr', 'currencies' => ['SAR']],
        '3' => ['name' => 'applepay', 'currencies' => ['AED', 'SAR']],
        '4' => ['name' => 'omannet', 'currencies' => ['OMR']],
        '5' => ['name' => 'mada', 'currencies' => ['SAR']],
        '6' => ['name' => 'creditcard', 'currencies' => null],
        '7' => ['name' => 'sadad', 'currencies' => ['SAR']],
        '8' => ['name' => 'atfawry', 'currencies' => ['EGP']],
        '9' => ['name' => 'knpay', 'currencies' => ['KWD']],
        '10' => ['name' => 'amex', 'currencies' => ['AED', 'SAR']]
    ];
    const URL_AUTHENTICATION = "https://www.paytabs.com/apiv2/validate_secret_key";
    const PAYPAGE_URL = "https://www.paytabs.com/apiv2/create_pay_page";
    const VERIFY_URL = "https://www.paytabs.com/apiv2/verify_payment";

    private $merchant_email;
    private $secret_key;

    function __construct($merchant_email, $secret_key)
    {
        $this->merchant_email = $merchant_email;
        $this->secret_key = $secret_key;
    }

    function authentication()
    {
        $obj = json_decode($this->runPost(self::URL_AUTHENTICATION, array("merchant_email" => $this->merchant_email, "secret_key" =>  $this->secret_key)), TRUE);

        if ($obj->response_code == "4000") {
            return TRUE;
        }
        return FALSE;
    }

    function create_pay_page($values)
    {
        $values['merchant_email'] = $this->merchant_email;
        $values['secret_key'] = $this->secret_key;

        $serverIP = getHostByName(getHostName());
        $values['ip_merchant'] = PaytabsHelper::getNonEmpty($serverIP, $_SERVER['SERVER_ADDR'], 'NA');

        $values['ip_customer'] = PaytabsHelper::getNonEmpty($values['ip_customer'], $_SERVER['REMOTE_ADDR'], 'NA');

        return json_decode($this->runPost(self::PAYPAGE_URL, $values));
    }

    function verify_payment($payment_reference)
    {
        $values['merchant_email'] = $this->merchant_email;
        $values['secret_key'] = $this->secret_key;
        $values['payment_reference'] = $payment_reference;
        return json_decode($this->runPost(self::VERIFY_URL, $values));
    }

    function runPost($url, $fields)
    {
        $fields_string = "";
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . urlencode($value) . '&';
        }
        $fields_string = rtrim($fields_string, '&');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
