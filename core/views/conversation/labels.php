<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a list of labels that apply to a conversation.
 *
 * @package esoTalk
 */

foreach ($data["labels"] as $label) {
	echo "<span class='label label-$label' title='".T("label.$label")."'><i class='".ETConversationModel::$labels[$label][1]."'></i></span>\n";
}

?>