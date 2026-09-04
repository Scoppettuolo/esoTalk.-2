<?php
if (!defined("IN_ESOTALK")) exit;
$form = $data["gravatarForm"];
echo $form->open();
?>
<ul class="form">
<li><label><?php echo T("Avatar size"); ?></label> <?php echo $form->input("size", "number", array("min" => 16, "max" => 512)); ?> <small class="help">16–512 px</small></li>
<li><label><?php echo T("Rating"); ?></label>
<select name="rating" class="input">
<?php foreach (array("g" => "G", "pg" => "PG", "r" => "R", "x" => "X") as $k => $l): ?>
<option value="<?php echo $k; ?>"<?php if ($form->getValue("rating") === $k) echo " selected"; ?>><?php echo $l; ?></option>
<?php endforeach; ?>
</select>
</li>
<li><label><?php echo T("Default style"); ?></label>
<select name="default" class="input">
<?php foreach (array("identicon","mp","monsterid","wavatar","retro","robohash","blank") as $d): ?>
<option value="<?php echo $d; ?>"<?php if ($form->getValue("default") === $d) echo " selected"; ?>><?php echo $d; ?></option>
<?php endforeach; ?>
</select>
</li>
<li class="sep"></li>
<li><?php echo $form->button("save", T("Save Changes")); ?></li>
</ul>
<?php echo $form->close(); ?>
