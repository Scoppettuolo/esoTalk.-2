$(function () {
	if (!$("#conversationBody, .posts").length) return;
	var $bar = $("<div id='et-read-progress'></div>").appendTo("body");
	$(window).on("scroll resize", function () {
		var h = $(document).height() - $(window).height();
		var p = h > 0 ? ($(window).scrollTop() / h) * 100 : 0;
		$bar.css("width", Math.min(100, Math.max(0, p)) + "%");
	});
});
