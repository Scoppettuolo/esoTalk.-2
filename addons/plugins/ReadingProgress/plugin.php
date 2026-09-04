<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ReadingProgress"] = array(
	"name" => "Reading Progress",
	"description" => "Thin progress bar at the top while scrolling a conversation (modern UX).",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_ReadingProgress extends ETPlugin {

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("progress.css"));
	$sender->addJSFile($this->resource("progress.js"));
}

}
