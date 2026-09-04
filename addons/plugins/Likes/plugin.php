<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["Likes"] = array(
	"name" => "Likes",
	"description" => "Allow members to like posts. Shows a like count under each post.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_Likes extends ETPlugin {

/** @var bool|null */
protected $tableReady = null;

public function setup($oldVersion = "")
{
	try {
		$structure = ET::$database->structure();
		$structure
			->table("post_like")
			->column("postId", "int(11) unsigned", false)
			->column("memberId", "int(11) unsigned", false)
			->column("time", "int(11) unsigned", false)
			->key(array("postId", "memberId"), "primary")
			->key("memberId")
			->exec(false);
		$this->tableReady = true;
	} catch (Exception $e) {
		$this->tableReady = false;
	}
	return true;
}

protected function isTableReady()
{
	if ($this->tableReady === null) {
		try {
			ET::SQL()->select("1")->from("post_like")->limit(1)->exec();
			$this->tableReady = true;
		} catch (Exception $e) {
			$this->tableReady = false;
		}
	}
	return $this->tableReady;
}

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("likes.css"));
	$sender->addJSFile($this->resource("likes.js"));
}

public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
{
	if (!empty($post["deleteMemberId"])) return;
	if (!$this->isTableReady()) return;

	$count = isset($post["likeCount"]) ? (int)$post["likeCount"] : 0;
	$liked = !empty($post["likedByMe"]);

	$btnClass = $liked ? "liked" : "";
	$label = $liked ? T("Unlike") : T("Like");
	$postId = (int)$post["postId"];
	$controls = "<a href='#' class='control-like $btnClass' data-postid='$postId' title='".sanitizeHTML($label)."'>".
		"<i class='icon-thumbs-up'></i> <span class='like-count'>".($count > 0 ? $count : "")."</span></a>";

	array_unshift($formatted["controls"], $controls);
}

/**
 * Attach like counts after posts are loaded (avoids breaking the main query if table is missing).
 */
public function handler_postModel_getPostsAfter($sender, &$posts)
{
	if (!$posts || !$this->isTableReady()) return;

	$ids = array();
	foreach ($posts as $p) {
		if (!empty($p["postId"])) $ids[] = (int)$p["postId"];
	}
	if (!$ids) return;

	try {
		$rows = ET::SQL()
			->select("postId")
			->select("COUNT(*)", "c")
			->from("post_like")
			->where("postId IN (:ids)")
			->bind(":ids", $ids)
			->groupBy("postId")
			->exec()
			->allRows("postId");

		$mine = array();
		if (ET::$session->userId) {
			$mineRows = ET::SQL()
				->select("postId")
				->from("post_like")
				->where("postId IN (:ids)")
				->where("memberId", (int)ET::$session->userId)
				->bind(":ids", $ids)
				->exec()
				->allRows("postId");
			$mine = $mineRows ? array_keys($mineRows) : array();
		}

		foreach ($posts as &$post) {
			$pid = $post["postId"];
			$post["likeCount"] = isset($rows[$pid]) ? (int)$rows[$pid]["c"] : 0;
			$post["likedByMe"] = in_array($pid, $mine);
		}
		unset($post);
	} catch (Exception $e) {
		// Never break the conversation view because of likes.
		$this->tableReady = false;
	}
}

public function action_conversationController_like($sender)
{
	header("Content-Type: application/json; charset=utf-8");

	if (!ET::$session->userId) {
		$sender->json("error", "login");
		$sender->renderJSON();
		return;
	}

	
	if (method_exists($sender, "validateToken") && !$sender->validateToken()) {
		$sender->json("error", "token");
		$sender->renderJSON();
		return;
	}
if (!$this->isTableReady()) {
		// Attempt to create table on the fly
		$this->setup();
		if (!$this->isTableReady()) {
			$sender->json("error", "unavailable");
			$sender->renderJSON();
			return;
		}
	}

	$postId = (int)R("postId");
	if (!$postId) {
		$sender->json("error", "invalid");
		$sender->renderJSON();
		return;
	}

	try {
		$existing = ET::SQL()
			->select("*")
			->from("post_like")
			->where("postId", $postId)
			->where("memberId", ET::$session->userId)
			->exec()
			->firstRow();

		if ($existing) {
			ET::SQL()->delete("post_like")
				->where("postId", $postId)
				->where("memberId", ET::$session->userId)
				->exec();
			$liked = false;
		} else {
			ET::SQL()->insert("post_like")
				->set(array(
					"postId" => $postId,
					"memberId" => ET::$session->userId,
					"time" => time()
				))
				->exec();
			$liked = true;
		}

		$count = (int) ET::SQL()
			->select("COUNT(*)", "c")
			->from("post_like")
			->where("postId", $postId)
			->exec()
			->result();

		$sender->json("liked", $liked);
		$sender->json("count", $count);
	} catch (Exception $e) {
		$sender->json("error", "server");
	}

	$sender->renderJSON();
}

}
