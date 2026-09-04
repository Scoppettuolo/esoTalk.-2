<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["WordFilter"] = array(
	"name" => "Word Filter",
	"description" => "Replace or mask banned words in posts. Configure a list of words in plugin settings.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_WordFilter extends ETPlugin {

/** @var array|null */
protected $words = null;

protected function wordList()
{
	if ($this->words !== null) return $this->words;
	$raw = (string) C("plugin.WordFilter.words", "");
	$list = preg_split('/[\r\n,]+/', $raw);
	$out = array();
	foreach ($list as $w) {
		$w = trim($w);
		if ($w !== "") $out[] = $w;
	}
	$this->words = $out;
	return $out;
}

public function handler_format_format($sender)
{
	$words = $this->wordList();
	if (!$words) return;

	$replacement = C("plugin.WordFilter.replacement", "***");
	$content = $sender->content;

	foreach ($words as $word) {
		$pattern = '/(?<![\p{L}\p{N}_])'.preg_quote($word, '/').'(?![\p{L}\p{N}_])/iu';
		$content = preg_replace($pattern, $replacement, $content);
	}
	$sender->content = $content;
}

public function settings($sender)
{
	$form = ETFactory::make("form");
	$form->action = URL("admin/plugins/settings/WordFilter");
	$form->setValue("words", C("plugin.WordFilter.words", ""));
	$form->setValue("replacement", C("plugin.WordFilter.replacement", "***"));

	if ($form->validPostBack("save")) {
		ET::writeConfig(array(
			"plugin.WordFilter.words" => (string)$form->getValue("words"),
			"plugin.WordFilter.replacement" => mb_substr((string)$form->getValue("replacement"), 0, 32)
		));
		$sender->message(T("message.changesSaved"), "success autoDismiss");
		$sender->redirect(URL("admin/plugins"));
	}

	$sender->data("wordFilterForm", $form);
	return $this->view("settings");
}

}
