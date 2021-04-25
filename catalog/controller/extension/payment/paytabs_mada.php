<?php

class ControllerExtensionPaymentPaytabsMada extends Controller
{
	public $_code = 'mada';

	private $paytabsController;


	public function init()
	{
		$this->load->library('paytabs_api');

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
}
