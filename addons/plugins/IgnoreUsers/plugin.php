<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["IgnoreUsers"] = array(
	"name" => "Ignore Users",
	"description" => "Hide posts from members you ignore (stored in preferences).",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_IgnoreUsers extends ETPlugin {

protected function ignoredIds()
{
	if (!ET::$session->userId) return array();
	$raw = ET::$session->preference("ignoredMembers", "");
	if (is_array($raw)) return array_map("intval", $raw);
	if (!$raw) return array();
	$ids = array_filter(array_map("intval", explode(",", $raw)));
	return $ids;
}

public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
{
	if (!ET::$session->userId) return;
	$ids = $this->ignoredIds();
	if (!$ids || empty($post["memberId"])) return;
	if (in_array((int)$post["memberId"], $ids, true)) {
		$formatted["class"][] = "ignored-post";
		$formatted["body"] = "<p class='ignored-notice'>".T("Post hidden — you ignore this member.")."</p>";
		$formatted["controls"] = array();
	}
}

public function handler_memberController_init($sender)
{
	if (!ET::$session->userId) return;
	$sender->addJSFile($this->resource("ignore.js"));
}

public function handler_memberController_profile($sender, &$member, &$controls, &$panes)
{
	if (!ET::$session->userId || $member["memberId"] == ET::$session->userId) return;
	$ids = $this->ignoredIds();
	$is = in_array((int)$member["memberId"], $ids, true);
	$label = $is ? T("Unignore") : T("Ignore");
	$controls[] = "<a href='#' class='control-ignore-member' data-memberid='".(int)$member["memberId"]."' data-ignored='".($is?1:0)."'>".$label."</a>";
}

public function action_memberController_toggleIgnore($sender)
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
$id = (int)R("memberId");
	if (!$id || $id == ET::$session->userId) {
		$sender->json("error", "invalid");
		$sender->renderJSON();
		return;
	}
	$ids = $this->ignoredIds();
	if (in_array($id, $ids, true)) {
		$ids = array_values(array_diff($ids, array($id)));
		$ignored = false;
	} else {
		$ids[] = $id;
		$ignored = true;
	}
	ET::memberModel()->setPreferences(ET::$session->user, array("ignoredMembers" => implode(",", $ids)));
	ET::$session->user["preferences"]["ignoredMembers"] = implode(",", $ids);
	$sender->json("ignored", $ignored);
	$sender->renderJSON();
}

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("ignore.css"));
}

}
