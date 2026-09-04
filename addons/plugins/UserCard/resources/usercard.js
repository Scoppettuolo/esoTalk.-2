(function ($) {
	var $card, timer, currentId;
	function ensure() {
		if ($card) return $card;
		$card = $("<div id='et-usercard' hidden></div>").appendTo("body");
		$card.on("mouseenter", function () { clearTimeout(timer); });
		$card.on("mouseleave", hide);
		return $card;
	}
	function hide() {
		timer = setTimeout(function () {
			ensure().attr("hidden", true);
			currentId = null;
		}, 200);
	}
	$(document).on("mouseenter", ".postHeader h3 a, .name a", function () {
		clearTimeout(timer);
		var href = $(this).attr("href") || "";
		var m = href.match(/member\/(\d+)/);
		if (!m) return;
		var id = m[1];
		if (id === currentId) {
			ensure().removeAttr("hidden");
			return;
		}
		currentId = id;
		var $link = $(this);
		$.ETAjax({
			url: "conversation/userCard",
			data: { memberId: id },
			success: function (data) {
				if (!data.member) return;
				var mem = data.member;
				var html = "<div class='uc-head'>" + mem.avatar +
					"<div><div class='uc-name'><a href='" + mem.url + "'>" + $("<div>").text(mem.username).html() + "</a></div>" +
					"<div class='uc-meta'>" + $("<div>").text(mem.account).html() + "</div></div></div>" +
					"<div class='uc-stats'>" + mem.countPosts + " posts · joined " + mem.joinTime + "</div>";
				var $c = ensure();
				$c.html(html).removeAttr("hidden");
				var off = $link.offset();
				$c.css({ top: off.top + $link.outerHeight() + 6, left: Math.min(off.left, $(window).width() - 260) });
			}
		});
	});
	$(document).on("mouseleave", ".postHeader h3 a, .name a", hide);
})(jQuery);
