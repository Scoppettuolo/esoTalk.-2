<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Mentions"] = array(
	"name" => "Mentions",
	"description" => "Highlights @username mentions in posts with a clear social-style link.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Mentions extends ETPlugin {

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("mentions.css"));
}

public function handler_format_format($sender)
{
	if (!is_string($sender->content) || $sender->content === "") return;

	// Highlight @Username that aren't already inside a link
	$sender->content = preg_replace_callback(
		'/(^|[\s>])@([a-zA-Z0-9_\-]{2,20})\b/',
		function ($m) {
			$name = $m[2];
			$url = URL("members/?search=".urlencode($name));
			return $m[1]."<a class='mention' href='".$url."'>@".sanitizeHTML($name)."</a>";
		},
		$sender->content
	);
}

}
