<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["MemberColors"] = array(
	"name" => "Member Colors",
	"description" => "Classic esoTalk: each member has a visible post color.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

/** Restores the v1 color contract while keeping v2 member preferences. */
class ETPlugin_MemberColors extends ETPlugin {

	const COLORS = 27;

	public function setup($oldVersion = "")
	{
		/* A v1 database may still have member.color. The query is intentionally optional
		 * so a clean v2 database continues normally. */
		try {
			$result = ET::SQL()->select("memberId")->select("color")->from("member")->where("color IS NOT NULL")->exec();
			while ($row = $result->nextRow()) {
				$member = ET::memberModel()->getById($row["memberId"]);
				if (!$member) continue;
				$prefs = is_array($member["preferences"]) ? $member["preferences"] : array();
				$legacy = (int)$row["color"];
				if (empty($prefs["postColor"]) && $legacy >= 1 && $legacy <= self::COLORS)
					ET::memberModel()->setPreferences($member, array("postColor" => $legacy));
			}
		} catch (Exception $e) {
			/* The v2 schema has no legacy color column; nothing to migrate. */
		}
		return true;
	}

	public function handler_conversationController_renderBefore($sender)
	{
		$sender->addCSSFile($this->resource("colors.css"));
	}

	public function handler_settingsController_init($sender)
	{
		$sender->addCSSFile($this->resource("colors.css"));
		$sender->addJSFile($this->resource("palette.js"));
	}

	public function handler_settingsController_initGeneral($sender, $form)
	{
		$form->addSection("appearance", T("Appearance"));
		$color = (int)ET::$session->preference("postColor", 0);
		if ($color < 1 || $color > self::COLORS) $color = 0;
		$form->setValue("postColor", $color);
		$form->addField("appearance", "postColor", array($this, "fieldColor"), array($this, "saveColor"));
	}

	public function fieldColor($form)
	{
		$current = (int)$form->getValue("postColor");
		if ($current < 1 || $current > self::COLORS) $current = $this->defaultColor(ET::$session->userId);
		$memberName = !empty(ET::$session->user["username"]) ? name(ET::$session->user["username"]) : T("Member");
		$html = "<div class='member-color-preview post c$current' id='memberColorPreview' data-color='$current' aria-label='".sanitizeHTML(T("Post color"))."'>";
		$html .= "<div class='postContent thing'><div class='postHeader'><div class='info'><h3>".sanitizeHTML($memberName)."</h3><span>".sanitizeHTML(T("Post color"))."</span></div></div>";
		$html .= "<div class='postBody'><p>".sanitizeHTML(T("Choose a color for your posts (classic esoTalk style).")) . "</p></div></div></div>";
		$html .= "<div class='member-color-palette' id='memberColorPalette' role='radiogroup' aria-label='".sanitizeHTML(T("Post color"))."'>";
		for ($i = 1; $i <= self::COLORS; $i++) {
			$selected = ($i === $current);
			$html .= "<a href='#' class='mc$i".($selected ? " selected" : "")."' data-color='$i' role='radio' aria-checked='".($selected ? "true" : "false")."' title='".sanitizeHTML(sprintf(T("Color %s"), $i))."'></a>";
		}
		$html .= "</div>".$form->input("postColor", "hidden");
		$html .= "<small class='help'>".T("Choose a color for your posts (classic esoTalk style).")."</small>";
		return $html;
	}

	public function saveColor($form, $key, &$prefs)
	{
		$c = (int)$form->getValue($key);
		if ($c < 1 || $c > self::COLORS) $c = 1;
		$prefs["postColor"] = $c;
	}

	public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
	{
		$color = $this->getPostColor($post);
		if (!isset($formatted["class"])) $formatted["class"] = array();
		elseif (!is_array($formatted["class"])) $formatted["class"] = preg_split("/\s+/", trim((string)$formatted["class"]), -1, PREG_SPLIT_NO_EMPTY);
		$formatted["class"][] = "c".$color;
		if (!isset($formatted["data"]) || !is_array($formatted["data"])) $formatted["data"] = array();
		$formatted["data"]["memberid"] = (int)($post["memberId"] ?? 0);
		$formatted["data"]["color"] = $color;
	}

	/* v1 also coloured the current user's reply composer and the edit box. */
	public function handler_conversationController_renderReplyBox($sender, &$formatted, $conversation)
	{
		$color = $this->getPostColor(ET::$session->user);
		if (!isset($formatted["class"])) $formatted["class"] = array();
		elseif (!is_array($formatted["class"])) $formatted["class"] = preg_split("/\s+/", trim((string)$formatted["class"]), -1, PREG_SPLIT_NO_EMPTY);
		$formatted["class"][] = "c".$color;
		if (!isset($formatted["data"]) || !is_array($formatted["data"])) $formatted["data"] = array();
		$formatted["data"]["memberid"] = (int)ET::$session->userId;
		$formatted["data"]["color"] = $color;
	}

	public function handler_conversationController_renderEditBox($sender, &$formatted, $post)
	{
		$color = $this->getPostColor($post);
		if (!isset($formatted["class"])) $formatted["class"] = array();
		elseif (!is_array($formatted["class"])) $formatted["class"] = preg_split("/\s+/", trim((string)$formatted["class"]), -1, PREG_SPLIT_NO_EMPTY);
		$formatted["class"][] = "c".$color;
		if (!isset($formatted["data"]) || !is_array($formatted["data"])) $formatted["data"] = array();
		$formatted["data"]["memberid"] = (int)($post["memberId"] ?? 0);
		$formatted["data"]["color"] = $color;
	}

	public function handler_memberModel_createAfter($sender, $values)
	{
		if (empty($values["memberId"])) return;
		$member = ET::memberModel()->getById($values["memberId"]);
		if (!$member) return;
		$prefs = is_array($member["preferences"]) ? $member["preferences"] : array();
		if (empty($prefs["postColor"]))
			ET::memberModel()->setPreferences($member, array("postColor" => $this->defaultColor($member["memberId"])));
	}

	protected function getPostColor($post)
	{
		$color = 0;
		if (!empty($post["preferences"]) && is_array($post["preferences"]) && !empty($post["preferences"]["postColor"]))
			$color = (int)$post["preferences"]["postColor"];
		return ($color >= 1 && $color <= self::COLORS) ? $color : $this->defaultColor($post["memberId"] ?? 0);
	}

	protected function defaultColor($memberId)
	{
		$memberId = (int)$memberId;
		return $memberId > 0 ? (($memberId - 1) % self::COLORS) + 1 : 1;
	}
}
?>
