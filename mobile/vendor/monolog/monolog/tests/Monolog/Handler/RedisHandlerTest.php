<?php
//dezend by  QQ:2172298892
namespace Monolog\Handler;

class RedisHandlerTest extends \Monolog\TestCase
{
	public function testConstructorShouldThrowExceptionForInvalidRedis()
	{
		new RedisHandler(new \stdClass(), 'key');
	}

	public function testConstructorShouldWorkWithPredis()
	{
		$redis = $this->getMock('Predis\\Client');
		$this->assertInstanceof('Monolog\\Handler\\RedisHandler', new RedisHandler($redis, 'key'));
	}

	public function testConstructorShouldWorkWithRedis()
	{
		$redis = $this->getMock('Redis');
		$this->assertInstanceof('Monolog\\Handler\\RedisHandler', new RedisHandler($redis, 'key'));
	}

	public function testPredisHandle()
	{
		$redis = $this->getMock('Predis\\Client', array('rpush'));
		$redis->expects($this->once())->method('rpush')->with('key', 'test');
		$record = $this->getRecord(\Monolog\Logger::WARNING, 'test', array('data' => new \stdClass(), 'foo' => 34));
		$handler = new RedisHandler($redis, 'key');
		$handler->setFormatter(new \Monolog\Formatter\LineFormatter('%message%'));
		$handler->handle($record);
	}

	public function testRedisHandle()
	{
		$redis = $this->getMock('Redis', array('rpush'));
		$redis->expects($this->once())->method('rPush')->with('key', 'test');
		$record = $this->getRecord(\Monolog\Logger::WARNING, 'test', array('data' => new \stdClass(), 'foo' => 34));
		$handler = new RedisHandler($redis, 'key');
		$handler->setFormatter(new \Monolog\Formatter\LineFormatter('%message%'));
		$handler->handle($record);
	}

	public function testRedisHandleCapped()
	{
		$redis = $this->getMock('Redis', array('multi', 'rpush', 'ltrim', 'exec'));
		$redis->expects($this->once())->method('multi')->will($this->returnSelf());
		$redis->expects($this->once())->method('rpush')->will($this->returnSelf());
		$redis->expects($this->once())->method('ltrim')->will($this->returnSelf());
		$redis->expects($this->once())->method('exec')->will($this->returnSelf());
		$record = $this->getRecord(\Monolog\Logger::WARNING, 'test', array('data' => new \stdClass(), 'foo' => 34));
		$handler = new RedisHandler($redis, 'key', \Monolog\Logger::DEBUG, true, 10);
		$handler->setFormatter(new \Monolog\Formatter\LineFormatter('%message%'));
		$handler->handle($record);
	}

	public function testPredisHandleCapped()
	{
		$redis = $this->getMock('Predis\\Client', array('transaction'));
		$redisTransaction = $this->getMock('Predis\\Client', array('rpush', 'ltrim'));
		$redisTransaction->expects($this->once())->method('rpush')->will($this->returnSelf());
		$redisTransaction->expects($this->once())->method('ltrim')->will($this->returnSelf());
		$redis->expects($this->once())->method('transaction')->will($this->returnCallback(function($cb) use($redisTransaction) {
			$cb($redisTransaction);
		}));
		$record = $this->getRecord(\Monolog\Logger::WARNING, 'test', array('data' => new \stdClass(), 'foo' => 34));
		$handler = new RedisHandler($redis, 'key', \Monolog\Logger::DEBUG, true, 10);
		$handler->setFormatter(new \Monolog\Formatter\LineFormatter('%message%'));
		$handler->handle($record);
	}
}

?>
