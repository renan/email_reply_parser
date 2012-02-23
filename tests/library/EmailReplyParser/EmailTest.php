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
		#$this->assertRegExp('/^On [^\:]+\:/', $fragments[2]->content);
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
		#$this->assertRegExp('/^You can list/', $fragments[2]->content);
		#$this->assertRegExp('/^> /', $fragments[3]->content);
		$this->assertRegExp('/^_/', $fragments[5]->content);
	}

	public function testRecognizesDateStringAboveQuote() {
		$fragments = $this->_getEmailFragments('1_4');
		$this->assertEquals(2, count($fragments));

		$this->assertRegExp('/^Awesome/', $fragments[0]->content);
		#$this->assertRegExp('/^On/', $fragments[1]->content);
		$this->assertRegExp('/Loader/', $fragments[1]->content);
	}

	protected function _getEmailFragments($fixture) {
		$text = file_get_contents($this->fixturesPath . 'email_' . $fixture . '.txt');
		$Email = new Email();
		return $Email->read($text);
	}

	protected function _assertBooleans($fragments, $boolean, $sequence) {
		foreach ($sequence as $i => $expected) {
			$message = sprintf('Item: $fragments[%d]->%s', $i, $boolean);
			$this->assertEquals($expected, $fragments[$i]->{$boolean}, $message);
		}
	}
}
