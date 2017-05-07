<?php
/**
 * Created by PhpStorm.
 * User: rkerkhof
 * Date: 6-5-17
 * Time: 16:46
 */

namespace WildPHP\Core\Connection\IRCMessages;
use WildPHP\Core\Connection\IncomingIrcMessage;

/**
 * Class RPL_TOPIC
 * @package WildPHP\Core\Connection\IRCMessages
 *
 * Syntax: :server 332 nickname #channel :topic
 */
class RPL_TOPIC implements BaseMessage
{
	use NicknameTrait;
	use ChannelTrait;
	use MessageTrait;

	protected static $verb = '332';

	/**
	 * @param IncomingIrcMessage $incomingIrcMessage
	 *
	 * @return RPL_TOPIC
	 * @throws \ErrorException
	 */
	public static function fromIncomingIrcMessage(IncomingIrcMessage $incomingIrcMessage): self
	{
		if ($incomingIrcMessage->getVerb() != self::$verb)
			throw new \InvalidArgumentException('Expected incoming ' . self::$verb . '; got ' . $incomingIrcMessage->getVerb());

		$args = $incomingIrcMessage->getArgs();
		$nickname = array_shift($args);
		$channel = array_shift($args);
		$message = array_shift($args);

		$object = new self();
		$object->setNickname($nickname);
		$object->setChannel($channel);
		$object->setMessage($message);

		return $object;
	}
}