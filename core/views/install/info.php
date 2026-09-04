<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the esoTalk installation form.
 *
 * @package esoTalk
 */

$form = $data["form"];
?>
<h1><?php echo T("Welcome to esoTalk"); ?></h1>
<h2><?php printf(T("message.installerWelcome"), "https://github.com/Scoppettuolo/docs/debug"); ?></h2>

<?php echo $form->open(); ?>

<div class='details'>

	<ul class='form'>
		<li><?php echo $form->input("forumTitle", "text", array("placeholder" => T("Forum title"))); ?></li>
		<li class='advanced'><?php echo $form->input("baseURL", "text", array("placeholder" => T("Base URL"))); ?></li>
		<li class='advanced'><label><?php echo $form->checkbox("friendlyURLs"); ?> <?php echo T("Use friendly URLs"); ?></label></li>
	</ul>

	<br>

		<ul class='form'>
			<li class='clear'><label><?php echo T("Database driver"); ?></label><?php echo $form->select("databaseDriver", array("sqlite" => "SQLite (recommended)", "mysql" => "MySQL / MariaDB (PDO)")); ?></li>
			<li class='clear sqliteDatabase'><?php echo $form->input("sqlitePath", "text", array("placeholder" => T("SQLite file path"))); ?><small>Use an absolute path or a path relative to the forum directory.</small></li>
			<li class='half mysqlDatabase'><?php echo $form->input("mysqlHost", "text", array("placeholder" => T("MySQL Host"))); ?></li>
			<li class='half mysqlDatabase'><?php echo $form->input("mysqlUser", "text", array("placeholder" => T("MySQL Username"))); ?></li>
			<li class='half clear mysqlDatabase'><?php echo $form->input("mysqlPass", "password", array("placeholder" => T("MySQL Password"))); ?></li>
			<li class='half mysqlDatabase'><?php echo $form->input("mysqlDB", "text", array("placeholder" => T("MySQL Database"))); ?></li>
			<li class='advanced clear'><?php echo $form->input("tablePrefix", "text", array("placeholder" => T("Table Prefix"))); ?></li>
			<li class='clear'><?php echo $form->getError("database"); ?><?php echo $form->getError("mysql"); ?></li>
		</ul>

	<br>

	<ul class='form'>
		<li class='half'><?php echo $form->input("adminUser", "text", array("placeholder" => T("Admin Username"))); ?></li>
		<li class='half'><?php echo $form->input("adminEmail", "text", array("placeholder" => T("Admin Email"))); ?></li>
		<li class='half clear'><?php echo $form->input("adminPass", "password", array("placeholder" => T("Admin Password"))); ?></li>
		<li class='half'><?php echo $form->input("adminConfirm", "password", array("placeholder" => T("Confirm Password"))); ?></li>
	</ul>

	<br>

	<ul class='form' style='text-align:center'>
		<li><?php echo $form->button("submit", T("Install esoTalk")." &#155;", array("class" => "submit")); ?></li>
		<li><a href='#advanced' id='advancedLink'><?php echo T("Advanced Options"); ?></a></li>
	</ul>

	<script>
		$(function() {
			function updateDatabaseFields() {
				var sqlite = $("select[name='databaseDriver']").val() === "sqlite";
				$(".sqliteDatabase").toggle(sqlite);
				$(".mysqlDatabase").toggle(!sqlite);
			}
			$("select[name='databaseDriver']").on("change", updateDatabaseFields);
			updateDatabaseFields();
			$("#advancedLink").click(function(e) {
				e.preventDefault();
				$(".advanced").slideToggle("fast");
			});
			<?php if (empty($form->errors["tablePrefix"])): ?>$(".advanced").hide();<?php endif; ?>
		});
	</script>

	<?php echo $form->close(); ?>
</div>
