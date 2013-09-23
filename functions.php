<?php
	/**
	 * Generate a random word from the given dictionary file
	 * @param $filename Name of the dictionary file
	 * @return Random word
	 */
	function generate_word($filename) {
		$dictionary_file = new SplFileObject($filename);
		$dictionary_file->seek(rand(0, 80367));

		return trim($dictionary_file->current());
	}

	/**
	 * Accepts a word and returns it as underscores for obfuscation
	 * @param $word A given word
	 * @return Returns the word as underscores
	 */
	function turn_to_underscores($word) {
		$underscored_word = "";

		for ($i = 0; $i < strlen($word); $i++) {
			$underscored_word .= "_";
		}

		return $underscored_word;
	}

	/**
	 * Resets all the properties of the game
	 */
	function reset_game() {
		$_SESSION["word"] = generate_word("dictionary.txt");
		$_SESSION["word-progress"] = turn_to_underscores($_SESSION["word"]);
		$_SESSION["chances-left"] = 9;
		$_SESSION["guesses"] = array();
		$_SESSION["incorrect-guesses"] = array();
		$_SESSION["won"] = false;
		$_SESSION["lost"] = false;

		header('Location: index.php');
	}

	/**
	 * Clears the SESSION's error array
	 */
	function clear_errors() {
		if (isset($_SESSION["errors"]) && $_SESSION["errors"]) {
			unset($_SESSION["errors"]);
		}
	}

	/**
	 * Check if the user submitted guess is part of the word
	 * @param $guess User's guess (a letter)
	 */
	function check_guess($guess) {
		// If any errors have been printed, continuing to guess letters removes the errors allowing user to just play anonymously
		unset($_SESSION["errors"]);

		array_push($_SESSION["guesses"], $guess);

		// If their guessed letter is in the word
		if (stripos($_SESSION["word"], $guess) !== false) {
			$occurrence_positions = strposall($_SESSION["word"], $guess);
			$progress = $_SESSION["word-progress"];

			foreach ($occurrence_positions as $values) {
				$progress[$values] = $guess;
			}

			$_SESSION["word-progress"] = $progress;
		}
		// If their guessed letter is not in the word
		else {
			if (!in_array($guess, $_SESSION["incorrect-guesses"])) {
				$_SESSION["chances-left"]--;
				array_push($_SESSION["incorrect-guesses"], $guess);
			}
		}
	}

	/**
	 * Given the user's progress, returns true if there are no underscores remaining
	 * @param $word_progress String representing user's progress in guessing the word
	 * @return True if no underscores left in given string
	 */
	function check_if_won($word_progress) {
		if (strpos($word_progress, "_") === FALSE) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Returns an array holding the positions of the needle variable from the haystack variable
	 * @param  $haystack String to search
	 * @param  $needle String to search
	 * @return Array holding the positions of the needle
	 */
	function strposall($haystack, $needle) {
		$occurrence_positions = array();

		$pos = stripos($haystack, $needle);
		if ($pos !== false) {
			array_push($occurrence_positions, $pos);
		}

		while ($pos = stripos($haystack, $needle, $pos + 1)) {
			array_push($occurrence_positions, $pos);
		}

		return $occurrence_positions;
	}

	/**
	 * Given the number of chances left, returns the color the text should be (more red as less chances)
	 * @param  $chances_left Number of chances left
	 * @return Returns the color the text should be for the amount of chances left (more red as less chances)
	 */
	function chances_color($chances_left) {
		$color = "blue";

		switch ($chances_left) {
			case 9:
				$color = "#51bf00";
				break;
			case 8:
				$color = "#6ba808";
				break;
			case 7:
				$color = "#81940e";
				break;
			case 6:
				$color = "#968115";
				break;
			case 5:
				$color = "#b8631e";
				break;
			case 4:
				$color = "#d04e26";
				break;
		}

		if ($chances_left <= 3) {
			$color = "#f1302f";
		}

		return $color;
	}
?>