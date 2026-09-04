<?php
if (!defined("IN_ESOTALK")) exit;

/**
 * RSS feed controller — modern rewrite of the legacy FeedController.
 * URL: ?p=feed  or  ?p=feed/conversation/{id}
 */
class ETFeedController extends ETController {

public function action_index($conversationId = false)
{
	header("Content-Type: application/rss+xml; charset=utf-8");

	$forumTitle = C("esoTalk.forumTitle");
	$base = C("esoTalk.baseURL");
	$items = array();
	$title = $forumTitle;
	$link = $base;
	$description = T("Recent posts");

	$conversationId = $conversationId ? (int)$conversationId : (int)R("conversationId");

	try {
		if ($conversationId) {
			$conversation = ET::conversationModel()->getById($conversationId);
				/* getById() already applies addAllowedPredicate() and returns false
				 * when the current user cannot view the conversation. The v2 model
				 * does not expose a separate canView field. */
				if (!$conversation) {
					header("HTTP/1.0 403 Forbidden");
				echo '<?xml version="1.0"?><rss version="2.0"><channel><title>Forbidden</title></channel></rss>';
				exit;
			}
			$title = $conversation["title"]." - ".$forumTitle;
			$link = $base.conversationURL($conversationId, $conversation["title"]);
			$description = $conversation["title"];

			$result = ET::SQL()
				->select("p.postId, p.content, p.time, m.username")
				->from("post p")
				->from("member m", "m.memberId=p.memberId", "inner")
				->where("p.conversationId", $conversationId)
				->where("p.deleteMemberId IS NULL")
				->orderBy("p.time DESC")
				->limit(20)
				->exec();
		} else {
			$result = ET::SQL()
				->select("p.postId, p.content, p.time, m.username, c.title, c.conversationId, c.private, c.countPosts")
				->from("post p")
				->from("conversation c", "c.conversationId=p.conversationId", "left")
				->from("member m", "m.memberId=p.memberId", "inner")
				->where("c.private", 0)
				->where("c.countPosts > 0")
				->where("p.deleteMemberId IS NULL")
				->orderBy("p.time DESC")
				->limit(20)
				->exec();
		}

		while ($row = $result->nextRow()) {
			$itemTitle = !empty($row["title"])
				? $row["username"]." - ".$row["title"]
				: $row["username"];
			$body = ET::formatter()->init($row["content"])->inline(true)->format()->get();
			$body = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($body))), 0, 400);
			$items[] = array(
				"title" => $itemTitle,
				"description" => $body,
					"link" => URL(postURL($row["postId"]), true),
					"date" => date("D, d M Y H:i:s O", (int)$row["time"]),
					"guid" => URL(postURL($row["postId"]), true),
			);
		}
	} catch (Exception $e) {
		// empty feed on error
	} catch (Throwable $e) {
	}

	$pubDate = !empty($items[0]["date"]) ? $items[0]["date"] : date("D, d M Y H:i:s O");

	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	echo '<rss version="2.0">'."\n";
	echo "<channel>\n";
	echo "<title>".sanitizeHTML($title)."</title>\n";
	echo "<link>".sanitizeHTML($link)."</link>\n";
	echo "<description>".sanitizeHTML($description)."</description>\n";
	echo "<pubDate>$pubDate</pubDate>\n";
	foreach ($items as $item) {
		echo "<item>\n";
		echo "<title>".sanitizeHTML($item["title"])."</title>\n";
		echo "<link>".sanitizeHTML($item["link"])."</link>\n";
		echo "<guid>".sanitizeHTML($item["guid"])."</guid>\n";
		echo "<pubDate>{$item["date"]}</pubDate>\n";
		echo "<description>".sanitizeHTML($item["description"])."</description>\n";
		echo "</item>\n";
	}
	echo "</channel>\n</rss>";
	exit;
}

public function action_conversation($conversationId = false)
{
	$this->action_index($conversationId);
}

}
