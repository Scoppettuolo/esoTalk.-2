<?php
if (!defined("IN_ESOTALK")) exit;

// Back-compat alias: ETCache is already a full file cache.
if (!class_exists("ETCache", false)) {
	require_once PATH_LIBRARY."/ETCache.class.php";
}

class ETFileCache extends ETCache {
}
