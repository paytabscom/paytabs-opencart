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
								<select name="payment_paytabs_status" id="input-status" class="form-control">
									<?php if ($payment_paytabs_status) { ?>
										<option value="1" selected="selected"><?= $text_enabled ?></option>
										<option value="0"><?= $text_disabled ?></option>
									<?php } else { ?>
										<option value="1"><?= $text_enabled ?></option>
										<option value="0" selected="selected"><?= $text_disabled ?></option>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group required">
							<label class="col-sm-2 control-label" for="entry-app-id"><?= $entry_merchant_email ?></label>
							<div class="col-sm-10">
								<input type="text" name="payment_paytabs_merchant_email" value="<?= $payment_paytabs_merchant_email ?>" placeholder="<?= $entry_merchant_email ?>" id="entry-merchant-id" class="form-control" />
								<?php if ($error_merchant_email) { ?>
									<div class="text-danger"><?= $error_merchant_email ?></div>
								<?php } ?>
							</div>
						</div>

						<div class="form-group required">
							<label class="col-sm-2 control-label" for="entry-merchant-private-key"><?= $entry_secret_key ?></label>
							<div class="col-sm-10">
								<input type="text" name="payment_paytabs_merchant_secret_key" value="<?= $payment_paytabs_merchant_secret_key ?>" placeholder="<?= $entry_secret_key ?>" id="entry-merchant-private-key" class="form-control" />
								<?php if ($error_merchant_secret_key) { ?>
									<div class="text-danger"><?= $error_merchant_secret_key ?></div>
								<?php } ?>
							</div>
						</div>

						<?php if ($method == 'valu') { ?>
							<div class="form-group required">
								<label class="col-sm-2 control-label" for="entry-valu_product_id"><?= $entry_valu_product_id ?></label>
								<div class="col-sm-10">
									<input type="text" name="payment_paytabs_valu_product_id" value="<?= $payment_paytabs_valu_product_id ?>" placeholder="<?= $entry_valu_product_id ?>" id="entry-valu_product_id" class="form-control" />
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
								<input type="text" name="payment_paytabs_total" value="<?= $payment_paytabs_total ?>" placeholder="<?= $entry_total ?>" id="input-total" class="form-control" />
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-order-status"><?= $entry_order_status ?></label>
							<div class="col-sm-10">
								<select name="payment_paytabs_order_status_id" id="input-order-status" class="form-control">
									<?php foreach ($order_statuses as $order_status) { ?>
										<?php if ($order_status['order_status_id'] == $payment_paytabs_order_status_id) { ?>
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
								<select name="payment_paytabs_geo_zone_id" id="input-geo-zone" class="form-control">
									<option value="0"><?= $text_all_zones ?></option>
									<?php foreach ($geo_zones as $geo_zone) { ?>
										<?php if ($geo_zone['geo_zone_id'] == $payment_paytabs_geo_zone_id) { ?>
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
								<input type="text" name="payment_paytabs_sort_order" value="<?= $payment_paytabs_sort_order ?>" placeholder="<?= $entry_sort_order ?>" id="input-sort-order" class="form-control" />
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-hide_personal_info"><?= $entry_hide_personal_info ?></label>
							<div class="col-sm-10">
								<select name="payment_paytabs_hide_personal_info" id="input-hide_personal_info" class="form-control">
									<?php if ($payment_paytabs_hide_personal_info) { ?>
										<option value="1" selected="selected"><?= $text_yes ?></option>
										<option value="0"><?= $text_no ?></option>
									<?php } else { ?>
										<option value="1"><?= $text_yes ?></option>
										<option value="0" selected="selected"><?= $text_no ?></option>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-hide_billing"><?= $entry_hide_billing ?></label>
							<div class="col-sm-10">
								<select name="payment_paytabs_hide_billing" id="input-hide_billing" class="form-control">
									<?php if ($payment_paytabs_hide_billing) { ?>
										<option value="1" selected="selected"><?= $text_yes ?></option>
										<option value="0"><?= $text_no ?></option>
									<?php } else { ?>
										<option value="1"><?= $text_yes ?></option>
										<option value="0" selected="selected"><?= $text_no ?></option>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label" for="input-hide_view_invoice"><?= $entry_hide_view_invoice ?></label>
							<div class="col-sm-10">
								<select name="payment_paytabs_hide_view_invoice" id="input-hide_view_invoice" class="form-control">
									<?php if ($payment_paytabs_hide_view_invoice) { ?>
										<option value="1" selected="selected"><?= $text_yes ?></option>
										<option value="0"><?= $text_no ?></option>
									<?php } else { ?>
										<option value="1"><?= $text_yes ?></option>
										<option value="0" selected="selected"><?= $text_no ?></option>
									<?php } ?>
								</select>
							</div>
						</div>

					</div>

				</form>
				<div class="alert alert-info"><?= $help_paytabs_account_setup ?></div>
			</div>
		</div>
	</div>
</div>
<?= $footer ?>