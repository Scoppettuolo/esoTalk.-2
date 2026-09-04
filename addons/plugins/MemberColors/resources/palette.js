$(function() {
	function updatePreview(color) {
		var $preview = $("#memberColorPreview");
		if (!$preview.length) return;
		$preview.removeClass(function(index, className) {
			return (className.match(/(^|\s)c\d+(?=\s|$)/g) || []).join(" ");
		});
		$preview.addClass("c" + color).attr("data-color", color);
	}

	function selectColor(item) {
		var $item = $(item);
		var color = parseInt($item.data("color"), 10);
		if (!color) return;
		$("#memberColorPalette a").removeClass("selected").attr("aria-checked", "false");
		$item.addClass("selected").attr("aria-checked", "true");
		$("input[name=postColor]").val(color);
		updatePreview(color);
	}

	$(document).on("click", "#memberColorPalette a", function(e) {
		e.preventDefault();
		selectColor(this);
	});

	$(document).on("keydown", "#memberColorPalette a", function(e) {
		if (e.key === "Enter" || e.key === " ") {
			e.preventDefault();
			selectColor(this);
		}
	});

	var initial = parseInt($("input[name=postColor]").val(), 10);
	if (initial) updatePreview(initial);
});
