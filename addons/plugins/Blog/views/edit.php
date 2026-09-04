<?php
if (!defined("IN_ESOTALK")) exit;
$form = $data["form"];
$post = !empty($data["post"]) ? $data["post"] : null;
?>
<div class="area">
<div class="bodyHeader">
	<p class="subText" style="margin:0 0 4px"><a href="<?php echo URL("blog/manage"); ?>">← <?php echo T("Manage Blog"); ?></a></p>
	<h1 style="margin:0"><?php echo $post ? T("Edit post") : T("New post"); ?></h1>
</div>

<div class="sheet" style="margin-top:16px">
<div class="sheetContent">
<?php echo $form->open(); ?>
<ul class="form">
	<li>
		<label><?php echo T("Title"); ?></label>
		<?php echo $form->input("title", "text", array("style" => "width:100%;max-width:640px", "required" => "required")); ?>
		<?php echo $form->getError("title"); ?>
	</li>
	<li>
		<label><?php echo T("Slug"); ?></label>
		<?php echo $form->input("slug", "text", array("style" => "width:100%;max-width:400px", "placeholder" => T("auto-generated-from-title"))); ?>
		<div class="blog-edit-help"><?php echo T("URL-friendly name. Leave blank to generate from the title."); ?></div>
		<?php echo $form->getError("slug"); ?>
	</li>
	<li>
		<label><?php echo T("Excerpt"); ?></label>
		<?php echo $form->input("excerpt", "textarea", array("rows" => 2, "style" => "width:100%;max-width:640px", "maxlength" => 500)); ?>
		<div class="blog-edit-help"><?php echo T("Short summary for the listing. Leave blank to auto-generate."); ?></div>
	</li>
	<li>
		<label><?php echo T("Content"); ?></label>
		<?php echo $form->input("content", "textarea", array("rows" => 16, "style" => "width:100%;max-width:720px;font-family:monospace")); ?>
		<div class="blog-edit-help"><?php echo T("Supports the same formatting as forum posts (BBCode, links, lists…)."); ?></div>
		<?php echo $form->getError("content"); ?>
	</li>
	<li>
		<label class="checkbox"><?php echo $form->checkbox("published"); ?> <?php echo T("Published"); ?></label>
	</li>
	<li class="sep"></li>
	<li>
		<?php echo $form->button("save", T("Save Changes"), array("class" => "big submit")); ?>
		<a href="<?php echo URL("blog/manage"); ?>" class="button"><?php echo T("Cancel"); ?></a>
	</li>
</ul>
<?php echo $form->close(); ?>
</div>
</div>
</div>
