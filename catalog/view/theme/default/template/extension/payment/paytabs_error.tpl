<?= $header ?>
<div id="common-success" class="container">
	<ul class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb) { ?>
			<li>
				<a href="<?= $breadcrumb['href'] ?>"><?= $breadcrumb['text'] ?></a>
			</li>
		<?php } ?>
	</ul>
	<div class="row">
		<?= $column_left ?>
		<?php
		if ($column_left && $column_right) {
			$class = 'col-sm-6';
		} elseif ($column_left || $column_right) {
			$class = 'col-sm-9';
		} else {
			$class = 'col-sm-12';
		}
		?>

		<div id="content" class="<?= $class ?>">
			<?= $content_top ?>
			<h1><?= "Failed Payment!" ?></h1>
			<div class="alert alert-danger">
				<?= $paytabs_error ?>
			</div>
			<?= $text_message ?>
			<div class="buttons">
				<div class="pull-right">
					<a href="<?= $continue ?>" class="btn btn-primary"><?= "Continue" ?></a>
				</div>
			</div>
			<?= $content_bottom ?>
		</div>
		<?= $column_right ?>
	</div>
</div>
<?= $footer ?>