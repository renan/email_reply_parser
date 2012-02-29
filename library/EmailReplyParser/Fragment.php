<?php
namespace EmailReplyParser;

/**
 * Represents a group of paragraphs in the email sharing common attributes.
 * Paragraphs should get their own fragment if they are a quoted area or a
 * signature.
 */
class Fragment {

/**
 * Lines in this Fragment.
 *
 * @param array
 */
	public $lines = array();

/**
 * Determines if this Fragment should be hidden from users.
 *
 * @param boolean
 */
	public $hidden = false;

/**
 * Determines if this Fragment is a signature.
 *
 * @param boolean
 */
	public $signature = false;

/**
 * Determines if this Fragment is a quote.
 *
 * @param boolean
 */
	public $quoted = false;

/**
 * This is reserved for the joined String that is build when this Fragment is finished.
 *
 * @param string
 */
	public $content;

/**
 * Store the first line and marks the Fragment as quoted, if it is.
 *
 * @param boolean $isQuoted Eitheir if the line if quoted or not.
 * @param string $firstLine A line of text from the email.
 */
	public function __construct($isQuoted, $firstLine) {
		$this->quoted = $isQuoted;
		$this->lines[] = $firstLine;
	}

/**
 * Builds the string content by joining the lines and reversing them.
 *
 */
	public function finish() {
		$this->content = implode("\n", $this->lines);
		$this->content = Fragment::reverse($this->content);
	}

/**
 * Get the last line of this Fragment.
 *
 */
	public function getLastLine() {
		$count = count($this->lines);
		return $this->lines[$count - 1];
	}

/**
 * Utility method to reverse a text string.
 *
 * @todo Think of a better way to identify what is the encoding and how to reverse it.
 * @param string $text
 */
	public static function reverse($text) {
		preg_match_all('/./us', $text, $matches);
		$reversed = join('', array_reverse($matches[0]));
		
		if (empty($reversed)) {
			$reversed = strrev($text);
		}

		return $reversed;
	}
}
