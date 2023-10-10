<?php

class ModelExtensionPaymentPaytabsTabby extends Model
{
	public $_code = 'tabby';

	private $paytabsController;


	private function init()
	{
		$this->load->library('paytabs_api');

		$this->paytabsController = new PaytabsCatalogModel($this);
	}


	public function getMethod($address, $total)
	{
		$this->init();

		return $this->paytabsController->getMethod($address, $total);
	}
}
