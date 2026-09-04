(function ($) {
	function keyFor(textarea) {
		var id = $(textarea).closest("form").attr("action") || location.pathname;
		return "et_draft_" + id;
	}
	$(function () {
		$("textarea[name=content]").each(function () {
			var $t = $(this);
			var key = keyFor(this);
			if (!$t.val()) {
				try {
					var saved = localStorage.getItem(key);
					if (saved) $t.val(saved);
				} catch (e) {}
			}
			var timer;
			$t.on("input", function () {
				clearTimeout(timer);
				timer = setTimeout(function () {
					try { localStorage.setItem(key, $t.val()); } catch (e) {}
				}, 400);
			});
			$t.closest("form").on("submit", function () {
				try { localStorage.removeItem(key); } catch (e) {}
			});
		});
	});
})(jQuery);
