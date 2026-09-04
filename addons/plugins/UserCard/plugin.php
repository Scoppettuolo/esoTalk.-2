<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["UserCard"] = array(
	"name" => "User Card",
	"description" => "Lightweight hover card with avatar, post count and profile link.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_UserCard extends ETPlugin {

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("usercard.css"));
	$sender->addJSFile($this->resource("usercard.js"));
}

public function handler_memberController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("usercard.css"));
	$sender->addJSFile($this->resource("usercard.js"));
}

public function action_conversationController_userCard($sender)
{
	header("Content-Type: application/json; charset=utf-8");

	$id = (int)R("memberId");
	if (!$id) {
		$sender->json("error", "invalid");
		$sender->renderJSON();
		return;
	}

	$member = ET::memberModel()->getById($id);
	if (!$member) {
		$sender->json("error", "notfound");
		$sender->renderJSON();
		return;
	}

	$posts = 0;
	try {
		$posts = (int) ET::SQL()->select("COUNT(*)")->from("post")
			->where("memberId", $id)->where("deleteMemberId IS NULL")->exec()->result();
	} catch (Exception $e) {}

	$avatarHtml = avatar($member, "usercard-avatar");
	$sender->json("memberId", (int)$member["memberId"]);
	$sender->json("username", $member["username"]);
	$sender->json("avatar", $avatarHtml);
	$sender->json("posts", $posts);
	$sender->json("profileUrl", URL("member/".$member["memberId"]));
	$sender->json("account", isset($member["account"]) ? $member["account"] : "");
	$sender->renderJSON();
}

}
