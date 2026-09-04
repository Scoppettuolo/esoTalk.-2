<?php
if (!defined("IN_ESOTALK")) exit;
?>
<div id='conversationsFilter' class='bodyHeader'>
<form class='search big' id='search' action='<?php echo URL("conversations/".$data["channelSlug"]); ?>' method='get'>
<fieldset>
<i class='icon-search'></i>
<input name='search' type='text' class='text' value='<?php echo sanitizeHTML($data["searchString"]); ?>' spellcheck='false' placeholder='<?php echo T("Search conversations..."); ?>'/>
<a class='control-reset' href='<?php echo URL("conversations/".$data["channelSlug"]); ?>'><i class='icon-remove'></i></a>
</fieldset>
</form>
<ul id='channels' class='channels tabs'>
<li class='channelListItem'><a href='<?php echo URL("channels"); ?>' class='channel-list' data-channel='list' title='<?php echo T("Channel List"); ?>'><i class='icon-list'></i></a></li>
<?php $this->renderView("channels/tabs", $data); ?>
</ul>
<?php if ($data["controlsMenu"]->count()): ?>
<ul id='searchControls' class='controls'><?php echo $data["controlsMenu"]->getContents(); ?></ul>
<?php endif; ?>
<?php if ($data["gambitsMenu"]->count()): ?>
<div id='gambits'><ul class='popupMenu'><?php echo $data["gambitsMenu"]->getContents(); ?></ul></div>
<?php endif; ?>
<?php if (!empty($data["classicTagCloud"])): ?>
<div class='classic-tag-cloud' aria-label='<?php echo sanitizeHTML(T("Popular tags")); ?>'>
<span class='classic-tag-cloud-title'><?php echo T("Popular tags"); ?></span>
<?php
$maxUses = 1;
foreach ($data["classicTagCloud"] as $cloudTag) $maxUses = max($maxUses, (int)$cloudTag["uses"]);
foreach ($data["classicTagCloud"] as $cloudTag):
	$tag = (string)$cloudTag["tag"];
	$scale = 85 + round(75 * ((int)$cloudTag["uses"] / $maxUses));
	$url = URL("conversations/".$data["channelSlug"]."/?search=".urlencode("#tag:".$tag));
?>
<a class='classic-tag' style='font-size:<?php echo $scale; ?>%' href='<?php echo $url; ?>' title='<?php echo sanitizeHTML(sprintf(T("Used %s times"), (int)$cloudTag["uses"])); ?>'><?php echo sanitizeHTML($tag); ?></a>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<div id='conversations'><?php $this->renderView("conversations/results", $data); ?></div>
