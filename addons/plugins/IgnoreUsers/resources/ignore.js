$(document).on("click", ".control-ignore-member", function (e) {
	e.preventDefault();
	var $a = $(this);
	$.ETAjax({
		url: "member/toggleIgnore",
		data: { memberId: $a.data("memberid") },
		success: function (data) {
			if (data.error) return;
			$a.text(data.ignored ? "Unignore" : "Ignore");
			$a.data("ignored", data.ignored ? 1 : 0);
		}
	});
});
