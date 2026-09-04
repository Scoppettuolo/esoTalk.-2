<?php if (!defined("IN_ESOTALK")) exit; ?>
<div class="sheet"><div class="sheetContent">
	<p>To set a channel color, edit the channel in <strong>Admin → Channels</strong> and add a field named <code>color</code> in attributes, or use the color picker injected on the edit form (hex e.g. <code>#2563eb</code>).</p>
	<p>Palette suggestions:</p>
	<div style="display:flex;flex-wrap:wrap;gap:6px;">
	<?php foreach ($data["palette"] as $c): ?>
		<span style="width:28px;height:28px;border-radius:6px;background:<?php echo $c; ?>" title="<?php echo $c; ?>"></span>
	<?php endforeach; ?>
	</div>
</div></div>
