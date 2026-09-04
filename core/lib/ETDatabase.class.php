<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Licensed under the GNU Affero General Public License v3.0 (AGPLv3). See LICENSE.txt.

if (!defined("IN_ESOTALK")) exit;

/**
 * The database class handles the database connection and the running of queries against the database.
 *
 * @package esoTalk
 */
#[\AllowDynamicProperties]
class ETDatabase extends ETPluggable {


/**
 * An instance of the PDO connection object.
 * @var PDO
 */
protected $pdoConnection;


/**
 * An instance of the database structure class.
 * @var ETDatabaseStructure
 */
protected $structure;


/**
 * The database host.
 * @var string
 */
protected $host;


/**
 * The database port.
 * @var string
 */
protected $port;


/**
 * The database user.
 * @var string
 */
protected $user;


/**
 * The database password.
 * @var string
 */
protected $password;


/**
 * The database name.
 * @var string
 */
protected $dbName;

/**
 * The selected PDO driver (sqlite or mysql).
 * @var string
 */
protected $driver = "mysql";

/**
 * The SQLite database file path.
 * @var string
 */
protected $sqlitePath;


/**
 * An array of connection options to use when creating a PDO connection.
 * @var array
 */
protected $connectionOptions = array();


/**
 * The database table prefix.
 * @var string
 */
public $tablePrefix;


/**
 * Whether or not we are currently in the middle of a database transaction.
 * @var bool
 */
protected $inTransaction = false;


/**
 * An array of queries that have been run.
 * @var array
 */
public $queries = array();


/**
 * Get an instance of the database structure class.
 *
 * @return ETDatabaseStructure An instance of the database structure class.
 */
public function structure()
{
	if (!$this->structure) $this->structure = ETFactory::make("databaseStructure");
	return $this->structure;
}


/**
 * Create a new instance of the SQL query class.
 *
 * @return ETSQLQuery A new instance of the SQL query class.
 */
public function SQL()
{
	return ETFactory::make("sqlQuery");
}


/**
 * Get the PDO connection to the database, creating a new one if it does not already exist.
 *
 * @return PDO The PDO connection.
 */
public function connection()
{
	if (!$this->pdoConnection) {
		$defaults = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		);

		if ($this->isSQLite()) {
			$path = $this->sqlitePath ?: $this->dbName;
			if (!$path) throw new PDOException("No SQLite database path was configured.");
			if ($path !== ":memory:" && !is_dir(dirname($path)) && !@mkdir(dirname($path), 0775, true))
				throw new PDOException("The SQLite database directory could not be created.");
			$dsn = "sqlite:".$path;
			foreach ($this->connectionOptions as $key => $value) {
				if (in_array($key, array(PDO::ATTR_PERSISTENT, PDO::ATTR_ERRMODE, PDO::ATTR_DEFAULT_FETCH_MODE, PDO::ATTR_EMULATE_PREPARES), true))
					$defaults[$key] = $value;
			}
			$this->pdoConnection = new PDO($dsn, null, null, $defaults);
			$this->pdoConnection->exec("PRAGMA foreign_keys = ON");
			if (method_exists($this->pdoConnection, "sqliteCreateFunction")) {
				$this->pdoConnection->sqliteCreateFunction("FIELD", function($value, ...$items) {
					$position = array_search($value, $items, true);
					return $position === false ? 0 : $position + 1;
				}, -1);
				$this->pdoConnection->sqliteCreateFunction("CONCAT_WS", function($separator, ...$items) {
					$items = array_values(array_filter($items, function($item) { return $item !== null; }));
					return implode((string)$separator, $items);
				}, -1);
				$this->pdoConnection->sqliteCreateFunction("IF", function($condition, $yes, $no) { return $condition ? $yes : $no; }, 3);
				$this->pdoConnection->sqliteCreateFunction("LEFT", function($value, $length) { return substr((string)$value, 0, (int)$length); }, 2);
				$this->pdoConnection->sqliteCreateFunction("UNIX_TIMESTAMP", function() { return time(); }, 0);
				$this->pdoConnection->sqliteCreateFunction("VERSION", function() { return defined("SQLITE3_VERSION") ? "SQLite ".SQLITE3_VERSION : "SQLite"; }, 0);
			}
		} else {
			$dsn = "mysql:host=".$this->host
				.($this->port ? ";port=".$this->port : "")
				.";dbname=".$this->dbName
				.";charset=utf8mb4";
			if (defined("PDO::MYSQL_ATTR_INIT_COMMAND"))
				$defaults[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
			$opts = $this->connectionOptions + $defaults;
			$this->pdoConnection = new PDO($dsn, $this->user, $this->password, $opts);
		}
	}
	return $this->pdoConnection;
}


/**
 * Fetches the version of the database engine in use.
 *
 * @return string The database engine version.
 */
public function getVersion()
{
	return $this->query($this->isSQLite() ? "SELECT sqlite_version()" : "SELECT VERSION()")->result();
}

/** @return bool */
public function isSQLite()
{
	return strtolower($this->driver) === "sqlite";
}

/** @return string */
public function getDriver()
{
	return $this->driver;
}

/**
 * Return user table names without relying on SHOW TABLES.
 * @return array
 */
public function getTables()
{
	if ($this->isSQLite()) {
		$rows = $this->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->allRows();
		return array_map(function($row) { return reset($row); }, $rows);
	}
	$rows = $this->query("SHOW TABLES")->allRows();
	return array_map(function($row) { return reset($row); }, $rows);
}


/**
 * Initialize the database class with database details. These details will be used when a PDO connection
 * is made.
 *
 * @param string $host The database host.
 * @param string $user The database user.
 * @param string $password The database password.
 * @param string $dbName The database name.
 * @param string $tablePrefix The database table prefix.
 * @param array $connectionOptions An array of connection options to use when making the PDO connection.
 * @return void
 */
public function init($host, $user, $password, $dbName, $tablePrefix = "", $connectionOptions = array(), $port = null, $driver = "mysql", $sqlitePath = null)
{
	$this->pdoConnection = null;

	$this->host = $host;
	$this->port = $port;
	$this->user = $user;
	$this->password = $password;
	$this->dbName = $dbName;
	$this->tablePrefix = $tablePrefix;
	$this->connectionOptions = is_array($connectionOptions) ? $connectionOptions : array();
	$this->driver = strtolower((string)$driver) === "sqlite" ? "sqlite" : "mysql";
	$this->sqlitePath = $sqlitePath ?: $dbName;

	// The application builds escaped SQL itself for compatibility with the original code.
	if (!$this->isSQLite()) {
		if (defined('PDO::MYSQL_ATTR_DIRECT_QUERY')) $this->connectionOptions[PDO::MYSQL_ATTR_DIRECT_QUERY] = true;
		else $this->connectionOptions[PDO::ATTR_EMULATE_PREPARES] = true;
	}
}


/**
 * Close the connection to the database.
 *
 * @return void
 */
public function close()
{
	$this->commitTransaction();
	unset($this->pdoConnection);
}


/**
 * Begin a database transaction.
 *
 * @return void
 */
public function beginTransaction()
{
	$this->connection()->beginTransaction();
	$this->inTransaction = true;
}


/**
 * Roll back a database transaction.
 *
 * @return void
 */
public function rollbackTransaction()
{
	if (!$this->inTransaction) return;
	$this->connection()->rollback();
	$this->inTransaction = false;
}


/**
 * Commit a database transaction.
 *
 * @return void
 */
public function commitTransaction()
{
	if (!$this->inTransaction) return;
	$this->connection()->commit();
	$this->inTransaction = false;
}


/**
 * Get the ID of the last record inserted into the database.
 *
 * @return string The last insert ID.
 */
public function lastInsertId()
{
	return $this->connection()->lastInsertId();
}


/**
 * Escape a value so that it is safe to use in an SQL query.
 *
 * @param mixed $value The value to escape.
 * @param int $dataType Explicit data type for the value using PDO::PARAM_* constants. If null,
 * 		the type of $value will be used.
 * @return mixed The escaped value.
 */
public function escapeValue($value, $dataType = null)
{
	// If the value is a raw SQL query object, don't sanitize it.
	if ($value instanceof ETSQLRaw) return $value;

	// If the value is an array, escape each element individually and return a comma-separated string.
	if (is_array($value)) {
		foreach ($value as &$v) $v = $this->escapeValue($v, $dataType);
    	return implode(",", $value);
	}

	// If no data type was specified, work it out based on the variable content.
	if ($dataType === null) {
		if ($value === true or $value === false) $dataType = PDO::PARAM_BOOL;
		elseif ($value === null) $dataType = PDO::PARAM_NULL;
		elseif (is_int($value)) $dataType = PDO::PARAM_INT;
		else $dataType = PDO::PARAM_STR;
	}

	// Now escape the value according to the data type.
	switch ($dataType) {
		case PDO::PARAM_BOOL:
			return $value ? "1" : "0";

		case PDO::PARAM_NULL:
			return "NULL";

		case PDO::PARAM_INT:
			$value = (int)$value;
			return $value ? (string)$value : "0";

		default:
			if ($this->isSQLite()) {
				// SQLite escapes a quote by doubling it; MySQL's backslash quoting causes syntax errors.
				return "'".str_replace("'", "''", (string)$value)."'";
			}
			$value = str_replace(array('\\\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $value);
			return "'".$value."'";
	}
}


/**
 * Run a query against the database.
 *
 * @param string $query The query to run.
 * @return ETSQLResult|bool An SQL result, or false if the query was unsuccessful.
 */
public function query($query)
{
	// If the query is empty, don't bother proceeding.
	if (!$query) return false;

	// Get the database connection.
	$connection = $this->connection();

	$this->trigger("beforeQuery", array(&$query));
	if ($this->isSQLite()) $query = $this->rewriteSQLiteQuery($query);
	$this->queries[] = $query;

		// Execute the query.
		try {
			$statement = ($this->isSQLite() && preg_match('/\\bON\\s+CONFLICT(?:\\s*\\([^)]*\\))?\\s+DO\\s+UPDATE\\s+SET\\b/i', $query))
				? $this->executeSQLiteUpsert($connection, $query)
				: $connection->query($query);
		} catch (PDOException $exception) {
			throw $exception;
		}

		// Was there an error?
	if (!$statement) {
					$error = $connection->errorInfo();
			throw new Exception("SQL Error (".$error[0].", ".$error[1]."): ".$error[2]."<br><br><pre>".$this->highlightQueryErrors($query, $error[2])."</pre>");
	}

	// Set up a new ETSQLResult object with the result statement.
	$result = ETFactory::make("sqlResult", $statement);

	$this->trigger("afterQuery", array($result));

	return $result;
}


/**
 * Find anything in single quotes in the error and make it red in the query (just to make debugging a bit
 * easier!)
 *
 * @param string $query The SQL query that failed.
 * @param string $error The error string that was returned by the connection.
 * @return string The SQL query with the guessed "errorous" parts highlighted.
 */
/**
 * Translate the small set of MySQL expressions used by esoTalk into SQLite syntax.
 * The query builder continues to emit MySQL-compatible SQL for MySQL installations.
 */
protected function rewriteSQLiteQuery($query)
{
	$query = preg_replace('/^\\s*\\((SELECT\\b.*)\\)\\s+UNION\\s+\\((SELECT\\b.*)\\)\\s*$/is', '$1 UNION $2', $query);
	$query = preg_replace('/\\(\\s*!\\s*([A-Za-z_][A-Za-z0-9_.]*)\\s*\\)/i', '(NOT $1)', $query);
	$query = preg_replace('/\\s+USE INDEX\\s*\\([^)]*\\)/i', '', $query);
	$query = preg_replace_callback('/MATCH\s*\(([^)]*)\)\s+AGAINST\s*\(\s*(.*?)\s*(?:IN\s+BOOLEAN\s+MODE)?\s*\)/is', function($m) {
		$columns = preg_split('/\s*,\s*/', trim($m[1]));
		$parts = array();
		foreach ($columns as $column) if ($column !== '') $parts[] = "COALESCE($column,'')";
		if (!$parts) return '0';
		return '(INSTR('.implode(" || ' ' || ", $parts).','.$m[2].') > 0)';
	}, $query);
	$query = preg_replace('/\bBIT_OR\s*\(/i', 'MAX(', $query);
	$query = preg_replace('/\\bLEFT\\s*\\(\\s*([^,()]+)\\s*,\\s*([0-9]+)\\s*\\)/i', 'substr($1,1,$2)', $query);
	$query = preg_replace('/\\bUNIX_TIMESTAMP\\s*\\(\\s*\\)/i', "CAST(strftime('%s','now') AS INTEGER)", $query);
		$query = preg_replace('/\\bRAND\\s*\\(\\s*\\)/i', '(ABS(random()) / 9223372036854775807.0)', $query);
		$query = preg_replace('/\\bFLOOR\\s*\\(\\s*([^,()]+)\\s*\\)/i', 'CAST(($1) AS INTEGER)', $query);
		$query = preg_replace('/\\bON DUPLICATE KEY UPDATE\\b/i', 'ON CONFLICT DO UPDATE SET', $query);
	$query = preg_replace('/\\bVALUES\\s*\\(\\s*([A-Za-z_][A-Za-z0-9_]*)\\s*\\)/i', 'excluded.$1', $query);

	// SQLite has no IF(condition, yes, no). Parse balanced parentheses so nested IFs work too.
	while (preg_match('/\\bIF\\s*\\(/i', $query, $match, PREG_OFFSET_CAPTURE)) {
		$start = $match[0][1];
		$open = strpos($query, '(', $start);
		$depth = 0; $quote = null; $close = null;
		$length = strlen($query);
		for ($i = $open; $i < $length; $i++) {
			$c = $query[$i];
			if ($quote !== null) {
				if ($c === $quote && ($i === 0 || $query[$i - 1] !== '\\\\')) $quote = null;
				continue;
			}
			if ($c === "'" || $c === '"') { $quote = $c; continue; }
			if ($c === '(') $depth++;
			elseif ($c === ')' && --$depth === 0) { $close = $i; break; }
		}
		if ($close === null) break;
		$inside = substr($query, $open + 1, $close - $open - 1);
		$args = array(); $part = ''; $nested = 0; $quote = null;
		for ($i = 0, $n = strlen($inside); $i < $n; $i++) {
			$c = $inside[$i];
			if ($quote !== null) {
				$part .= $c;
				if ($c === $quote && ($i === 0 || $inside[$i - 1] !== '\\\\')) $quote = null;
				continue;
			}
			if ($c === "'" || $c === '"') { $quote = $c; $part .= $c; continue; }
			if ($c === '(') $nested++;
			elseif ($c === ')') $nested--;
			if ($c === ',' && $nested === 0) { $args[] = trim($part); $part = ''; }
			else $part .= $c;
		}
		$args[] = trim($part);
		if (count($args) !== 3) break;
		$replacement = '(CASE WHEN '.$args[0].' THEN '.$args[1].' ELSE '.$args[2].' END)';
		$query = substr($query, 0, $start).$replacement.substr($query, $close + 1);
	}
	return $query;
}

/** Split a SQL list while respecting quoted strings and nested parentheses. */
protected function splitSQLiteList($text)
{
	$parts = array(); $start = 0; $depth = 0; $quote = null; $length = strlen($text);
	for ($i = 0; $i < $length; $i++) {
		$c = $text[$i];
		if ($quote !== null) {
			if ($c === $quote) {
				if ($i + 1 < $length && $text[$i + 1] === $quote) { $i++; continue; }
				$quote = null;
			}
			continue;
		}
		if ($c === "'" || $c === '"' || $c === '`') { $quote = $c; continue; }
		if ($c === '(') $depth++;
		elseif ($c === ')') $depth--;
		elseif ($c === ',' && $depth === 0) { $parts[] = trim(substr($text, $start, $i - $start)); $start = $i + 1; }
	}
	$parts[] = trim(substr($text, $start));
	return array_values(array_filter($parts, function($part) { return $part !== ''; }));
}

/**
 * Execute an upsert without SQLite's newer ON CONFLICT ... DO UPDATE syntax.
 * This also works with older SQLite libraries bundled with some PHP/XAMPP builds.
 */
protected function executeSQLiteUpsert($connection, $query)
{
	if (!preg_match('/^\\s*INSERT\\s+INTO\\s+(`?[A-Za-z0-9_]+`?)\\s*\\((.*?)\\)\\s+VALUES\\s*(.*?)\\s+ON\\s+CONFLICT(?:\\s*\\([^)]*\\))?\\s+DO\\s+UPDATE\\s+SET\\s+(.+)\\s*$/is', $query, $m))
		return $connection->query($query);
	$table = $m[1];
	$fields = $this->splitSQLiteList($m[2]);
	$rowsText = trim($m[3]);
	$assignments = $this->splitSQLiteList($m[4]);
	$tableName = trim($table, '` ');
	$primary = array();
	$schema = $connection->query("PRAGMA table_info('".str_replace("'", "''", $tableName)."')")->fetchAll(PDO::FETCH_ASSOC);
	foreach ($schema as $row) if (!empty($row['pk'])) $primary[(int)$row['pk']] = $row['name'];
	ksort($primary); $primary = array_values($primary);
	if (!$primary) return $connection->query($query);
	$lastStatement = null;
	foreach ($this->splitSQLiteList($rowsText) as $rowText) {
		$rowText = trim($rowText);
		if (substr($rowText, 0, 1) !== '(' || substr($rowText, -1) !== ')') continue;
		$values = $this->splitSQLiteList(substr($rowText, 1, -1));
		$insertSQL = "INSERT OR IGNORE INTO $table (".implode(', ', $fields).") VALUES (".implode(', ', $values).")";
		$lastStatement = $connection->query($insertSQL);
		$changed = (int)$connection->query("SELECT changes()")->fetchColumn();
		if ($changed > 0) continue;
		$valueByField = array();
		foreach ($fields as $i => $field) $valueByField[trim($field, '` ')] = $values[$i] ?? 'NULL';
		$set = array();
		foreach ($assignments as $assignment) {
			$eq = strpos($assignment, '=');
			if ($eq === false) continue;
			$field = trim(substr($assignment, 0, $eq));
			$expression = trim(substr($assignment, $eq + 1));
			$expression = preg_replace_callback('/\\bexcluded\\.(`?[A-Za-z0-9_]+`?)\\b/i', function($match) use ($valueByField) {
				return $valueByField[trim($match[1], '` ')] ?? 'NULL';
			}, $expression);
			$set[] = "$field=$expression";
		}
		if (!$set) continue;
		$where = array();
		foreach ($primary as $key) $where[] = "`$key`=".($valueByField[$key] ?? 'NULL');
		$lastStatement = $connection->query("UPDATE $table SET ".implode(', ', $set)." WHERE ".implode(' AND ', $where));
	}
	return $lastStatement ?: $connection->query("SELECT 1 WHERE 0");
}

protected function highlightQueryErrors($query, $error)
{
	$query = sanitizeHTML($query);
	preg_match("/'(.+?)'/", $error, $matches);
	if (!empty($matches[1])) $query = str_replace($matches[1], "<span class='highlight'>{$matches[1]}</span>", $query);
	return $query;
}

}
