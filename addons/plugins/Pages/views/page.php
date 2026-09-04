<?php
if (!defined("IN_ESOTALK")) exit;
$page = $data["page"];
?>
<div class="area">
<div class="bodyHeader">
	<h1><?php echo sanitizeHTML($page["title"]); ?></h1>
</div>
<div class="postBody" style="line-height:1.7">
<?php
// Render with esoTalk formatter (safe HTML + BBCode/links)
echo ET::formatter()->init($page["content"])->format()->get();
?>
</div>
</div>
