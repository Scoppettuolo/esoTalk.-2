<?php
if (!defined("IN_ESOTALK")) exit;

/**
 * Shows a summary of the members allowed in a conversation.
 *
 * @package esoTalk
 */

$conversation = $data["conversation"];

$names = array();
$avatars = array();

foreach ($conversation["membersAllowedSummary"] as $member) {
	if ($member["type"] == "member") {
		$names[] = "<span class='name'>".memberLink($member["id"], $member["name"])."</span>";
		if (count($avatars) < 3) $avatars[] = avatar($member + array("memberId" => $member["id"]), "thumb");
	} else {
		$names[] = "<span class='name'>".groupLink($member["name"])."</span>";
	}
}

$canKey = !empty($conversation["countPosts"])
	? "%s can view this conversation."
	: "%s will be able to view this conversation.";

if (count($names)) {
	echo "<span class='avatars'>".implode(" ", $avatars)."</span> ";

	if (count($names) > 1) {
		if (count($names) > 3) {
			$otherNames = array_splice($names, 3);
			$lastName = "<a href='#' class='showMore name'>".sprintf(T("%s others"), count($otherNames))."</a>";
		} else {
			$lastName = array_pop($names);
		}
		printf(T($canKey), sprintf(T("%s and %s"), implode(", ", $names), $lastName));
	} else {
		printf(T($canKey), $names[0]);
	}
} else {
	printf(T($canKey), T("Everyone"));
}
