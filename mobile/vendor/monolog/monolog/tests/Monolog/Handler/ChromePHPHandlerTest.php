<?php
//dezend by  QQ:2172298892
namespace Monolog\Handler;

class ChromePHPHandlerTest extends \Monolog\TestCase
{
	protected function setUp()
	{
		TestChromePHPHandler::reset();
		$_SERVER['HTTP_USER_AGENT'] = 'Monolog Test; Chrome/1.0';
	}

	public function testHeaders()
	{
		$handler = new TestChromePHPHandler();
		$handler->setFormatter($this->getIdentityFormatter());
		$handler->handle($this->getRecord(\Monolog\Logger::DEBUG));
		$handler->handle($this->getRecord(\Monolog\Logger::WARNING));
		$expected = array('X-ChromeLogger-Data' => base64_encode(utf8_encode(json_encode(array(
	'version'     => ChromePHPHandler::VERSION,
	'columns'     => array('label', 'log', 'backtrace', 'type'),
	'rows'        => array('test', 'test'),
	'request_uri' => ''
	)))));
		$this->assertEquals($expected, $handler->getHeaders());
	}

	public function testHeadersOverflow()
	{
		$handler = new TestChromePHPHandler();
		$handler->handle($this->getRecord(\Monolog\Logger::DEBUG));
		$handler->handle($this->getRecord(\Monolog\Logger::WARNING, str_repeat('a', 150 * 1024)));
		$handler->handle($this->getRecord(\Monolog\Logger::WARNING, str_repeat('a', 100 * 1024)));
		$expected = array('X-ChromeLogger-Data' => base64_encode(utf8_encode(json_encode(array(
	'version'     => ChromePHPHandler::VERSION,
	'columns'     => array('label', 'log', 'backtrace', 'type'),
	'rows'        => array(
		array('test', 'test', 'unknown', 'log'),
		array('test', str_repeat('a', 150 * 1024), 'unknown', 'warn'),
		array('monolog', 'Incomplete logs, chrome header size limit reached', 'unknown', 'warn')
		),
	'request_uri' => ''
	)))));
		$this->assertEquals($expected, $handler->getHeaders());
	}

	public function testConcurrentHandlers()
	{
		$handler = new TestChromePHPHandler();
		$handler->setFormatter($this->getIdentityFormatter());
		$handler->handle($this->getRecord(\Monolog\Logger::DEBUG));
		$handler->handle($this->getRecord(\Monolog\Logger::WARNING));
		$handler2 = new TestChromePHPHandler();
		$handler2->setFormatter($this->getIdentityFormatter());
		$handler2->handle($this->getRecord(\Monolog\Logger::DEBUG));
		$handler2->handle($this->getRecord(\Monolog\Logger::WARNING));
		$expected = array('X-ChromeLogger-Data' => base64_encode(utf8_encode(json_encode(array(
	'version'     => ChromePHPHandler::VERSION,
	'columns'     => array('label', 'log', 'backtrace', 'type'),
	'rows'        => array('test', 'test', 'test', 'test'),
	'request_uri' => ''
	)))));
		$this->assertEquals($expected, $handler2->getHeaders());
	}
}
class TestChromePHPHandler extends ChromePHPHandler
{
	protected $headers = array();

	static public function reset()
	{
		self::$initialized = false;
		self::$overflowed = false;
		self::$sendHeaders = true;
		self::$json['rows'] = array();
	}

	protected function sendHeader($header, $content)
	{
		$this->headers[$header] = $content;
	}

	public function getHeaders()
	{
		return $this->headers;
	}
}

?>
