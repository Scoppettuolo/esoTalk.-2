(function () {
	function preferred() {
		if (window.ET_DARK_PREF === "dark") return true;
		if (window.ET_DARK_PREF === "light") return false;
		try {
			var ls = localStorage.getItem("et_dark");
			if (ls === "1") return true;
			if (ls === "0") return false;
		} catch (e) {}
		if (window.ET_DARK_PREF === "auto" || !window.ET_DARK_PREF) {
			return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
		}
		return false;
	}
	function t(key, fallback) {
		try {
			if (window.T && typeof T === "function") return T(key);
		} catch (e) {}
		return fallback || key;
	}
	function apply(on) {
		var root = document.documentElement;
		root.classList.toggle("et-dark", !!on);
		root.setAttribute("data-theme", on ? "dark" : "light");
		root.style.colorScheme = on ? "dark" : "light";
		try { localStorage.setItem("et_dark", on ? "1" : "0"); } catch (e) {}
		var label = document.querySelector("#darkModeToggle span");
		if (label) label.textContent = on ? t("Light", "Light") : t("Dark mode", "Dark mode");
	}
	apply(preferred());
	document.addEventListener("DOMContentLoaded", function () {
		apply(preferred());
		if (window.jQuery) {
			jQuery(document).on("click", "#darkModeToggle", function (e) {
				e.preventDefault();
				apply(!document.documentElement.classList.contains("et-dark"));
			});
		} else {
			document.addEventListener("click", function (e) {
				var el = e.target.closest && e.target.closest("#darkModeToggle");
				if (el) {
					e.preventDefault();
					apply(!document.documentElement.classList.contains("et-dark"));
				}
			});
		}
	});
})();
