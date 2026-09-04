<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ShareLinks"] = array(
	"name" => "Share Links",
	"description" => "Adds share buttons (Twitter/X, Facebook, copy link) on conversation pages.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_ShareLinks extends ETPlugin {

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("share.css"));
	$sender->addJSFile($this->resource("share.js"));
}

public function handler_conversationController_conversationIndexDefault($sender, &$conversation, &$controls, &$replyForm, &$replyControls)
{
	if (empty($conversation["conversationId"])) return;
	$url = URL(conversationURL($conversation["conversationId"], $conversation["title"]), true);
	$title = !empty($conversation["title"]) ? $conversation["title"] : "Conversation";
	$encUrl = rawurlencode($url);
	$encTitle = rawurlencode($title);

	$html = "<span class='share-links' data-url='".sanitizeHTML($url)."'>".
		"<a class='share-x' href='https://twitter.com/intent/tweet?url=$encUrl&text=$encTitle' target='_blank' rel='noopener noreferrer' title='X / Twitter'><i class='icon-twitter'></i></a> ".
		"<a class='share-fb' href='https://www.facebook.com/sharer/sharer.php?u=$encUrl' target='_blank' rel='noopener noreferrer' title='Facebook'><i class='icon-facebook'></i></a> ".
		"<a class='share-copy' href='#' title='".T("Copy link")."'><i class='icon-link'></i></a>".
		"</span>";

	if (is_object($controls) && method_exists($controls, "add")) {
		$controls->add("share", $html);
	}
}

}
