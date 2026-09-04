<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Solved"] = array(
	"name" => "Solved",
	"description" => "Mark a post as the solution/best answer (Flarum-inspired). Starter or moderators can mark it.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Solved extends ETPlugin {

protected function getAttrs($conversation)
{
	$attrs = array();
	if (!empty($conversation["attributes"])) {
		$attrs = is_array($conversation["attributes"])
			? $conversation["attributes"]
			: (esotalk_unserialize($conversation["attributes"]) ?: array());
	}
	return is_array($attrs) ? $attrs : array();
}

protected function saveAttrs($conversationId, $attrs)
{
	ET::SQL()->update("conversation")
		->set("attributes", serialize($attrs))
		->where("conversationId", $conversationId)
		->exec();
}

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("solved.css"));
	$sender->addJSFile($this->resource("solved.js"));
}

public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
{
	if (!empty($post["deleteMemberId"])) return;

	$attrs = $this->getAttrs($conversation);
	$solvedId = isset($attrs["solvedPostId"]) ? (int)$attrs["solvedPostId"] : 0;
	$isSolved = $solvedId && $solvedId === (int)$post["postId"];

	if ($isSolved) {
		$formatted["class"][] = "is-solved";
		array_unshift($formatted["info"], "<span class='solved-badge'><i class='icon-ok'></i> ".T("Solution")."</span>");
	}

	$canMark = ET::$session->userId && (
		ET::$session->isAdmin()
		|| (!empty($conversation["canModerate"]))
		|| (!empty($conversation["startMemberId"]) && $conversation["startMemberId"] == ET::$session->userId)
	);

	if ($canMark && empty($post["deleteMemberId"])) {
		$label = $isSolved ? T("Unmark solution") : T("Mark as solution");
		$formatted["controls"][] = "<a href='#' class='control-solved' data-postid='".(int)$post["postId"]."' data-cid='".(int)$conversation["conversationId"]."' title='$label'><i class='icon-ok-sign'></i></a>";
	}
}

public function action_conversationController_solve($sender)
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
$postId = (int)R("postId");
	$cid = (int)R("conversationId");
	if (!$postId || !$cid) {
		$sender->json("error", "invalid");
		$sender->renderJSON();
		return;
	}

	$conversation = ET::conversationModel()->getById($cid);
	if (!$conversation) {
		$sender->json("error", "notfound");
		$sender->renderJSON();
		return;
	}

	$canMark = ET::$session->isAdmin()
		|| !empty($conversation["canModerate"])
		|| $conversation["startMemberId"] == ET::$session->userId;
	if (!$canMark) {
		$sender->json("error", "denied");
		$sender->renderJSON();
		return;
	}

	$attrs = $this->getAttrs($conversation);
	if (!empty($attrs["solvedPostId"]) && (int)$attrs["solvedPostId"] === $postId) {
		unset($attrs["solvedPostId"]);
		$solved = false;
	} else {
		$attrs["solvedPostId"] = $postId;
		$solved = true;
	}
	$this->saveAttrs($cid, $attrs);

	$sender->json("solved", $solved);
	$sender->json("postId", $postId);
	$sender->renderJSON();
}

}
