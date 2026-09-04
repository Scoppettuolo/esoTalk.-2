<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Shows a list of the most recent posts on the esoTalk blog.
 *
 * @package esoTalk
 */
?>
<ul class='list'>
<?php foreach ($data["posts"] as $post): ?>
<li>
<h4><a href='<?php echo $post["link"]; ?>' target='_blank'><?php echo $post["title"]; ?></a></h4> <small><?php echo ucfirst(relativeTime($post["ts"])); ?></small>
<p><?php echo $post["summary"]; ?> <a href='<?php echo $post["link"]; ?>' target='_blank'><?php echo T("Read more"); ?> &raquo;</a></p>
</li>
<?php endforeach; ?>
</ul>