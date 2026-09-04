<?php
if (!defined("IN_ESOTALK")) exit;
$posts = $data["posts"];
$token = ET::$session->token;
?>
<div class="area">
<div class="bodyHeader" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
	<div>
		<h1 style="margin:0"><?php echo T("Manage Blog"); ?></h1>
		<p class="subText" style="margin:4px 0 0"><a href="<?php echo URL("blog"); ?>">← <?php echo T("View public blog"); ?></a></p>
	</div>
	<a href="<?php echo URL("blog/create"); ?>" class="button big"><?php echo T("New post"); ?></a>
</div>

<?php if (empty($posts)): ?>
	<p class="help"><?php echo T("No blog posts yet."); ?></p>
<?php else: ?>
	<table class="blog-manage-table">
		<thead>
			<tr>
				<th><?php echo T("Title"); ?></th>
				<th><?php echo T("Status"); ?></th>
				<th><?php echo T("Date"); ?></th>
				<th><?php echo T("Actions"); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($posts as $p): ?>
			<tr>
				<td>
					<strong>
						<a href="<?php echo URL("blog/".urlencode($p["slug"]).(empty($p["published"]) ? "?preview=".$token : "")); ?>">
							<?php echo sanitizeHTML($p["title"]); ?>
						</a>
					</strong>
					<div class="subText"><?php echo sanitizeHTML($p["slug"]); ?>
						<?php if (!empty($p["username"])): ?> · <?php echo sanitizeHTML($p["username"]); endif; ?>
					</div>
				</td>
				<td>
					<?php if (!empty($p["published"])): ?>
						<span style="color:#059669"><?php echo T("Published"); ?></span>
					<?php else: ?>
						<span style="color:#d97706"><?php echo T("Draft"); ?></span>
					<?php endif; ?>
				</td>
				<td><?php echo relativeTime($p["time"], true); ?></td>
				<td class="blog-manage-actions">
					<a href="<?php echo URL("blog/edit/".$p["postId"]); ?>" class="button"><?php echo T("Edit"); ?></a>
					<a href="<?php echo URL("blog/toggle/".$p["postId"]."?token=".$token); ?>" class="button">
						<?php echo !empty($p["published"]) ? T("Unpublish") : T("Publish"); ?>
					</a>
					<a href="<?php echo URL("blog/delete/".$p["postId"]."?token=".$token); ?>" class="button"
						onclick="return confirm('<?php echo addslashes(T("Delete this post")); ?>?');"><?php echo T("Delete"); ?></a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
</div>
