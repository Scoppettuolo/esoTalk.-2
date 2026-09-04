<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * JSON master view. Displays messages and data collected in the controller as a JSON object.
 *
 * @package esoTalk
 */

$this->json("messages", $this->getMessages());

$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS;
if (defined("JSON_INVALID_UTF8_SUBSTITUTE")) $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
echo json_encode($this->json, $flags);