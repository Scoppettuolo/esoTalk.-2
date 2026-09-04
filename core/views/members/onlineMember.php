<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the online members sheet.
 *
 * @package esoTalk
 */
$member = $data["member"];
?>
<li>
<span class='action'>
<?php echo avatar($member, "thumb"), " ", memberLink($member["memberId"], $member["username"]), " "; ?>
<?php
$action = ET::memberModel()->getLastActionInfo($member["lastActionTime"], $member["lastActionDetail"]);
if ($action[0]) printf(T("is %s"), (!empty($action[1]) ? "<a href='{$action[1]}'>" : "").lcfirst(sanitizeHTML($action[0])).(!empty($action[1]) ? "</a>" : ""));
?>
</span>
</li>