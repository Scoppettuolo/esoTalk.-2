<?php if (!defined("IN_ESOTALK")) exit;
$form = $data["wordFilterForm"];
echo $form->open();
?>
<div class='sheet'>
<div class='sheetContent'>
	<?php echo $form->input("words", "textarea", array("style" => "width:100%;height:140px", "placeholder" => "one word per line")); ?>
	<p class='help'><?php echo T("Enter words to filter, one per line."); ?></p>
	<label><?php echo T("Replacement"); ?></label>
	<?php echo $form->input("replacement", "text"); ?>
	<div class='buttons'>
		<?php echo $form->button("save", T("Save Changes"), array("class" => "big submit")); ?>
	</div>
</div>
</div>
<?php echo $form->close(); ?>
