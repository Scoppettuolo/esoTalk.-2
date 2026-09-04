(function($) {
	"use strict";

	var options = window.ET && ET.classicConversation ? ET.classicConversation : {alignment: "alternate", initialSide: "r"};

	function opposite(side) {
		return side === "r" ? "l" : "r";
	}

	function preferredSide(side) {
		if (options.alignment === "left") return "l";
		if (options.alignment === "right") return "r";
		if (options.alignment === "none") return "none";
		return side;
	}

	/* The server supplies sides for normal and AJAX post blocks. This repairs a single post
	 * returned by editPost, which is formatted without conversationIndex context. */
	function repairUnannotatedPosts() {
		var previousAuthor = null;
		var side = options.initialSide || "r";
		$("#conversationPosts > li").each(function() {
			var $post = $(this).children(".post");
			if (!$post.length) return;
			var author = $post.attr("data-memberid") || $post.data("memberid") || null;
			var hasSide = $post.hasClass("side-l") || $post.hasClass("side-r") || $post.hasClass("side-none");
			if (previousAuthor !== null && String(previousAuthor) !== String(author)) side = opposite(side);
			if (!hasSide) {
				var applied = preferredSide(side);
				$post.addClass("side-" + applied).attr("data-classic-side", applied);
			}
			else if ($post.hasClass("side-l")) side = "l";
			else if ($post.hasClass("side-r")) side = "r";
			previousAuthor = author;
		});
	}

	$(function() {
		repairUnannotatedPosts();
		if (window.MutationObserver && document.getElementById("conversationPosts")) {
			var observer = new MutationObserver(function() { repairUnannotatedPosts(); });
			observer.observe(document.getElementById("conversationPosts"), {childList: true, subtree: true});
		}
	});
})(jQuery);
