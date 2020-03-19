<?php

class ControllerExtensionPaymentPaytabsApplepay extends Controller
{
	public $_code = 'applepay';

	private $paytabsController;


	public function init()
	{
		$this->load->helper('paytabs_api');

		$this->paytabsController = new PaytabsCatalogController($this);
	}

	public function index()
	{
		$this->init();

		return $this->paytabsController->index($data);
	}


	public function callback()
	{
		$this->init();

		$this->paytabsController->callback();
	}
}
