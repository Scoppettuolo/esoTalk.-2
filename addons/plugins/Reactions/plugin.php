<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Reactions"] = array(
	"name" => "Reactions",
	"description" => "Flarum-style reactions on posts: like, love, laugh, wow, sad.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Reactions extends ETPlugin {

public static $types = array("like" => "👍", "love" => "❤️", "laugh" => "😂", "wow" => "😮", "sad" => "😢");

public function setup($oldVersion = "")
{
	try {
		ET::$database->structure()
			->table("post_reaction")
			->column("postId", "int(11) unsigned", false)
			->column("memberId", "int(11) unsigned", false)
			->column("type", "varchar(16)", false)
			->column("time", "int(11) unsigned", false)
			->key(array("postId", "memberId", "type"), "primary")
			->key("postId")
			->exec(false);
	} catch (Exception $e) {}
	return true;
}

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("reactions.css"));
	$sender->addJSFile($this->resource("reactions.js"));
}

public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
{
	if (!empty($post["deleteMemberId"])) return;
	$postId = (int)$post["postId"];
	$counts = array();
	$mine = array();
	try {
		$rows = ET::SQL()->select("type, COUNT(*) AS c")
			->select("SUM(memberId=".intval(ET::$session->userId ?: 0).")", "mine")
			->from("post_reaction")
			->where("postId", $postId)
			->groupBy("type")
			->exec()->allRows();
		foreach ($rows as $r) {
			$counts[$r["type"]] = (int)$r["c"];
			if (!empty($r["mine"])) $mine[$r["type"]] = true;
		}
	} catch (Exception $e) {
		return;
	}

	$html = "<div class='reactions' data-postid='$postId'>";
	foreach (self::$types as $type => $emoji) {
		$c = isset($counts[$type]) ? $counts[$type] : 0;
		$active = !empty($mine[$type]) ? " active" : "";
		$label = $c ? " $c" : "";
		$html .= "<a href='#' class='reaction$active' data-type='$type' title='$type'>$emoji<span class='r-count'>$label</span></a>";
	}
	$html .= "</div>";
	$formatted["footer"][] = $html;
}

public function action_conversationController_react($sender)
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
	$type = preg_replace('/[^a-z]/', '', strtolower((string)R("type")));
	if (!$postId || !isset(self::$types[$type])) {
		$sender->json("error", "invalid");
		$sender->renderJSON();
		return;
	}
	$exists = ET::SQL()->select("*")->from("post_reaction")
		->where("postId", $postId)->where("memberId", ET::$session->userId)->where("type", $type)
		->exec()->numRows();
	if ($exists) {
		ET::SQL()->delete()->from("post_reaction")
			->where("postId", $postId)->where("memberId", ET::$session->userId)->where("type", $type)->exec();
		$on = false;
	} else {
		ET::SQL()->insert("post_reaction")->set(array(
			"postId" => $postId,
			"memberId" => ET::$session->userId,
			"type" => $type,
			"time" => time()
		))->exec();
		$on = true;
	}
	$count = ET::SQL()->select("COUNT(*)")->from("post_reaction")->where("postId", $postId)->where("type", $type)->exec()->result();
	$sender->json("type", $type);
	$sender->json("on", $on);
	$sender->json("count", (int)$count);
	$sender->renderJSON();
}

}
