<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["BBCode"] = array(
	"name" => "BBCode",
	"description" => "Formats BBCode within posts, allowing users to style their text.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);


/**
 * BBCode Formatter Plugin
 *
 * Interprets BBCode in posts and converts it to HTML formatting when rendered. Also adds BBCode formatting
 * buttons to the post editing/reply area.
 */
class ETPlugin_BBCode extends ETPlugin {


/**
 * Add an event handler to the initialization of the conversation controller to add BBCode CSS and JavaScript
 * resources.
 *
 * @return void
 */
public function handler_conversationController_renderBefore($sender)
{
	$sender->addJSFile($this->resource("bbcode.js"));
	$sender->addCSSFile($this->resource("bbcode.css"));
}


/**
 * Add an event handler to the "getEditControls" method of the conversation controller to add BBCode
 * formatting buttons to the edit controls.
 *
 * @return void
 */
public function handler_conversationController_getEditControls($sender, &$controls, $id)
{
	addToArrayString($controls, "fixed", "<a href='javascript:BBCode.fixed(\"$id\");void(0)' title='".T("Code")."' class='control-fixed'><i class='icon-code'></i></a>", 0);
	addToArrayString($controls, "image", "<a href='javascript:BBCode.image(\"$id\");void(0)' title='".T("Image")."' class='control-img'><i class='icon-picture'></i></a>", 0);
	addToArrayString($controls, "link", "<a href='javascript:BBCode.link(\"$id\");void(0)' title='".T("Link")."' class='control-link'><i class='icon-link'></i></a>", 0);
	addToArrayString($controls, "strike", "<a href='javascript:BBCode.strikethrough(\"$id\");void(0)' title='".T("Strike")."' class='control-s'><i class='icon-strikethrough'></i></a>", 0);
	addToArrayString($controls, "header", "<a href='javascript:BBCode.header(\"$id\");void(0)' title='".T("Header")."' class='control-h'><i class='icon-h-sign'></i></a>", 0);
	addToArrayString($controls, "italic", "<a href='javascript:BBCode.italic(\"$id\");void(0)' title='".T("Italic")."' class='control-i'><i class='icon-italic'></i></a>", 0);
	addToArrayString($controls, "bold", "<a href='javascript:BBCode.bold(\"$id\");void(0)' title='".T("Bold")."' class='control-b'><i class='icon-bold'></i></a>", 0);

}


/**
 * Add an event handler to the formatter to take out and store code blocks before formatting takes place.
 *
 * @return void
 */
public function handler_format_beforeFormat($sender)
{
	$this->blockFixedContents = array();
	$this->inlineFixedContents = array();

	// Hide block-level [code] ... [/code] (multiline preferred)
	$regexp = "/(.*)^\s*\[code\]\n?(.*?)\n?\[\/code]$/ims";
	while (preg_match($regexp, $sender->content, $m)) {
		if ($sender->inline) {
			$this->inlineFixedContents[] = $m[2];
			$replacement = $m[1] . "<code></code>";
		} else {
			$this->blockFixedContents[] = $m[2];
			$replacement = $m[1] . "</p><pre></pre><p>";
		}
		// Replace only the first match to avoid infinite loop issues
		$sender->content = preg_replace($regexp, $replacement, $sender->content, 1);
	}

	// Remaining inline-level [code] ... [/code]
	$sender->content = preg_replace_callback("/\[code\]\n?(.*?)\n?\[\/code]/is", function($matches) {
		$this->inlineFixedContents[] = $matches[1];
		return "<code></code>";
	}, $sender->content);
}

public function handler_format_format($sender)
{
	// TODO: Rewrite BBCode parser to use the method found here:
	// http://stackoverflow.com/questions/1799454/is-there-a-solid-bb-code-parser-for-php-that-doesnt-have-any-dependancies/1799788#1799788
	// Remove control characters from the post.
	//$sender->content = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $sender->content);
	// \[ (i|b|color|url|somethingelse) \=? ([^]]+)? \] (?: ([^]]*) \[\/\1\] )

	// Images: [img]url[/img] — only allow http(s) URLs, escape attributes
	$sender->content = preg_replace_callback("/\[img\](.*?)\[\/img\]/i", function ($m) use ($sender) {
		if ($sender->inline) return "[image]";
		$url = trim(html_entity_decode($m[1], ENT_QUOTES, "UTF-8"));
		// Strip control chars and whitespace
		$url = preg_replace('/[\x00-\x1F\x7F\s]+/', '', $url);
		if (!preg_match('#^https?://#i', $url)) return "";
		// Block javascript: and data: already handled by scheme check
		$safe = htmlspecialchars($url, ENT_QUOTES, "UTF-8");
		return "<img src='$safe' alt='' loading='lazy'/>";
	}, $sender->content);

	// Links with display text: [url=http://url]text[/url]
	$sender->content = preg_replace_callback("/\[url=(\w{2,6}:\/\/)?([^\]]*?)\](.*?)\[\/url\]/i", array($this, "linksCallback"), $sender->content);

	// Bold: [b]bold text[/b]
	$sender->content = preg_replace("|\[b\](.*?)\[/b\]|si", "<b>$1</b>", $sender->content);

	// Italics: [i]italic text[/i]
	$sender->content = preg_replace("/\[i\](.*?)\[\/i\]/si", "<i>$1</i>", $sender->content);

	// Strikethrough: [s]strikethrough[/s]
	$sender->content = preg_replace("/\[s\](.*?)\[\/s\]/si", "<del>$1</del>", $sender->content);

	// Headers: [h]header[/h]
	$replacement = $sender->inline ? "<b>$1</b>" : "</p><h4>$1</h4><p>";
	$sender->content = preg_replace("/\[h\](.*?)\[\/h\]/", $replacement, $sender->content);
}


/**
 * The callback function used to replace URL BBCode with HTML anchor tags.
 *
 * @param array $matches An array of matches from the regular expression.
 * @return string The replacement HTML anchor tag.
 */
public function linksCallback($matches)
{
	return ET::formatter()->formatLink($matches[1].$matches[2], $matches[3]);
}


/**
 * Add an event handler to the formatter to put code blocks back in after formatting has taken place.
 *
 * @return void
 */
public function handler_format_afterFormat($sender)
{
	// Retrieve the contents of the inline <code> tags from the array in which they are stored.
	$sender->content = preg_replace_callback("/<code><\/code>/i", function($m) {
		$content = array_shift($this->inlineFixedContents);
		return "<code>" . $content . "</code>";
	}, $sender->content);

	// Retrieve the contents of the block <pre> tags from the array in which they are stored.
	if (!$sender->inline) {
		$sender->content = preg_replace_callback("/<pre><\/pre>/i", function($m) {
			$content = array_pop($this->blockFixedContents);
			return "<pre>" . $content . "</pre>";
		}, $sender->content);
	}
}

}
