$(document).ready(function() {

	// Clicking view score button makes score window and shadow appear
	$(".score-button").click(function() {
		$(".score-window").toggle();
		$(".shadow").toggle();
	});

	// Clicking login button makes login window and shadow appear and focuses on text input
	$(".login-button").click(function() {
		$(".login-window").toggle();
		$(".shadow").toggle();
		$(".login-window input[type='text']").focus();
	});

	// Clicking register button makes register window and shadow appear and focuses on text input
	$(".register-button").click(function() {
		$(".register-window").toggle();
		$(".shadow").toggle();
		$(".register-window input[type='text']").focus();
	});

	// Hides the scores, login or register window (and removes the shadow) is the user presses Escape
	$(document).keyup(function(e) {
		if (e.keyCode == 27) {
			$(".score-window").hide();
			$(".login-window").hide();
			$(".register-window").hide();
			$(".shadow").hide();
		}
	});

	// Hides the scores, login or register window (and removes the shadow) is the user clicks the x
	$(document).on("click", ".icon-remove", function() {
		$(".score-window").hide();
		$(".login-window").hide();
		$(".register-window").hide();
		$(".errors").hide();
		$(".success").hide();
		$(".shadow").hide();
	});

	// Hides the scores, login or register window (and removes the shadow) is the user clicks outside of it
	$(document).mouseup(function(e) {
		if ($(".score-window").is(":visible")) {
			if ($(".score-window").has(e.target).length == 0) {
				$(".score-window").hide();
				$(".shadow").hide();
			}
		}

		if ($(".login-window").is(":visible")) {
			if ($(".login-window").has(e.target).length == 0) {
				$(".login-window").hide();
				$(".shadow").hide();
			}
		}

		if ($(".register-window").is(":visible")) {
			if ($(".register-window").has(e.target).length == 0) {
				$(".register-window").hide();
				$(".shadow").hide();
			}
		}
	});

	// Clicking Login text when Score window is up brings up Login window
	$(".score-window p:nth-child(2) a").click(function() {
		$(".score-window").hide();
		$(".login-window").show();
	});

	// Clicking Register text when Score window is up brings up Register Window
	$(".score-window p:nth-child(4) a").click(function() {
		$(".score-window").hide();
		$(".register-window").show();
	});

	// Clicking Register text when Login window is up brings up Register window
	$(".login-window a").click(function() {
		$(".login-window").hide();
		$(".register-window").show();
	});
});