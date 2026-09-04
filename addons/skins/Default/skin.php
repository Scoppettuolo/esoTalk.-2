<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Default skin file (modernized for current core).
 *
 * @package esoTalk
 */

ET::$skinInfo["Default"] = array(
	"name" => "Default",
	"description" => "The default esoTalk skin (updated for modern core).",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETSkin_Default extends ETSkin {


/**
 * Initialize the skin.
 *
 * @param ETController $sender The page controller.
 * @return void
 */
public function handler_init($sender)
{
	// Prefer system fonts with Open Sans as enhancement
	$sender->addCSSFile("https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap");
	$sender->addCSSFile("core/skin/base.css", true);
	$sender->addCSSFile("core/skin/font-awesome.css", true);
	$sender->addCSSFile($this->resource("styles.css"), true);

	// If we're viewing from a mobile browser, add the mobile CSS and change the master view.
	if ($isMobile = isMobileBrowser()) {
		$sender->addCSSFile($this->resource("mobile.css"), true);
		$sender->masterView = "mobile.master";
		$sender->addToHead("<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover'>");
	}

	// The generated color sheet is public CSS, but older upgrades may not have it.
	// Create it before adding the link so Apache never rewrites a missing CSS file to HTML.
	$primary = C("skin.Default.primaryColor") ?: "#364159";
	$colorsFile = PATH_CONFIG."/colors.css";
	$needsColors = !file_exists($colorsFile);
	if (!$needsColors) {
		$existingColors = @file_get_contents($colorsFile);
		$existingTrimmed = is_string($existingColors) ? trim($existingColors) : "";
		$needsColors = $existingColors === false || $existingTrimmed === "" || strpos($existingColors, "{primary}") !== false || strpos($existingColors, "<html") !== false || strpos($existingColors, "/* Primary color */") === false;
	}
	if ($needsColors || !C("skin.Default.primaryColor")) $this->writeColors($primary);
	$sender->addCSSFile("config/colors.css", true);
}


/**
 * Write the skin's color configuration and CSS.
 *
 * @param string $primary The primary color.
 * @return void
 */
protected function writeColors($primary)
{
	ET::writeConfig(array("skin.Default.primaryColor" => $primary));

	$rgb = colorUnpack($primary, true);
	$hsl = rgb2hsl($rgb);

	$primary = colorPack(hsl2rgb($hsl), true);

	$hsl[1] = max(0, $hsl[1] - 0.3);
	$secondary = colorPack(hsl2rgb(array(2 => 0.6) + $hsl), true);
	$tertiary = colorPack(hsl2rgb(array(2 => 0.92) + $hsl), true);

	$template = $this->file("resources/colors.css", true);
	if (!is_file($template)) return;
	$css = file_get_contents($template);
	if ($css === false) return;
	$css = str_replace(array("{primary}", "{secondary}", "{tertiary}"), array($primary, $secondary, $tertiary), $css);
	file_put_contents(PATH_CONFIG."/colors.css", $css);
}


/**
 * Construct and process the settings form for this skin, and return the path to the view that should be
 * rendered.
 *
 * @param ETController $sender The page controller.
 * @return string The path to the settings view to render.
 */
public function settings($sender)
{
	// Set up the settings form.
	$form = ETFactory::make("form");
	$form->action = URL("admin/appearance");
	$form->setValue("primaryColor", C("skin.Default.primaryColor"));

	// If the form was submitted...
	if ($form->validPostBack("save")) {
		$this->writeColors($form->getValue("primaryColor"));

		$sender->message(T("message.changesSaved"), "success autoDismiss");
		$sender->redirect(URL("admin/appearance"));
	}

	$sender->data("skinSettingsForm", $form);
	$sender->addJSFile("core/js/lib/farbtastic.js");
	return $this->view("settings");
}


}
