$(function () {
	var $input = $("<input type='file' id='et-attach-input' accept='image/*'>").appendTo("body");
	var targetArea = null;
	$(document).on("click", ".control-attach", function (e) {
		e.preventDefault();
		targetArea = $(this).data("area");
		$input.val("").click();
	});
	$input.on("change", function () {
		if (!this.files || !this.files[0] || !targetArea) return;
		var fd = new FormData();
		fd.append("image", this.files[0]);
		fd.append("token", ET.token);
		$.ajax({
			url: ET.webPath + "/?p=conversation/uploadImage",
			type: "POST",
			data: fd,
			processData: false,
			contentType: false,
			dataType: "json",
			success: function (data) {
				if (data.error || !data.bbcode) {
					alert("Upload failed");
					return;
				}
				var $ta = $("#" + targetArea + " textarea, #" + targetArea).filter("textarea").first();
				if (!$ta.length) $ta = $("textarea[name=content]").last();
				var v = $ta.val() || "";
				$ta.val(v + (v ? "\n" : "") + data.bbcode + "\n");
			},
			error: function () { alert("Upload failed"); }
		});
	});
});
