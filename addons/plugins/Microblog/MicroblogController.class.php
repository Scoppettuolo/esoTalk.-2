<?php
if (!defined("IN_ESOTALK")) exit;

class ETPlugin_Microblog_Controller extends ETController {

protected function ensureTable()
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
}

public function action_index($filter = "")
{
	$this->ensureTable();

	$form = ETFactory::make("form");
	$form->action = URL("microblog");

	if (ET::$session->userId && $form->validPostBack("postStatus")) {
		$content = trim(strip_tags((string)$form->getValue("content")));
		$len = mb_strlen($content);
		if ($len < 1) {
			$form->error("content", T("message.empty"));
		} elseif ($len > 280) {
			$form->error("content", T("Status too long (max 280 characters)."));
		} else {
			ET::SQL()->insert("microblog")->set(array(
				"memberId" => ET::$session->userId,
				"content" => $content,
				"time" => time()
			))->exec();
			$this->message(T("Status posted!"), "success autoDismiss");
			$this->redirect(URL("microblog"));
			return;
		}
	}

	$onlyMine = ($filter === "mine" && ET::$session->userId);
	$posts = array();
	try {
		$sql = ET::SQL()
			->select("mb.*, m.username, m.avatarFormat")
			->from("microblog mb")
			->from("member m", "m.memberId=mb.memberId", "left")
			->orderBy("mb.time DESC")
			->limit(60);
		if ($onlyMine) {
			$sql->where("mb.memberId", ET::$session->userId);
		}
		$posts = $sql->exec()->allRows();
	} catch (Exception $e) {}

	$this->title = T("Microblog");
	$this->data("form", $form);
	$this->data("posts", $posts);
	$this->data("onlyMine", $onlyMine);
	$this->render("index");
}

public function action_delete($id = 0)
{
	$this->ensureTable();
	$id = (int)$id;
	if (!$id || !ET::$session->userId) {
		$this->redirect(URL("microblog"));
		return;
	}
	// CSRF protection
	$token = R("token");
	if (!$token || !ET::$session->validateToken($token)) {
		$this->message(T("message.noPermission"), "warning");
		$this->redirect(URL("microblog"));
		return;
	}
	$row = ET::SQL()->select("*")->from("microblog")->where("id", $id)->exec()->firstRow();
	if ($row && ((int)$row["memberId"] === (int)ET::$session->userId || ET::$session->isAdmin())) {
		ET::SQL()->delete()->from("microblog")->where("id", $id)->exec();
		$this->message(T("Status deleted."), "success autoDismiss");
	}
	$this->redirect(URL("microblog"));
}

}
