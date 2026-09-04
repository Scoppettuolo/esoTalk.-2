<?php
if (!defined("IN_ESOTALK")) exit;
$info = C("esoTalk.admin.lastUpdateCheckInfo", array("version" => ESOTALK_VERSION));
?>
<div id='adminUpdateNotification' class='area help admin-update-banner'>
<?php if (version_compare($info["version"], ESOTALK_VERSION, ">")): ?>
	<h3><?php printf(T("message.esoTalkUpdateAvailable"), $info["version"]); ?></h3>
	<p><?php echo T("message.esoTalkUpdateAvailableHelp"); ?></p>
	<?php if (!empty($info["releaseNotes"])): ?>
		<p><a href='<?php echo $info["releaseNotes"]; ?>' target='_blank' class='button'><?php echo T("Upgrade Now"); ?></a></p>
	<?php endif; ?>
<?php else: ?>
	<h3><?php echo T("message.esoTalkUpToDate"); ?></h3>
	<p class='subText'><?php echo T("Your forum is running the latest modern build."); ?></p>
<?php endif; ?>
</div>
