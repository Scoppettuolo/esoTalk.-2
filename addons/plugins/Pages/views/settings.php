<?php if (!defined("IN_ESOTALK")) exit;
$form = $data["pageForm"];
$pages = $data["pages"];
?>
<div class='sheet'><div class='sheetContent'>
	<h3>Existing pages</h3>
	<ul>
	<?php if (!$pages): ?><li><em>No pages yet.</em></li><?php endif; ?>
	<?php foreach ($pages as $p): ?>
		<li>
			<strong><?php echo sanitizeHTML($p["title"]); ?></strong>
			(<?php echo sanitizeHTML($p["slug"]); ?>)
			— <a href='<?php echo URL("page/".$p["slug"]); ?>' target='_blank'>View</a>
			<?php
			$df = ETFactory::make("form");
			$df->action = URL("admin/plugins/settings/Pages");
			echo $df->open();
			echo $df->input("pageId", "hidden", array("value" => $p["pageId"]));
			echo $df->button("deletePage", "Delete", array("class" => "button"));
			echo $df->close();
			?>
		</li>
	<?php endforeach; ?>
	</ul>
	<hr>
	<h3>Add / edit page</h3>
	<?php echo $form->open(); ?>
	<ul class='form'>
		<li><label>Title</label><?php echo $form->input("title"); ?></li>
		<li><label>Slug</label><?php echo $form->input("slug", "text", array("placeholder" => "about")); ?></li>
		<li><label>Content (plain text / BBCode)</label><?php echo $form->input("content", "textarea", array("style" => "width:100%;height:180px")); ?></li>
		<li><label class='checkbox'><?php echo $form->checkbox("menu"); ?> Show in main menu</label></li>
		<li><?php echo $form->button("savePage", T("Save Changes"), array("class" => "big submit")); ?></li>
	</ul>
	<?php echo $form->close(); ?>
</div></div>
