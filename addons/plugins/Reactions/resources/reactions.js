$(document).on("click", ".reactions .reaction", function (e) {
	e.preventDefault();
	var $a = $(this);
	var postId = $a.closest(".reactions").data("postid");
	var type = $a.data("type");
	$.ETAjax({
		url: "conversation/react",
		data: { postId: postId, type: type },
		success: function (data) {
			if (data.error) return;
			$a.toggleClass("active", !!data.on);
			$a.find(".r-count").text(data.count ? " " + data.count : "");
		}
	});
});
