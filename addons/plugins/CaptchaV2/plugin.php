<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["CaptchaV2"] = array(
	"name" => "Captcha v2",
	"description" => "Adds a lightweight anti-bot challenge to registration.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_CaptchaV2 extends ETPlugin {

	public function handler_userController_initJoin($sender, $form)
	{
		$form->addSection("captcha", T("Verification"));
		$form->addField("captcha", "classicCaptcha", array($this, "fieldCaptcha"), array($this, "saveCaptcha"));
	}

	public function fieldCaptcha($form)
	{
		$challenge = ET::$session->getValue("classicCaptchaChallenge");
		$answer = ET::$session->getValue("classicCaptchaAnswer");
		if (!$challenge || $answer === null) {
			$a = random_int(2, 9);
			$b = random_int(2, 9);
			$challenge = "$a + $b";
			$answer = $a + $b;
			ET::$session->store("classicCaptchaChallenge", $challenge);
			ET::$session->store("classicCaptchaAnswer", (string)$answer);
		}
		return "<label class='classic-captcha-question' for='classicCaptcha'>".sanitizeHTML($challenge)." = ?</label>".
			$form->input("classicCaptcha", "text", array("autocomplete" => "off", "inputmode" => "numeric", "maxlength" => 3, "class" => "text"));
	}

	public function saveCaptcha($form, $key, &$data)
	{
		$expected = (string)ET::$session->getValue("classicCaptchaAnswer");
		$given = trim((string)$form->getValue($key));
		if ($expected === "" || !hash_equals($expected, $given)) {
			$form->error($key, T("The verification answer is incorrect."));
			return;
		}
		ET::$session->remove("classicCaptchaChallenge");
		ET::$session->remove("classicCaptchaAnswer");
	}
}
?>
