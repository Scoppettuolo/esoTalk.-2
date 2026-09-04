<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the conversation list. The optional classic mode restores the v1 table
 * (starter avatar, starter, post count and last reply) while leaving the modern
 * list available as the default.
 *
 * @package esoTalk
 */

if (C("esoTalk.classicConversationList", false)): ?>
<table cellspacing='0' cellpadding='2' class='c classicConversationList'>
<thead>
<tr>
<th class='star'>&nbsp;</th>
<th class='avatar'>&nbsp;</th>
<th class='conversation'><?php echo T("Conversation"); ?></th>
<th class='posts'><?php echo T("Posts"); ?></th>
<th class='author'><?php echo T("Started by"); ?></th>
<th class='lastReply'><?php echo T("Last reply"); ?></th>
</tr>
</thead>
<tbody id='conversations'>
<?php foreach ((array)$data["results"] as $conversation):
	$conversationURL = conversationURL($conversation["conversationId"], $conversation["title"]);
	$className = "c".(int)$conversation["conversationId"];
	if (!empty($conversation["starred"])) $className .= " starred";
	if (!empty($conversation["unread"]) && ET::$session->user) $className .= " unread";
	if ((int)$conversation["startMemberId"] === (int)ET::$session->userId) $className .= " mine";
	foreach ((array)$conversation["labels"] as $label) $className .= " label-".sanitizeHTML($label);
	$startMember = array(
		"memberId" => $conversation["startMemberId"],
		"username" => $conversation["startMember"],
		"avatarFormat" => $conversation["startMemberAvatarFormat"]
	);
?>
<tr id='c<?php echo (int)$conversation["conversationId"]; ?>' class='<?php echo $className; ?>'>
<td class='star'><?php if (ET::$session->user) echo star($conversation["conversationId"], !empty($conversation["starred"])); ?></td>
<td class='avatar'><a href='<?php echo URL($conversationURL); ?>' aria-label='<?php echo sanitizeHTML($conversation["startMember"]); ?>'><?php echo avatar($startMember, "thumb"); ?></a></td>
<td class='conversation'>
<span class='labels'><?php foreach ((array)$conversation["labels"] as $label) echo label($label, $label == "draft" ? URL($conversationURL."#reply") : ""); ?></span>
<strong<?php if (ET::$session->user && empty($conversation["unread"])): ?> class='read'<?php endif; ?>><a href='<?php echo URL($conversationURL.((ET::$session->user && !empty($conversation["unread"])) ? "/unread" : "")); ?>'><?php echo highlight(sanitizeHTML($conversation["title"]), ET::$session->getValue("highlight")); ?></a></strong>
<?php if (!empty($conversation["classicTags"]) && is_array($conversation["classicTags"])): ?><br/><small class='tags'><?php echo sanitizeHTML(implode(", ", $conversation["classicTags"])); ?></small><?php endif; ?>
</td>
<td class='posts'><span class='postCount'><?php echo (int)$conversation["countPosts"]; ?></span></td>
<td class='author'><?php echo memberLink($conversation["startMemberId"], $conversation["startMember"]); ?><br/><small><?php echo relativeTime($conversation["startTime"], true); ?></small></td>
<td class='lastReply'><?php if ((int)$conversation["countPosts"] > 1): ?><?php echo memberLink($conversation["lastPostMemberId"], $conversation["lastPostMember"]); ?><br/><small><?php echo relativeTime($conversation["lastPostTime"], true); ?></small><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php if ($data["showViewMoreLink"]): ?>
<p class='viewMore classic-viewMore'><a href='<?php
$searchWithoutLimit = ET::searchModel()->removeGambit($data["searchString"], function ($term) { return strpos($term, strtolower(T("gambit.limit:"))) === 0; });
echo URL("conversations/".$data["channelSlug"]."?search=".urlencode($searchWithoutLimit.($searchWithoutLimit ? " + " : "")."#".T("gambit.limit:").($data["limit"] + C("esoTalk.search.limitIncrement"))));
?>'><?php echo T("View more"); ?></a></p>
<?php endif; ?>
<?php else: ?>
<ul class='list conversationList'>
<?php foreach ((array)$data["results"] as $conversation):
	$this->renderView("conversations/conversation", $data + array("conversation" => $conversation));
endforeach; ?>
<?php if ($data["showViewMoreLink"]): ?>
<li class='viewMore'>
<a href='<?php
$searchWithoutLimit = ET::searchModel()->removeGambit($data["searchString"], function ($term) { return strpos($term, strtolower(T("gambit.limit:"))) === 0; });
echo URL("conversations/".$data["channelSlug"]."?search=".urlencode($searchWithoutLimit.($searchWithoutLimit ? " + " : "")."#".T("gambit.limit:").($data["limit"] + C("esoTalk.search.limitIncrement"))));
?>'><?php echo T("View more"); ?></a>
</li>
<?php endif; ?>
</ul>
<?php endif; ?>
