<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Pages"] = array(
	"name" => "Pages",
	"description" => "Static pages (About, Rules…) editable from admin, optional main menu links.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Pages extends ETPlugin {

public function setup($oldVersion = "")
{
	try {
		ET::$database->structure()
			->table("page")
			->column("pageId", "int(11) unsigned", false)
			->column("slug", "varchar(63)", false)
			->column("title", "varchar(255)", false)
			->column("content", "mediumtext")
			->column("menu", "tinyint(1)", 1)
			->column("position", "int(11)", 0)
			->key("pageId", "primary")
			->key("slug", "unique")
			->exec(false);
	} catch (Exception $e) {}
	return true;
}

public function boot()
{
	ETFactory::registerController("page", "ETPlugin_Pages_Controller", $this->file("PagesController.class.php", true));
}

public function handler_init($sender)
{
	if (!is_object($sender) || !method_exists($sender, "addToMenu")) return;
	try {
		$pages = ET::SQL()->select("*")->from("page")->where("menu", 1)->orderBy("position ASC")->exec()->allRows();
		if (!$pages) return;
		foreach ($pages as $p) {
			$sender->addToMenu("main", "page-".$p["slug"],
				"<a href='".URL("page/".$p["slug"])."'>".sanitizeHTML($p["title"])."</a>");
		}
	} catch (Exception $e) {}
}

public function settings($sender)
{
	$this->setup();
	$form = ETFactory::make("form");
	$form->action = URL("admin/plugins/settings/Pages");

	if ($form->validPostBack("savePage")) {
		$slug = slug($form->getValue("slug") ?: $form->getValue("title"));
		$title = trim((string)$form->getValue("title"));
		$content = (string)$form->getValue("content");
		$menu = $form->getValue("menu") ? 1 : 0;
		$id = (int)$form->getValue("pageId");
		if ($title && $slug) {
			$data = array("slug" => $slug, "title" => $title, "content" => $content, "menu" => $menu);
			if ($id) ET::SQL()->update("page")->set($data)->where("pageId", $id)->exec();
			else ET::SQL()->insert("page")->set($data + array("position" => 0))->exec();
			$sender->message(T("message.changesSaved"), "success autoDismiss");
		}
		$sender->redirect(URL("admin/plugins/settings/Pages"));
		return;
	}
	if ($form->validPostBack("deletePage")) {
		$id = (int)$form->getValue("pageId");
		if ($id) ET::SQL()->delete()->from("page")->where("pageId", $id)->exec();
		$sender->redirect(URL("admin/plugins/settings/Pages"));
		return;
	}

	$pages = array();
	try { $pages = ET::SQL()->select("*")->from("page")->orderBy("position ASC")->exec()->allRows(); }
	catch (Exception $e) {}
	$sender->data("pages", $pages);
	$sender->data("pageForm", $form);
	return $this->view("settings");
}

}
