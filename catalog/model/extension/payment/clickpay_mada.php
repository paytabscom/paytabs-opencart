<?php

class ModelExtensionPaymentClickpayMada extends Model
{
	public $_code = 'mada';

	private $clickpayController;


	private function init()
	{
        $this->load->library('clickpay_api');

        $this->clickpayController = new ClickpayCatalogModel($this);
	}


	public function getMethod($address, $total)
	{
		$this->init();

		return $this->clickpayController->getMethod($address, $total);
	}
}
