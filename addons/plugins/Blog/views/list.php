<?php
if (!defined("IN_ESOTALK")) exit;
$posts = $data["posts"];
$page = (int)$data["page"];
$pages = (int)$data["pages"];
$total = (int)$data["total"];
?>
<div class="area">
<div class="bodyHeader" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
	<div>
		<h1 style="margin:0"><?php echo T("Blog"); ?></h1>
		<?php if ($total): ?>
			<p class="subText" style="margin:4px 0 0"><?php printf(T("%s posts"), number_format($total)); ?></p>
		<?php endif; ?>
	</div>
	<?php if (!empty($data["isAdmin"])): ?>
		<div>
			<a href="<?php echo URL("blog/create"); ?>" class="button"><?php echo T("New post"); ?></a>
			<a href="<?php echo URL("blog/manage"); ?>" class="button"><?php echo T("Manage Blog"); ?></a>
		</div>
	<?php endif; ?>
</div>

<?php if (empty($posts)): ?>
	<p class="help"><?php echo T("No blog posts yet."); ?></p>
<?php else: ?>
	<div class="blog-list">
	<?php foreach ($posts as $p): ?>
		<article class="blog-card">
			<h2><a href="<?php echo URL("blog/".urlencode($p["slug"])); ?>"><?php echo sanitizeHTML($p["title"]); ?></a></h2>
			<div class="blog-meta">
				<?php echo relativeTime($p["time"], true); ?>
				<?php if (!empty($p["username"])): ?>
					· <a href="<?php echo URL("member/".$p["memberId"]); ?>"><?php echo sanitizeHTML($p["username"]); ?></a>
				<?php endif; ?>
			</div>
			<?php if (!empty($p["excerpt"])): ?>
				<p class="blog-excerpt"><?php echo sanitizeHTML($p["excerpt"]); ?></p>
			<?php endif; ?>
			<a class="blog-readmore" href="<?php echo URL("blog/".urlencode($p["slug"])); ?>"><?php echo T("Read more"); ?> →</a>
		</article>
	<?php endforeach; ?>
	</div>

	<?php if ($pages > 1): ?>
		<nav class="blog-pagination" aria-label="<?php echo T("Pagination"); ?>">
			<?php if ($page > 1): ?>
				<a href="<?php echo URL("blog/?page=".($page-1)); ?>">&laquo; <?php echo T("Previous"); ?></a>
			<?php endif; ?>
			<?php for ($i = 1; $i <= $pages; $i++): ?>
				<?php if ($i === $page): ?>
					<span class="current"><?php echo $i; ?></span>
				<?php else: ?>
					<a href="<?php echo URL("blog/?page=".$i); ?>"><?php echo $i; ?></a>
				<?php endif; ?>
			<?php endfor; ?>
			<?php if ($page < $pages): ?>
				<a href="<?php echo URL("blog/?page=".($page+1)); ?>"><?php echo T("Next"); ?> &raquo;</a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
<?php endif; ?>
</div>
