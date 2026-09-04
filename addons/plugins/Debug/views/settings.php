<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the settings form for the Debug plugin.
 *
 * @package esoTalk
 */

$form = $data["debugSettingsForm"];
?>
<?php echo $form->open(); ?>

<div class='section'>

<ul class='form'>

<li>
<label>Database</label>
<?php echo $form->button("upgradeDB", "Upgrade Database"); ?>
</li>

</ul>

</div>

<?php echo $form->close(); ?>
