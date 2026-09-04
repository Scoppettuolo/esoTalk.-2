<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Flags"] = array(
	"name" => "Flags",
	"description" => "Allow members to flag/report posts for moderator review (Flarum-inspired).",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Flags extends ETPlugin {

public function setup($oldVersion = "")
{
	try {
		$structure = ET::$database->structure();
		$structure
			->table("post_flag")
			->column("flagId", "int(11) unsigned", false)
			->column("postId", "int(11) unsigned", false)
			->column("memberId", "int(11) unsigned", false)
			->column("reason", "varchar(255)")
			->column("time", "int(11) unsigned", false)
			->column("resolved", "tinyint(1)", 0)
			->key("flagId", "primary")
			->key("postId")
			->key("memberId")
			->key("resolved")
			->exec(false);
	} catch (Exception $e) {}
	return true;
}

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("flags.css"));
	$sender->addJSFile($this->resource("flags.js"));
}

public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
{
	if (!empty($post["deleteMemberId"]) || !ET::$session->userId) return;
	if ($post["memberId"] == ET::$session->userId) return; // don't flag own

	$postId = (int)$post["postId"];
	$formatted["controls"][] = "<a href='#' class='control-flag' data-postid='$postId' title='".T("Flag")."'><i class='icon-flag'></i></a>";
}

public function action_conversationController_flag($sender)
{
	header("Content-Type: application/json; charset=utf-8");
	if (!ET::$session->userId) {
		$sender->json("error", "login");
		$sender->renderJSON();
		return;
	}

	if (method_exists($sender, "validateToken") && !$sender->validateToken()) {
		$sender->json("error", "token");
		$sender->renderJSON();
		return;
	}
	$this->setup();
	$postId = (int)R("postId");
	$reason = mb_substr(trim((string)R("reason")), 0, 255);
	if (!$postId) {
		$sender->json("error", "invalid");
		$sender->renderJSON();
		return;
	}

	try {
		$exists = ET::SQL()->select("*")->from("post_flag")
			->where("postId", $postId)
			->where("memberId", ET::$session->userId)
			->where("resolved", 0)
			->exec()->firstRow();
		if ($exists) {
			$sender->json("ok", true);
			$sender->json("message", T("Already flagged"));
			$sender->renderJSON();
			return;
		}
		ET::SQL()->insert("post_flag")->set(array(
			"postId" => $postId,
			"memberId" => ET::$session->userId,
			"reason" => $reason ?: "flagged",
			"time" => time(),
			"resolved" => 0
		))->exec();
		$sender->json("ok", true);
		$sender->json("message", T("Thanks for the report"));
	} catch (Exception $e) {
		$sender->json("error", "server");
	}
	$sender->renderJSON();
}

public function handler_adminController_init($sender)
{
	// Count open flags for dashboard - optional
}

}
