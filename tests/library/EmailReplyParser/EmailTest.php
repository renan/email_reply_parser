<?php
use EmailReplyParser\Email;

class EmailTest extends \PHPUnit_Framework_TestCase {
	public $fixturesPath;

	public function setUp() {
		$this->fixturesPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
	}

	public function testReadSimpleBody() {
		$fragments = $this->_getEmailFragments('1_1');
		$this->assertEquals(3, count($fragments));

		$this->_assertBooleans($fragments, 'quoted', array(false, false, false));
		$this->_assertBooleans($fragments, 'hidden', array(false, true, true));
		$this->_assertBooleans($fragments, 'signature', array(false, true, true));

		$expected = <<<EOF
Hi folks

What is the best way to clear a Riak bucket of all key, values after 
running a test?
I am currently using the Java HTTP API.
EOF;
		$this->assertEquals($expected, $fragments[0]->content);

		$expected = <<<EOF
-Abhishek Kona

EOF;
		$this->assertEquals($expected, $fragments[1]->content);
	}

	public function testReadTopPost() {
		$fragments = $this->_getEmailFragments('1_3');
		$this->assertEquals(5, count($fragments));

		$this->_assertBooleans($fragments, 'quoted', array(false, false, true, false, false));
		$this->_assertBooleans($fragments, 'hidden', array(false, true, true, true, true));
		$this->_assertBooleans($fragments, 'signature', array(false, true, false, false, true));

		$this->assertRegExp('/^Oh thanks.\n\nHaving/', $fragments[0]->content);
		$this->assertRegExp('/^-A/', $fragments[1]->content);
		$this->assertRegExp('/^\nOn [^\:]+\:/', $fragments[2]->content);
		$this->assertRegExp('/^_/', $fragments[4]->content);
	}

	public function testReadBottomPost() {
		$fragments = $this->_getEmailFragments('1_2');
		$this->assertEquals(6, count($fragments));

		$this->_assertBooleans($fragments, 'quoted', array(false, true, false, true, false, false));
		$this->_assertBooleans($fragments, 'hidden', array(false, false, false, true, true, true));
		$this->_assertBooleans($fragments, 'signature', array(false, false, false, false, false, true));

		$this->assertEquals('Hi,', $fragments[0]->content);
		$this->assertRegExp('/^On [^\:]+\:/', $fragments[1]->content);
		$this->assertRegExp('/^\nYou can list/', $fragments[2]->content);
		$this->assertRegExp('/^\n>/', $fragments[3]->content);
		$this->assertRegExp('/^_/', $fragments[5]->content);
	}

	public function testRecognizesDateStringAboveQuote() {
		$fragments = $this->_getEmailFragments('1_4');
		$this->assertEquals(2, count($fragments));

		$this->assertRegExp('/^Awesome/', $fragments[0]->content);
		$this->assertRegExp('/^\nOn/', $fragments[1]->content);
		$this->assertRegExp('/Loader/', $fragments[1]->content);
	}

	public function testComplexBodyWithOnlyOneFragment() {
		$fragments = $this->_getEmailFragments('1_5');
		$this->assertEquals(1, count($fragments));
	}

	public function testDealsWithMultilineReplyHeaders() {
		$fragments = $this->_getEmailFragments('1_6');
		$this->assertEquals(2, count($fragments));

		$this->assertRegExp('/^I get/', $fragments[0]->content);
		$this->assertRegExp('/^\nOn/', $fragments[1]->content);
		$this->assertRegExp('/Was this/', $fragments[1]->content);
	}

	public function testRecognizeOnlyOneSignature() {
		$fragments = $this->_getEmailFragments('2_1');
		$this->assertEquals(2, count($fragments));

		$this->_assertBooleans($fragments, 'quoted', array(false, false));
		$this->_assertBooleans($fragments, 'hidden', array(false, true));
		$this->_assertBooleans($fragments, 'signature', array(false, true));
	}

	public function testParseBigReplies() {
		$text = str_repeat(
			'This is a very big reply (5 MB), each line containing 64 bytes.' . PHP_EOL,
			(1024 / 64) * 1024 * 5
		);
		$fragments = Email::read($text);

		$this->assertEquals(1, count($fragments));
		$this->assertEquals($text, $fragments[0]->content);
	}

	public function testParseSpecificEncoding() {
		$text = file_get_contents($this->fixturesPath . 'fragment_iso8859.txt');
		$fragments = Email::read($text, 'ISO-8859-1');

		$this->assertEquals(1, count($fragments));
		$this->assertEquals($fragments[0]->content, $text);
		$this->assertEquals($fragments[0]->encoding, 'ISO-8859-1');
	}

	protected function _getEmailFragments($fixture) {
		$text = file_get_contents($this->fixturesPath . 'email_' . $fixture . '.txt');
		return Email::read($text);
	}

	protected function _assertBooleans($fragments, $boolean, $sequence) {
		foreach ($sequence as $i => $expected) {
			$message = sprintf('Item: $fragments[%d]->%s', $i, $boolean);
			$this->assertEquals($expected, $fragments[$i]->{$boolean}, $message);
		}
	}
}
