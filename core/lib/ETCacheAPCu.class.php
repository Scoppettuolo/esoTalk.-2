<?php
if (!defined("IN_ESOTALK")) exit;

/**
 * APCu-backed cache (use when the APCu extension is available).
 * Config: $config["esoTalk.cache"] = "cacheAPCu";
 */
class ETCacheAPCu extends ETCache {

public function exists($key)
{
	if (!function_exists("apcu_exists")) return parent::exists($key);
	return apcu_exists($this->prefix($key));
}

public function get($key)
{
	if (!function_exists("apcu_fetch")) return parent::get($key);
	$success = false;
	$v = apcu_fetch($this->prefix($key), $success);
	return $success ? $v : false;
}

public function store($key, $value, $ttl = 0)
{
	if (!function_exists("apcu_store")) return parent::store($key, $value, $ttl);
	return apcu_store($this->prefix($key), $value, (int)$ttl);
}

public function remove($key)
{
	if (!function_exists("apcu_delete")) return parent::remove($key);
	return apcu_delete($this->prefix($key));
}

protected function prefix($key)
{
	return "esotalk_".$key;
}

}
