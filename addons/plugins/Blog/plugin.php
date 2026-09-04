<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Blog"] = array(
	"name" => "Blog",
	"description" => "Full blog section: public listing with pagination, individual posts, drafts, excerpts, and admin management (create/edit/delete/publish).",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Blog extends ETPlugin {

public function setup($oldVersion = "")
{
	try {
		ET::$database->structure()
			->table("blog_post")
			->column("postId", "int(11) unsigned", false)
			->column("title", "varchar(255)", false)
			->column("slug", "varchar(63)", false)
			->column("content", "mediumtext")
			->column("excerpt", "varchar(500)")
			->column("time", "int(11) unsigned", false)
			->column("updateTime", "int(11) unsigned")
			->column("memberId", "int(11) unsigned")
			->column("published", "tinyint(1)", 1)
			->key("postId", "primary")
			->key("slug", "unique")
			->key("time")
			->key("published")
			->exec(false);
	} catch (Exception $e) {}
	return true;
}

public function boot()
{
	ETFactory::registerController("blog", "ETPlugin_Blog_Controller", $this->file("BlogController.class.php", true));
}

public function handler_init($sender)
{
	if (!is_object($sender) || !method_exists($sender, "addToMenu")) return;
	$sender->addToMenu("main", "blog", "<a href='".URL("blog")."'>".T("Blog")."</a>");
	if (method_exists($sender, "addCSSFile")) {
		$sender->addCSSFile($this->resource("blog.css"));
	}
}

public function settings($sender)
{
	$this->setup();
	return $this->view("settings");
}

}
