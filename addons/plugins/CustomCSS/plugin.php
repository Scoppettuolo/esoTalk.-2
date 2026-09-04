<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["CustomCSS"] = array(
	"name" => "Custom CSS / JS",
	"description" => "Inject custom CSS and JavaScript into every page (for theming and analytics snippets).",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_CustomCSS extends ETPlugin {

public function handler_init($sender)
{
	$css = C("plugin.CustomCSS.css");
	$js = C("plugin.CustomCSS.js");
	if ($css) {
		$sender->addToHead("<style type='text/css' id='custom-css'>\n".$css."\n</style>");
	}
	if ($js) {
		$sender->addToHead("<script>\n".$js."\n</script>");
	}
}

public function settings($sender)
{
	$form = ETFactory::make("form");
	$form->action = URL("admin/plugins/settings/CustomCSS");
	$form->setValue("css", C("plugin.CustomCSS.css", ""));
	$form->setValue("js", C("plugin.CustomCSS.js", ""));

	if ($form->validPostBack("save")) {
		ET::writeConfig(array(
			"plugin.CustomCSS.css" => $form->getValue("css"),
			"plugin.CustomCSS.js" => $form->getValue("js")
		));
		$sender->message(T("message.changesSaved"), "success autoDismiss");
		$sender->redirect(URL("admin/plugins"));
	}

	$sender->data("customCSSForm", $form);
	return $this->view("settings");
}

}
