<?php

namespace Opencart\Admin\Controller\Extension\Clickpay\Payment;

require_once DIR_EXTENSION . 'clickpay/system/library/clickpay_api.php';

use Opencart\System\Library\ClickpayAdminController;

class ClickpayMada extends ClickpayAdminController
{
	public $_code = 'mada';
}
