<?php
if (!defined("IN_ESOTALK")) exit;

class ETPlugin_Pages_Controller extends ETController {

public function action_index($slug = "")
{
	if (!$slug) {
		$this->redirect(URL(""));
		return;
	}
	try {
		$page = ET::SQL()->select("*")->from("page")->where("slug", $slug)->exec()->firstRow();
	} catch (Exception $e) {
		$page = false;
	}
	if (!$page) {
		$this->render404("Page not found");
		return;
	}
	$this->title = $page["title"];
	$this->data("page", $page);
	$this->render("page");
}

}
