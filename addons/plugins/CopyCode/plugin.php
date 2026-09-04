<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;
ET::$pluginInfo["CopyCode"] = array(
	"name" => "Copy Code",
	"description" => "Adds a copy button to code blocks in posts.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);
class ETPlugin_CopyCode extends ETPlugin {
public function handler_conversationController_renderBefore($sender) {
	$sender->addCSSFile($this->resource("copy.css"));
	$sender->addJSFile($this->resource("copy.js"));
}
}
