<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet with a form to edit a channel's details and permissions.
 *
 * @package esoTalk
 */

$channel = $data["channel"];
$form = $data["form"];
?>
<div class='sheet' id='editChannelSheet'>
<div class='sheetContent'>

<h3><?php echo T(!empty($channel) ? "Edit Channel" : "Create Channel"); ?></h3>

<?php echo $form->open(); ?>

<div class='sheetBody'>

<div class='section'>

<ul class='form'>

<li>
<label><?php echo T("Channel title"); ?></label>
<?php echo $form->input("title"); ?>
</li>

<li>
<label><?php echo T("Channel slug"); ?></label>
<?php echo $form->input("slug", "text", array("id" => "channelSlug")); ?>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Channel description"); ?></label>
<?php echo $form->input("description", "textarea"); ?>
<small><?php echo T("HTML is allowed."); ?></small>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Color"); ?></label>
<?php echo $form->input("color", "text", array("placeholder" => "#2563eb", "id" => "channelColorInput", "style" => "width:120px")); ?>
<small><?php echo T("Channel color (hex). Leave empty for default."); ?></small>
<div class="channel-color-swatches" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">
<?php
$swatches = array("#64748b","#2563eb","#7c3aed","#db2777","#dc2626","#ea580c","#ca8a04","#16a34a","#0d9488","#0891b2");
foreach ($swatches as $c) {
	echo "<a href='#' class='color-swatch' data-color='$c' title='$c' style='display:inline-block;width:24px;height:24px;border-radius:4px;background:$c;border:1px solid rgba(0,0,0,.2)'></a>";
}
?>
</div>
<script>
(function(){
	function bind(){
		var nodes = document.querySelectorAll(".color-swatch");
		for (var i=0;i<nodes.length;i++){
			nodes[i].onclick = function(e){
				e.preventDefault();
				var inp = document.getElementById("channelColorInput");
				if (inp) inp.value = this.getAttribute("data-color");
			};
		}
	}
	if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", bind);
	else bind();
})();
</script>
</li>

<li class='sep'></li>

<li>
<label><?php echo T("Subscription"); ?></label>
<div class='checkboxGroup'>
<label class='checkbox'><?php echo $form->checkbox("defaultUnsubscribed"); ?> <?php echo T("Unsubscribe new users by default"); ?></label>
</div>
</li>

</ul>

</div>

<div class='section'>

<!-- Permissions Grid -->
<table id='channelPermissions' class='permissionsGrid'>
<thead><tr><td>&nbsp;</td><?php foreach ($data["permissions"] as $k => $v): ?><th class='permissionColumn permission-<?php echo $k; ?>'><?php echo T($v); ?></th><?php endforeach; ?></tr></thead>
<tbody>

<tr id='permissions-guests'>
<th><?php echo groupName("guest", true); ?></th>
<?php foreach ($data["permissions"] as $k => $v): ?>
<td><?php if ($k == "view"): echo $form->checkbox("permissions[".GROUP_ID_GUEST."][$k]", array("class" => "permission-$k")); endif; ?></td>
<?php endforeach; ?>
</tr>

<tr id='permissions-members'>
<th><?php echo groupName("member", true); ?></th>
<?php foreach ($data["permissions"] as $k => $v): ?>
<td><?php if ($k != "moderate"): echo $form->checkbox("permissions[".GROUP_ID_MEMBER."][$k]", array("class" => "permission-$k")); endif; ?></td>
<?php endforeach; ?>
</tr>

<?php foreach ($data["groups"] as $id => $group): ?>
<tr class='group'>
<th><?php echo groupName($group["name"], true); ?></th>
<?php foreach ($data["permissions"] as $k => $v): ?>
<td><?php echo $form->checkbox("permissions[$id][$k]", array("class" => "permission-$k")); ?></td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>

<tr id='permissions-administrators'>
<th><?php echo groupName("administrator", true); ?></th>
<?php foreach ($data["permissions"] as $k => $v): ?>
<td><input type='checkbox' checked='checked' disabled='disabled'/></td>
<?php endforeach; ?>
</tr>

</tbody>
</table>

<div class='subText' id='permissions-copy'><?php echo T("Copy permissions from"); ?> <?php
$copyOptions = array("" => "");
foreach ($data["channels"] as $id => $channel) $copyOptions[$id] = str_repeat("&nbsp;", $channel["depth"] * 5).$channel["title"];
echo $form->select("copyPermissions", $copyOptions);
?></div>

</div>

</div>

<div class='buttons'>
<?php echo $form->saveButton(); ?>
<?php echo $form->cancelButton(); ?>
</div>

<?php echo $form->close(); ?>

</div>
</div>