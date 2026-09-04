$(function () {
	var meta = document.querySelector("meta[name='et-views']");
	if (!meta) return;
	var n = meta.getAttribute("content");
	var $title = $("#conversationTitle, .bodyHeader h1").first();
	if ($title.length) {
		$title.append(" <span class='et-view-count' title='Views'><i class='icon-eye-open'></i> " + n + "</span>");
	}
});
