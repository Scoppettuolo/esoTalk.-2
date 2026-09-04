<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Spoiler"] = array(
	"name" => "Spoiler",
	"description" => "Adds [spoiler] BBCode tags that hide content until clicked.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Spoiler extends ETPlugin {

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("spoiler.css"));
	$sender->addJSFile($this->resource("spoiler.js"));
}

public function handler_conversationController_getEditControls($sender, &$controls, $id)
{
	addToArrayString($controls, "spoiler",
		"<a href='javascript:Spoiler.insert(\"$id\");void(0)' title='".T("Spoiler")."' class='control-spoiler'><i class='icon-eye-close'></i></a>", 1);
}

public function handler_format_format($sender)
{
	if (!empty($sender->inline)) return;
	if (!is_string($sender->content) || $sender->content === "") return;

	$sender->content = preg_replace_callback(
		'/(?:\[spoiler(?:=([^\]]+))?\](.*?)\[\/spoiler\])/is',
		function ($m) {
			$title = !empty($m[1]) ? sanitizeHTML($m[1]) : T("Spoiler");
			$body = $m[2];
			return "<div class='spoiler'><button type='button' class='spoiler-toggle' aria-expanded='false'>".
				"<i class='icon-eye-close'></i> ".$title."</button>".
				"<div class='spoiler-body' hidden>".$body."</div></div>";
		},
		$sender->content
	);
}

}
