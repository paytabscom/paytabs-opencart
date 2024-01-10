<?php

namespace Opencart\Admin\Controller\Extension\Clickpay\Payment;

require_once DIR_EXTENSION . 'clickpay/system/library/clickpay_api.php';

use Opencart\System\Library\ClickpayAdminController;

class ClickpayStcpay extends ClickpayAdminController
{
	public $_code = 'stcpay';
}
