<?= $header ?>
<?= $column_left ?>
<div id="content">
	<div class="page-header">
		<div class="container-fluid">
			<div class="pull-right">
				<button type="submit" form="form-payment" data-toggle="tooltip" title="<?= $button_save ?>" class="btn btn-primary">
					<i class="fa fa-save"></i>
				</button>
				<a href="<?= $cancel ?>" data-toggle="tooltip" title="<?= $button_cancel ?>" class="btn btn-default">
					<i class="fa fa-reply"></i>
				</a>
			</div>
			<h1><?= $title ?></h1>
			<ul class="breadcrumb">
				<?php foreach ($breadcrumbs as $breadcrumb) { ?>
					<li>
						<a href="<?= $breadcrumb['href'] ?>"><?= $breadcrumb['text'] ?></a>
					</li>
				<?php } ?>
			</ul>
		</div>
	</div>

	<div class="container-fluid">
		<?php if ($error_warning) { ?>
			<div class="alert alert-danger alert-dismissible">
				<i class="fa fa-exclamation-circle"></i>
				<?= $error_warning ?>
				<button type="button" class="close" data-dismiss="alert">&times;</button>
			</div>
		<?php } ?>

		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="fa fa-pencil"></i>
					<?= $text_edit ?>
				</h3>
			</div>
			<div class="panel-body">
				<form action="<?= $action ?>" method="post" enctype="multipart/form-data" id="form-payment" class="form-horizontal">
					<div class="tab-content">

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-status"><?= $entry_status ?></label>
							<div class="col-sm-10">
								<select name="payment_clickpay_status" id="input-status" class="form-control">
									<?php if ($payment_clickpay_status) { ?>
										<option value="1" selected="selected"><?= $text_enabled ?></option>
										<option value="0"><?= $text_disabled ?></option>
									<?php } else { ?>
										<option value="1"><?= $text_enabled ?></option>
										<option value="0" selected="selected"><?= $text_disabled ?></option>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-endpoint"><?= $entry_endpoint ?></label>
							<div class="col-sm-10">
								<select name="payment_clickpay_endpoint" id="input-endpoint" class="form-control">
									<?php foreach ($endpoints as $key => $title) { ?>
										<?php if ($key == $payment_clickpay_endpoint) { ?>
											<option value="<?= $key ?>" selected="selected"><?= $title ?></option>
										<?php } else { ?>
											<option value="<?= $key ?>"><?= $title ?></option>
										<?php } ?>
									<?php } ?>
								</select>
								<?php if ($error_endpoint) { ?>
									<div class="text-danger"><?= $error_endpoint ?></div>
								<?php } ?>
							</div>
						</div>

						<div class="form-group required">
							<label class="col-sm-2 control-label" for="entry-profile-id"><?= $entry_profile_id ?></label>
							<div class="col-sm-10">
								<input type="text" name="payment_clickpay_profile_id" value="<?= $payment_clickpay_profile_id ?>" placeholder="<?= $entry_profile_id ?>" id="entry-merchant-id" class="form-control" />
								<?php if ($error_profile_id) { ?>
									<div class="text-danger"><?= $error_profile_id ?></div>
								<?php } ?>
							</div>
						</div>

						<div class="form-group required">
							<label class="col-sm-2 control-label" for="entry-server-key"><?= $entry_server_key ?></label>
							<div class="col-sm-10">
								<input type="text" name="payment_clickpay_server_key" value="<?= $payment_clickpay_server_key ?>" placeholder="<?= $entry_server_key ?>" id="entry-server-key" class="form-control" />
								<?php if ($error_server_key) { ?>
									<div class="text-danger"><?= $error_server_key ?></div>
								<?php } ?>
							</div>
						</div>

						<?php if ($method == 'valu') { ?>
							<div class="form-group required">
								<label class="col-sm-2 control-label" for="entry-valu_product_id"><?= $entry_valu_product_id ?></label>
								<div class="col-sm-10">
									<input type="text" name="payment_clickpay_valu_product_id" value="<?= $payment_clickpay_valu_product_id ?>" placeholder="<?= $entry_valu_product_id ?>" id="entry-valu_product_id" class="form-control" />
									<?php if ($error_valu_product_id) { ?>
										<div class="text-danger"><?= $error_valu_product_id ?></div>
									<?php } ?>
								</div>
							</div>
						<?php } ?>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-total">
								<span data-toggle="tooltip" title="<?= $help_total ?>"><?= $entry_total ?></span>
							</label>
							<div class="col-sm-10">
								<input type="text" name="payment_clickpay_total" value="<?= $payment_clickpay_total ?>" placeholder="<?= $entry_total ?>" id="input-total" class="form-control" />
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-order-status"><?= $entry_order_status ?></label>
							<div class="col-sm-10">
								<select name="payment_clickpay_order_status_id" id="input-order-status" class="form-control">
									<?php foreach ($order_statuses as $order_status) { ?>
										<?php if ($order_status['order_status_id'] == $payment_clickpay_order_status_id) { ?>
											<option value="<?= $order_status['order_status_id'] ?>" selected="selected"><?= $order_status['name'] ?></option>
										<?php } else { ?>
											<option value="<?= $order_status['order_status_id'] ?>"><?= $order_status['name'] ?></option>
										<?php } ?>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-order-failed-status"><?= $entry_order_failed_status ?></label>
							<div class="col-sm-10">
								<select name="payment_clickpay_order_failed_status_id" id="input-order-failed-status" class="form-control">
									<option value="0">No Action</option>
									<?php foreach ($order_statuses as $order_status) { ?>
									<?php if ($order_status['order_status_id'] == $payment_clickpay_order_failed_status_id) { ?>
									<option value="<?= $order_status['order_status_id'] ?>" selected="selected"><?= $order_status['name'] ?></option>
									<?php } else { ?>
									<option value="<?= $order_status['order_status_id'] ?>"><?= $order_status['name'] ?></option>
									<?php } ?>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-order-fraud-status"><?= $entry_order_fraud_status ?></label>
							<div class="col-sm-10">
								<select name="payment_clickpay_order_fraud_status_id" id="input-order-fraud-status" class="form-control">
									<?php foreach ($order_statuses as $order_status) { ?>
									<?php if ($order_status['order_status_id'] == $payment_clickpay_order_fraud_status_id) { ?>
									<option value="<?= $order_status['order_status_id'] ?>" selected="selected"><?= $order_status['name'] ?></option>
									<?php } else { ?>
									<option value="<?= $order_status['order_status_id'] ?>"><?= $order_status['name'] ?></option>
									<?php } ?>
									<?php } ?>
								</select>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-geo-zone"><?= $entry_geo_zone ?></label>
							<div class="col-sm-10">
								<select name="payment_clickpay_geo_zone_id" id="input-geo-zone" class="form-control">
									<option value="0"><?= $text_all_zones ?></option>
									<?php foreach ($geo_zones as $geo_zone) { ?>
										<?php if ($geo_zone['geo_zone_id'] == $payment_clickpay_geo_zone_id) { ?>
											<option value="<?= $geo_zone['geo_zone_id'] ?>" selected="selected"><?= $geo_zone['name'] ?></option>
										<?php } else { ?>
											<option value="<?= $geo_zone['geo_zone_id'] ?>"><?= $geo_zone['name'] ?></option>
										<?php } ?>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-sort-order"><?= $entry_sort_order ?></label>
							<div class="col-sm-10">
								<input type="text" name="payment_clickpay_sort_order" value="<?= $payment_clickpay_sort_order ?>" placeholder="<?= $entry_sort_order ?>" id="input-sort-order" class="form-control" />
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-hide_shipping"><?= $entry_hide_shipping ?></label>
							<div class="col-sm-10">
								<select name="payment_clickpay_hide_shipping" id="input-hide_shipping" class="form-control">
									<?php if ($payment_clickpay_hide_shipping) { ?>
										<option value="1" selected="selected"><?= $text_yes ?></option>
										<option value="0"><?= $text_no ?></option>
									<?php } else { ?>
										<option value="1"><?= $text_yes ?></option>
										<option value="0" selected="selected"><?= $text_no ?></option>
									<?php } ?>
								</select>
							</div>
						</div>

						<?php if ($support_iframe) { ?>
						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-iframe"><?= $entry_iframe ?></label>
							<div class="col-sm-10">
								<select name="payment_clickpay_iframe" id="input-iframe" class="form-control">
									<?php if ($payment_clickpay_iframe) { ?>
										<option value="1" selected="selected"><?= $text_yes ?></option>
										<option value="0"><?= $text_no ?></option>
									<?php } else { ?>
										<option value="1"><?= $text_yes ?></option>
										<option value="0" selected="selected"><?= $text_no ?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<?php } ?>

						<?php if ($is_card_payment) { ?>
							<div class="form-group">
								<label class="col-sm-2 control-label" for="input-allow_associated_methods"><?= $entry_allow_associated_methods ?></label>
								<div class="col-sm-10">
									<select name="payment_clickpay_allow_associated_methods" id="input-allow_associated_methods" class="form-control">
										<?php if ($payment_clickpay_allow_associated_methods) { ?>
											<option value="1" selected="selected"><?= $text_yes ?></option>
											<option value="0"><?= $text_no ?></option>
										<?php } else { ?>
											<option value="1"><?= $text_yes ?></option>
											<option value="0" selected="selected"><?= $text_no ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
						<?php } ?>

						<div class="row mb-3">
							<label class="col-sm-2 col-form-label" for="entry-config-id"><?= $entry_config_id ?></label>
							<div class="col-sm-10">
								<input type="number" min="1" name="payment_clickpay_config_id" value="<?= $payment_clickpay_config_id ?>" id="entry-config-id" class="form-control"/>
							</div>
						</div>

						<div class="row mb-3">
							<label class="col-sm-2 col-form-label" for="entry-alt-currency"><?= $entry_alt_currency ?></label>
							<div class="col-sm-10">
								<input type="text" name="payment_clickpay_alt_currency" value="<?= $payment_clickpay_alt_currency ?>" id="entry-alt-currency" class="form-control"/>
							</div>
						</div>

					</div>

				</form>
				<div class="alert alert-info"><?= $help_clickpay_account_setup ?></div>
			</div>
		</div>
	</div>
</div>
<?= $footer ?>