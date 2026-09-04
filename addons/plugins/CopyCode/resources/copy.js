$(function () {
	$(".postBody pre").each(function () {
		var $pre = $(this);
		if ($pre.parent().hasClass("code-block")) return;
		$pre.wrap('<div class="code-block"></div>');
		var $btn = $('<button type="button" class="copy-code-btn" title="Copy">Copy</button>');
		$pre.parent().prepend($btn);
	});

	$(document).on("click", ".copy-code-btn", function (e) {
		e.preventDefault();
		var $btn = $(this);
		var text = $btn.siblings("pre").text();
		var done = function () {
			var old = $btn.text();
			$btn.text("Copied!");
			setTimeout(function () { $btn.text(old); }, 1500);
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(done);
		} else {
			var ta = document.createElement("textarea");
			ta.value = text;
			document.body.appendChild(ta);
			ta.select();
			try { document.execCommand("copy"); done(); } catch (err) {}
			document.body.removeChild(ta);
		}
	});
});
