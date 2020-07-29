<?php if ($paypage) { ?>
	<form action="<?= $payment_url ?>" method="post" class="pull-right">
		<button type="submit" class="btn btn-primary"><?= $button_confirm ?></button>
	</form>
<?php } else { ?>
	<div class="alert alert-danger">
		<?= $paypage_msg ?>
	</div>
<?php } ?>