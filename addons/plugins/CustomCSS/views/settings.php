<?php if (!defined("IN_ESOTALK")) exit;
$form = $data["customCSSForm"];
echo $form->open();
?>
<div class='sheet'><div class='sheetContent'>
	<label>Custom CSS</label>
	<?php echo $form->input("css", "textarea", array("style" => "width:100%;height:160px;font-family:monospace")); ?>
	<label>Custom JavaScript</label>
	<?php echo $form->input("js", "textarea", array("style" => "width:100%;height:120px;font-family:monospace")); ?>
	<p class='help'>Use for small theme tweaks or analytics scripts. Do not paste untrusted code.</p>
	<div class='buttons'>
		<?php echo $form->button("save", T("Save Changes"), array("class" => "big submit")); ?>
	</div>
</div></div>
<?php echo $form->close(); ?>
