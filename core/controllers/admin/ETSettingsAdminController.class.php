<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * This controller handles the settings section of the admin CP. It sets up and processes the settings form,
 * including uploading a header image.
 *
 * @package esoTalk
 */
class ETSettingsAdminController extends ETAdminController {


/**
 * Show and process the settings form.
 *
 * @return void
 */
public function action_index()
{
	// Make an array of languages for the default forum language select.
	$languages = array();
	foreach (ET::getLanguages() as $v) {
		$languages[$v] = ET::$languageInfo[$v]["name"];
	}

	// Get a list of member groups.
	$groups = ET::groupModel()->getAll();

	// Set up the form.
	$form = ETFactory::make("form");
	$form->action = URL("admin/settings");

		// Set the default values for the forum inputs.
		$form->setValue("forumTitle", C("esoTalk.forumTitle"));
		$form->setValue("forumDescription", C("esoTalk.forumDescription", ""));
		$form->setValue("showDescription", (bool)C("esoTalk.showDescription", false));
		$form->setValue("metaDescription", (bool)C("esoTalk.metaDescription", false));
		$form->setValue("classicConversationList", (bool)C("esoTalk.classicConversationList", false));
		$form->setValue("language", C("esoTalk.language"));
		$logo = C("esoTalk.forumLogo", false);
		$form->setValue("forumHeader", C("esoTalk.forumHeader", $logo ? "image" : "title"));
		$form->setValue("forumLogoSource", !$logo ? "default" : (preg_match("~^https?://~i", $logo) ? "url" : "upload"));
		$form->setValue("forumLogoURL", preg_match("~^https?://~i", (string)$logo) ? $logo : "");
		$icon = C("esoTalk.forumIcon", false);
		$form->setValue("forumIconSource", !$icon ? "default" : (preg_match("~^https?://~i", $icon) ? "url" : "upload"));
		$form->setValue("forumIconURL", preg_match("~^https?://~i", (string)$icon) ? $icon : "");
		$favicon = C("esoTalk.shortcutIcon", false);
		$form->setValue("shortcutIconSource", !$favicon ? "default" : (preg_match("~^https?://~i", $favicon) ? "url" : "upload"));
		$form->setValue("shortcutIconURL", preg_match("~^https?://~i", (string)$favicon) ? $favicon : "");
		$form->setValue("defaultRoute", C("esoTalk.defaultRoute"));
	$form->setValue("forumVisibleToGuests", C("esoTalk.visibleToGuests"));
	$form->setValue("memberListVisibleToGuests", C("esoTalk.members.visibleToGuests"));
	$form->setValue("registrationOpen", C("esoTalk.registration.open"));
	$form->setValue("requireConfirmation", C("esoTalk.registration.requireConfirmation"));
	$form->setValue("messageDisplayTime", max(1, (int)C("esoTalk.messageDisplayTime", 20)));
	$form->setValue("tagCloudLimit", max(0, min(200, (int)C("esoTalk.search.tagCloudLimit", 30))));
	$form->setValue("showAvatarThumbnails", (bool)C("esoTalk.avatars.showThumbnails", true));

	$c = C("esoTalk.conversation.editPostTimeLimit");
	if ($c === -1) $form->setValue("editPostMode", "forever");
	elseif ($c === "reply") $form->setValue("editPostMode", "reply");
	else {
		$form->setValue("editPostMode", "custom");
		$form->setValue("editPostTimeLimit", $c);
	}
	

	// If the save button was clicked...
	if ($form->validPostBack("save")) {

			// Construct an array of config options to write.
			$config = array(
				"esoTalk.forumTitle" => $form->getValue("forumTitle"),
				"esoTalk.forumHeader" => $form->getValue("forumHeader") == "image" ? "image" : "title",
				"esoTalk.forumDescription" => trim((string)$form->getValue("forumDescription")),
				"esoTalk.showDescription" => (bool)$form->getValue("showDescription"),
				"esoTalk.metaDescription" => (bool)$form->getValue("metaDescription"),
				"esoTalk.classicConversationList" => (bool)$form->getValue("classicConversationList"),
				"esoTalk.language" => $form->getValue("language"),
				"esoTalk.forumLogo" => C("esoTalk.forumLogo", false),
				"esoTalk.forumIcon" => C("esoTalk.forumIcon", false),
				"esoTalk.shortcutIcon" => C("esoTalk.shortcutIcon", false),
				"esoTalk.defaultRoute" => $form->getValue("defaultRoute"),
			"esoTalk.visibleToGuests" => $form->getValue("forumVisibleToGuests"),
			"esoTalk.members.visibleToGuests" => $form->getValue("forumVisibleToGuests") and $form->getValue("memberListVisibleToGuests"),
			"esoTalk.registration.open" => $form->getValue("registrationOpen"),
				"esoTalk.registration.requireConfirmation" => in_array($v = $form->getValue("requireConfirmation"), array(false, "email", "approval", "email+approval"), true) ? $v : false,
				"esoTalk.messageDisplayTime" => max(1, (int)$form->getValue("messageDisplayTime")),
				"esoTalk.search.tagCloudLimit" => max(0, min(200, (int)$form->getValue("tagCloudLimit"))),
				"esoTalk.avatars.showThumbnails" => (bool)$form->getValue("showAvatarThumbnails"),
			);

		switch ($form->getValue("editPostMode")) {
			case "forever": $config["esoTalk.conversation.editPostTimeLimit"] = -1; break;
			case "reply": $config["esoTalk.conversation.editPostTimeLimit"] = "reply"; break;
			case "custom": $config["esoTalk.conversation.editPostTimeLimit"] = (int)$form->getValue("editPostTimeLimit"); break;
		}

				// Make sure a forum title is present.
				if (!strlen($config["esoTalk.forumTitle"])) $form->error("forumTitle", T("message.empty"));
				if ((int)$form->getValue("messageDisplayTime") < 1) $form->error("messageDisplayTime", T("message.invalidMessageDisplayTime"));

			// Preserve the existing logo when no replacement file/URL was supplied.
			if ($form->getValue("forumHeader") == "image") {
				$config["esoTalk.forumLogo"] = $this->saveImageSetting($form, "forumLogoSource", "forumHeaderImage", "forumLogoURL", $config["esoTalk.forumLogo"], PATH_UPLOADS."/logo", 500, 40, "max");
			} else {
				$this->removeLocalAsset($config["esoTalk.forumLogo"]);
				$config["esoTalk.forumLogo"] = false;
			}
			$config["esoTalk.forumIcon"] = $this->saveImageSetting($form, "forumIconSource", "forumIconImage", "forumIconURL", $config["esoTalk.forumIcon"], PATH_UPLOADS."/forum-icon", 256, 256, "crop");
			$config["esoTalk.shortcutIcon"] = $this->saveImageSetting($form, "shortcutIconSource", "shortcutIconImage", "shortcutIconURL", $config["esoTalk.shortcutIcon"], PATH_UPLOADS."/forum-favicon", 64, 64, "crop");

			if (!$form->errorCount()) {
				ET::writeConfig($config);
			$this->message(T("message.changesSaved"), "success autoDismiss");
			$this->redirect(URL("admin/settings"));
		}

	}

	$this->data("form", $form);
	$this->data("languages", $languages);
	$this->data("groups", $groups);
	$this->title = T("Forum Settings");
	$this->render("admin/settings");
}


/**
 * Upload a header image.
 *
 * @return void
 */
protected function saveImageSetting($form, $sourceField, $uploadField, $urlField, $current, $destination, $width, $height, $sizeMode)
{
	$source = $form->getValue($sourceField);
	if ($source === "default" || !$source) {
		$this->removeLocalAsset($current);
		return false;
	}

	if ($source === "url") {
		$url = trim((string)$form->getValue($urlField));
		if (!preg_match("~^https?://[^\\s]+$~i", $url)) {
			$form->error($urlField, T("message.invalidImageURL"));
			return $current;
		}
		if ($current !== $url) $this->removeLocalAsset($current);
		return $url;
	}

	// An unchanged upload radio with no new file must keep the current asset.
	if (!isset($_FILES[$uploadField]) || (int)@$_FILES[$uploadField]["error"] === UPLOAD_ERR_NO_FILE) return $current;

	try {
		$file = ET::uploader()->getUploadedFile($uploadField, array("image/jpeg", "image/png", "image/gif", "image/pjpeg", "image/x-png"));
		$saved = ET::uploader()->saveAsImage($file, $destination, $width, $height, $sizeMode);
		$relative = str_replace(PATH_UPLOADS, "uploads", $saved);
		if ($current !== $relative) $this->removeLocalAsset($current);
		return $relative;
	} catch (Exception $e) {
		$form->error($uploadField, $e->getMessage());
		return $current;
	}
}

protected function removeLocalAsset($path)
{
	if (!$path || strpos((string)$path, "://") !== false) return;
	$file = strpos((string)$path, PATH_ROOT) === 0 ? $path : PATH_ROOT."/".ltrim((string)$path, "/\\");
	if (is_file($file)) @unlink($file);
}

}
