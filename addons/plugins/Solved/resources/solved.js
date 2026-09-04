$(document).on("click", ".control-solved", function (e) {
	e.preventDefault();
	var $a = $(this);
	$.ETAjax({
		url: "conversation/solve",
		data: { postId: $a.data("postid"), conversationId: $a.data("cid") },
		success: function (data) {
			if (data.error) return;
			location.reload();
		}
	});
});
