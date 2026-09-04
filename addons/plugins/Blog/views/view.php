<?php
if (!defined("IN_ESOTALK")) exit;
$post = $data["post"];
$bodyHtml = isset($data["bodyHtml"]) ? $data["bodyHtml"] : "";
?>
<div class="area blog-article">
<div class="bodyHeader" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
	<div>
		<p class="subText" style="margin:0 0 4px"><a href="<?php echo URL("blog"); ?>">← <?php echo T("Blog"); ?></a></p>
		<h1 style="margin:0">
			<?php echo sanitizeHTML($post["title"]); ?>
			<?php if (empty($post["published"])): ?>
				<span class="blog-draft-badge"><?php echo T("Draft"); ?></span>
			<?php endif; ?>
		</h1>
		<div class="blog-meta">
			<?php echo relativeTime($post["time"], true); ?>
			<?php if (!empty($post["username"])): ?>
				· <a href="<?php echo URL("member/".$post["memberId"]); ?>"><?php echo sanitizeHTML($post["username"]); ?></a>
			<?php endif; ?>
			<?php if (!empty($post["updateTime"]) && (int)$post["updateTime"] > (int)$post["time"] + 60): ?>
				· <?php echo T("Updated"); ?> <?php echo relativeTime($post["updateTime"], true); ?>
			<?php endif; ?>
		</div>
	</div>
	<?php if (!empty($data["isAdmin"])): ?>
		<div>
			<a href="<?php echo URL("blog/edit/".$post["postId"]); ?>" class="button"><?php echo T("Edit"); ?></a>
			<a href="<?php echo URL("blog/manage"); ?>" class="button"><?php echo T("Manage Blog"); ?></a>
		</div>
	<?php endif; ?>
</div>

<div class="postBody">
	<?php echo $bodyHtml; ?>
</div>
</div>
