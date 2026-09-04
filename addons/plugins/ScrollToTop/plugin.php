<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ScrollToTop"] = array(
	"name" => "Scroll to Top",
	"description" => "Floating button to return to the top of long pages.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_ScrollToTop extends ETPlugin {
public function handler_init($sender) {
	if (!is_object($sender)) return;
	if (method_exists($sender, "addCSSFile")) $sender->addCSSFile($this->resource("top.css"));
	if (method_exists($sender, "addJSFile")) $sender->addJSFile($this->resource("top.js"));
}
}
