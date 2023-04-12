<?php

namespace Opencart\Admin\Controller\Extension\Paytabs\Payment;

require_once DIR_EXTENSION . 'paytabs/system/library/paytabs_api.php';

use Opencart\System\Library\PaytabsAdminController;

class PaytabsPaypal extends PaytabsAdminController
{
	public $_code = 'paypal';
}
