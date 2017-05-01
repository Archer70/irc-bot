<?php

/*
	WildPHP - a modular and easily extendable IRC bot written in PHP
	Copyright (C) 2016 WildPHP

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace WildPHP\Core\Connection\IncomingIrcMessages;


use WildPHP\Core\Channels\Channel;
use WildPHP\Core\Channels\ChannelCollection;
use WildPHP\Core\Connection\IncomingIrcMessage;
use WildPHP\Core\Connection\UserPrefix;
use WildPHP\Core\Users\User;
use WildPHP\Core\Users\UserCollection;

class KICK implements BaseMessage
{
	/**
	 * @var UserPrefix
	 */
	protected $prefix = null;

	/**
	 * @var Channel
	 */
	protected $channel = null;

	/**
	 * @var User
	 */
	protected $user = null;

	/**
	 * @var string
	 */
	protected $message = '';

	/**
	 * @param IncomingIrcMessage $incomingIrcMessage
	 *
	 * @return KICK
	 * @throws \ErrorException
	 */
	public static function fromIncomingIrcMessage(IncomingIrcMessage $incomingIrcMessage)
	{
		if ($incomingIrcMessage->getVerb() != 'KICK')
			throw new \InvalidArgumentException('Expected incoming KICK; got ' . $incomingIrcMessage->getVerb());

		$container = $incomingIrcMessage->getContainer();

		$prefix = UserPrefix::fromIncomingIrcMessage($incomingIrcMessage);
		$channel = $incomingIrcMessage->getArgs()[0];
		$user = UserCollection::fromContainer($container)->findByNickname($prefix->getNickname());

		if (!$user)
			throw new \ErrorException('Could not find user in collection; state mismatch!');

		if (ChannelCollection::fromContainer($container)->containsChannelName($channel))
			$channel = ChannelCollection::fromContainer($container)->findByChannelName($channel);

		// It's most likely a private conversation.
		elseif (!ChannelCollection::fromContainer($container)->containsChannelName($user->getNickname()))
			$channel = ChannelCollection::fromContainer($container)->createFakeConversationChannel($user);

		else
			$channel = ChannelCollection::fromContainer($container)->findByChannelName($user->getNickname());

		$message = $incomingIrcMessage->getArgs()[2];

		$object = new self();

		$object->setPrefix($prefix);
		$object->setUser($user);
		$object->setChannel($channel);
		$object->setMessage($message);

		return $object;
	}

	/**
	 * @return UserPrefix
	 */
	public function getPrefix(): UserPrefix
	{
		return $this->prefix;
	}

	/**
	 * @param UserPrefix $prefix
	 */
	public function setPrefix(UserPrefix $prefix)
	{
		$this->prefix = $prefix;
	}

	/**
	 * @return Channel
	 */
	public function getChannel(): Channel
	{
		return $this->channel;
	}

	/**
	 * @param Channel $channel
	 */
	public function setChannel(Channel $channel)
	{
		$this->channel = $channel;
	}

	/**
	 * @return User
	 */
	public function getUser(): User
	{
		return $this->user;
	}

	/**
	 * @param User $user
	 */
	public function setUser(User $user)
	{
		$this->user = $user;
	}

	/**
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * @param string $message
	 */
	public function setMessage(string $message)
	{
		$this->message = $message;
	}
}