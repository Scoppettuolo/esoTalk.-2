$(function () {
	var $btn = $("<button type='button' id='et-scroll-top' title='Top' aria-label='Scroll to top'><i class='icon-chevron-up'></i></button>").appendTo("body");
	$(window).on("scroll", function () {
		$btn.toggleClass("visible", $(window).scrollTop() > 400);
	});
	$btn.on("click", function () {
		$("html, body").animate({ scrollTop: 0 }, 280);
	});
});
