<?=  $header ?>
<div id="common-success" class="container">
	<ul class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb) { ?>
			<li>
				<a href="<?= $breadcrumb['href'] ?>"><?= $breadcrumb['text'] ?></a>
			</li>
		<?php } ?>
	</ul>
	<div class="row">{{ column_left }}
		{% if column_left and column_right %}
			{% set class = 'col-sm-6' %}
		{% elseif column_left or column_right %}
			{% set class = 'col-sm-9' %}
		{% else %}
			{% set class = 'col-sm-12' %}
		{% endif %}
		<div id="content" class="{{ class }}">{{ content_top }}
				<iframe width="100%" height="700px" src="{<?= $payment_url ?>" frameborder="0"></iframe>
		</div>
		{{ column_right }}</div>
</div>
<?=  $footer ?>
