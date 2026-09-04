<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Signatures"] = array(
	"name" => "Signatures",
	"description" => "Allows members to set a short signature shown under their posts.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Signatures extends ETPlugin {

public function handler_settingsController_initGeneral($sender, $form)
{
	$form->addSection("signature", T("Signature"));
	$form->setValue("signature", ET::$session->preference("signature", ""));
	$form->addField("signature", "signature", array($this, "fieldSignature"), array($this, "saveSignature"));
}

public function fieldSignature($form)
{
	return $form->input("signature", "textarea", array("class" => "signature-input")).
		"<small class='help'>".T("Shown under your posts. Keep it short.")."</small>";
}

public function saveSignature($form, $key, &$prefs)
{
	$sig = $form->getValue($key);
	if (!is_string($sig)) $sig = "";
	$sig = trim(strip_tags($sig));
	$prefs["signature"] = function_exists("mb_substr") ? mb_substr($sig, 0, 500) : substr($sig, 0, 500);
}

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("signatures.css"));
}

public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
{
	if (!empty($post["deleteMemberId"])) return;
	if (empty($post["preferences"]) || !is_array($post["preferences"])) return;
	if (empty($post["preferences"]["signature"])) return;
	if (empty($formatted["body"]) || !is_string($formatted["body"])) return;

	$sig = sanitizeHTML($post["preferences"]["signature"]);
	$formatted["body"] .= "<div class='post-signature'>".nl2br($sig)."</div>";
}

}
