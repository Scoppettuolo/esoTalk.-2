/**
 * Lightweight $.history for esoTalk — HTML5 History API (no iframe/IE hacks).
 * API compatible with the old plugin: $.history.init(), $.history.load(path, prependWebPath)
 */
(function ($) {
	"use strict";

	var current = "";
	var silent = false;

	function normalize(path) {
		if (!path) return "";
		return String(path).replace(/^#/, "").replace(/^\//, "");
	}

	function emit(path) {
		current = path;
		$(document).trigger("statechange", [path]);
	}

	$.history = {
		init: function () {
			// popstate (back/forward)
			$(window).on("popstate.etHistory", function (e) {
				if (silent) return;
				var path = normalize(
					(e.originalEvent && e.originalEvent.state) ||
					(location.pathname + location.search).replace(new RegExp("^" + (ET.webPath || "").replace(/[.*+?^${}()|[\]\\]/g, "\\$&")), "") ||
					location.hash
				);
				emit(path);
			});

			// Initial
			var initial = normalize(
				(location.pathname + location.search).replace(
					new RegExp("^" + (window.ET && ET.webPath ? ET.webPath.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") : "")),
					""
				)
			);
			if (!initial && location.hash) initial = normalize(location.hash);
			current = initial;
		},

		load: function (path, prependWebPath) {
			path = normalize(path);
			if (path === current) return;
			silent = true;
			try {
				var url = (prependWebPath && window.ET && ET.webPath ? ET.webPath.replace(/\/$/, "") + "/" : "") + path;
				if (window.history && history.pushState) {
					history.pushState(path, "", url || ("#" + path));
				} else {
					location.hash = path;
				}
			} catch (err) {
				location.hash = path;
			}
			silent = false;
			emit(path);
		},

		get: function () {
			return current;
		}
	};
})(jQuery);
