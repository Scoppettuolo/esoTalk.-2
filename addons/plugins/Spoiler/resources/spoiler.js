$(document).on("click", ".spoiler-toggle", function (e) {
	e.preventDefault();
	var $btn = $(this);
	var $body = $btn.next(".spoiler-body");
	var open = $btn.attr("aria-expanded") === "true";
	$btn.attr("aria-expanded", open ? "false" : "true");
	if (open) {
		$body.attr("hidden", true);
		$btn.find("i").removeClass("icon-eye-open").addClass("icon-eye-close");
	} else {
		$body.removeAttr("hidden");
		$btn.find("i").removeClass("icon-eye-close").addClass("icon-eye-open");
	}
});
