<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["CacheManager"] = array(
	"name" => "Cache Manager",
	"description" => "Clear the file cache from the admin panel (channels, groups, sitemap, CSS/JS aggregates).",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_CacheManager extends ETPlugin {

protected function clearCache()
{
	$removed = 0;
	if (is_object(ET::$cache) && method_exists(ET::$cache, "clear")) {
		ET::$cache->clear();
	}
	$dir = defined("PATH_CACHE") ? PATH_CACHE : (PATH_ROOT."/cache");
	if (!is_dir($dir)) return 0;
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($iterator as $file) {
		$path = $file->getPathname();
		// Never delete the directory itself; only files matching safe extensions or known names
		if ($file->isFile()) {
			$name = $file->getFilename();
			if (preg_match('/\.(css|js|xml|cache|tmp|html)$/i', $name) || $name === "sitemap.xml") {
				if (@unlink($path)) $removed++;
			}
		}
	}
	return $removed;
}

public function settings($sender)
{
	$form = ETFactory::make("form");
	$form->action = URL("admin/plugins/settings/CacheManager");

	if ($form->validPostBack("clear")) {
		$n = $this->clearCache();
		$sender->message(sprintf(T("%s cache files cleared."), $n), "success autoDismiss");
		$sender->redirect(URL("admin/plugins"));
	}

	$sender->data("cacheForm", $form);
	return $this->view("settings");
}

}
