<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["AutoClose"] = array(
	"name" => "Auto Close",
	"description" => "Automatically locks conversations after a period of inactivity (default 90 days).",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_AutoClose extends ETPlugin {

public function handler_conversationController_conversationIndex($sender, &$conversation, &$posts, &$startFrom, &$searchString)
{
	$this->maybeLock($conversation);
}

protected function maybeLock(&$conversation)
{
	if (empty($conversation["conversationId"]) || !empty($conversation["locked"])) return;

	$days = (int) C("plugin.AutoClose.days", 90);
	if ($days <= 0) return;

	$last = !empty($conversation["lastPostTime"]) ? (int)$conversation["lastPostTime"] : (int)$conversation["startTime"];
	if ($last < time() - ($days * 86400)) {
		ET::SQL()->update("conversation")
			->set("locked", 1)
			->where("conversationId", $conversation["conversationId"])
			->exec();
		$conversation["locked"] = 1;
	}
}

public function settings($sender)
{
	$form = ETFactory::make("form");
	$form->action = URL("admin/plugins/settings/AutoClose");
	$form->setValue("days", C("plugin.AutoClose.days", 90));

	if ($form->validPostBack("save")) {
		$days = max(0, (int)$form->getValue("days"));
		ET::writeConfig(array("plugin.AutoClose.days" => $days));
		$sender->message(T("message.changesSaved"), "success autoDismiss");
		$sender->redirect(URL("admin/plugins"));
	}

	$sender->data("autoCloseForm", $form);
	return $this->view("settings");
}

}
