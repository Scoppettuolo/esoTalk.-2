<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// Modernized for PHP 7.4+/8.x compatibility.
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * The sitemap controller generates a basic XML sitemap of public conversations.
 *
 * @package esoTalk
 */
class ETSitemapController extends ETController {

/**
 * Generate and output the sitemap.
 *
 * @return void
 */
public function action_index()
{
	// Cache for the configured time (default 1 hour).
	$cacheFile = PATH_CACHE . "/sitemap.xml";
	$cacheTime = (int) C("esoTalk.sitemapCacheTime", 3600);

	if (file_exists($cacheFile) && filemtime($cacheFile) > time() - $cacheTime) {
		header("Content-Type: application/xml; charset=utf-8");
		readfile($cacheFile);
		return;
	}

	// Build a simple sitemap of the most recent public conversations.
	$sql = ET::SQL()
		->select("c.conversationId")
		->select("c.title")
		
		->select("c.lastPostTime")
		->select("c.countPosts")
		->from("conversation c")
		->where("c.private", 0)
		->where("c.countPosts > 0")
		->orderBy("c.lastPostTime DESC")
		->limit(5000);

	$result = $sql->exec();

	$base = C("esoTalk.baseURL");
	if (!$base) {
		$base = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http")
			. "://" . ($_SERVER["HTTP_HOST"] ?? "localhost") . "/";
	}

	$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	// Home page
	$xml .= "  <url>\n";
	$xml .= "    <loc>" . htmlspecialchars($base, ENT_QUOTES, 'UTF-8') . "</loc>\n";
	$xml .= "    <changefreq>hourly</changefreq>\n";
	$xml .= "    <priority>1.0</priority>\n";
	$xml .= "  </url>\n";

	while ($row = $result->nextRow()) {
			$url = $base . URL(conversationURL($row["conversationId"], $row["title"]));
		$lastmod = !empty($row["lastPostTime"]) ? gmdate("Y-m-d\\TH:i:s+00:00", $row["lastPostTime"]) : null;

		$xml .= "  <url>\n";
		$xml .= "    <loc>" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "</loc>\n";
		if ($lastmod) {
			$xml .= "    <lastmod>" . $lastmod . "</lastmod>\n";
		}
		$xml .= "    <changefreq>weekly</changefreq>\n";
		$xml .= "  </url>\n";
	}

	$xml .= "</urlset>\n";

	// Write cache
	@file_put_contents($cacheFile, $xml);

	header("Content-Type: application/xml; charset=utf-8");
	echo $xml;
}

}
