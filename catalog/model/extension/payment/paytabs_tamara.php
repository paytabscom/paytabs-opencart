<?php

class ModelExtensionPaymentPaytabsTamara extends Model
{
	public $_code = 'tamara';

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

	public function _getTitle()
	{
		return '&nbsp;<p style="color: grey">Monthly payments, No late fees. <a href="https://tamara.co" target="_blank" className="see-more-link">More options</a></p>';
	}
}
