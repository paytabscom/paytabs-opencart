<?php

namespace Opencart\Catalog\Controller\Extension\Paytabs\Payment;

class  PaytabsStcpay extends \Opencart\System\Engine\Controller
{
	public $_code = 'stcpay';

	private $paytabsController;


	public function init()
	{
		require_once DIR_EXTENSION . 'paytabs/system/library/paytabs_api.php';

		$this->paytabsController = new PaytabsCatalogController($this);
	}

	public function index()
	{
		$this->init();

		return $this->paytabsController->index($data);
	}


	public function confirm()
	{
		$this->init();
		return $this->paytabsController->confirm($data);
	}


	public function callback()
	{
		$this->init();

		$this->paytabsController->callback();
	}

	public function redirectAfterPayment()
	{
		$this->init();

		$this->paytabsController->redirectAfterPayment();
	}
}
