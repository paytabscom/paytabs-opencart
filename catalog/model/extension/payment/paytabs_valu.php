<?php

class ModelExtensionPaymentPaytabsValu extends Model
{
	public $_code = 'valu';

	private $paytabsController;


	private function init()
	{
		$this->load->helper('paytabs_api');

		$this->paytabsController = new PaytabsCatalogModel($this);
	}


	public function getMethod($address, $total)
	{
		$this->init();

		return $this->paytabsController->getMethod($address, $total);
	}
}
