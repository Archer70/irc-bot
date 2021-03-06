<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Connection\IRCMessages;

use WildPHP\Core\Connection\IncomingIrcMessage;

/**
 * Class RPL_NAMREPLY
 * @package WildPHP\Core\Connection\IRCMessages
 *
 * Syntax: :server 353 nickname visibility channel :nicknames
 */
class RPL_NAMREPLY extends BaseIRCMessage implements ReceivableMessage
{
	use NicknameTrait;
	use ChannelTrait;
	use ServerTrait;

	protected static $verb = '353';

	protected $visibility = '';

	protected $nicknames = [];

	/**
	 * @param IncomingIrcMessage $incomingIrcMessage
	 *
	 * @return \self
	 * @throws \InvalidArgumentException
	 */
	public static function fromIncomingIrcMessage(IncomingIrcMessage $incomingIrcMessage): self
	{
		if ($incomingIrcMessage->getVerb() != self::getVerb())
			throw new \InvalidArgumentException('Expected incoming ' . self::getVerb() . '; got ' . $incomingIrcMessage->getVerb());

		$server = $incomingIrcMessage->getPrefix();
		$args = $incomingIrcMessage->getArgs();
		$nickname = array_shift($args);
		$visibility = array_shift($args);
		$channel = array_shift($args);
		$nicknames = explode(' ', array_shift($args));

		$object = new self();
		$object->setNickname($nickname);
		$object->setVisibility($visibility);
		$object->setChannel($channel);
		$object->setNicknames($nicknames);
		$object->setServer($server);

		return $object;
	}

	/**
	 * @return string
	 */
	public function getVisibility(): string
	{
		return $this->visibility;
	}

	/**
	 * @param string $visibility
	 */
	public function setVisibility(string $visibility)
	{
		$this->visibility = $visibility;
	}

	/**
	 * @return array
	 */
	public function getNicknames(): array
	{
		return $this->nicknames;
	}

	/**
	 * @param array $nicknames
	 */
	public function setNicknames(array $nicknames)
	{
		$this->nicknames = $nicknames;
	}
}