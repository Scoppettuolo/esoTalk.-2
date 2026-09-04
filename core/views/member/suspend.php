<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet with a form to toggle the suspension of a member.
 *
 * @package esoTalk
 */

$member = $data["member"];
$form = $data["form"];
$isSuspended = $member["account"] == ACCOUNT_SUSPENDED;
?>
<div class='sheet' id='suspendSheet'>
<div class='sheetContent'>

<?php echo $form->open(); ?>

<h3><?php echo T($isSuspended ? "Unsuspend" : "Suspend"); ?> <?php echo $member["username"]; ?></h3>

<div class='sheetBody'>

<div class='section'>
<p>
<?php printf(T($isSuspended ? "message.unsuspendMemberHelp" : "message.suspendMemberHelp"), $member["username"]); ?>
</p>
</div>

</div>

<div class='buttons'>
<?php echo $form->button($isSuspended ? "unsuspend" : "suspend", T($isSuspended ? "Unsuspend" : "Suspend"), array("class" => "big submit")); ?>
<?php echo $form->cancelButton(); ?>
</div>

<?php echo $form->close(); ?>

</div>
</div>
