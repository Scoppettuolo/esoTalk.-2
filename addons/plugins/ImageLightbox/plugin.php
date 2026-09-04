<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ImageLightbox"] = array(
	"name" => "Image Lightbox",
	"description" => "Click images in posts to view them larger (lightweight, no external deps).",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_ImageLightbox extends ETPlugin {

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("lightbox.css"));
	$sender->addJSFile($this->resource("lightbox.js"));
}

}
