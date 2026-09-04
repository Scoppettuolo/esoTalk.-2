<?php
if (!defined("IN_ESOTALK")) exit;
?>
<div class='sheet'>
<div class='sheetContent'>
	<h3><?php echo T("Page Not Found"); ?></h3>
	<div class='section'>
		<p><?php echo T("The page you're looking for could not be found."); ?></p>
		<p>
			<a href='<?php echo URL(""); ?>' class='button'><?php echo T("Home"); ?></a>
			<a href='<?php echo URL("conversations"); ?>' class='button'><?php echo T("Conversations"); ?></a>
		</p>
		<?php if (C("esoTalk.debug")): ?>
		<p class='help' style="margin-top:1em;font-size:12px;opacity:.7">
			Debug: selfURL=<?php echo sanitizeHTML(isset(ET::$controller) ? ET::$controller->selfURL : ""); ?>
			| webPath=<?php echo sanitizeHTML(ET::$webPath); ?>
			| controller=<?php echo sanitizeHTML(isset(ET::$controllerName) ? ET::$controllerName : ""); ?>
		</p>
		<?php endif; ?>
	</div>
</div>
</div>
