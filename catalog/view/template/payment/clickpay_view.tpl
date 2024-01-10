<?php if ($iframe_mode) { ?>

	<div id="pt_paymentpage">
		<div class="pull-right">
			<button type="button" class="btn btn-primary" onclick="pt_submit('<?= $order_id ?>')"><?= $button_confirm ?></button>
		</div>

		<div id="pt_loading" style="display: none;">
			<img src="catalog/view/theme/default/image/clickpay/logo-animation.gif" alt="Clickpay loading" style="max-height: 200px; margin: auto; display: block;">
		</div>
	</div>

	<script>
		function pt_submit(order_id) {
			$('#pt_loading').show('fast').prev().hide();

			$.post('<?= $url_confirm ?>', {
					order: order_id
				})
				.always(function() {
					$('#pt_loading').hide('fast');
				})
				.done(function(res) {
					console.log(res);
					$('#pt_paymentpage').html(res);
				})
				.fail(function(xhr, status, error) {
					console.log(status, error);
					$('#pt_paymentpage').html(error);
				});
		}
	</script>

<?php } else { ?>

	<form action="<?= $url_confirm ?>" method="post" class="pull-right">
		<input type="hidden" name="order" value="<?= $order_id ?>">
		<button type="submit" class="btn btn-primary"><?= $button_confirm ?></button>
	</form>

<?php } ?>