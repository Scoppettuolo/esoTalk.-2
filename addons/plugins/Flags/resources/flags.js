$(document).on("click", ".control-flag", function (e) {
	e.preventDefault();
	var postId = $(this).data("postid");
	var reason = window.prompt("Reason (optional):", "") || "";
	$.ETAjax({
		url: "conversation/flag",
		data: { postId: postId, reason: reason },
		success: function (data) {
			if (data.error === "login") { alert("Please log in"); return; }
			if (data.message) ETMessages.showMessage(data.message, { className: "success autoDismiss" });
		}
	});
});
