<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["DarkMode"] = array(
	"name" => "Dark Mode",
	"description" => "Toggle light/dark theme. Preference is saved for members and in localStorage for guests.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_DarkMode extends ETPlugin {

public function handler_init($sender)
{
	if (!is_object($sender) || !method_exists($sender, "addCSSFile")) return;
	$pref = ET::$session->userId ? ET::$session->preference("darkMode", "") : "";
	$prefJSON = json_encode($pref);
	/* Apply the theme in the head so the browser does not paint dark-system form
	 * controls while the forum is explicitly set to light. */
	$sender->addToHead("<script>window.ET_DARK_PREF=$prefJSON;(function(){var p=window.ET_DARK_PREF,on=p==='dark';try{var s=localStorage.getItem('et_dark');if(p==='auto'||!p){if(s==='1')on=true;else if(s==='0')on=false;else on=!!(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);}}catch(e){if(p==='auto'||!p)on=!!(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);}var r=document.documentElement;r.classList.toggle('et-dark',on);r.setAttribute('data-theme',on?'dark':'light');r.style.colorScheme=on?'dark':'light';})();</script>");
	$sender->addCSSFile($this->resource("dark.css"));
	$sender->addJSFile($this->resource("dark.js"));

	if (method_exists($sender, "addToMenu")) {
		$sender->addToMenu("user", "darkMode",
			"<a href='#' id='darkModeToggle' class='link-darkMode' title='Dark mode'><i class='icon-adjust'></i> <span>".T("Dark mode")."</span></a>",
			array("before" => "settings")
		);
	}
}

public function handler_settingsController_initGeneral($sender, $form)
{
	$form->addSection("appearance", T("Appearance"));
	$form->setValue("darkMode", ET::$session->preference("darkMode", "auto"));
	$form->addField("appearance", "darkMode", array($this, "fieldDark"), array($this, "saveDark"));
}

public function fieldDark($form)
{
	$v = $form->getValue("darkMode") ?: "auto";
	$html = "<select name='darkMode' class='input'>";
	foreach (array("auto" => T("System default"), "light" => T("Light"), "dark" => T("Dark")) as $k => $label) {
		$sel = $k === $v ? " selected" : "";
		$html .= "<option value='$k'$sel>$label</option>";
	}
	$html .= "</select>";
	return $html;
}

public function saveDark($form, $key, &$prefs)
{
	$v = $form->getValue($key);
	if (!in_array($v, array("auto", "light", "dark"), true)) $v = "auto";
	$prefs["darkMode"] = $v;
}

}
