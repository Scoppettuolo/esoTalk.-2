<?php
if (!defined("IN_ESOTALK")) exit;

$previousPost = null;
foreach ($data["posts"] as $post):
	$formattedPost = $this->formatPostForTemplate($post, $data["conversation"]);
	if ($previousPost && empty($previousPost["deleteMemberId"]) && $previousPost["memberId"] == $post["memberId"])
		$formattedPost["hideAvatar"] = true;
	$thisPostTime = relativeTime($post["time"]);
?>
<li data-index='<?php echo date("Y", $post["time"]).date("m", $post["time"]); ?>' class='<?php echo !empty($post["_classicGroupStart"]) ? "classic-group-start" : ""; ?>'>
<?php if (!$previousPost || relativeTime($previousPost["time"]) != $thisPostTime): ?>
<div class='timeMarker'<?php if ($thisPostTime == T("just now")): ?> data-now='1'<?php endif; ?>><?php echo $thisPostTime; ?></div>
<?php endif; ?>
<?php $this->renderView("conversation/post", array("post" => $formattedPost)); ?>
</li>
<?php $previousPost = $post; endforeach; ?>
