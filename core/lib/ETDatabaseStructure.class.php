<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * The database structure class provides a wrapper for defining table structures so that they can be easily
 * manipluated and upgraded without having to run arbitrary queries in a linear upgrade script.
 *
 * Similar to the SQLQuery class, this implementation tries to be as SQL-neutral as possible, but is
 * ultimately written to work with MySQL. It can be extended to provide structure management for a different
 * database engine.
 *
 * @package esoTalk
 */
#[\AllowDynamicProperties]
class ETDatabaseStructure {


/**
 * The name of the table currently being constructed.
 * @var string
 */
protected $tableName = "";


/**
 * The engine of the table currently being constructed.
 * @var string
 */
protected $engine = "";


/**
 * Whether or not the table currently exists.
 * @var bool
 */
protected $exists = null;


/**
 * An array of existing columns and their information.
 * @var array
 */
protected $existingColumns = null;


/**
 * An array of existing keys and their information.
 * @var array
 */
protected $existingKeys = null;


/**
 * An array of columns in the table currently being constructed.
 * @var array
 */
protected $columns = array();


/**
 * An array of keys in the table currently being constructed.
 * @var array
 */
protected $keys = array();


/**
 * Class constructor: reset all variables in the instance so we have a fresh slate.
 *
 * @return void
 */
public function __construct()
{
	$this->reset();
}


/**
 * Reset the instance so that all structure information is cleared.
 *
 * @return ETDatabaseStructure
 */
public function reset()
{
	$this->tableName = "";
	$this->engine = "InnoDB";
	$this->columns = array();
	$this->keys = array();
	$this->exists = null;
	$this->existingColumns = null;
	$this->existingKeys = null;

	return $this;
}


/**
 * Start the construction of a table.
 *
 * @param string $tableName The name of the table.
 * @param string $engine The table engine that the table should use.
 * @return ETDatabaseStructure
 */
public function table($tableName, $engine = "InnoDB")
{
	$this->reset();
	$this->tableName = $tableName;
	$this->engine = $engine;

	return $this;
}


/**
 * Add a column to the table.
 *
 * @param string $name The name of the column.
 * @param string $type The type signature of the column.
 * @param mixed $default The default value of the column. If this is false, the column will be NOT NULL.
 * @return ETDatabaseStructure
 */
public function column($name, $type, $default = null)
{
	$this->columns[$name] = array("type" => $type, "null" => $default !== false, "default" => $default);

	return $this;
}


/**
 * Add a key to the table.
 *
 * @param mixed $columns The name of the column, or an array of columns, to index.
 * @param string $type The type of key: primary, unique, fulltext, or empty for a normal key.
 * @return ETDatabaseStructure
 */
public function key($columns, $type = "")
{
	$columns = (array)$columns;

	// If this is a primary key, and there's only one column, and that column's type is an int, set auto
	// increment on it.
	if ($type == "primary" and count($columns) == 1 and substr($this->columns[$columns[0]]["type"], 0, 3) == "int")
		$this->columns[$columns[0]]["autoIncrement"] = true;

	// If it's not a primary key, we must work out a name for the key.
	if ($type != "primary") $name = $this->tableName."_".implode("_", $columns);
	else {
		$name = "PRIMARY";
		foreach ($columns as $column) $this->columns[$column]["null"] = false;
	}

	// Add the key to the keys array.
	$this->keys[$name] = array("type" => $type, "columns" => $columns);

	return $this;
}


/**
 * Execute the queries necessary to bring the database structure up-to-date with the one that has been defined
 * in the instance.
 *
 * @param bool $drop Whether or not an existing table should be dropped before creating one adhering to the
 * 		structure definition.
 * @return ETDatabaseStructure
 */
public function exec($drop = false)
{
	if (ET::$database->isSQLite()) return $this->execSQLite($drop);

	// Do we need to drop the table before we recreate it?
	if ($drop) {
		ET::SQL("DROP TABLE IF EXISTS `".ET::$database->tablePrefix.$this->tableName."`");
		$this->exists = false;
	}

	// Firstly, work out whether or not the table exists. If it doesn't, we'll have to create the table from
	// scratch.
	if (!$this->exists()) {
		$sql = "CREATE TABLE `".ET::$database->tablePrefix.$this->tableName."` (\n\t";
		$lines = array();

		// Add the columns.
		foreach ($this->columns as $name => $column) {
			$lines[] = "`$name` ".$this->columnDefinition($column);
		}

		// Add the keys.
		foreach ($this->keys as $name => $key) {
			$lines[] = $this->keyDefinition($name, $key);
		}

		// Put it all together.
		$sql .= implode(",\n\t", $lines)."\n)";
		if ($this->engine) $sql .= " ENGINE=$this->engine";
		$charset = C("esoTalk.database.characterEncoding") ?: "utf8mb4";
		$sql .= " DEFAULT CHARSET=".$charset." COLLATE=".$charset."_unicode_ci";

		ET::SQL($sql);
	}

	// Otherwise, based on what's already there, we need to modify the table to be up-to-date.
	else {
		$alterPrefix = "ALTER TABLE `".ET::$database->tablePrefix.$this->tableName."`";

		// Set the table's engine and character set if necessary.
		$engine = ET::SQL("SHOW TABLE STATUS LIKE '".ET::$database->tablePrefix.$this->tableName."'")->firstRow();
		$engine = $engine["Engine"];
		if ($engine != $this->engine)
			ET::SQL($alterPrefix." ENGINE=$this->engine");

		// Go through the columns and add/modify them as necessary.
		$existingColumns = $this->existingColumns();
		$previousColumn = false;
		foreach ($this->columns as $name => $column) {

			$definition = $this->columnDefinition($column);

			// If the column doesn't exist, we'll need to add it.
			if (!array_key_exists($name, $existingColumns)) {
				ET::SQL($alterPrefix." ADD `$name` ".$definition.($previousColumn !== false ? " AFTER `$previousColumn`" : ""));
			}

			// If it does exist, work out if we need to modify it.
			else {
				$existingDefinition = $this->columnDefinition($existingColumns[$name]);
				if ($definition != $existingDefinition) {
					ET::SQL($alterPrefix." MODIFY `$name` ".$definition);
				}
			}

			$previousColumn = $name;

		}

		// Go through the keys and add/modify them as necessary.
		$existingKeys = $this->existingKeys();
		foreach ($this->keys as $name => $key) {

			$definition = $this->keyDefinition($name, $key);

			// If this key already exists, and it's different to the one we want, drop it and re-add it.
			if (array_key_exists($name, $existingKeys)) {
				$existingDefinition = $this->keyDefinition($name, $existingKeys[$name]);
				if ($definition != $existingDefinition) {
					ET::SQL($alterPrefix." DROP ".($name == "PRIMARY" ? "PRIMARY KEY" : "KEY `$name`"));
					ET::SQL($alterPrefix." ADD ".$definition);
				}
			}

			// If the key doesn't exist, just add it.
			else {
				ET::SQL($alterPrefix." ADD ".$definition);
			}

		}
	}

	$this->reset();
}

/**
 * Execute the schema definition using SQLite DDL and PRAGMA metadata.
 * SQLite intentionally ignores MySQL storage engines, collations and FULLTEXT declarations.
 */
protected function execSQLite($drop = false)
{
	$table = ET::$database->tablePrefix.$this->tableName;
	$quotedTable = "`".$table."`";
	if ($drop) {
		ET::SQL("DROP TABLE IF EXISTS $quotedTable");
		$this->exists = false;
	}

	if (!$this->exists()) {
		$lines = array();
		foreach ($this->columns as $name => $column)
			$lines[] = "`$name` ".$this->columnDefinition($column);
		foreach ($this->keys as $name => $key) {
			$definition = $this->keyDefinition($name, $key);
			if ($definition !== "") $lines[] = $definition;
		}
		ET::SQL("CREATE TABLE $quotedTable (".implode(", ", $lines).")");
		$this->exists = true;
	} else {
		$existingColumns = $this->existingColumns();
		foreach ($this->columns as $name => $column) {
			if (!array_key_exists($name, $existingColumns))
				ET::SQL("ALTER TABLE $quotedTable ADD COLUMN `$name` ".$this->columnDefinition($column));
		}
	}

	// SQLite does not support MySQL's KEY syntax in CREATE TABLE. Create ordinary and unique
	// indexes separately, and treat FULLTEXT as a normal index for compatibility.
	foreach ($this->keys as $name => $key) {
		if ($name === "PRIMARY") continue;
		$type = ($key["type"] === "unique") ? "UNIQUE " : "";
		$columns = array_map(function($column) { return "`$column`"; }, $key["columns"]);
		ET::SQL("CREATE ".$type."INDEX IF NOT EXISTS `$name` ON $quotedTable (".implode(",", $columns).")");
	}

	$this->reset();
	return $this;
}


/**
 * Get the SQL definition string for a column (eg. int(11) unsigned NOT NULL AUTO_INCREMENT).
 *
 * @param array $column The column details.
 * @return string
 */
protected function columnDefinition($column)
{
	if (ET::$database->isSQLite()) {
		$type = $column["type"];
		$type = preg_replace('/\\s+unsigned\\b/i', '', $type);
		$type = preg_replace('/\\b(?:tinyint|smallint|mediumint|int|bigint)(?:\\(\\d+\\))?/i', 'INTEGER', $type);
		$type = preg_replace('/\\b(?:tinyblob|blob|mediumblob|longblob)\\b/i', 'BLOB', $type);
		$type = preg_replace('/\\benum\\s*\\([^)]*\\)/i', 'TEXT', $type);
		$type = preg_replace('/\\bmediumtext\\b/i', 'TEXT', $type);
		$type = preg_replace('/\\bvarchar\\s*\\(\\d+\\)/i', 'TEXT', $type);
		$type = preg_replace('/\\bchar\\s*\\(\\d+\\)/i', 'TEXT', $type);
		$definition = trim($type);
		if (!empty($column["autoIncrement"])) $definition = "INTEGER PRIMARY KEY AUTOINCREMENT";
		if (!$column["null"] && empty($column["autoIncrement"])) $definition .= " NOT NULL";
		if ($column["default"] !== false && empty($column["autoIncrement"]))
			$definition .= " DEFAULT ".ET::$database->escapeValue(is_null($column["default"]) ? null : (string)$column["default"]);
		return $definition;
	}

	$definition = $column["type"];
	if (!$column["null"]) $definition .= " NOT NULL";
	if ($column["default"] !== false) $definition .= " DEFAULT ".ET::$database->escapeValue(is_null($column["default"]) ? null : (string)$column["default"]);
	if (!empty($column["autoIncrement"])) $definition .= " AUTO_INCREMENT";
	return $definition;
}


/**
 * Get the SQL definition string for a key (eg. UNIQUE KEY `keyname` (`column1`,`column2`)).
 *
 * @param string $name The name of the the key.
 * @param array $key The key details.
 * @return string
 */
protected function keyDefinition($name, $key)
{
	$rawColumns = $key["columns"];
	$quotedColumns = array_map(function($column) { return "`$column`"; }, $rawColumns);
	if ($name == "PRIMARY") {
		// The single integer primary key is already declared inline as INTEGER PRIMARY KEY AUTOINCREMENT.
		if (ET::$database->isSQLite() && count($rawColumns) === 1 && !empty($this->columns[$rawColumns[0]]["autoIncrement"])) return "";
		return "PRIMARY KEY (".implode(",", $quotedColumns).")";
	}
	if (ET::$database->isSQLite()) {
		// Unique constraints are valid inside CREATE TABLE; ordinary indexes are created after it.
		return $key["type"] === "unique" ? "UNIQUE (".implode(",", $quotedColumns).")" : "";
	}
	return ($key["type"] ? strtoupper($key["type"])." " : "")."KEY `$name` (".implode(",", $quotedColumns).")";
}


/**
 * Returns whether or not the current table exists in the database.
 *
 * @return bool
 */
public function exists()
{
	if ($this->exists === null) {
		if (ET::$database->isSQLite())
			$this->exists = (bool)ET::SQL("SELECT name FROM sqlite_master WHERE type='table' AND name=".ET::$database->escapeValue(ET::$database->tablePrefix.$this->tableName))->numRows();
		else
			$this->exists = (bool)ET::SQL("SHOW TABLES LIKE '".ET::$database->tablePrefix.$this->tableName."'")->numRows();
	}
	return $this->exists;
}


/**
 * Returns whether or not a column exists in the database.
 *
 * @param string $name The name of the column to check.
 * @return bool
 */
public function columnExists($name)
{
	return array_key_exists($name, $this->existingColumns());
}


/**
 * Returns whether or not an key exists in the database.
 *
 * @param string $name The name of the key, or "PRIMARY" for the primary key.
 * @return bool
 */
public function keyExists($name)
{
	return array_key_exists($name, $this->existingKeys());
}


/**
 * Returns a list of columns and their information in the current table.
 *
 * @return array
 */
public function existingColumns()
{
	if ($this->existingColumns === null) {
		if (ET::$database->isSQLite()) {
			$result = ET::SQL("PRAGMA table_info(`".ET::$database->tablePrefix.$this->tableName."`)")->allRows();
			$this->existingColumns = array();
			foreach ($result as $column) {
				$this->existingColumns[$column["name"]] = array(
					"type" => $column["type"],
					"null" => !$column["notnull"],
					"default" => $column["dflt_value"],
					"autoIncrement" => false
				);
			}
		} else {
			$result = ET::SQL("SHOW COLUMNS FROM `".ET::$database->tablePrefix.$this->tableName."`")->allRows();
			$this->existingColumns = array();
			foreach ($result as $column) {
				$this->existingColumns[$column["Field"]] = array(
					"type" => $column["Type"],
					"null" => $column["Null"] == "YES",
					"default" => ($column["Default"] !== null or $column["Null"] == "YES") ? $column["Default"] : false,
					"autoIncrement" => $column["Extra"] == "auto_increment"
				);
			}
		}
	}
	return $this->existingColumns;
}


/**
 * Returns a list of keys and their information in the current table.
 *
 * @return array
 */
public function existingKeys()
{
	if ($this->existingKeys === null) {
		if (ET::$database->isSQLite()) {
			$result = ET::SQL("PRAGMA index_list(`".ET::$database->tablePrefix.$this->tableName."`)")->allRows();
			$this->existingKeys = array();
			foreach ($result as $key) {
				$name = $key["name"];
				$columns = ET::SQL("PRAGMA index_info(`$name`)")->allRows();
				$this->existingKeys[$name] = array("type" => !empty($key["unique"]) ? "unique" : "", "columns" => array());
				foreach ($columns as $column) $this->existingKeys[$name]["columns"][] = $column["name"];
			}
		} else {
			$result = ET::SQL("SHOW INDEXES FROM `".ET::$database->tablePrefix.$this->tableName."`")->allRows();
			$this->existingKeys = array();
			foreach ($result as $key) {
				if (!isset($this->existingKeys[$key["Key_name"]])) {
					$this->existingKeys[$key["Key_name"]] = array(
						"type" => $key["Non_unique"] ? "" : "unique",
						"columns" => array()
					);
					if ($key["Index_type"] == "FULLTEXT")
						$this->existingKeys[$key["Key_name"]]["type"] = "fulltext";
				}
				$this->existingKeys[$key["Key_name"]]["columns"][] = $key["Column_name"];
			}
		}
	}
	return $this->existingKeys;
}


/**
 * Drop the current table from the database.
 *
 * @return ETDatabaseStructure
 */
public function drop()
{
	ET::SQL("DROP TABLE IF EXISTS `".ET::$database->tablePrefix.$this->tableName."`");
	return $this;
}


/**
 * Drop the specified column from the current table.
 *
 * @param string $name The name of the column.
 * @return ETDatabaseStructure
 */
public function dropColumn($name)
{
	if ($this->columnExists($name))
		ET::SQL("ALTER TABLE `".ET::$database->tablePrefix.$this->tableName."` DROP COLUMN `$name`");
	return $this;
}


/**
 * Drop the specified key from the current table.
 *
 * @param string $name The name of the key, or "PRIMARY" to drop the primary key.
 * @return ETDatabaseStructure
 */
public function dropKey($name)
{
	if ($this->keyExists($name)) {
		if (ET::$database->isSQLite()) ET::SQL("DROP INDEX IF EXISTS `$name`");
		else ET::SQL("ALTER TABLE `".ET::$database->tablePrefix.$this->tableName."` DROP ".($name == "primary" ? "PRIMARY KEY" : "KEY `$name`"));
	}
	return $this;
}


/**
 * Rename the current table.
 *
 * @param string $newName The new name of the table.
 * @return ETDatabaseStructure
 */
public function rename($newName)
{
	if ($this->exists())
		ET::SQL("ALTER TABLE `".ET::$database->tablePrefix.$this->tableName."` RENAME TO `$newName`");
	return $this;
}


/**
 * Rename the specified column on the current table.
 *
 * @param string $name The name of the column.
 * @param string $newName The new name of the column.
 * @return ETDatabaseStructure
 */
public function renameColumn($name, $newName)
{
	if ($this->columnExists($name)) {
		if (ET::$database->isSQLite()) ET::SQL("ALTER TABLE `".ET::$database->tablePrefix.$this->tableName."` RENAME COLUMN `$name` TO `$newName`");
		else {
			$existing = $this->existingColumns();
			$definition = $this->columnDefinition($existing[$name]);
			ET::SQL("ALTER TABLE `".ET::$database->tablePrefix.$this->tableName."` CHANGE COLUMN `$name` `$newName` $definition");
		}
	}
	return $this;
}

}
