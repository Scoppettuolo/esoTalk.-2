<?php
if (!defined("IN_ESOTALK")) exit;
?>

<?php if (!empty($data["showWelcomeSheet"])): ?>
<div class='sheet' id='adminWelcomeSheet'>
<div class='sheetContent'>
<h3><?php echo T("Welcome to esoTalk!"); ?></h3>
<div class='section'>
<p><?php echo T("We've logged you in and taken you straight to your forum's administration panel. You're welcome."); ?></p>
<p><?php echo T("To get started with your forum, you might like to:"); ?></p>
<ul>
<li><a href='<?php echo URL("admin/appearance"); ?>'><?php echo T("Customize your forum's appearance"); ?></a></li>
<li><a href='<?php echo URL("admin/channels"); ?>'><?php echo T("Manage your forum's channels (categories)"); ?></a></li>
<li><a href='<?php echo URL("conversation/start"); ?>'><?php echo T("Start a new conversation"); ?></a></li>
</ul>
</div>
</div>
</div>
<?php endif; ?>

<?php $this->renderView("admin/updateNotification"); ?>

<div class='admin-dashboard-grid'>

	<div class='area admin-card' id='adminStatistics'>
		<h3><?php echo T("Forum Statistics"); ?></h3>
		<ul class='form admin-stats-list'>
		<?php foreach ($data["statistics"] as $k => $v): ?>
			<li><label><?php echo $k; ?></label> <span><?php echo $v; ?></span></li>
		<?php endforeach; ?>
		</ul>
	</div>

	<div class='area admin-card' id='adminSystemInfo'>
		<h3><?php echo T("System"); ?></h3>
		<ul class='form admin-stats-list'>
			<li><label><?php echo T("esoTalk version"); ?></label> <span><?php echo ESOTALK_VERSION; ?></span></li>
			<li><label><?php echo T("PHP version"); ?></label> <span><?php echo phpversion(); ?></span></li>
			<li><label><?php echo T("MySQL version"); ?></label> <span><?php echo ET::SQL("SELECT VERSION()")->result(); ?></span></li>
		</ul>
	</div>

	<div class='area admin-card' id='adminQuickLinks'>
		<h3><?php echo T("Quick links"); ?></h3>
		<ul class='admin-quick-links'>
			<li><a href='<?php echo URL("admin/settings"); ?>'><i class='icon-cog'></i> <?php echo T("Forum Settings"); ?></a></li>
			<li><a href='<?php echo URL("admin/appearance"); ?>'><i class='icon-eye-open'></i> <?php echo T("Appearance"); ?></a></li>
			<li><a href='<?php echo URL("admin/channels"); ?>'><i class='icon-tags'></i> <?php echo T("Channels"); ?></a></li>
			<li><a href='<?php echo URL("admin/plugins"); ?>'><i class='icon-puzzle-piece'></i> <?php echo T("Plugins"); ?></a></li>
			<li><a href='<?php echo URL("admin/groups"); ?>'><i class='icon-group'></i> <?php echo T("Groups"); ?></a></li>
			<li><a href='<?php echo URL("members"); ?>'><i class='icon-user'></i> <?php echo T("Members"); ?></a></li>
			<li><a href='<?php echo URL("blog/manage"); ?>'><i class='icon-book'></i> <?php echo T("Manage Blog"); ?></a></li>
			<li><a href='<?php echo URL("conversation/start"); ?>'><i class='icon-plus'></i> <?php echo T("New Conversation"); ?></a></li>
		</ul>
	</div>

</div>
