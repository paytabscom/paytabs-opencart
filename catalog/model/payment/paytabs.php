<?php

namespace Opencart\Catalog\Model\Extension\Paytabs\Payment;

abstract class Paytabs extends \Opencart\System\Engine\Model
{
	public $_code = '';

	protected $paytabsController;


	private function init()
	{
		require_once DIR_EXTENSION . 'paytabs/system/library/paytabs_api.php';

		$this->paytabsController = new PaytabsCatalogModel($this);
	}


	public function getMethod($address, $total)
	{
		$this->init();

		return $this->paytabsController->getMethod($address, $total);
	}
}
