$(function () {
	// Inject color picker into channel edit form if present
	var $form = $("form").filter(function () {
		return $(this).find("[name=title], [name=slug]").length;
	}).first();
	if (!$form.length || typeof ETChannelColors === "undefined") return;
	if ($form.find(".channel-color-picker").length) return;

	var $wrap = $("<div class='form'><li><label>Color</label><div class='channel-color-picker'></div></li></div>");
	var $picker = $wrap.find(".channel-color-picker");
	var $input = $("<input type='text' name='color' placeholder='#2563eb' class='text'>");
	$picker.append($input);

	// Try to read existing from a data attribute or hidden field
	$.each(ETChannelColors, function (i, c) {
		var $b = $("<button type='button'></button>").css("background", c).attr("data-color", c);
		$b.on("click", function (e) {
			e.preventDefault();
			$input.val(c);
			$picker.find("button").removeClass("selected");
			$(this).addClass("selected");
		});
		$picker.prepend($b);
	});

	$form.find(".buttons, .sheet .buttons").first().before($wrap);

	// On submit, ensure color is posted - admin already merges form values into attributes
	// Patch: some installs need color copied into attributes via AJAX - form field name "color" 
	// is merged if setValues attributes includes it on save in controller.
});
