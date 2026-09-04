<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ConversationViews"] = array(
	"name" => "Conversation Views",
	"description" => "Tracks and displays how many times a conversation has been viewed.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_ConversationViews extends ETPlugin {

public function handler_conversationController_conversationIndex($sender, &$conversation, &$posts, &$startFrom, &$searchString)
{
	if (empty($conversation["conversationId"])) return;

	$attrs = array();
	if (!empty($conversation["attributes"])) {
		$attrs = is_array($conversation["attributes"])
			? $conversation["attributes"]
			: (esotalk_unserialize($conversation["attributes"]) ?: array());
	}
	if (!is_array($attrs)) $attrs = array();

	$views = isset($attrs["views"]) ? (int)$attrs["views"] : 0;

	// Count once per session per conversation
	$sessionKey = "viewed_".$conversation["conversationId"];
	if (empty($_SESSION[$sessionKey])) {
		$views++;
		$attrs["views"] = $views;
		ET::SQL()->update("conversation")
			->set("attributes", serialize($attrs))
			->where("conversationId", $conversation["conversationId"])
			->exec();
		$_SESSION[$sessionKey] = 1;
	}

	$conversation["viewCount"] = $views;
	$sender->data("viewCount", $views);
}

public function handler_conversationController_conversationIndexDefault($sender, &$conversation, &$controls, &$replyForm, &$replyControls)
{
	if (empty($conversation["viewCount"])) return;
	$n = (int)$conversation["viewCount"];
	$sender->addToHead("<meta name='et-views' content='$n'>");
	$label = $n === 1 ? T("1 view") : sprintf(T("%s views"), number_format($n));
	if (is_object($controls) && method_exists($controls, "add")) {
		$controls->add("views", "<span class='conversation-views'><i class='icon-eye-open'></i> ".sanitizeHTML($label)."</span>");
	}
}

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("views.css"));
	$sender->addJSFile($this->resource("views.js"));
}

}
