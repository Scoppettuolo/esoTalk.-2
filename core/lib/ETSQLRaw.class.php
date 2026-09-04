<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * Simple object that represents a raw SQL expression. If this is passed as a value in a query
 * constructed with ETSQLQuery, the value it represents will not be sanitized.
 *
 * @package esoTalk
 */
class ETSQLRaw {


/**
 * The raw expression.
 * @var string
 */
public $expression = "";


public function __construct($expression = "")
{
	$this->expression = $expression;
}


public function __toString()
{
	return $this->expression;
}


}