<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["PostNumbers"] = array(
	"name" => "Post Numbers",
	"description" => "Show #1, #2, … on each post (Flarum-style post index).",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_PostNumbers extends ETPlugin {

protected $index = 0;
protected $startFrom = 0;

public function handler_conversationController_conversationIndex($sender, &$conversation, &$posts, &$startFrom, &$searchString)
{
	$this->startFrom = (int)$startFrom;
	$this->index = 0;
}

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("numbers.css"));
}

public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
{
	if (!empty($post["deleteMemberId"])) return;
	$this->index++;
	$num = $this->startFrom + $this->index;
	$formatted["info"][] = "<a href='".URL(postURL($post["postId"]))."' class='post-number' title='Post #$num'>#$num</a>";
}

}
