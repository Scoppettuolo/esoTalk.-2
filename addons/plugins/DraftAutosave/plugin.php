<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["DraftAutosave"] = array(
	"name" => "Draft Autosave",
	"description" => "Saves reply drafts in the browser (localStorage) so you don't lose text if the page reloads.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_DraftAutosave extends ETPlugin {

public function handler_conversationController_renderBefore($sender)
{
	$sender->addJSFile($this->resource("autosave.js"));
}

public function handler_conversationsController_renderBefore($sender)
{
	// Also on conversation start page when embedded
	$sender->addJSFile($this->resource("autosave.js"));
}

}
