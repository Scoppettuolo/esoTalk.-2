<?php if (!defined("IN_ESOTALK")) exit;
$form = $data["form"];
$posts = $data["posts"];
$onlyMine = !empty($data["onlyMine"]);
?>
<div class='area'>
<div class='bodyHeader' style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
	<h1 style="margin:0"><?php echo T("Microblog"); ?></h1>
	<div>
		<a href='<?php echo URL("microblog"); ?>' class='button<?php if (!$onlyMine): ?> primary<?php endif; ?>'><?php echo T("All"); ?></a>
		<?php if (ET::$session->userId): ?>
			<a href='<?php echo URL("microblog/mine"); ?>' class='button<?php if ($onlyMine): ?> primary<?php endif; ?>'><?php echo T("My statuses"); ?></a>
		<?php endif; ?>
	</div>
</div>

<?php if (ET::$session->userId): ?>
<div class='sheet' style="margin:16px 0">
<div class='sheetContent'>
	<?php echo $form->open(); ?>
	<ul class='form'>
		<li>
			<label><?php echo T("What's happening?"); ?></label>
			<?php echo $form->input("content", "textarea", array(
				"rows" => 3,
				"maxlength" => 280,
				"placeholder" => T("Write a short status (max 280 characters)..."),
				"style" => "width:100%;box-sizing:border-box",
				"id" => "microblogContent"
			)); ?>
			<?php echo $form->getError("content"); ?>
			<small class='help'><span id='microblogCount'>0</span>/280</small>
		</li>
		<li><?php echo $form->button("postStatus", T("Post"), array("class" => "big submit")); ?></li>
	</ul>
	<?php echo $form->close(); ?>
</div>
</div>
<script>
(function(){
	var ta = document.getElementById('microblogContent');
	var cnt = document.getElementById('microblogCount');
	if (!ta || !cnt) return;
	function upd(){ cnt.textContent = (ta.value || '').length; }
	ta.addEventListener('input', upd);
	upd();
})();
</script>
<?php else: ?>
	<p class='help'><?php echo T("You must be logged in to view this page."); ?> <a href='<?php echo URL("user/login"); ?>'><?php echo T("Log In"); ?></a></p>
<?php endif; ?>

<?php if (empty($posts)): ?>
	<p class='help'><?php echo T("No status updates yet."); ?></p>
<?php else: ?>
	<ul class='list'>
	<?php foreach ($posts as $p): ?>
		<li class='thing' style="padding:14px 0;border-bottom:1px solid #e5e7eb">
			<div style="display:flex;gap:12px;align-items:flex-start">
				<div style="flex:1;min-width:0">
					<strong><a href='<?php echo URL("member/".$p["memberId"]); ?>'><?php echo sanitizeHTML($p["username"]); ?></a></strong>
					<span class='subText' style="margin-left:8px"><?php echo relativeTime($p["time"], true); ?></span>
					<div class='postBody' style="margin-top:6px;line-height:1.5"><?php echo nl2br(sanitizeHTML($p["content"])); ?></div>
				</div>
				<?php if (ET::$session->userId && ((int)ET::$session->userId === (int)$p["memberId"] || ET::$session->isAdmin())): ?>
					<a href='<?php echo URL("microblog/delete/".$p["id"]."?token=".ET::$session->token); ?>' class='control-delete' title='<?php echo T("Delete"); ?>' onclick="return confirm('<?php echo addslashes(T("Delete")); ?>?');"><i class='icon-remove'></i></a>
				<?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
	</ul>
<?php endif; ?>
</div>
