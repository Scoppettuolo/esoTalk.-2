<?php if (!defined("IN_ESOTALK")) exit;
$form = $data["autoCloseForm"];
echo $form->open();
?>
<div class="sheet"><div class="sheetContent">
	<label>Days of inactivity before auto-lock (0 = disabled)</label>
	<?php echo $form->input("days", "number"); ?>
	<div class="buttons">
		<?php echo $form->button("save", T("Save Changes"), array("class" => "big submit")); ?>
	</div>
</div></div>
<?php echo $form->close(); ?>
