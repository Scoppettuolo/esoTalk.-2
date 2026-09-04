<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * The unapproved admin controller allows administrators to approve newly signed up members.
 * It is not accessible unless esoTalk.registration.requireConfirmation == approval.
 *
 * @package esoTalk
 */
class ETUnapprovedAdminController extends ETAdminController {


/**
 * Show a sheet containing a list of groups. Pretty simple, really!
 *
 * @return void
 */
public function action_index()
{
	ET::activityModel()->markNotificationsAsRead('unapproved');
	
	$sql = ET::SQL();
	if (C("esoTalk.registration.requireConfirmation") === "email+approval")
		$sql->where("(confirmed=0 OR account='".ACCOUNT_PENDING."')");
	else
		$sql->where("confirmed", 0);
	$sql->orderBy("m.memberId desc");
	$members = ET::memberModel()->getWithSQL($sql);

	$this->data("members", $members);
	$this->render("admin/unapproved");
}


/**
 * Approve a member.
 *
 * @param int $memberId The ID of the member to approve.
 * @return void
 */
public function action_approve($memberId)
{
	if (!$this->validateToken()) return;

	// Get this member's details. In combined mode approval is tracked by account=pending.
	$member = ET::memberModel()->getById((int)$memberId);
	$combined = C("esoTalk.registration.requireConfirmation") === "email+approval";
	if (!$member || ($combined ? $member["account"] !== ACCOUNT_PENDING : $member["confirmed"])) {
		$this->redirect(URL("admin/unapproved"));
		return;
	}

	$updates = array("account" => ACCOUNT_MEMBER);
	if (!$combined) $updates["confirmed"] = true;
	ET::memberModel()->updateById($memberId, $updates);

	sendEmail($member["email"],
		sprintf(T("email.approved.subject"), $member["username"]),
		sprintf(T("email.header"), $member["username"]).sprintf(T("email.approved.body"), C("esoTalk.forumTitle"), URL("user/login", true))
	);

	$this->message(T("message.changesSaved"), "success autoDismiss");
	$this->redirect(URL("admin/unapproved"));
}


/**
 * Deny a member; delete their account.
 *
 * @param int $memberId The ID of the member to deny.
 * @return void
 */
public function action_deny($memberId)
{
	// Get this member's details. In combined mode account=pending remains until approval.
	$member = ET::memberModel()->getById((int)$memberId);
	$combined = C("esoTalk.registration.requireConfirmation") === "email+approval";
	if (!$member || ($combined ? $member["account"] !== ACCOUNT_PENDING : $member["confirmed"])) {
		$this->redirect(URL("admin/unapproved"));
		return;
	}

	ET::memberModel()->deleteById($memberId);

	$this->message(T("message.changesSaved"), "success autoDismiss");
	$this->redirect(URL("admin/unapproved"));
}

}
