<?php
use EmailReplyParser\Fragment;

class FragmentTest extends \PHPUnit_Framework_TestCase {
	public $fixturesPath;

	public function setUp() {
		$this->fixturesPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
	}

	public function testReverseDifferentEncodings() {
		// ISO-8859-1
		$reversed = file_get_contents($this->fixturesPath . 'fragment_iso8859_reversed.txt');
		$original = file_get_contents($this->fixturesPath . 'fragment_iso8859.txt');
		$original = Fragment::reverse($original, 'ISO-8859-1');

		$this->assertEquals($reversed, $original);

		// UTF-8
		$reversed = file_get_contents($this->fixturesPath . 'fragment_utf8_reversed.txt');
		$original = file_get_contents($this->fixturesPath . 'fragment_utf8.txt');
		$original = Fragment::reverse($original);

		$this->assertEquals($reversed, $original);
	}
}
