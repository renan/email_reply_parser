<?php
namespace EmailReplyParser;

/**
 * EmailReplyParser is a small library to parse plain text email content.  The
 * goal is to identify which fragments are quoted, part of a signature, or
 * original body content.  We want to support both top and bottom posters, so
 * no simple "REPLY ABOVE HERE" content is used.
 *
 * Beyond RFC 5322 there aren't any real standards for how emails are created.
 * This attempts to parse out common conventions for things like replies:
 *
 *     this is some text
 *
 *     On <date>, <author> wrote:
 *     > blah blah
 *     > blah blah
 *
 * ... and signatures:
 *
 *     this is some text
 *
 *     -- 
 *     Bob
 *     http://homepage.com/~bob
 *
 * Each of these are parsed into Fragment objects.
 *
 * EmailReplyParser also attempts to figure out which of these blocks should
 * be hidden from users.
 */
class Email {

/**
 * This determines if any 'visible' Fragment has been found.
 * Once any visible Fragment is found, stop looking for hidden ones.
 *
 * @param boolean
 */
	protected static $_foundVisible = false;

/**
 * List of Fragments this Email have.
 *
 * @param array
 */
	protected static $_fragments = array();

/**
 * This instance variable points to the current Fragment. If the matched
 * line fits, it should be added to this Fragment. Otherwise, finish it
 * and start a new Fragment.
 *
 * @param Fragment
 */
	protected static $_fragment = null;

/**
 * Splits the given text into a list of Fragments.  This is roughly done by
 * reversing the text and parsing from the bottom to the top.  This way we
 * can check for 'On <date>, <author> wrote:' lines above quoted blocks.
 *
 * @param string $text A email body.
 * @return A list of parsed Fragments.
 */
	public static function read($text) {
		// Resets everything before starting
		self::$_foundVisible = false;
		self::$_fragments = array();
		self::$_fragment = null;

		// Check for multi-line reply headers. Some clients break up
		// the "On DATE, NAME <EMAIL> wrote:" line into multiple lines.
		$text = preg_replace_callback('/^(On.*wrote:)$/msU', function ($matches) {
			return str_replace("\n", ' ', $matches[1]);
		}, $text);

		// The text is reversed initially due to the way we check for hidden fragments.
		$text = Fragment::reverse($text);

		// Split into lines.
		$lines = preg_split("/\n/", $text);

		// Scan each line of the email content.
		foreach ($lines as $line) {
			self::_scanLine($line);
		}

		// Finish up the final fragment. Finishing a fragment will detect any
		// attributes (hidden, signature, reply), and join each line into a string.
		self::_finishFragment();

		// Now that parsing is done, reverse the order.
		self::$_fragments = array_reverse(self::$_fragments);
		return self::$_fragments;
	}

/**
 * Scans the given line of text and figures out which fragment it belongs to.
 *
 * @param string $line A line of text from the email
 */
	protected static function _scanLine($line) {
		$line = ltrim($line);

		// We're looking for leading `>`'s to see if this line is part of a quoted Fragment.
		$isQuoted = !!preg_match('/(>+)$/', $line);

		// Mark the current Fragment as a signature if the current line is empty
		// and the Fragment starts with a common signature indicator.
		if (self::$_fragment && $line === '' && preg_match('/(--|__|\w-$)/', self::$_fragment->getLastLine())) {
			self::$_fragment->signature = true;
			self::_finishFragment();
			return;
		}

		// If the line matches the current fragment, add it. Note that a common
		// reply header also counts as part of the quoted Fragment, even though
		// it doesn't start with `>`.
		if (self::$_fragment &&
			(self::$_fragment->quoted === $isQuoted ||
			(self::$_fragment->quoted && (self::_isQuotedHeader($line) || $line === '')))) {
			self::$_fragment->lines[] = $line;
		} else {
			// Otherwise, finish the fragment and start a new one.
			self::_finishFragment();
			self::$_fragment = new Fragment($isQuoted, $line);
		}
	}

/**
 * Detects if a given line is a header above a quoted area.  It is only
 * checked for lines preceding quoted regions.
 *
 * Pretty much checks for "On DATE, NAME <EMAIL> wrote:"
 * 
 * @param string $line A line of text from the email.
 */
	protected static function _isQuotedHeader($line) {
		return !!preg_match('/^:etorw.*nO$/', $line);
	}

/**
 * Builds the fragment string and reverses it, after all lines have been
 * added.  It also checks to see if this Fragment is hidden.  The hidden
 * Fragment check reads from the bottom to the top.
 *
 * Any quoted Fragments or signature Fragments are marked hidden if they
 * are below any visible Fragments.  Visible Fragments are expected to
 * contain original content by the author.  If they are below a quoted
 * Fragment, then the Fragment should be visible to give context to the
 * reply.
 *
 * Example:
 *   some original text (visible)
 *
 *   > do you have any two's? (quoted, visible)
 *
 *   Go fish! (visible)
 *
 *   > -- 
 *   > Player 1 (quoted, hidden)
 *
 *   -- 
 *   Player 2 (signature, hidden)
 */
	protected static function _finishFragment() {
		if (!self::$_fragment) {
			return;
		}

		self::$_fragment->finish();
		if (!self::$_foundVisible) {
			if (self::$_fragment->quoted || self::$_fragment->signature || trim(self::$_fragment->content) === '') {
				self::$_fragment->hidden = true;
			} else {
				self::$_foundVisible = true;
			}
		}

		self::$_fragments[] = self::$_fragment;
		self::$_fragment = null;
	}
}
