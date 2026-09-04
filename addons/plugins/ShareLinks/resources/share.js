$(document).on("click", ".share-copy", function (e) {
	e.preventDefault();
	var url = $(this).closest(".share-links").data("url") || window.location.href;
	if (navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(url).then(function () {
			if (window.ETMessages) ETMessages.showMessage("Link copied", { className: "success autoDismiss" });
		});
	} else {
		var ta = document.createElement("textarea");
		ta.value = url;
		document.body.appendChild(ta);
		ta.select();
		try { document.execCommand("copy"); } catch (err) {}
		document.body.removeChild(ta);
		if (window.ETMessages) ETMessages.showMessage("Link copied", { className: "success autoDismiss" });
	}
});
