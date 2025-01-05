<?php

class ModelExtensionPaymentPaytabsTru extends Model
{
	public $_code = 'tru';
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