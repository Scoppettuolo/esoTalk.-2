<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a fatal error that has occurred in the esoTalk installer.
 *
 * @package esoTalk
 */
?>
<h1><?php echo T("Fatal Error"); ?></h1>

<h2><?php printf(T("message.fatalError"), "https://github.com/Scoppettuolo/docs/debug"); ?></h2>

<div class='details'>
	<div class='code'><?php echo $data["error"]; ?></div>
</div>

<br>

<p>
	<a href='<?php echo URL("install/info"); ?>' class='button submit'>&#139; <?php echo T("Go Back"); ?></a>
</p>
