<?php
/**
 * Created by PhpStorm.
 * User: rkerkhof
 * Date: 6-5-17
 * Time: 20:17
 */

use PHPUnit\Framework\TestCase;
use WildPHP\Core\ComponentContainer;
use WildPHP\Core\Connection\IncomingIrcMessage;
use WildPHP\Core\Connection\IRCMessages\AUTHENTICATE;
use WildPHP\Core\Connection\IRCMessages\AWAY;
use WildPHP\Core\Connection\IRCMessages\ERROR;
use WildPHP\Core\Connection\IRCMessages\JOIN;
use WildPHP\Core\Connection\IRCMessages\KICK;
use WildPHP\Core\Connection\IRCMessages\MODE;
use WildPHP\Core\Connection\IRCMessages\NICK;
use WildPHP\Core\Connection\IRCMessages\NOTICE;
use WildPHP\Core\Connection\IRCMessages\PART;
use WildPHP\Core\Connection\IRCMessages\PING;
use WildPHP\Core\Connection\IRCMessages\PONG;
use WildPHP\Core\Connection\IRCMessages\PRIVMSG;
use WildPHP\Core\Connection\IRCMessages\QUIT;
use WildPHP\Core\Connection\Parser;

class IrcMessageTest extends TestCase
{
	public function testAuthenticateCreate()
	{
		$authenticate = new AUTHENTICATE('+');

		$this->assertEquals('+', $authenticate->getResponse());

		$expected = 'AUTHENTICATE +' . "\r\n";
		$this->assertEquals($expected, $authenticate->__toString());
	}

	public function testAuthenticateReceive()
	{
		$line = Parser::parseLine('AUTHENTICATE +' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$authenticate = AUTHENTICATE::fromIncomingIrcMessage($incoming);

		$this->assertEquals('+', $authenticate->getResponse());
	}

	public function testAwayCreate()
	{
		$away = new AWAY('A sample message');

		$this->assertEquals('A sample message', $away->getMessage());

		$expected = 'AWAY :A sample message' . "\r\n";
		$this->assertEquals($expected, $away->__toString());
	}

	public function testAwayReceive()
	{
		$line = Parser::parseLine(':nickname!host AWAY :A sample message' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$away = AWAY::fromIncomingIrcMessage($incoming);

		$this->assertEquals('nickname', $away->getNickname());
		$this->assertEquals('A sample message', $away->getMessage());
	}

	public function testErrorCreate()
	{
		$error = new ERROR('Test error message');

		$this->assertEquals('Test error message', $error->getMessage());

		$expected = 'ERROR :Test error message' . "\r\n";
		$this->assertEquals($expected, $error->__toString());
	}

	public function testErrorReceive()
	{
		$line = Parser::parseLine('ERROR :A sample message' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$error = ERROR::fromIncomingIrcMessage($incoming);

		$this->assertEquals('A sample message', $error->getMessage());
	}

	public function testJoinCreate()
	{
		$join = new JOIN(['#channel1', '#channel2'], ['key1', 'key2']);

		$this->assertEquals(['#channel1', '#channel2'], $join->getChannels());
		$this->assertEquals(['key1', 'key2'], $join->getKeys());

		$expected = 'JOIN #channel1,#channel2 key1,key2' . "\r\n";
		$this->assertEquals($expected, $join->__toString());
	}

	public function testJoinCreateKeyMismatch()
	{
		$this->expectException(InvalidArgumentException::class);

		new JOIN(['#channel1', '#channel2'], ['key1']);
	}

	public function testJoinReceiveExtended()
	{
		$line = Parser::parseLine(':nickname!host JOIN #channel ircAccountName :realname' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$join = JOIN::fromIncomingIrcMessage($incoming);

		$this->assertEquals('nickname', $join->getNickname());
		$this->assertEquals(['#channel'], $join->getChannels());
		$this->assertEquals('ircAccountName', $join->getIrcAccount());
		$this->assertEquals('realname', $join->getRealname());
	}

	public function testJoinReceiveRegular()
	{
		$line = Parser::parseLine(':nickname!host JOIN #channel' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$join = JOIN::fromIncomingIrcMessage($incoming);

		$this->assertEquals('nickname', $join->getNickname());
		$this->assertEquals(['#channel'], $join->getChannels());
		$this->assertEquals('', $join->getIrcAccount());
		$this->assertEquals('', $join->getRealname());
	}

	public function testKickCreate()
	{
		$kick = new KICK('#channel', 'nickname', 'Bleep you!');

		$this->assertEquals('#channel', $kick->getChannel());
		$this->assertEquals('nickname', $kick->getTarget());
		$this->assertEquals('Bleep you!', $kick->getMessage());

		$expected = 'KICK #channel nickname :Bleep you!' . "\r\n";
		$this->assertEquals($expected, $kick->__toString());
	}

	public function testKickReceive()
	{
		$line = Parser::parseLine(':nickname!host KICK #somechannel othernickname :You deserved it!');
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$kick = KICK::fromIncomingIrcMessage($incoming);

		$this->assertEquals('nickname', $kick->getNickname());
		$this->assertEquals('othernickname', $kick->getTarget());
		$this->assertEquals('#somechannel', $kick->getChannel());
		$this->assertEquals('You deserved it!', $kick->getMessage());
	}

	public function testModeCreate()
	{
		$mode = new MODE('target', '-o+b', ['arg1', 'arg2']);

		$this->assertEquals('target', $mode->getTarget());
		$this->assertEquals('-o+b', $mode->getFlags());
		$this->assertEquals(['arg1', 'arg2'], $mode->getArguments());

		$expected = 'MODE target -o+b arg1 arg2' . "\r\n";
		$this->assertEquals($expected, $mode->__toString());
	}

	public function testModeReceiveChannel()
	{
		$line = Parser::parseLine(':nickname!host MODE #channel -o+b arg1 arg2' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$mode = MODE::fromIncomingIrcMessage($incoming);

		$this->assertEquals('#channel', $mode->getTarget());
		$this->assertEquals('nickname', $mode->getNickname());
		$this->assertEquals('-o+b', $mode->getFlags());
		$this->assertEquals(['arg1', 'arg2'], $mode->getArguments());
	}

	public function testModeReceiveUser()
	{
		$line = Parser::parseLine(':nickname!host MODE user -o+b' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$mode = MODE::fromIncomingIrcMessage($incoming);

		$this->assertEquals('user', $mode->getTarget());
		$this->assertEquals('nickname', $mode->getNickname());
		$this->assertEquals('-o+b', $mode->getFlags());
		$this->assertEquals([], $mode->getArguments());
	}

	public function testModeReceiveInitial()
	{
		$line = Parser::parseLine(':nickname MODE nickname -o+b' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$mode = MODE::fromIncomingIrcMessage($incoming);

		$this->assertEquals('nickname', $mode->getTarget());
		$this->assertEquals('nickname', $mode->getNickname());
		$this->assertEquals('-o+b', $mode->getFlags());
		$this->assertEquals([], $mode->getArguments());
	}

	public function testNickCreate()
	{
		$nick = new NICK('newnickname');

		$this->assertEquals('newnickname', $nick->getNewNickname());

		$expected = 'NICK newnickname' . "\r\n";
		$this->assertEquals($expected, $nick->__toString());
	}

	public function testNickReceive()
	{
		$line = Parser::parseLine(':nickname!host NICK newnickname' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$nick = NICK::fromIncomingIrcMessage($incoming);

		$this->assertEquals('nickname', $nick->getNickname());
		$this->assertEquals('newnickname', $nick->getNewNickname());
	}

	public function testNoticeCreate()
	{
		$notice = new NOTICE('#somechannel', 'This is a test message');

		$this->assertEquals('#somechannel', $notice->getChannel());
		$this->assertEquals('This is a test message', $notice->getMessage());

		$expected = 'NOTICE #somechannel :This is a test message' . "\r\n";
		$this->assertEquals($expected, $notice->__toString());
	}

	public function testNoticeReceive()
	{
		$line = Parser::parseLine(':nickname!host NOTICE #somechannel :This is a test message' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$notice = NOTICE::fromIncomingIrcMessage($incoming);

		$this->assertEquals('#somechannel', $notice->getChannel());
		$this->assertEquals('This is a test message', $notice->getMessage());
	}

	public function testPartCreate()
	{
		$part = new PART(['#channel1', '#channel2'], 'I am out');

		$this->assertEquals(['#channel1', '#channel2'], $part->getChannels());
		$this->assertEquals('I am out', $part->getMessage());

		$expected = 'PART #channel1,#channel2 :I am out' . "\r\n";
		$this->assertEquals($expected, $part->__toString());
	}

	public function testPartReceive()
	{
		$line = Parser::parseLine(':nickname!host PART #channel :I have a valid reason' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$part = PART::fromIncomingIrcMessage($incoming);

		$this->assertEquals('nickname', $part->getNickname());
		$this->assertEquals(['#channel'], $part->getChannels());
		$this->assertEquals('I have a valid reason', $part->getMessage());
	}

	public function testPingCreate()
	{
		$ping = new PING('testserver1', 'testserver2');

		$this->assertEquals('testserver1', $ping->getServer1());
		$this->assertEquals('testserver2', $ping->getServer2());

		$expected = 'PING testserver1 testserver2' . "\r\n";
		$this->assertEquals($expected, $ping->__toString());
	}

	public function testPingReceive()
	{
		$line = Parser::parseLine('PING testserver1 testserver2' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$ping = PING::fromIncomingIrcMessage($incoming);

		$this->assertEquals('testserver1', $ping->getServer1());
		$this->assertEquals('testserver2', $ping->getServer2());
	}

	public function testPongCreate()
	{
		$pong = new PONG('testserver1', 'testserver2');

		$this->assertEquals('testserver1', $pong->getServer1());
		$this->assertEquals('testserver2', $pong->getServer2());

		$expected = 'PONG testserver1 testserver2' . "\r\n";
		$this->assertEquals($expected, $pong->__toString());
	}

	public function testPongReceive()
	{
		$line = Parser::parseLine('PONG testserver1 testserver2' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$pong = PONG::fromIncomingIrcMessage($incoming);

		$this->assertEquals('testserver1', $pong->getServer1());
		$this->assertEquals('testserver2', $pong->getServer2());
	}

	public function testPrivmsgCreate()
	{
		$privmsg = new PRIVMSG('#somechannel', 'This is a test message');

		$this->assertEquals('#somechannel', $privmsg->getChannel());
		$this->assertEquals('This is a test message', $privmsg->getMessage());

		$expected = 'PRIVMSG #somechannel :This is a test message' . "\r\n";
		$this->assertEquals($expected, $privmsg->__toString());
	}

	public function testPrivmsgReceive()
	{
		$line = Parser::parseLine(':nickname!host PRIVMSG #somechannel :This is a test message' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$privmsg = PRIVMSG::fromIncomingIrcMessage($incoming);

		$this->assertEquals('#somechannel', $privmsg->getChannel());
		$this->assertEquals('This is a test message', $privmsg->getMessage());
	}

	public function testQuitCreate()
	{
		$quit = new QUIT('A sample message');

		$this->assertEquals('A sample message', $quit->getMessage());

		$expected = 'QUIT :A sample message' . "\r\n";
		$this->assertEquals($expected, $quit->__toString());
	}

	public function testQuitReceive()
	{
		$line = Parser::parseLine(':nickname!host QUIT :A sample message' . "\r\n");
		$incoming = new IncomingIrcMessage($line, new ComponentContainer());
		$quit = QUIT::fromIncomingIrcMessage($incoming);

		$this->assertEquals('nickname', $quit->getNickname());
		$this->assertEquals('A sample message', $quit->getMessage());
	}
}