<?php
if (!defined("IN_ESOTALK")) exit;

$conversation = $data["conversation"];
$className = "channel-".$conversation["channelId"];
if ($conversation["starred"]) $className .= " starred";
if ($conversation["unread"] && ET::$session->user) $className .= " unread";
if ($conversation["startMemberId"] == ET::$session->userId) $className .= " mine";
foreach ((array)$conversation["labels"] as $label) $className .= " label-".$label;
$conversationURL = conversationURL($conversation["conversationId"], $conversation["title"]);
?>
<li id='c<?php echo (int)$conversation["conversationId"]; ?>' class='<?php echo sanitizeHTML($className); ?>'>
<?php if (ET::$session->user): ?>
<div class='col-star'><?php echo star($conversation["conversationId"], $conversation["starred"]); ?>
<?php if ($conversation["unread"]): ?><a href='<?php echo URL("conversation/markAsRead/".$conversation["conversationId"]."?token=".ET::$session->token."&return=".urlencode(ET::$controller->selfURL)); ?>' class='unreadIndicator' title='<?php echo T("Mark as read"); ?>'><i class='icon-ok'></i></a><?php endif; ?>
</div>
<?php endif; ?>
<div class='col-conversation'>
<span class='labels'><?php foreach ((array)$conversation["labels"] as $label) echo label($label, $label == "draft" ? URL($conversationURL."#reply") : ""); ?></span>
<strong class='title'><a href='<?php echo URL($conversationURL.(ET::$session->user && $conversation["unread"] ? "/unread" : "")); ?>'><?php echo highlight(sanitizeHTML($conversation["title"]), ET::$session->getValue("highlight")); ?></a></strong>
<?php if (!empty($conversation["classicTags"]) && is_array($conversation["classicTags"])): ?>
<span class='classic-tags'><?php foreach ($conversation["classicTags"] as $tag): ?><a class='classic-tag' href='<?php echo URL("conversations/".$data["channelSlug"]."/?search=".urlencode("#tag:".$tag)); ?>'><?php echo sanitizeHTML($tag); ?></a><?php endforeach; ?></span>
<?php endif; ?>
<?php if (ET::$session->getValue("highlight")): ?><span class='controls'><a href='<?php echo URL($conversationURL."/?search=".urlencode($data["fulltextString"])); ?>' class='showMatchingPosts'><?php echo T("Show matching posts"); ?></a></span><?php endif; ?>
<?php if ($conversation["sticky"]): ?><div class='excerpt'><?php echo ET::formatter()->init($conversation["firstPost"])->inline(true)->firstLine()->clip(200)->format()->get(); ?></div><?php endif; ?>
</div>
<div class='col-channel'><?php $channel = $data["channelInfo"][$conversation["channelId"]]; echo "<a href='".URL(searchURL("", $channel["slug"]))."' class='channel channel-".$conversation["channelId"]."' data-channel='".sanitizeHTML($channel["slug"])."'>".$channel["title"]."</a>"; ?></div>
<div class='col-lastPost'><?php echo "<span class='action'>".avatar(array("memberId" => $conversation["lastPostMemberId"], "username" => $conversation["lastPostMember"], "avatarFormat" => $conversation["lastPostMemberAvatarFormat"], "email" => $conversation["lastPostMemberEmail"]), "thumb")." ", sprintf(T("%s posted %s"), "<span class='lastPostMember name'>".memberLink($conversation["lastPostMemberId"], $conversation["lastPostMember"])."</span>", "<a href='".URL($conversationURL."/unread")."' class='lastPostTime'>".relativeTime($conversation["lastPostTime"], true)."</a>"), "</span>"; ?></div>
<div class='col-replies'><span><a href='<?php echo URL($conversationURL."/unread"); ?>'><?php echo $conversation["replies"]; ?></a></span></div>
</li>
