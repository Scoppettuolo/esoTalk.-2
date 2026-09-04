<?php
if (!defined("IN_ESOTALK")) exit;

$form = $data["form"];
$conversation = $data["conversation"];
$tagValue = !empty($conversation["classicTagsString"]) ? $conversation["classicTagsString"] : (string)(R("tags") ?: "");
if ($tagValue === "" && !empty($conversation["conversationId"])) {
	try {
		$result = ET::SQL()->select("tag")->from("conversation_tag")->where("conversationId=:conversationId")->bind(":conversationId", (int)$conversation["conversationId"])->orderBy("tag ASC")->exec();
		$existingTags = array();
		while ($row = $result->nextRow()) $existingTags[] = $row["tag"];
		$tagValue = implode(", ", $existingTags);
	} catch (Exception $e) {
		/* The plugin setup may not have run yet. */
	}
}
echo $form->open();
?>
<div id='conversation' class='editing'>
<div id='conversationHeader' class='bodyHeader'>
<h1 id='conversationTitle'><?php echo $form->input("title", "text", array("placeholder" => T("Enter a conversation title"), "tabindex" => 100, "maxlength" => 100)); ?></h1>
<?php $this->renderView("conversation/channelPath", array("conversation" => $conversation)); ?>
<a href='<?php echo URL("conversation/changeChannel/".$conversation["conversationId"]); ?>' id='control-changeChannel'><i class='icon-tag'></i> <?php echo T("Change channel"); ?></a>
<div class='classic-tags-editor'>
<label for='classicTags'><?php echo T("Tags"); ?></label>
<input id='classicTags' name='tags' type='text' class='text' maxlength='500' value='<?php echo sanitizeHTML($tagValue); ?>' placeholder='<?php echo sanitizeHTML(T("tag-one, tag-two")); ?>' />
<small class='help'><?php echo T("Separate tags with commas or spaces."); ?></small>
</div>
</div>
<?php if ($conversation["conversationId"]): ?>
<?php echo $form->saveButton(); ?>
<a href='<?php echo URL(R("return", conversationURL($conversation["conversationId"], $conversation["title"]))); ?>' class='button cancel'><?php echo T("Cancel"); ?></a>
<?php endif; ?>
<?php if (!$conversation["conversationId"]): ?>
<div id='conversationPrivacy' class='area'>
<span class='allowedList action'><?php $this->renderView("conversation/membersAllowedSummary", $data); ?></span>
<a href='#membersAllowedSheet' id='control-changeMembersAllowed'><i class='icon-pencil'></i> <?php echo T("Change"); ?></a>
</div>
<div id='conversationReply'>
<?php $this->renderView("conversation/reply", array("form" => $form, "conversation" => $conversation, "controls" => $data["replyControls"])); ?>
</div>
<?php endif; ?>
<?php echo $form->close(); ?>
</div>
<?php if ($conversation["startMemberId"] == ET::$session->userId || $conversation["canModerate"]): ?>
<?php echo $data["membersAllowedForm"]->open(); ?>
<?php $this->renderView("conversation/editMembersAllowed", array("form" => $data["membersAllowedForm"], "conversation" => $conversation)); ?>
<?php echo $data["membersAllowedForm"]->close(); ?>
<?php endif; ?>
