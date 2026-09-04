$(document).on("click", ".control-like", function (e) {
	e.preventDefault();
	var $a = $(this);
	var postId = $a.data("postid");
	if (!postId) return;
	$.ETAjax({
		url: "conversation/like",
		data: { postId: postId },
		success: function (data) {
			if (data.error === "login") {
				alert("Please log in to like posts.");
				return;
			}
			if (data.liked) $a.addClass("liked");
			else $a.removeClass("liked");
			$a.find(".like-count").text(data.count > 0 ? data.count : "");
			$a.attr("title", data.liked ? "Unlike" : "Like");
		}
	});
});
