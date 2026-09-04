<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Attachments"] = array(
	"name" => "Attachments",
	"description" => "Upload images into posts (JPEG/PNG/GIF/WebP). Inserts as [img] URL.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Attachments extends ETPlugin {

public function handler_conversationController_renderBefore($sender)
{
	$sender->addJSFile($this->resource("upload.js"));
	$sender->addCSSFile($this->resource("upload.css"));
}

public function handler_conversationController_getEditControls($sender, &$controls, $id)
{
	addToArrayString($controls, "attach",
		"<a href='javascript:void(0)' class='control-attach' data-area='$id' title='".T("Upload image")."'><i class='icon-paper-clip'></i></a>",
		0
	);
}

public function action_conversationController_uploadImage($sender)
{
	header("Content-Type: application/json; charset=utf-8");
	if (!ET::$session->userId) {
		$sender->json("error", "login");
		$sender->renderJSON();
		return;
	}
	if (!$sender->validateToken()) {
		$sender->json("error", "token");
		$sender->renderJSON();
		return;
	}
	if (empty($_FILES["image"])) {
		$sender->json("error", "nofile");
		$sender->renderJSON();
		return;
	}
	$file = $_FILES["image"];
	if ($file["error"] !== UPLOAD_ERR_OK) {
		$sender->json("error", "upload");
		$sender->renderJSON();
		return;
	}
	if ($file["size"] > 5 * 1024 * 1024) {
		$sender->json("error", "toolarge");
		$sender->renderJSON();
		return;
	}
	$info = @getimagesize($file["tmp_name"]);
	$allowed = array(IMAGETYPE_JPEG => "jpg", IMAGETYPE_PNG => "png", IMAGETYPE_GIF => "gif");
	if (defined("IMAGETYPE_WEBP")) $allowed[IMAGETYPE_WEBP] = "webp";
	if (!$info || !isset($allowed[$info[2]])) {
		$sender->json("error", "type");
		$sender->renderJSON();
		return;
	}
	// Extra MIME check when finfo is available
	if (function_exists("finfo_open")) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = $finfo ? finfo_file($finfo, $file["tmp_name"]) : "";
		if ($finfo) finfo_close($finfo);
		$okMimes = array("image/jpeg", "image/png", "image/gif", "image/webp");
		if ($mime && !in_array($mime, $okMimes, true)) {
			$sender->json("error", "type");
			$sender->renderJSON();
			return;
		}
	}
	$dir = PATH_ROOT."/uploads/posts";
	if (!is_dir($dir)) @mkdir($dir, 0755, true);
	@file_put_contents($dir."/index.html", "");
	$name = date("Ymd")."_".bin2hex(random_bytes(8)).".".$allowed[$info[2]];
	$dest = $dir."/".$name;
	if (!@move_uploaded_file($file["tmp_name"], $dest)) {
		$sender->json("error", "save");
		$sender->renderJSON();
		return;
	}
	$url = getWebPath("uploads/posts/".$name);
	$sender->json("url", $url);
	$sender->json("bbcode", "[img]".$url."[/img]");
	$sender->renderJSON();
}

}
