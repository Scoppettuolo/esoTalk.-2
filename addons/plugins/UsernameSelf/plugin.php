<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["UsernameSelf"] = array(
	"name" => "Username Self",
	"description" => "Allows members to change their own username from general settings.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_UsernameSelf extends ETPlugin {

	public function handler_settingsController_initGeneral($sender, $form)
	{
		if (!ET::$session->user) return;
		$form->addSection("account", T("Account"));
		$form->setValue("username", ET::$session->user["username"]);
		$form->addField("account", "username", array($this, "fieldUsername"), array($this, "saveUsername"));
	}

	public function fieldUsername($form)
	{
		return $form->input("username", "text", array("maxlength" => 20, "autocomplete" => "username")).
			"<br><small class='help'>".T("Usernames must be 3–20 characters and unique.")."</small>";
	}

	public function saveUsername($form, $key, &$preferences)
	{
		if (!ET::$session->user) return;
		$new = trim((string)$form->getValue($key));
		$current = (string)ET::$session->user["username"];
		if ($new === $current) return;
		$error = ET::memberModel()->validateUsername($new, true);
		if ($error) {
			$form->error($key, T("message.".$error));
			return;
		}
		if (!ET::memberModel()->updateById(ET::$session->userId, array("username" => $new))) {
			$form->error($key, T("message.cannotSave"));
			return;
		}
		ET::$session->user["username"] = $new;
	}
}
?>
