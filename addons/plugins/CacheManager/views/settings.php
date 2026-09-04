<?php if (!defined("IN_ESOTALK")) exit;
$form = $data["cacheForm"];
echo $form->open();
?>
<div class="sheet"><div class="sheetContent">
	<p>Clear cached data (channels, groups, aggregated CSS/JS, sitemap). Safe to run anytime.</p>
	<div class="buttons">
		<?php echo $form->button("clear", "Clear cache", array("class" => "big submit")); ?>
	</div>
</div></div>
<?php echo $form->close(); ?>
