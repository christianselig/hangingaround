<?php
	session_start();

	require "functions.php";
	require "password.php";
	require "config.php";

	// If they pressed a submit button
	if (isset($_POST["submit"])) {
		// If they pressed the login submit button
		if ($_POST["submit"] === "LOG IN") {
			$email = $_POST["email"];
			$password = $_POST["password"];

			$errors = array();

			if (!$_POST["email"] || !$_POST["password"]) {
				array_push($errors, "<strong>Empty fields!</strong> You need to include both the username and password to login.");
			}
			else if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
				array_push($errors, "<strong>Invalid email.</strong> Make sure to log in with your email, not username.");
			}

			// If input is valid
			if (count($errors) === 0) {
				$stmt = $db->prepare("SELECT id, username, password FROM users WHERE email = ?");
				$stmt->bind_param("s", $email);
				$stmt->bind_result($db_id, $db_username, $db_password);
				$stmt->execute();
				$stmt->fetch();

				// if (password_verify($password, $row["password"])) {
				if (password_verify($password, $db_password)) {
					// Save the user's id for identification
					$_SESSION["id"] = $db_id;
					$_SESSION["username"] = $db_username;

					// Clear any errors if they occurred
					if (isset($_SESSION["errors"])) {
						unset($_SESSION["errors"]);
					}
				}
				else {
					array_push($errors, "<strong>Typo?</strong> Incorrect username or password.");
				}

				$stmt->free_result();
			}

			// If there were errors with logging in
			if (count($errors)) {
				$_SESSION["errors"] = $errors;
			}
		}
		// If they pressed the register submit button
		else if ($_POST["submit"] === "REGISTER") {
			$username = $_POST["username"];
			$email = $_POST["email"];
			$password = $_POST["password"];
			$confirm_password = $_POST["confirm-password"];

			// Array to hold any errors from registering
			$errors = array();

			// Check to see all fields are filled in
			if (!$username || !$email || !$password || !$confirm_password) {
				array_push($errors, "<strong>Empty field(s).</strong> Please fill in all the fields.");
			}
			else {
				// Username checks
				if (strlen($username) > 12) {
					array_push($errors, "<strong>Too long!</strong> Usernames cannot be longer than 12 characters, sorry.");
				}
				if (!preg_match("/^[a-zA-Z\d_ ].*$/", $username)) {
					array_push($errors, "<strong>Invalid username.</strong> Usernames can contain letters, numbers, underscores and/or spaces.");
				}

				// Email address checks
				if (strlen($email) > 32) {
					array_push($errors, "<strong>Too long!</strong> Email address cannot be longer than 32 characters.");
				}
				if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
					array_push($errors, "<strong>Invalid email.</strong> That's not a valid email address.");
				}

				// Password checks
				if (strlen($password) < 6) {
					array_push($errors, "<strong>Too short.</strong> Password must be longer than 6 characters.");
				}
				if (!preg_match("/^[a-zA-Z\d_ ].*$/", $password)) {
					array_push($errors, "<strong>Invalid password.</strong> Can contain letters, digits, underscores and/or spaces.");
				}
				if ($password !== $confirm_password) {
					array_push($errors, "<strong>Didn't match.</strong> Password and confirm password do not match.");
				}
			}

			// If there were no errors in input
			if (count($errors) === 0) {
				$stmt1 = $db->prepare("SELECT email FROM users WHERE email = ?");
				$stmt2 = $db->prepare("SELECT username FROM users WHERE username = ?");

				$stmt1->bind_param("s", $email);
				$stmt2->bind_param("s", $username);

				$stmt1->bind_result($db_email);
				$stmt2->bind_result($db_username);

				$stmt1->execute();
				$stmt2->execute();

				$stmt1->fetch();
				$stmt2->fetch();

				if ($db_email) {
					array_push($errors, "<strong>Taken.</strong> That email address is already taken.");
				}

				if ($db_username) {
					array_push($errors, "<strong>Taken.</strong> That username's taken. Quality choice though!");
				}

				if (count($errors) === 0) {
					// Hash password with bcrypt (native in PHP 5.5, using library to get usage in current version)
					$hashed_password = password_hash($password, PASSWORD_BCRYPT);

					$stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
					$stmt->bind_param("sss", $username, $email, $hashed_password);
					$stmt->execute();

					// If the registration of the user (adding them to the database) didn't work
					if ($db->affected_rows === 1) {
						$_SESSION["register-success"] = true;
					}
					else {
						array_push($errors, "<strong>Wait, what?</strong> An error occurred. Weird. <a href='mailto:me@christianselig.com'>Email me!</a>");
					}

					$stmt->free_result();
				}

				$stmt1->free_result();
				$stmt2->free_result();
			}

			// If there were errors in registering the user
			if (count($errors)) {
				$_SESSION["errors"] = $errors;
			}
		}
		// If they pressed Log Out
		else if ($_POST["submit"] === "Log Out") {
			$_SESSION = array();
			session_destroy();
		}
		else if ($_POST["submit"] === "Reset Game") {
			reset_game();
		}
		else if ($_POST["submit"] === "Play Again") {
			reset_game();
		}
	}

	// Initialization of needed variables
	if (!isset($_SESSION["word"])) {
		$_SESSION["word"] = generate_word("dictionary.txt");
		$_SESSION["word-progress"] = turn_to_underscores($_SESSION["word"]);
	}

	if (!isset($_SESSION["chances-left"])) {
		$_SESSION["chances-left"] = 9;
	}
	else if ($_SESSION["chances-left"] < 0) {
		$_SESSION["chances-left"] = 0;
	}

	if (!isset($_SESSION["guesses"])) {
		$_SESSION["guesses"] = array();
	}

	if (!isset($_SESSION["incorrect-guesses"])) {
		$_SESSION["incorrect-guesses"] = array();
	}

	// If the user made a guess (also prevents URL guesses once user has lost)
	if ($_SESSION["chances-left"] > 0) {
		if (isset($_GET["guess"])) {
			check_guess($_GET["guess"]);
		}
	}

	// Checks if user has won (increment wins), hasn't won (keep playing) or has already won (reset game)
	if (!isset($_SESSION["won"]) || (isset($_SESSION["won"]) && !$_SESSION["won"])) {
		$_SESSION["won"] = check_if_won($_SESSION["word-progress"]);

		if ($_SESSION["won"]) {
			// If logged in, increment their amount of wins by 1
			if (isset($_SESSION["id"])) {
				$stmt = $db->prepare("UPDATE users SET wins = wins + 1 WHERE id = ?");
				$stmt->bind_param("i", $_SESSION["id"]);
				$stmt-> execute();
				$stmt->free_result();
			}
			// If not logged in, increment the anonymous wins by 1
			else {
				$db->query("UPDATE users SET wins = wins + 1 WHERE id = 1");
			}
		}
	}
	else if (isset($_SESSION["won"]) && $_SESSION["won"]) {
		reset_game();
	}

	// Checks if user has lost (increment losses), hasn't lost (keep playing) or has already lost (reset game)
	if (!isset($_SESSION["lost"]) || (isset($_SESSION["lost"]) && !$_SESSION["lost"])) {
		$_SESSION["lost"] = ($_SESSION["chances-left"] === 0);

		if ($_SESSION["lost"]) {
			// If logged in, increment their amount of losses by 1
			if (isset($_SESSION["id"])) {
				$stmt = $db->prepare("UPDATE users SET losses = losses + 1 WHERE id = ?");
				$stmt->bind_param("s", $_SESSION["id"]);
				$stmt->execute();
				$stmt->free_result();
			}
			// If not logged in, increment the anonymous wins by 1
			else {
				$db->query("UPDATE users SET losses = losses + 1 WHERE id = 1");
			}
		}
	}
	else if (isset($_SESSION["lost"]) && $_SESSION["lost"]) {
		reset_game();
	}
?>

<!-- Credit to http://tutorialzine.com/2009/10/cool-login-system-php-jquery/
	 for help with constructing some of the login portions. Did not copy, just
	 followed tutorial for some ways to do things.
-->

<html>
<head>
	<title>Hangman!</title>

	<meta name="description" content="Play the Hangman game online" />
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<meta name="author" content="Christian Selig">

	<!-- Stylesheets -->
	<link href="normalize-css.googlecode.com/svn/trunk/normalize.css" rel="stylesheet" type="text/css" media="all" />
	<link href="//netdna.bootstrapcdn.com/font-awesome/3.0.2/css/font-awesome.css" rel="stylesheet">
	<link href="css/style.css" rel="stylesheet" type="text/css" media="all" />

	<!-- Scripts -->
	<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
	<script src="scripts.js" type="text/javascript"></script>
</head>

<body>
	<div class="wrapper">
		<div class="header">
			<div class="header-buttons">
				<?php
					if (isset($_SESSION["id"])) {
						echo "<a href='#' class='score-button'>View Scores <i class='icon-angle-down'></i></a>\n";
						echo "<form action='index.php' method='post'><input type='submit' name='submit' class='logout-button' value='Log Out'></form>\n";
					}
					else {
						echo "<a href='#' class='score-button'>View Scores <i class='icon-angle-down'></i></a>\n";
						echo "<a href='#' class='login-button'>Log In <i class='icon-angle-down'></i></a>\n";
						echo "<a href='#' class='register-button'>Register <i class='icon-angle-down'></i></a>";
					}
				?>
			</div>
		</div>

		<div class="score-window">
			<i class="icon-remove" title="Close"></i>
			<?php
				if (isset($_SESSION["id"])) {
					// Get user data
					$stmt = $db->prepare("SELECT wins, losses FROM users WHERE id = ?");
					$stmt->bind_param("i", $_SESSION["id"]);
					$stmt->bind_result($db_wins, $db_losses);
					$stmt->execute();
					$stmt->fetch();

					// Print out user scores
					echo "<p><strong>User:</strong> " . $_SESSION["username"] . "</p>\n";
					echo "<p><strong>Wins:</strong> " . $db_wins . "</p>\n";
					echo "<p><strong>Losses:</strong> " . $db_losses . "</p>\n";

					// Make sure division by zero does not occur
					echo "<p><strong>Ratio:</strong> ";
					echo ($db_losses === 0) ? "&infin;" : number_format($db_wins/$db_losses, 2);
					echo "</p>";

					$stmt->free_result();
				}
				else {
					echo "<p><a href='#'>Log in</a> to save your score and view your rank.</p><br>";
					echo "<p>No account? <a href='#'>Register</a>!</p>";
				}

				// Print out scoreboard
				echo "<div class='scoreboard'>\n<table>\n";
				echo "<tr><th>User</th> <th>Wins</th> <th>Losses</th></tr>";

				if ($result = $db->query("SELECT username, wins, losses FROM users WHERE id != 1 ORDER BY wins DESC LIMIT 10")) {
					while ($row = $result->fetch_assoc()) {
						echo "<tr><td>" . $row["username"] . "</td> <td>" . $row["wins"] . "</td> <td>" . $row["losses"] . "</td></tr>\n";
					}
				}
				else {
					echo $db->error;
				}


				// echo "<tr><td>" . $row["username"] . "</td> <td>" . $row["wins"] . "</td> <td>" . $row["losses"] . "</td></tr>\n";
				echo "</table>\n</div>\n";

				if ($result = $db->query("SELECT wins, losses FROM users WHERE id = 1")) {
					$row = $result->fetch_assoc();

					$result->free_result();
				}
			?>
		</div>

		<div class="login-window">
			<i class="icon-remove" title="Close"></i>
			<form action="index.php" method="post">
				<h2 class="window-header">Log In</h2>
				<div class="input-wrap">
					<input type="email" name="email" placeholder="EMAIL">
					<hr>
					<input type="password" name="password" placeholder="PASSWORD">
				</div>

				<input type="submit" name="submit" class="submit-login" value="LOG IN">
			</form>
			<a href="#" class="register-option">OR REGISTER</a>
		</div>

		<div class="register-window">
			<i class="icon-remove" title="Close"></i>
			<form action="index.php" method="post">
				<h2 class="window-header">Register</h2>
				<div class="input-wrap">
					<input type="text" name="username" placeholder="USERNAME">
					<hr>
					<input type="email" name="email" placeholder="EMAIL">
					<hr>
					<input type="password" name="password" placeholder="PASSWORD">
					<hr>
					<input type="password" name="confirm-password" placeholder="CONFIRM PASSWORD">
				</div>

				<input type="submit" name="submit" value="REGISTER">
			</form>
		</div>

		<?php
			// Write out any errors that occurred to the page
			if (isset($_SESSION["errors"])) {
				echo "<div class='errors'>\n<i class='icon-remove'></i>\n<ul>\n";
				foreach ($_SESSION["errors"] as $error) {
					echo "<li>" . $error . "</li>\n";
				}
				echo "</ul>\n</div>\n";

				unset($_SESSION["errors"]);
			}
			else if (isset($_SESSION["register-success"])) {
				echo "<div class='success'>\n<i class='icon-remove'></i>\n";
				echo "<p><strong>Registered!</strong> You've been registered. Log in and play!</p>\n";
				echo "</div>\n";

				unset($_SESSION["register-success"]);
			}
		?>

		<div class="hangman-area">
			<div class="word-progress">
				<?php echo $_SESSION["word-progress"]; ?>
				<form action="index.php" method="post"><button type="submit" name="submit" class="reset-button" title="Admitting defeat?" value="Reset Game"><i class="icon-refresh"></i></button></form>
			</div>

			<?php
				if ($_SESSION["won"]) {
					echo "<img src='media/hangman-win.svg' alt='Hangman character'>";
				}
				else {
					echo "<img src='media/hangman" . $_SESSION["chances-left"] . ".svg' alt='Hangman character'>";
				}
			?>
		</div>

		<?php
			if ($_SESSION["won"]) {
		?>
				<script>$(document).ready(function() { $(".reset-button").hide(); });</script>
				<div class="results">
					<h2>Congratulations! You won!</h2>
					<p>Curious what the word means? <a href="http://thefreedictionary.com/<?php echo $_SESSION['word']; ?>">Read definition <i class="icon-arrow-right"></i></a></p>

					<form action="index.php" method="post"><button type="submit" name="submit" value="Play Again">Play Again <i class="icon-repeat"></i></button></form>
				</div>

		<?php
			}
			else if ($_SESSION["lost"]) {
		?>
				<script>$(document).ready(function() { $(".reset-button").hide(); });</script>
				<div class="results">
					<h2>Aww, you lost.</h2>
					<p>The word was <em><?php echo $_SESSION["word"]; ?></em>. <a href="http://thefreedictionary.com/<?php echo $_SESSION['word']; ?>">Read definition <i class="icon-arrow-right"></i></a></p>

					<form action="index.php" method="post"><button type="submit" name="submit" value="Play Again">Play Again <i class="icon-repeat"></i></button></form>
				</div>
		<?php
			}
			else {
		?>
				<div class="letters">
					<form>
						<?php
							// Print all the letters in the alphabet (uppercase)
							$alphabet = range("A", "Z");
							foreach ($alphabet as $letter) {
								if (!in_array($letter, $_SESSION["guesses"])) {
									echo "<a href='" . $_SERVER["PHP_SELF"] . "?guess=" . $letter . "'>" . $letter . "</a>\n";
								}
								else {
									echo "<a href='" . $_SERVER["PHP_SELF"] . "?guess=" . $letter . "' class='already-guessed'>" . $letter . "</a>\n";
								}

								if ($letter === "M") {
									echo "<br>";
								}
							}
						?>
					</form>
				</div>
		<?php
			}
		?>
	</div>

	<div class="shadow"></div>
</body>
</html>
