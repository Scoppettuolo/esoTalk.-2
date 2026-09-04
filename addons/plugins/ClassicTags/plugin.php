<?php
/**
 * Part of esoTalk. Licensed under AGPLv3. See LICENSE.txt.
 */
if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["ClassicTags"] = array(
	"name" => "Classic Tags",
	"description" => "Restores multiple conversation tags, tag: search and tag badges.",
	"version" => ESOTALK_VERSION,
	"author" => "Scoppettuolo",
	"authorEmail" => "",
	"authorURL" => "https://github.com/Scoppettuolo",
	"license" => "AGPLv3"
);

class ETPlugin_ClassicTags extends ETPlugin {

	protected $tableReady = null;

	public function setup($oldVersion = "")
	{
		$this->createTable();
		/* Return true so an existing installation keeps booting; isTableReady() retries
		 * on demand if the database was unavailable or the table was removed. */
		return true;
	}

	protected function createTable()
	{
		try {
			$charset = C("esoTalk.database.characterEncoding");
			if (!in_array($charset, array("utf8", "utf8mb4", "latin1"), true)) $charset = "utf8mb4";
			$collation = $charset === "latin1" ? "latin1_swedish_ci" : $charset."_unicode_ci";
			$table = ET::$database->tablePrefix."conversation_tag";
			/* Raw IF NOT EXISTS is intentional: it repairs an already enabled plugin
			 * whose setup() version was recorded without creating the table. */
			if (ET::$database->isSQLite()) {
				ET::SQL("CREATE TABLE IF NOT EXISTS `".$table."` (\n\t`conversationId` INTEGER NOT NULL,\n\t`tag` TEXT NOT NULL,\n\t`createdBy` INTEGER DEFAULT NULL,\n\t`time` INTEGER NOT NULL,\n\tPRIMARY KEY (`conversationId`, `tag`)\n)");
				ET::SQL("CREATE INDEX IF NOT EXISTS `conversation_tag_tag_conversationId` ON `".$table."` (`tag`, `conversationId`)");
			} else {
				ET::SQL("CREATE TABLE IF NOT EXISTS `".$table."` (\n\t`conversationId` int(11) unsigned NOT NULL,\n\t`tag` varchar(80) NOT NULL,\n\t`createdBy` int(11) unsigned DEFAULT NULL,\n\t`time` int(11) unsigned NOT NULL,\n\tPRIMARY KEY (`conversationId`, `tag`),\n\tKEY `conversation_tag_tag_conversationId` (`tag`, `conversationId`)\n) ENGINE=InnoDB DEFAULT CHARSET=".$charset." COLLATE=".$collation);
			}
			ET::SQL()->select("conversationId")->from("conversation_tag")->limit(1)->exec();
			$this->tableReady = true;
		} catch (Exception $e) {
			$this->tableReady = false;
		}
		return $this->tableReady;
	}

	public function init()
	{
		/* Models are registered during bootstrap, but their PHP files are lazy-loaded. */
		try {
			if (!class_exists("ETSearchModel", false)) ETFactory::make("searchModel");
		} catch (Exception $e) {
			return;
		}
		if (!class_exists("ETSearchModel", false)) return;
		ETSearchModel::addGambit(function($term) {
			return strpos($term, "tag:") === 0 && strlen(trim(substr($term, 4))) > 0;
		}, array($this, "gambitTag"));
		ETSearchModel::addAlias("tags", "tag:");
	}

	public function handler_conversationsController_renderBefore($sender)
	{
		$sender->addCSSFile($this->resource("tags.css"));
		$sender->data("classicTagCloud", $this->popularTags());
	}

	public function handler_conversationsController_constructGambitsMenu($sender, &$gambits)
	{
		addToArrayString($gambits["main"], "tag:", array("gambit-tag", "icon-tags"), 0);
	}

	/* Intentionally do not alter the main search SQL. A missing optional table must
	 * never be able to make the conversation list fatal. */
	public function handler_searchModel_afterGetResults($sender, &$results)
	{
		if (!$this->isTableReady()) {
			foreach ($results as &$conversation) $conversation["classicTags"] = array();
			unset($conversation);
			return;
		}
		foreach ($results as &$conversation)
			$conversation["classicTags"] = $this->getTags($conversation["conversationId"]);
		unset($conversation);
	}

	public function handler_conversationController_conversationIndex($sender, &$conversation, &$posts, &$startFrom, &$searchString)
	{
		if (!empty($conversation["conversationId"])) {
			$conversation["classicTags"] = $this->getTags($conversation["conversationId"]);
			$conversation["classicTagsString"] = implode(", ", $conversation["classicTags"]);
		}
	}

	public function handler_conversationController_conversationIndexDefault($sender, &$conversation, &$controls, &$replyForm, &$replyControls)
	{
		if (empty($conversation["classicTags"]) || !is_array($conversation["classicTags"])) return;
		$html = "<span class='classic-tags conversation-tags'>".$this->tagBadges($conversation["classicTags"])."</span>";
		if (is_object($controls) && method_exists($controls, "add")) $controls->add("classicTags", $html);
	}

	public function handler_conversationController_conversationSaveAfter($sender, &$conversation, $form)
	{
		if (empty($conversation["conversationId"]) || !$this->isTableReady()) return;
		$raw = R("tags");
		if ($raw === null) return;
		$this->saveTags((int)$conversation["conversationId"], $raw);
	}

	public function handler_conversationModel_createAfter($sender, $conversation, $postId, $content)
	{
		if (empty($conversation["conversationId"]) || !$this->isTableReady()) return;
		$this->saveTags((int)$conversation["conversationId"], R("tags"));
	}

	public function gambitTag($search, $term, $negate)
	{
		if (!$this->isTableReady()) return;
		$tag = strtolower(trim(substr($term, 4), " \t\"'"));
		if ($tag === "") return;
		$query = ET::SQL()
			->select("conversationId")
			->from("conversation_tag")
			->where("tag=:classicTag")
			->bind(":classicTag", $tag);
		$search->addIDFilter($query, $negate);
	}

	public function isTableReady()
	{
		/* Do not trust a cached setup result: an existing installation may have enabled
		 * the plugin without running setup(), or the table may have been removed. */
		try {
			ET::SQL()->select("conversationId")->from("conversation_tag")->limit(1)->exec();
			$this->tableReady = true;
			return true;
		} catch (Exception $e) {
			$this->tableReady = null;
		}
		return $this->createTable();
	}

	protected function popularTags()
	{
		if (!$this->isTableReady()) return array();
		$limit = max(0, min(200, (int)C("esoTalk.search.tagCloudLimit", 30)));
		if ($limit === 0) return array();
		try {
			$result = ET::SQL()
				->select("tag")
				->select("COUNT(*)", "uses")
				->from("conversation_tag")
				->groupBy("tag")
				->orderBy("uses DESC")
				->orderBy("tag ASC")
				->limit($limit)
				->exec();
			$tags = array();
			while ($row = $result->nextRow()) $tags[] = array("tag" => $row["tag"], "uses" => (int)$row["uses"]);
			return $tags;
		} catch (Exception $e) {
			return array();
		}
	}

	public function getTags($conversationId)
	{
		if (!$this->isTableReady()) return array();
		try {
			$result = ET::SQL()->select("tag")->from("conversation_tag")->where("conversationId=:conversationId")->bind(":conversationId", (int)$conversationId)->orderBy("tag ASC")->exec();
			$tags = array();
			while ($row = $result->nextRow()) $tags[] = $row["tag"];
			return $tags;
		} catch (Exception $e) {
			return array();
		}
	}

	protected function saveTags($conversationId, $raw)
	{
		if (!$this->isTableReady()) return;
		try {
			$tags = $this->parseTags($raw);
			ET::SQL()->delete()->from("conversation_tag")->where("conversationId=:conversationId")->bind(":conversationId", (int)$conversationId)->exec();
			if (!$tags) return;
			$rows = array();
			foreach ($tags as $tag) $rows[] = array((int)$conversationId, $tag, (int)ET::$session->userId, time());
			ET::SQL()->insert("conversation_tag")
				->setMultiple(array("conversationId", "tag", "createdBy", "time"), $rows)
				->exec();
		} catch (Exception $e) {
			/* A transient database/schema error must not break posting. */
			$this->tableReady = false;
		}
	}

	public function parseTags($raw)
	{
		if (is_array($raw)) $parts = $raw;
		else $parts = preg_split('/[,\s]+/', (string)$raw);
		$tags = array();
		foreach ($parts as $tag) {
			$tag = strtolower(trim((string)$tag));
			$tag = preg_replace('/[^\p{L}\p{N}_-]+/u', "", $tag);
			if ($tag === "" || strlen($tag) > 80 || in_array($tag, $tags, true)) continue;
			$tags[] = $tag;
		}
		return $tags;
	}

	public function tagBadges($tags)
	{
		$html = array();
		foreach ((array)$tags as $tag) {
			$url = URL("?search=".urlencode("#tag:".$tag));
			$html[] = "<a class='classic-tag' href='".$url."'>".sanitizeHTML($tag)."</a>";
		}
		return implode(" ", $html);
	}
}
?>
