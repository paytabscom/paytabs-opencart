<?php

namespace Opencart\Catalog\Model\Extension\Paytabs\Payment;

require_once DIR_EXTENSION . 'paytabs/system/library/paytabs_api.php';

use Opencart\System\Library\PaytabsCatalogModel;

class PaytabsPaypal extends PaytabsCatalogModel
{
	public $_code = 'paypal';
}
