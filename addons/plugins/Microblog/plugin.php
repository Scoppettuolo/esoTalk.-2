<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Microblog"] = array(
	"name" => "Microblog",
	"description" => "Estados cortos de usuario (estilo microblog). Feed global, publicación rápida y bloque en el perfil.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Microblog extends ETPlugin {

public function setup($oldVersion = "")
{
	try {
		ET::$database->structure()
			->table("microblog")
			->column("id", "int(11) unsigned", false)
			->column("memberId", "int(11) unsigned", false)
			->column("content", "varchar(280)", false)
			->column("time", "int(11) unsigned", false)
			->key("id", "primary")
			->key("memberId")
			->key("time")
			->exec(false);
	} catch (Exception $e) {}
	return true;
}

public function boot()
{
	ETFactory::registerController("microblog", "ETPlugin_Microblog_Controller", $this->file("MicroblogController.class.php", true));
}

public function handler_init($sender)
{
	if (!is_object($sender) || !method_exists($sender, "addToMenu")) return;
	$sender->addToMenu("main", "microblog", "<a href='".URL("microblog")."'>".T("Microblog")."</a>");
}

public function handler_memberProfile($sender, &$member)
{
	try {
		$rows = ET::SQL()->select("*")->from("microblog")
			->where("memberId", (int)$member["memberId"])
			->orderBy("time DESC")
			->limit(15)
			->exec()->allRows();
		$sender->data("microblogPosts", $rows);
	} catch (Exception $e) {
		$sender->data("microblogPosts", array());
	}
}

public function handler_renderMemberProfile($sender)
{
	$posts = $sender->data("microblogPosts");
	if ($posts === null) return;

	echo "<div class='area microblog-profile' style='margin-top:20px'>";
	echo "<h3>".T("Status updates")."</h3>";

	// Quick post form if viewing own profile
	$member = $sender->data("member");
	if (ET::$session->userId && $member && (int)$member["memberId"] === (int)ET::$session->userId) {
		echo "<p><a href='".URL("microblog")."' class='button'>".T("Post a status")."</a></p>";
	}

	if (!$posts) {
		echo "<p class='help'>".T("No status updates yet.")."</p>";
	} else {
		echo "<ul class='list'>";
		foreach ($posts as $p) {
			echo "<li class='thing' style='padding:10px 0;border-bottom:1px solid #eee'>";
			echo "<div class='postBody'>".nl2br(sanitizeHTML($p["content"]))."</div>";
			echo "<div class='subText'>".relativeTime($p["time"], true)."</div>";
			echo "</li>";
		}
		echo "</ul>";
	}
	echo "</div>";
}

}
