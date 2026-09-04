<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ChannelColors"] = array(
	"name" => "Channel Colors",
	"description" => "Assign a color to each channel. Badges and list items use that color.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_ChannelColors extends ETPlugin {

public static $palette = array(
	"#64748b", "#2563eb", "#7c3aed", "#db2777", "#dc2626",
	"#ea580c", "#ca8a04", "#16a34a", "#0d9488", "#0891b2",
	"#4f46e5", "#9333ea", "#c026d3", "#e11d48", "#f59e0b"
);

public function handler_init($sender)
{
	if (!is_object($sender) || !method_exists($sender, "addToHead")) return;
	$channels = ET::channelModel()->getAll();
	if (!$channels) return;

	$css = "a.channel { border-radius:4px; padding:2px 8px; }\n";
	foreach ($channels as $id => $ch) {
		$color = null;
		if (!empty($ch["attributes"]) && is_array($ch["attributes"]) && !empty($ch["attributes"]["color"])) {
			$color = $ch["attributes"]["color"];
		}
		if (!$color || !preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) continue;
		$id = (int)$id;
		// Only target channel badges, not whole list rows or posts
		$css .= "a.channel.channel-{$id} { background:{$color} !important; color:#fff !important; border-color:{$color} !important; }\n";
		$css .= ".conversationList .col-channel a.channel-{$id} { background:{$color}; color:#fff; }\n";
		$css .= ".conversationList > li.channel-{$id} { border-left: 3px solid {$color}; }\n";
	}
	$sender->addToHead("<style id='channel-colors'>\n".$css."</style>");
}

public function settings($sender)
{
	$sender->data("palette", self::$palette);
	return $this->view("settings");
}

}
