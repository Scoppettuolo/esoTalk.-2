<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
// Plastic-inspired skin adapted from esoTalk v1 for the modern v2 markup.
if (!defined("IN_ESOTALK")) exit;

ET::$skinInfo["Plastic"] = array(
    "name" => "Plastic",
    "description" => "The classic blue plastic esoTalk skin adapted for esoTalk 2.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETSkin_Plastic extends ETSkin {
    public function handler_init($sender)
    {
        $sender->addCSSFile("core/skin/base.css", true);
        $sender->addCSSFile("core/skin/font-awesome.css", true);
        // Reuse only the v2 structural layout; this skin overrides its visual tokens below.
        $sender->addCSSFile("addons/skins/Default/resources/styles.css", true);
        $sender->addCSSFile($this->resource("styles.css"), true);
        if (isMobileBrowser()) {
            $sender->addCSSFile($this->resource("mobile.css"), true);
            $sender->masterView = "mobile.master";
            $sender->addToHead("<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover'>");
        }
        $sender->addCSSFile("config/colors.css", true);
    }

    public function settings($sender)
    {
        return false;
    }
}
