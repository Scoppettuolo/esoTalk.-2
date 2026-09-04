$(function () {
	var $box = $("<div id='et-lightbox' role='dialog' aria-modal='true' aria-label='Image' hidden><button type='button' class='et-lightbox-close' aria-label='Close'>&times;</button><img alt=''></div>").appendTo("body");
	var $img = $box.find("img");

	function close() {
		$box.attr("hidden", true);
		$img.attr("src", "");
	}

	$(document).on("click", ".postBody img", function (e) {
		e.preventDefault();
		$img.attr("src", this.src);
		$img.attr("alt", this.alt || "");
		$box.removeAttr("hidden");
	});

	$box.on("click", function (e) {
		if (e.target === this || $(e.target).hasClass("et-lightbox-close")) close();
	});

	$(document).on("keydown", function (e) {
		if (e.key === "Escape" && !$box.attr("hidden")) close();
	});
});
