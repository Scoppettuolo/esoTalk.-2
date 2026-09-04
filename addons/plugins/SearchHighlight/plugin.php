<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["SearchHighlight"] = array(
	"name" => "Search Highlight",
	"description" => "Highlights search terms when viewing conversations from a search result.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_SearchHighlight extends ETPlugin {

/** @var array */
protected $terms = array();

public function handler_conversationController_renderBefore($sender)
{
	$sender->addCSSFile($this->resource("highlight.css"));
}

public function handler_conversationController_conversationIndex($sender, &$conversation, &$posts, &$startFrom, &$searchString)
{
	$this->terms = array();
	if (!$searchString) return;
	$terms = preg_split('/\s+/', trim($searchString));
	foreach ($terms as $t) {
		$t = trim($t);
		// Skip empty, gambits (#foo), and very short tokens
		if ($t === "" || $t[0] === "#" || $t[0] === "+" || $t[0] === "!") continue;
		// Strip author:/contributor: prefixes for highlighting the value
		if (strpos($t, ":") !== false) {
			$parts = explode(":", $t, 2);
			$t = isset($parts[1]) ? $parts[1] : $t;
		}
		$t = trim($t, "\"'");
		if (mb_strlen($t) >= 2) $this->terms[] = $t;
	}
	$this->terms = array_values(array_unique($this->terms));
}

public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
{
	if (!$this->terms || empty($formatted["body"]) || !is_string($formatted["body"])) return;

	$body = $formatted["body"];
	foreach ($this->terms as $term) {
		$safe = preg_quote($term, "/");
		$result = @preg_replace(
			"/(?<![\\w-])(". $safe .")(?![\\w-])/iu",
			"<mark class='search-hit'>\$1</mark>",
			$body
		);
		if (is_string($result)) $body = $result;
	}
	$formatted["body"] = $body;
}

}
