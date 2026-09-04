<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Gravatar"] = array(
	"name" => "Gravatar",
	"description" => "Use Gravatar as fallback when a member has no uploaded avatar. Configurable size, rating and default style.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

/**
 * Enables Gravatar fallback via core avatar() helper (C("plugin.Gravatar.enabled")).
 * Also stores size/rating/default for optional use by skins/core.
 */
class ETPlugin_Gravatar extends ETPlugin {

public function setup($oldVersion = "")
{
	$config = array("plugin.Gravatar.enabled" => true);
	if (C("plugin.Gravatar.size") === null) $config["plugin.Gravatar.size"] = 80;
	if (C("plugin.Gravatar.rating") === null) $config["plugin.Gravatar.rating"] = "g";
	if (C("plugin.Gravatar.default") === null) $config["plugin.Gravatar.default"] = "identicon";
	ET::writeConfig($config);
	return true;
}

public function disable()
{
	ET::writeConfig(array("plugin.Gravatar.enabled" => false));
}

public function settings($sender)
{
	$form = ETFactory::make("form");
	$form->action = URL("admin/plugins/settings/Gravatar");
	$form->setValue("size", C("plugin.Gravatar.size", 80));
	$form->setValue("rating", C("plugin.Gravatar.rating", "g"));
	$form->setValue("default", C("plugin.Gravatar.default", "identicon"));

	if ($form->validPostBack("save")) {
		$size = max(16, min(512, (int)$form->getValue("size")));
		$rating = strtolower((string)$form->getValue("rating"));
		if (!in_array($rating, array("g", "pg", "r", "x"), true)) $rating = "g";
		$default = preg_replace('/[^a-z0-9]/', '', strtolower((string)$form->getValue("default")));
		if ($default === "") $default = "identicon";
		ET::writeConfig(array(
			"plugin.Gravatar.enabled" => true,
			"plugin.Gravatar.size" => $size,
			"plugin.Gravatar.rating" => $rating,
			"plugin.Gravatar.default" => $default
		));
		$sender->message(T("message.changesSaved"), "success autoDismiss");
		$sender->redirect(URL("admin/plugins"));
	}

	$sender->data("gravatarForm", $form);
	return $this->view("settings");
}

}
