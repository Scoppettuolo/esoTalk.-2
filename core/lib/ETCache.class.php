<?php
if (!defined("IN_ESOTALK")) exit;

/**
 * File-based caching service.
 * Stores serialized values under cache/ with optional TTL.
 * Falls back to in-memory only for the current request if the cache dir is not writable.
 *
 * @package esoTalk
 */
#[\AllowDynamicProperties]
class ETCache {

/** @var array Request-local memory layer */
protected $memory = array();

/** @var string */
protected $dir;

/** @var bool */
protected $writable = false;

public function __construct()
{
	$this->dir = PATH_CACHE."/data";
	if (!is_dir($this->dir)) {
		@mkdir($this->dir, 0755, true);
		@file_put_contents($this->dir."/index.html", "");
	}
	$this->writable = is_dir($this->dir) && is_writable($this->dir);
}

protected function path($key)
{
	$safe = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $key);
	return $this->dir."/".md5($key)."_".$safe.".cache";
}

public function exists($key)
{
	if (array_key_exists($key, $this->memory)) return true;
	if (!$this->writable) return false;
	$file = $this->path($key);
	if (!is_file($file)) return false;
	$data = esotalk_unserialize(file_get_contents($file));
	if (!is_array($data) || !array_key_exists("v", $data)) return false;
	if (!empty($data["e"]) && $data["e"] < time()) {
		@unlink($file);
		return false;
	}
	$this->memory[$key] = $data["v"];
	return true;
}

public function get($key)
{
	if (array_key_exists($key, $this->memory)) return $this->memory[$key];
	if (!$this->writable) return false;
	$file = $this->path($key);
	if (!is_file($file)) return false;
	$raw = @file_get_contents($file);
	if ($raw === false) return false;
	$data = esotalk_unserialize($raw);
	if (!is_array($data) || !array_key_exists("v", $data)) return false;
	if (!empty($data["e"]) && $data["e"] < time()) {
		@unlink($file);
		return false;
	}
	$this->memory[$key] = $data["v"];
	return $data["v"];
}

public function store($key, $value, $ttl = 0)
{
	$this->memory[$key] = $value;
	if (!$this->writable) return false;
	$data = array(
		"v" => $value,
		"e" => $ttl > 0 ? time() + (int)$ttl : 0,
		"t" => time()
	);
	$file = $this->path($key);
	$ok = @file_put_contents($file, serialize($data), LOCK_EX);
	return $ok !== false;
}

public function remove($key)
{
	unset($this->memory[$key]);
	if (!$this->writable) return true;
	$file = $this->path($key);
	if (is_file($file)) @unlink($file);
	return true;
}

/**
 * Remove all cache files (keeps index.html).
 */
public function clear()
{
	$this->memory = array();
	if (!$this->writable) return true;
	foreach (glob($this->dir."/*.cache") as $f) {
		@unlink($f);
	}
	return true;
}

}
