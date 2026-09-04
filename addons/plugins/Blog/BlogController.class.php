<?php
if (!defined("IN_ESOTALK")) exit;

/**
 * Blog controller — public list/view + admin manage/create/edit/delete.
 */
class ETPlugin_Blog_Controller extends ETController {

const PER_PAGE = 10;

protected function ensureTable()
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
}

protected function requireAdmin()
{
	if (!ET::$session->isAdmin()) {
		$this->renderMessage(T("Error"), T("You do not have permission."));
		return false;
	}
	return true;
}

/** Build a short plain-text excerpt from content. */
protected function makeExcerpt($content, $len = 200)
{
	$plain = trim(preg_replace('/\s+/', ' ', strip_tags((string)$content)));
	if (mb_strlen($plain) <= $len) return $plain;
	return rtrim(mb_substr($plain, 0, $len), " .,;:") . "…";
}

/** Unique slug helper. */
protected function uniqueSlug($slug, $excludeId = 0)
{
	$slug = slug($slug);
	if ($slug === "") $slug = "post";
	$base = $slug;
	$i = 2;
	while (true) {
		$sql = ET::SQL()->select("postId")->from("blog_post")->where("slug", $slug);
		if ($excludeId) $sql->where("postId != :ex")->bind(":ex", $excludeId);
		$row = $sql->exec()->firstRow();
		if (!$row) return $slug;
		$slug = $base."-".$i;
		$i++;
		if ($i > 100) return $base."-".bin2hex(random_bytes(3));
	}
}

/** Render post body safely through the esoTalk formatter. */
protected function formatBody($content)
{
	return ET::formatter()->init((string)$content)->format()->get();
}

public function action_index($slug = "")
{
	$this->ensureTable();
	if ($slug) return $this->action_view($slug);

	$page = max(1, (int)R("page", 1));
	$offset = ($page - 1) * self::PER_PAGE;

	$total = 0;
	$posts = array();
	try {
		$total = (int) ET::SQL()->select("COUNT(*)")->from("blog_post")->where("published", 1)->exec()->result();
		$posts = ET::SQL()->select("bp.*, m.username, m.avatarFormat")
			->from("blog_post bp")
			->from("member m", "m.memberId=bp.memberId", "left")
			->where("bp.published", 1)
			->orderBy("bp.time DESC")
			->limit(self::PER_PAGE)
			->offset($offset)
			->exec()->allRows();
	} catch (Exception $e) {}

	// Backfill excerpts if column exists but empty
	foreach ($posts as &$p) {
		if (empty($p["excerpt"]) && !empty($p["content"])) {
			$p["excerpt"] = $this->makeExcerpt($p["content"]);
		}
	}
	unset($p);

	$pages = $total > 0 ? (int)ceil($total / self::PER_PAGE) : 1;

	$this->title = T("Blog");
	$this->data("posts", $posts);
	$this->data("isAdmin", ET::$session->isAdmin());
	$this->data("page", $page);
	$this->data("pages", $pages);
	$this->data("total", $total);
	$this->render("list");
}

public function action_view($slug = "")
{
	$this->ensureTable();
	$slug = (string)$slug;
	$post = false;
	try {
		$post = ET::SQL()->select("bp.*, m.username, m.avatarFormat, m.memberId AS authorId")
			->from("blog_post bp")
			->from("member m", "m.memberId=bp.memberId", "left")
			->where("bp.slug", $slug)
			->exec()->firstRow();
	} catch (Exception $e) {}

	if (!$post) {
		$this->render404(T("Post not found"));
		return;
	}

	// Unpublished: only admin (or valid preview token) can see
	if (empty($post["published"])) {
		$preview = R("preview");
		$ok = ET::$session->isAdmin() || ($preview && ET::$session->validateToken($preview));
		if (!$ok) {
			$this->render404(T("Post not found"));
			return;
		}
	}

	$this->title = $post["title"];
	$this->addToHead("<meta name='description' content='".sanitizeHTML($post["excerpt"] ?: $this->makeExcerpt($post["content"]))."'>");
	$this->data("post", $post);
	$this->data("bodyHtml", $this->formatBody($post["content"]));
	$this->data("isAdmin", ET::$session->isAdmin());
	$this->render("view");
}

public function action_manage()
{
	if (!$this->requireAdmin()) return;
	$this->ensureTable();

	$posts = array();
	try {
		$posts = ET::SQL()->select("bp.*, m.username")
			->from("blog_post bp")
			->from("member m", "m.memberId=bp.memberId", "left")
			->orderBy("bp.time DESC")
			->exec()->allRows();
	} catch (Exception $e) {}

	$this->title = T("Manage Blog");
	$this->data("posts", $posts);
	$this->render("manage");
}

public function action_create()
{
	if (!$this->requireAdmin()) return;
	$this->ensureTable();
	$this->editForm(null);
}

public function action_edit($id = 0)
{
	if (!$this->requireAdmin()) return;
	$this->ensureTable();
	$id = (int)$id;
	$post = ET::SQL()->select("*")->from("blog_post")->where("postId", $id)->exec()->firstRow();
	if (!$post) {
		$this->render404(T("Post not found"));
		return;
	}
	$this->editForm($post);
}

protected function editForm($post)
{
	$form = ETFactory::make("form");
	$form->action = $post ? URL("blog/edit/".$post["postId"]) : URL("blog/create");

	if ($post) {
		$form->setValue("title", $post["title"]);
		$form->setValue("slug", $post["slug"]);
		$form->setValue("content", $post["content"]);
		$form->setValue("excerpt", isset($post["excerpt"]) ? $post["excerpt"] : "");
		$form->setValue("published", $post["published"]);
	} else {
		$form->setValue("published", 1);
	}

	if ($form->validPostBack("save")) {
		$title = trim((string)$form->getValue("title"));
		$slugRaw = trim((string)$form->getValue("slug"));
		$content = (string)$form->getValue("content");
		$excerpt = trim((string)$form->getValue("excerpt"));
		$published = $form->getValue("published") ? 1 : 0;

		if ($title === "") {
			$form->error("title", T("message.empty"));
		} elseif (mb_strlen($content) < 1) {
			$form->error("content", T("message.empty"));
		} else {
			$slug = $this->uniqueSlug($slugRaw !== "" ? $slugRaw : $title, $post ? (int)$post["postId"] : 0);
			if ($excerpt === "") $excerpt = $this->makeExcerpt($content);
			else $excerpt = mb_substr($excerpt, 0, 500);

			$data = array(
				"title" => mb_substr($title, 0, 255),
				"slug" => $slug,
				"content" => $content,
				"excerpt" => $excerpt,
				"published" => $published,
				"memberId" => ET::$session->userId,
				"updateTime" => time(),
			);

			try {
				if ($post) {
					ET::SQL()->update("blog_post")->set($data)->where("postId", (int)$post["postId"])->exec();
					$this->message(T("message.changesSaved"), "success autoDismiss");
				} else {
					$data["time"] = time();
					ET::SQL()->insert("blog_post")->set($data)->exec();
					$this->message(T("Post created!"), "success autoDismiss");
				}
				$this->redirect(URL("blog/manage"));
				return;
			} catch (Exception $e) {
				$form->error("slug", T("Slug already in use or database error."));
			}
		}
	}

	$this->title = $post ? T("Edit post") : T("New post");
	$this->data("form", $form);
	$this->data("post", $post);
	$this->render("edit");
}

public function action_delete($id = 0)
{
	if (!$this->requireAdmin()) return;
	$token = R("token");
	if (!$token || !ET::$session->validateToken($token)) {
		$this->message(T("message.noPermission"), "warning");
		$this->redirect(URL("blog/manage"));
		return;
	}
	$id = (int)$id;
	if ($id) {
		ET::SQL()->delete()->from("blog_post")->where("postId", $id)->exec();
		$this->message(T("Post deleted."), "success autoDismiss");
	}
	$this->redirect(URL("blog/manage"));
}

public function action_toggle($id = 0)
{
	if (!$this->requireAdmin()) return;
	$token = R("token");
	if (!$token || !ET::$session->validateToken($token)) {
		$this->message(T("message.noPermission"), "warning");
		$this->redirect(URL("blog/manage"));
		return;
	}
	$id = (int)$id;
	$row = ET::SQL()->select("*")->from("blog_post")->where("postId", $id)->exec()->firstRow();
	if ($row) {
		$new = empty($row["published"]) ? 1 : 0;
		ET::SQL()->update("blog_post")->set(array(
			"published" => $new,
			"updateTime" => time()
		))->where("postId", $id)->exec();
		$this->message($new ? T("Post published.") : T("Post unpublished."), "success autoDismiss");
	}
	$this->redirect(URL("blog/manage"));
}

}
