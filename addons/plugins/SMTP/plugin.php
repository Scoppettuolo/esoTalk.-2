<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["SMTP"] = array(
	"name" => "SMTP",
	"description" => "Allows mail to be sent via an SMTP server. Based on work by Raphael Michel <webmaster@raphaelmichel.de>",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_SMTP extends ETPlugin {

	public function handler_sendEmailBefore($mail, &$to, &$subject, &$body)
	{
		if (!C("plugin.SMTP.server")) return;

		// PHPMailer 6 API
		$mail->isSMTP();
		$mail->SMTPAuth = true;
		$auth = C("plugin.SMTP.auth");
		if ($auth) $mail->SMTPSecure = $auth; // ssl / tls
		$mail->Host = C("plugin.SMTP.server");
		$mail->Port = (int)C("plugin.SMTP.port") ?: 587;
		$mail->Username = C("plugin.SMTP.username");
		$mail->Password = C("plugin.SMTP.password");
		$mail->SMTPAutoTLS = true;
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
		$form->action = URL("admin/plugins/settings/SMTP");
		$form->setValue("server", C("plugin.SMTP.server"));
		$form->setValue("username", C("plugin.SMTP.username"));
		$form->setValue("password", C("plugin.SMTP.password"));
		$form->setValue("port", C("plugin.SMTP.port"));
		$form->setValue("auth", C("plugin.SMTP.auth"));

		// If the form was submitted...
		if ($form->validPostBack("smtpSave")) {

			// Construct an array of config options to write.
			$config = array();
			$config["plugin.SMTP.server"] = $form->getValue("server");
			$config["plugin.SMTP.username"] = $form->getValue("username");
			$config["plugin.SMTP.password"] = $form->getValue("password");
			$config["plugin.SMTP.port"] = max(1, min(65535, (int)$form->getValue("port")));
			$config["plugin.SMTP.auth"] = $form->getValue("auth");

			if (!$form->errorCount()) {

				// Write the config file.
				ET::writeConfig($config);

				$sender->message(T("message.changesSaved"), "success autoDismiss");
				$sender->redirect(URL("admin/plugins"));

			}
		}

		$sender->data("smtpSettingsForm", $form);
		return $this->view("settings");
	}
}
