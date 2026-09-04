<?php
if (!defined("IN_ESOTALK")) exit;

global $conversation;
$conversation = $data["conversation"];
$baseURL = conversationURL($conversation["conversationId"], $conversation["title"]);
$searchString = $data["searchString"];
$classicURL = function($start) use ($baseURL, $searchString) {
	$url = $baseURL;
	if ((int)$start > 0 || $searchString) $url .= "/".(int)$start;
	if ($searchString) $url .= "?search=".urlencode($searchString);
	return URL($url);
};
$renderPagination = function() use ($data, $conversation, $classicURL) {
	$total = max(0, (int)$conversation["countPosts"]);
	$perPage = max(1, (int)C("esoTalk.conversation.postsPerPage"));
	$start = max(0, (int)$data["startFrom"]);
	$end = min($total, $start + $perPage);
	if (!$total) return;
	$last = max(0, (int)(floor(($total - 1) / $perPage) * $perPage));
	$previous = max(0, $start - $perPage);
	$next = min($last, $start + $perPage);
	echo "<nav class='classic-pagination' aria-label='Conversation pages'>";
	if ($start > 0) echo "<a href='".$classicURL(0)."' title='".sanitizeHTML(T("Original Post"))."'>&laquo;</a>";
	if ($start > 0) echo "<a href='".$classicURL($previous)."' title='".sanitizeHTML(T("Older"))."'>&lsaquo; ".T("Older")."</a>";
	echo "<span class='classic-page-status'>".(($start + 1)."–".$end." / ".number_format($total))."</span>";
	if ($next > $start) echo "<a href='".$classicURL($next)."' title='".sanitizeHTML(T("Newer"))."'>".T("Newer")." &rsaquo;</a>";
	if ($last > $start) echo "<a href='".$classicURL($last)."' title='".sanitizeHTML(T("Latest"))."'>".T("Latest")." &raquo;</a>";
	echo "</nav>";
};
$classes = array("channel-".$conversation["channelId"]);
if ($conversation["starred"]) $classes[] = "starred";
if ($conversation["startMemberId"] == ET::$session->userId) $classes[] = "mine";
?>
<div id='conversation' class='hasScrubber <?php echo sanitizeHTML(implode(" ", $classes)); ?>'>
<div class='scrubberColumn'>
<div class='scrubberContent'>
<?php $this->trigger("renderControlsBefore", array($data)); ?>
<form class='search' id='searchWithinConversation' action='<?php echo URL($baseURL); ?>' method='get'>
<fieldset>
<i class='icon-search'></i>
<input name='search' type='text' class='text' value='<?php echo sanitizeHTML($searchString); ?>' placeholder='<?php echo T("Search"); ?>'/>
<?php if ($searchString): ?><a href='<?php echo URL($baseURL); ?>' class='control-reset'><i class='icon-remove'></i></a><?php endif; ?>
</fieldset>
</form>
<?php echo starButton($conversation["conversationId"], $conversation["starred"])."\n"; ?>
<?php if (!ET::$session->user): ?>
<a href='<?php echo URL("user/login?return=".urlencode($this->selfURL)."/#reply"); ?>' class='button big'><i class='icon-plus'></i> <?php echo T("Post a Reply"); ?></a>
<?php else: ?>
<a href='#reply' class='button big' id='jumpToReply'><i class='icon-plus'></i> <?php echo T("Post a Reply"); ?></a>
<?php endif; ?>
<?php $this->trigger("renderScrubberBefore", array($data)); ?>
<?php if (!$searchString): ?>
<ul class='scrubber timelineScrubber'>
<?php
$currentYear = date("Y");
$currentMonth = date("n");
$latestYear = date("Y", $conversation["lastPostTime"]);
$latestMonth = date("n", $conversation["lastPostTime"]);
$oldestYear = date("Y", $conversation["startTime"]);
$oldestMonth = date("n", $conversation["startTime"]);
?>
<li class='scrubber-op scrubber-nav' data-index='first'><a href='<?php echo $classicURL(0); ?>'><i class='icon-arrow-up'></i> <?php echo T("Original Post"); ?></a></li>
<?php
$startFromYear = null;
$startFromMonth = null;
if ($data["startFrom"] > 0 && !empty($data["posts"])) {
	$startFromYear = date("Y", $data["posts"][0]["time"]);
	$startFromMonth = date("n", $data["posts"][0]["time"]);
}
$scrubber = array();
$y = $oldestYear;
$m = $oldestMonth;
while ($y < $latestYear || ($y == $latestYear && $m <= $latestMonth)) {
	if ($m > 12) { $m = 1; $y++; }
	$scrubber[$y][] = $m;
	$m++;
}
$recentMonths = array();
if (!empty($scrubber[$currentYear])) {
	$recentMonths = array_splice($scrubber[$currentYear], -5);
	if (!count($scrubber[$currentYear])) unset($scrubber[$currentYear]);
}
foreach ($scrubber as $year => $months) {
	$selected = ($startFromYear == $year && $startFromMonth <= max($months)) ? " selected" : "";
	echo "<li class='scrubber-{$year}01$selected' data-index='{$year}01'><a href='".URL($baseURL."/".$year."/1")."'>$year</a>";
	if (!empty($months)) {
		echo "<ul>";
		foreach ($months as $month) {
			$selected = ($startFromYear == $year && $startFromMonth == $month) ? " selected" : "";
			$name = esotalk_strftime("%B", mktime(0, 0, 0, $month, 1));
			$monthURL = URL($baseURL."/".$year."/".$month);
			echo "<li class='scrubber-".$year.str_pad($month, 2, "0", STR_PAD_LEFT)."$selected'><a href='$monthURL'>$name</a></li>";
		}
		echo "</ul>";
	}
	echo "</li>";
}
foreach ($recentMonths as $month) {
	$selected = ($startFromYear == $currentYear && $startFromMonth == $month) ? " selected" : "";
	$name = esotalk_strftime("%B", mktime(0, 0, 0, $month, 1));
	echo "<li class='scrubber-".$currentYear.str_pad($month, 2, "0", STR_PAD_LEFT)."$selected'><a href='".URL($baseURL."/".$currentYear."/".$month)."'>$name</a></li>";
}
?>
<li class='scrubber-now scrubber-nav' data-index='last'><a href='<?php echo URL($baseURL."/last"); ?>'><i class='icon-arrow-down'></i> <?php echo T("Latest"); ?></a></li>
</ul>
<?php endif; ?>
</div>
</div>

<div id='conversationHeader' class='bodyHeader'>
<h1 id='conversationTitle'><?php if ($conversation["canModerate"] || $conversation["startMemberId"] == ET::$session->userId): ?><a href='<?php echo URL("conversation/edit/".$conversation["conversationId"]); ?>'><?php echo sanitizeHTML($conversation["title"]); ?></a><?php else: echo sanitizeHTML($conversation["title"]); endif; ?></h1>
<?php $this->renderView("conversation/channelPath", array("conversation" => $conversation)); ?>
<span class='labels'><?php $this->renderView("conversation/labels", array("labels" => $conversation["labels"])); ?></span>
</div>
<?php if ($data["controlsMenu"]->count()): ?>
<ul id='conversationControls' class='controls'><?php echo $data["controlsMenu"]->getContents(); ?></ul>
<?php endif; ?>
<?php if (count($conversation["membersAllowedSummary"]) || $conversation["startMemberId"] == ET::$session->userId || $conversation["canModerate"]): ?>
<div id='conversationPrivacy' class='area'>
<span class='allowedList action'><?php $this->renderView("conversation/membersAllowedSummary", $data); ?></span>
<?php if ($conversation["startMemberId"] == ET::$session->userId): ?><a href='<?php echo URL("conversation/edit/".$conversation["conversationId"]); ?>' id='control-changeMembersAllowed'><i class='icon-pencil'></i> <?php echo T("Change"); ?></a><?php endif; ?>
</div>
<?php endif; ?>

<div id='conversationBody'>
<?php $renderPagination(); ?>
<?php if ($searchString && !$conversation["countPosts"]): ?>
<div class='area noResults help'><h4><?php echo T("message.noSearchResultsPosts"); ?></h4><ul><li><?php echo T("message.fulltextKeywordWarning"); ?></li><li><?php echo T("message.searchAllConversations"); ?></li></ul></div>
<?php else: ?>
<ol id='conversationPosts' class='postList' start='<?php echo $data["startFrom"] + 1; ?>'>
<?php if ($data["startFrom"] > 0): ?><li class='scrubberMore scrubberPrevious'><a href='<?php echo URL($baseURL."/".max(0, $data["startFrom"] - C("esoTalk.conversation.postsPerPage"))); ?>'>&lsaquo; <?php echo T("Older"); ?></a></li><?php endif; ?>
<?php $this->renderView("conversation/posts", $data); ?>
<?php if ($data["startFrom"] + C("esoTalk.conversation.postsPerPage") < $conversation["countPosts"]): ?><li class='scrubberMore scrubberNext'><a href='<?php echo URL($baseURL."/".($data["startFrom"] + C("esoTalk.conversation.postsPerPage"))); ?>'><?php echo T("Newer"); ?> &rsaquo;</a></li><?php endif; ?>
</ol>
<?php $renderPagination(); ?>
<?php if (!$searchString): ?>
<div id='conversationReply'>
<?php echo $data["replyForm"]->open(); ?>
<?php if (!$conversation["canReply"]): ?>
<?php if (!ET::$session->user): ?>
<?php $post = array("id" => "reply", "class" => "logInToReply", "title" => "", "body" => sprintf(T("message.logInToReply"), URL("user/login?return=".urlencode($this->selfURL)), URL("user/join?return=".urlencode($this->selfURL))), "avatar" => avatar()); $this->renderView("conversation/post", array("post" => $post)); ?>
<?php elseif (ET::$session->isSuspended()): ?><p class='help'><?php echo T("message.suspended"); ?></p>
<?php elseif ($conversation["locked"]): ?><p class='help'><?php echo T("message.locked"); ?></p>
<?php endif; ?>
<?php else: ?>
<?php $this->renderView("conversation/reply", array("form" => $data["replyForm"], "conversation" => $conversation, "controls" => $data["replyControls"])); ?>
<?php endif; ?>
<?php echo $data["replyForm"]->close(); ?>
</div>
<?php endif; ?>
<?php endif; ?>
</div>
</div>
