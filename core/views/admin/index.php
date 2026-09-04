<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;


?>
<div id='admin' class='clearfix'>

<ul id='adminMenu' class='tabs big'>

<?php echo $data["defaultMenu"]->getContents(); ?>

<?php if ($data["menu"]->count()): ?>
<li class='separator'></li>

<?php echo $data["menu"]->getContents(); ?>
<?php endif; ?>

</ul>

<div id='adminContent'>

<?php $this->renderView($data["view"], $data); ?>

</div>

</div>