<?php
if (!defined("IN_ESOTALK")) exit;

$post = $data["post"];
$class = $post["class"] ?? array();
if (!is_array($class)) $class = preg_split('/\s+/', trim((string)$class));
$class = array_filter($class);
?>
<div class='post hasControls <?php echo sanitizeHTML(implode(" ", $class)); ?>' id='<?php echo sanitizeHTML($post["id"]); ?>'<?php
if (!empty($post["data"])):
foreach ((array)$post["data"] as $key => $value):
	echo " data-".sanitizeHTML($key)."='".sanitizeHTML($value)."'";
endforeach;
endif; ?>
>
<?php if (!empty($post["avatar"])): ?>
<div class='avatar'<?php if (!empty($post["hideAvatar"])): ?> style='display:none'<?php endif; ?>><?php echo $post["avatar"]; ?></div>
<?php endif; ?>
<div class='postContent thing'>
<div class='postHeader'>
<div class='info'>
<h3><?php echo $post["title"]; ?></h3>
<?php if (!empty($post["info"])) foreach ((array)$post["info"] as $info) echo $info, "\n"; ?>
</div>
<div class='controls'>
<?php if (!empty($post["controls"])) foreach ((array)$post["controls"] as $control) echo $control, "\n"; ?>
</div>
</div>
<?php if ($post["body"] !== false && $post["body"] !== null): ?>
<div class='postBody'><?php echo $post["body"]; ?></div>
<?php endif; ?>
<?php if (!empty($post["footer"])): ?>
<div class='postFooter'><?php foreach ((array)$post["footer"] as $footer) echo $footer, "\n"; ?></div>
<?php endif; ?>
</div>
</div>
