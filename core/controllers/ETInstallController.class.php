<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * The install controller handles the whole of the installation process, from checking for warnings and errors
 * to performing the installation.
 *
 * @package esoTalk
 */
class ETInstallController extends ETController {


/**
 * Initialize the install controller.
 *
 * @return void
 */
public function init()
{
	$this->addJSFile("core/js/lib/jquery.js");
	$this->addJSFile("core/js/lib/jquery-migrate.min.js");

	// Set the master view to the message master view.
	$this->masterView = "message.master";
	$this->title = T("Install esoTalk");

	// If any fatal errors should prevent the installation from taking place, dispatch to the "errors" method.
	if ($errors = $this->fatalChecks()) {
		$this->data("errors", $errors);
		$this->controllerMethod = "errors";
	}

	// Prevent JS and CSS from being aggregated, as we might not be able to write to the cache folder.
	ET::$config["esoTalk.aggregateCSS"] = ET::$config["esoTalk.aggregateJS"] = false;

	$this->trigger("init");
}


/**
 * When we first arrive at the installer, check for any warnings (non-fatal errors.) If there are any fatal
 * errors, we won't even get this far due to the check in init().
 *
 * @return void
 */
public function action_index()
{
	$this->action_warnings();
}


/**
 * Render a list of fatal errors. The actual check for errors occurs and is passed to the view in init().
 *
 * @return void
 */
public function action_errors()
{
	$this->data("fatal", true);
	$this->render("install/warnings");
}


/**
 * Check for warnings (non-fatal errors) and render a list of them. If there aren't any, proceed to the next step.
 *
 * @return void
 */
public function action_warnings()
{
	$errors = $this->warningChecks();
	if (!$errors) $this->redirect(URL("install/info"));

	$this->data("errors", $errors);
	$this->render("install/warnings");
}


/**
 * Set up and show the main installation data-entry form.
 *
 * @return void
 */
public function action_info()
{
	// Set up the form.
	$form = ETFactory::make("form");
	$form->action = URL("install/info");

	// Set some default values.
	$form->setValue("databaseDriver", "sqlite");
	$form->setValue("sqlitePath", PATH_CONFIG."/esotalk.sqlite");
	$form->setValue("mysqlHost", "127.0.0.1");
	$form->setValue("tablePrefix", "et");

	// If we have values stored in the session, use them.
	if ($values = ET::$session->getValue("install")) $form->setValues($values);

	// Work out what the base URL is.
	$dir = substr($_SERVER["PHP_SELF"], 0, strrpos($_SERVER["PHP_SELF"], "/index.php"));
	$https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
		|| (isset($_SERVER["SERVER_PORT"]) && (int)$_SERVER["SERVER_PORT"] === 443);
	$baseURL = ($https ? "https" : "http")."://{$_SERVER["HTTP_HOST"]}{$dir}/";
	$form->setValue("baseURL", $baseURL);

	// Friendly URLs off by default — most reliable on XAMPP/subfolders.
	$form->setValue("friendlyURLs", false);

	// If the form was submitted...
	if ($form->isPostBack("submit")) {

		$values = $form->getValues();

		// Make sure the title isn't empty.
		if (!strlen($values["forumTitle"]))
			$form->error("forumTitle", T("message.empty"));

		// Make sure the admin's details are valid.
		if ($error = ET::memberModel()->validateUsername($values["adminUser"], false)) $form->error("adminUser", T("message.$error"));
		if ($error = ET::memberModel()->validateEmail($values["adminEmail"], false)) $form->error("adminEmail", T("message.$error"));
		if ($error = ET::memberModel()->validatePassword($values["adminPass"])) $form->error("adminPass", T("message.$error"));
		if ($values["adminPass"] != $values["adminConfirm"]) $form->error("adminConfirm", T("message.passwordsDontMatch"));

			// Try to connect to the selected database driver, then verify its version.
			$driver = strtolower((string)($values["databaseDriver"] ?? "sqlite"));
			if (!in_array($driver, array("sqlite", "mysql"), true)) $driver = "sqlite";
			$values["databaseDriver"] = $driver;
			try {
				if ($driver === "sqlite") {
					$path = trim((string)($values["sqlitePath"] ?? ""));
					if ($path === "") $path = PATH_CONFIG."/esotalk.sqlite";
					if ($path !== ":memory:" && substr($path, 0, 1) !== "/" && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) && substr($path, 0, 2) !== "\\\\\\\\")
						$path = PATH_ROOT."/".ltrim($path, "/\\\\");
					$values["sqlitePath"] = $path;
				}
				ET::$database->init($values["mysqlHost"] ?? "", $values["mysqlUser"] ?? "", $values["mysqlPass"] ?? "", $values["mysqlDB"] ?? "", $this->normalizeTablePrefix($values["tablePrefix"] ?? ""), array(), null, $driver, $values["sqlitePath"] ?? null);
				$pdo = ET::$database->connection();
				$version = ET::$database->getVersion();
				if ($driver === "mysql") {
					$ok = false;
					if (stripos($version, "MariaDB") !== false) {
						if (preg_match('/(\d+\.\d+)/', $version, $m) && version_compare($m[1], "10.3", ">=")) $ok = true;
					} else if (preg_match('/^(\d+\.\d+)/', $version, $m) && version_compare($m[1], "5.7", ">=")) {
						$ok = true;
					}
					if (!$ok) $form->error("database", T("message.greaterMySQLVersionRequired") . " (detected: " . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . ")");
				}
			} catch (PDOException $e) {
				$form->error("database", $e->getMessage());
			}

		// Check to see if there are any conflicting tables already in the database.
		// If there are, show an error with a hidden input. If the form is submitted again with this hidden input,
		// proceed to perform the installation regardless.
		if (!$form->errorCount() and $values["tablePrefix"] != @$values["confirmTablePrefix"]) {

			// Get a list of all existing tables.
				$theirTables = ET::$database->getTables();

			// Just do a check for the member table. If it exists with this prefix, we have a conflict.
				if (in_array($this->normalizeTablePrefix($values["tablePrefix"] ?? "")."member", $theirTables)) {

				$form->error("tablePrefix", T("message.tablePrefixConflict"));

				$form->addHidden("confirmTablePrefix", $values["tablePrefix"], true);

			}
		}

		// If there are no errors, proceed to the installation step.
		if (!$form->errorCount()) {

			// Put all the POST data into the session and proceed to the install step.
			ET::$session->store("install", $values);
			$this->redirect(URL("install/install"));

		}

	}

	$this->data("form", $form);
	$this->render("install/info");
}


/**
 * Now that all necessary checks have been made and data has been gathered, perform the installation.
 *
 * @return void
 */
public function action_install()
{
	// If we aren't supposed to be here, get out.
	if (!($info = ET::$session->getValue("install"))) $this->redirect(URL("install/info"));

	// Make sure the base URL has a trailing slash.
	if (substr($info["baseURL"], -1) != "/") $info["baseURL"] .= "/";

		// Normalize the optional table prefix. SQLite queries use the reserved word "group", so a blank input
		// receives the safe default et_; custom prefixes receive one separator only.
		$tablePrefix = $this->normalizeTablePrefix($info["tablePrefix"] ?? "");

	// Prepare the $config variable with the installation settings.
	$config = array(
		"esoTalk.installed" => true,
		"esoTalk.version" => ESOTALK_VERSION,
			"esoTalk.database.driver" => $info["databaseDriver"] ?? "sqlite",
			"esoTalk.database.sqlitePath" => $info["sqlitePath"] ?? PATH_CONFIG."/esotalk.sqlite",
			"esoTalk.database.host" => $info["mysqlHost"] ?? "",
			"esoTalk.database.user" => $info["mysqlUser"] ?? "",
			"esoTalk.database.password" => $info["mysqlPass"] ?? "",
			"esoTalk.database.dbName" => $info["mysqlDB"] ?? "",
			"esoTalk.database.prefix" => $tablePrefix,
		"esoTalk.forumTitle" => $info["forumTitle"],
		"esoTalk.baseURL" => $info["baseURL"],
		"esoTalk.emailFrom" => "do_not_reply@{$_SERVER["HTTP_HOST"]}",
		"esoTalk.cookie.name" => preg_replace(array("/\s+/", "/[^\w]/"), array("_", ""), $info["forumTitle"]),
			"esoTalk.urls.friendly" => false,
			"esoTalk.urls.rewrite" => false,
			"esoTalk.defaultRoute" => "conversations",
		);

	// Merge these new config settings into our current conifg variable.
	ET::$config = array_merge(ET::$config, $config);

	// Initialize the database with our MySQL details.
		ET::$database->init(C("esoTalk.database.host"), C("esoTalk.database.user"), C("esoTalk.database.password"), C("esoTalk.database.dbName"), C("esoTalk.database.prefix"), C("esoTalk.database.connectionOptions"), C("esoTalk.database.port"), C("esoTalk.database.driver", "sqlite"), C("esoTalk.database.sqlitePath", PATH_CONFIG."/esotalk.sqlite"));

	// Run the upgrade model's install function.
	try {
		ET::upgradeModel()->install($info);
	} catch (Exception $e) {
		$this->fatalError($e->getMessage());
	}

	// Write the $config variable to config.php.
	@unlink(PATH_CONFIG."/config.php");
	ET::writeConfig($config);

	// Write custom.css and index.html as empty files (if they're not already there.)
	if (!file_exists(PATH_CONFIG."/custom.css")) file_put_contents(PATH_CONFIG."/custom.css", "");
	file_put_contents(PATH_CONFIG."/index.html", "");
	file_put_contents(PATH_UPLOADS."/index.html", "");
	file_put_contents(PATH_UPLOADS."/avatars/index.html", "");

	// Write a .htaccess file if they are using friendly URLs (and mod_rewrite).
	if (C("esoTalk.urls.rewrite")) {
		file_put_contents(PATH_ROOT."/.htaccess", "# Generated by esoTalk
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php/$1 [QSA,L]
</IfModule>");
	}

	// Write a robots.txt file.
	file_put_contents(PATH_ROOT."/robots.txt", "User-agent: *
Crawl-delay: 10
Disallow: /conversations/*?search=*
Disallow: /members/
Disallow: /user/
Disallow: /conversation/start/");

	// Clear the session of install data.
	ET::$session->remove("install");

	// Re-initialize the session and log the administrator in.
	ET::$session = ETFactory::make("session");
	ET::$session->loginWithMemberId(1);

	// Redirect them to the administration page.
	$this->redirect(URL("admin"));
}


protected function normalizeTablePrefix($prefix)
{
	$prefix = trim((string)$prefix);
	if ($prefix === "") return "et_";
	return rtrim($prefix, "_")."_";
}


/**
 * Show a fatal error, providing the user with options to go back to the details form or try again.
 *
 * @param string $error The error that occurred.
 * @return void
 */
protected function fatalError($error)
{
	$this->data("error", $error);
	$this->render("install/error");
	exit;
}


/**
 * Perform warning checks (non-fatal errors).
 *
 * @return array An array of warnings that were found.
 */
protected function warningChecks()
{
	$errors = array();

	// Check for the gd extension (needed for avatar resizing, etc.).
	if (!extension_loaded("gd") && !extension_loaded("gd2")) {
		$errors[] = T("message.gdNotEnabledWarning");
	}

	// Recommended (non-fatal) extensions for full functionality on modern PHP.
	if (!extension_loaded("mbstring")) {
		$errors[] = "The <code>mbstring</code> extension is recommended for proper multi-byte string handling (UTF-8).";
	}
	if (!extension_loaded("json")) {
		$errors[] = "The <code>json</code> extension is required for many modern features.";
	}
	if (!extension_loaded("openssl")) {
		$errors[] = "The <code>openssl</code> extension is recommended for secure connections and hashing.";
	}

	return $errors;
}


/**
 * Perform error checks (fatal errors).
 *
 * @return array An array of errors that were found.
 */
protected function fatalChecks()
{
	$errors = array();

	// Make sure the installer is not locked.
	if (C("esoTalk.installed")) $errors[] = T("message.esoTalkAlreadyInstalled");

	// Check the PHP version.
	if (!version_compare(PHP_VERSION, "8.0.0", ">=")) {
		$errors[] = sprintf(T("message.greaterPHPVersionRequired"), "8.0.0");
	}

		// A fresh install defaults to SQLite, while MySQL/MariaDB remains available from the form.
		if (!extension_loaded("pdo_sqlite") && !extension_loaded("pdo_mysql"))
			$errors[] = "PDO SQLite or PDO MySQL is required. Enable pdo_sqlite (recommended) or pdo_mysql in php.ini.";

	// Check file permissions.
	$fileErrors = array();
	$filesToCheck = array("", "uploads", "uploads/avatars", "addons/plugins", "addons/skins", "addons/languages", "config", "cache");
	sort($filesToCheck);

	// Go through each file (directory)...
	foreach ($filesToCheck as $file) {

		// If it doesn't exist and we can't create it, or if it does exist but we can't write to it, add it as
		// an errorous file.
		if ((!file_exists(PATH_ROOT."/$file") and !@mkdir(PATH_ROOT."/$file")) or (!is_writable(PATH_ROOT."/$file") and !@chmod(PATH_ROOT."/$file", 0777))) {

			// If this directory name is empty (referring to the root directory), use the directory one level up.
			if (!$file) {
				$realPath = realpath($file);
				$fileErrors[] = substr($realPath, strrpos($realPath, "/") + 1)."/";
			}
			else $fileErrors[] = $file."/";

		}
	}
	if (count($fileErrors)) $errors[] = sprintf(T("message.installerFilesNotWritable"), implode("</strong>, <strong>", $fileErrors));

	return $errors;
}

}
