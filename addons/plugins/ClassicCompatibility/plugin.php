<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ClassicCompatibility"] = array(
	"name" => "Classic Compatibility",
	"description" => "Restores classic branding, feed links, PWA metadata and social sharing metadata.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_ClassicCompatibility extends ETPlugin {

	public function handler_renderBefore($sender)
	{
		$sender->addCSSFile($this->resource("branding.css"));
		if (!C("esoTalk.avatars.showThumbnails", true)) {
			$sender->addToHead("<style>.avatar.thumb{display:none!important}</style>");
		}
		$manifest = getWebPath($this->resource("site.webmanifest"));
		$theme = C("skin.Default.primaryColor") ?: "#364159";
		$description = C("esoTalk.meta.description");
		$forumDescription = trim((string)C("esoTalk.forumDescription", ""));
		if (!$description && C("esoTalk.metaDescription", false)) $description = $forumDescription;
		if (C("esoTalk.showDescription", false) && $forumDescription) $sender->data("classicForumDescription", $forumDescription);

			$icon = $this->assetURL(C("esoTalk.forumIcon", false), "icon.svg");
			$favicon = C("esoTalk.shortcutIcon", false) ? $this->assetURL(C("esoTalk.shortcutIcon", false), "icon.svg") : $icon;
		$title = !empty($sender->title) ? $sender->title : C("esoTalk.forumTitle");
		$url = !empty($sender->canonicalURL) ? $sender->canonicalURL : C("esoTalk.baseURL");
		$sender->addToHead("<link rel='manifest' href='".sanitizeHTML($manifest)."'>");
		$sender->addToHead("<meta name='theme-color' content='".sanitizeHTML($theme)."'>");
		$sender->addToHead("<link rel='icon' href='".sanitizeHTML($favicon)."'>");
		$sender->addToHead("<link rel='shortcut icon' href='".sanitizeHTML($favicon)."'>");
		$sender->addToHead("<link rel='apple-touch-icon' href='".sanitizeHTML($icon)."'>");
		$sender->addToHead("<meta name='msapplication-TileImage' content='".sanitizeHTML($icon)."'>");
		if ($title) {
			$sender->addToHead("<meta property='og:site_name' content='".sanitizeHTML(C("esoTalk.forumTitle"))."'>");
			$sender->addToHead("<meta property='og:title' content='".sanitizeHTML($title)."'>");
			$sender->addToHead("<meta name='twitter:title' content='".sanitizeHTML($title)."'>");
		}
		$sender->addToHead("<meta property='og:type' content='website'>");
		$sender->addToHead("<meta property='og:image' content='".sanitizeHTML($icon)."'>");
		$sender->addToHead("<meta name='twitter:card' content='summary'>");
		$sender->addToHead("<meta name='twitter:image' content='".sanitizeHTML($icon)."'>");
		if ($url) $sender->addToHead("<meta property='og:url' content='".sanitizeHTML($url)."'>");
			if ($description) {
				if (!($sender instanceof ETConversationsController) && !($sender instanceof ETConversationController)) $sender->addToHead("<meta name='description' content='".sanitizeHTML($description)."'>");
				$sender->addToHead("<meta property='og:description' content='".sanitizeHTML($description)."'>");
				$sender->addToHead("<meta name='twitter:description' content='".sanitizeHTML($description)."'>");
			}
	}

	protected function assetURL($configured, $fallback)
	{
		if ($configured && strpos((string)$configured, "://") !== false) return $configured;
		if ($configured) return getWebPath($configured);
		return getWebPath($this->resource($fallback));
	}

	public function handler_conversationController_conversationIndexDefault($sender, &$conversation, &$controls, &$replyForm, &$replyControls)
	{
		if (empty($conversation["conversationId"]) || !is_object($controls) || !method_exists($controls, "add")) return;
		$url = URL("feed/conversation/".(int)$conversation["conversationId"]);
		$controls->add("classicFeed", "<a href='".$url."' class='classic-feed' type='application/rss+xml'><i class='icon-rss'></i> <span>".T("Feed")."</span></a>");
	}
}
?>
