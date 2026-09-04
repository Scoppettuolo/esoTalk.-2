<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ClassicConversation"] = array(
	"name" => "Classic Conversation Layout",
	"description" => "Restores the v1 alternating conversation layout, avatar alignment and classic navigation hooks.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_ClassicConversation extends ETPlugin {

	public function handler_conversationController_renderBefore($sender)
	{
		$sender->addCSSFile($this->resource("classic.css"));
		$sender->addJSFile($this->resource("classic.js"));
		$sender->addJSVar("classicConversation", array(
			"alignment" => $this->alignment(),
			"initialSide" => "r"
		));
	}

	public function handler_settingsController_init($sender)
	{
		$sender->addCSSFile($this->resource("classic.css"));
	}

	public function handler_settingsController_initGeneral($sender, $form)
	{
		$form->addSection("appearance", T("Appearance"));
		$alignment = $this->alignment();
		$form->setValue("avatarAlignment", $alignment);
		$form->addField("appearance", "avatarAlignment", array($this, "fieldAlignment"), array($this, "saveAlignment"));
	}

	public function fieldAlignment($form)
	{
		$value = $form->getValue("avatarAlignment") ?: "alternate";
		$options = array(
			"alternate" => T("Alternate sides"),
			"left" => T("Always left"),
			"right" => T("Always right"),
			"none" => T("Hide avatar column")
		);
		$html = "<select name='avatarAlignment' class='input'>";
		foreach ($options as $key => $label)
			$html .= "<option value='".sanitizeHTML($key)."'".($key === $value ? " selected" : "").">".sanitizeHTML($label)."</option>";
		$html .= "</select><br><small class='help'>".T("The first message starts on the right; alternate mode changes side only when the author changes.")."</small>";
		return $html;
	}

	public function saveAlignment($form, $key, &$prefs)
	{
		$value = $form->getValue($key);
		if (!in_array($value, array("alternate", "left", "right", "none"), true)) $value = "alternate";
		$prefs["avatarAlignment"] = $value;
	}

	/**
	 * This event runs for the regular page and for conversation/index.ajax.
	 * We annotate the raw posts before the v2 views format them.
	 */
	public function handler_conversationsController_renderBefore($sender)
	{
		if (C("esoTalk.classicConversationList", false)) $sender->addCSSFile($this->resource("classic-list.css"));
	}

	public function handler_conversationController_conversationIndex($sender, &$conversation, &$posts, &$startFrom, &$searchString)
	{
		$this->annotatePosts($conversation, $posts, (int)$startFrom, $searchString);
	}

	public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
	{
		$side = !empty($post["_classicSide"]) ? $post["_classicSide"] : "r";
		if (!isset($formatted["class"])) $formatted["class"] = array();
		elseif (!is_array($formatted["class"])) $formatted["class"] = preg_split('/\s+/', trim((string)$formatted["class"]));
		$formatted["class"][] = "side-".$side;
		if (!empty($post["_classicGroupStart"])) $formatted["class"][] = "group-start";
		if (!empty($post["_classicGroupMiddle"])) $formatted["class"][] = "group-middle";
		if (!empty($post["_classicGroupEnd"])) $formatted["class"][] = "group-end";
		if (!isset($formatted["data"]) || !is_array($formatted["data"])) $formatted["data"] = array();
		$formatted["data"]["classic-side"] = $side;
		$formatted["data"]["memberid"] = (int)($post["memberId"] ?? 0);
	}

	public function handler_conversationController_renderReplyBox($sender, &$post, $conversation)
	{
		$side = !empty($conversation["_classicReplySide"]) ? $conversation["_classicReplySide"] : "r";
		if (!isset($post["class"])) $post["class"] = array();
		elseif (!is_array($post["class"])) $post["class"] = preg_split('/\s+/', trim((string)$post["class"]));
		$post["class"][] = "side-".$side;
		$post["class"][] = "classic-reply";
		if (!isset($post["data"]) || !is_array($post["data"])) $post["data"] = array();
		$post["data"]["classic-side"] = $side;
		$post["data"]["memberid"] = (int)ET::$session->userId;
	}

	protected function alignment()
	{
		$value = ET::$session->preference("avatarAlignment", "alternate");
		return in_array($value, array("alternate", "left", "right", "none"), true) ? $value : "alternate";
	}

	protected function annotatePosts(&$conversation, &$posts, $startFrom, $searchString)
	{
		$alignment = $this->alignment();
		$previous = null;
		$side = "r";

		/* If a page starts in the middle, recover the previous author so the parity is stable. */
		if ($startFrom > 0 && !$searchString && !empty($conversation["conversationId"])) {
			try {
				$result = ET::SQL()
					->select("memberId")
					->select("deleteMemberId")
					->from("post")
					->where("conversationId=:conversationId")
					->bind(":conversationId", (int)$conversation["conversationId"])
					->orderBy("time ASC")
					->orderBy("postId ASC")
					->limit($startFrom)
					->exec();
				while ($row = $result->nextRow()) {
					if ($previous !== null && !$this->startsGroup($previous, $row)) $previous = $row;
					else {
						if ($previous !== null) $side = $this->otherSide($side);
						$previous = $row;
					}
				}
			} catch (Exception $e) {
				/* A missing/old schema must not prevent the conversation from rendering. */
			}
		}

		foreach ($posts as $index => &$post) {
			$starts = $previous === null || $this->startsGroup($previous, $post);
			if ($starts && $previous !== null) $side = $this->otherSide($side);
			$post["_classicGroupStart"] = $starts;
			$post["_classicSide"] = ($alignment === "alternate") ? $side : ($alignment === "none" ? "none" : $alignment[0]);
			$post["_classicGroupMiddle"] = !$starts;
			$post["_classicGroupEnd"] = false;
			$previous = $post;
		}
		unset($post);

		/* Mark group ends after the complete visible range is known. */
		$count = count($posts);
		for ($i = 0; $i < $count; $i++) {
			$next = ($i + 1 < $count) ? $posts[$i + 1] : null;
			$posts[$i]["_classicGroupEnd"] = $next === null || $this->startsGroup($posts[$i], $next);
			if ($posts[$i]["_classicGroupEnd"]) $posts[$i]["_classicGroupMiddle"] = false;
		}

		if ($count) {
			$last = $posts[$count - 1]["_classicSide"];
			$conversation["_classicReplySide"] = ($alignment === "alternate" && !empty($conversation["lastPostMemberId"]) && (int)$conversation["lastPostMemberId"] !== (int)($posts[$count - 1]["memberId"] ?? 0))
				? $this->otherSide($last) : $last;
		}
		elseif ($alignment === "alternate") {
			$conversation["_classicReplySide"] = "r";
		}
		else {
			$conversation["_classicReplySide"] = $alignment === "none" ? "none" : $alignment[0];
		}
	}

	protected function startsGroup($previous, $current)
	{
		return !empty($previous["deleteMemberId"]) || !empty($current["deleteMemberId"]) || (int)($previous["memberId"] ?? 0) !== (int)($current["memberId"] ?? 0);
	}

	protected function otherSide($side)
	{
		return $side === "r" ? "l" : "r";
	}
}
?>
