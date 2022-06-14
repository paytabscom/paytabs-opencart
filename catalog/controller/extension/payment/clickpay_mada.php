<?php

class ControllerExtensionPaymentClickpayMada extends Controller
{
	public $_code = 'mada';

	private $clickpayController;


	public function init()
	{
		$this->load->library('clickpay_api');

		$this->clickpayController = new ClickpayCatalogController($this);
	}

	public function index()
	{
		$this->init();

		return $this->clickpayController->index($data);
	}


	public function confirm()
	{
		$this->init();
		return $this->clickpayController->confirm($data);
	}


	public function callback()
	{
		$this->init();

		$this->clickpayController->callback();
	}

	public function redirectAfterPayment()
	{
		$this->init();

		$this->clickpayController->redirectAfterPayment();
	}
}
