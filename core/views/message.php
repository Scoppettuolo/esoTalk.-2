<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a modal message sheet. Used by ETController::renderMessage().
 *
 * @package esoTalk
 */
$title = isset($data["title"]) ? $data["title"] : T("Message");
$message = isset($data["message"]) ? $data["message"] : "";
$return = R("return", "");
?>
<div class='sheet' id='messageSheet'>
<div class='sheetContent'>

<h3><?php echo sanitizeHTML($title); ?></h3>

<div class='section help'><?php echo $message; ?></div>

<div class='buttons'>
<a href='<?php echo URL($return); ?>' class='button big'><?php echo T("OK"); ?></a>
</div>

</div>
</div>
