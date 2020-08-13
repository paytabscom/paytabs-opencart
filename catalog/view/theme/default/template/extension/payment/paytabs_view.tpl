<?php if ($paypage) { ?>
	<form action="<?= $payment_url ?>" method="post" class="pull-right">
		<input type="hidden" name="temp" value="1" />
		<button type="submit" class="btn btn-primary"><?= $button_confirm ?></button>
	</form>
<?php } else { ?>
	<div class="alert alert-danger">
		<?= $paypage_msg ?>
	</div>
<?php } ?>