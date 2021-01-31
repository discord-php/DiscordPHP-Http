<?php

/*
 * This file is a part of the DiscordPHP-Http project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is
 * bundled with this source code in the LICENSE.md file.
 */

namespace Discord\Http;

use InvalidArgumentException;

class Endpoint
{
    // GET, POST
    const GLOBAL_APPLICATION_COMMANDS = 'applications/:application_id/commands';
    // GET, PATCH, DELETE
    const GLOBAL_APPLICATION_COMMAND = 'application/:application_id/commands/:command_id';
    // GET, POST
    const GUILD_APPLICATION_COMMANDS = 'application/:application_id/guilds/:guild_id/commands';
    // GET, PATCH, DELETE
    const GUILD_APPLICATION_COMMAND = 'application/:application_id/guilds/:guild_id/commands/:command_id';
    // POST
    const INTERACTION_RESPONSE = 'interactions/:interaction_id/:interaction_token/callback';
    // PATCH, DELETE
    const ORIGINAL_INTERACTION_RESPONSE = 'webhooks/:application_id/:interaction_token/messages/@original';
    // POST
    const CREATE_INTERACTION_FOLLOW_UP = 'webhooks/:application_id/:interaction_token';
    // PATCH, DELETE
    const INTERACTION_FOLLOW_UP = 'webhooks/:application_id/:interaction_token/messages/:message_id';

    // GET
    const AUDIT_LOG = 'guilds/:guild_id/audit-logs';

    // GET, PATCH, DELETE
    const CHANNEL = 'channels/:channel_id';
    // GET, POST
    const CHANNEL_MESSAGES = self::CHANNEL.'/messages';
    // GET, PATCH, DELETE
    const CHANNEL_MESSAGE = self::CHANNEL.'/messages/:message_id';
    // POST
    const CHANNEL_CROSSPOST_MESSAGE = self::CHANNEL.'/messages/:message_id/crosspost';
    // POST
    const CHANNEL_MESSAGES_BULK_DELETE = self::CHANNEL.'/messages/bulk-delete';
    // PUT, DELETE
    const CHANNEL_PERMISSIONS = self::CHANNEL.'/permissions/:overwrite_id';
    // GET, POST
    const CHANNEL_INVITES = self::CHANNEL.'/invites';
    // POST
    const CHANNEL_FOLLOW = self::CHANNEL.'/followers';
    // POST
    const CHANNEL_TYPING = self::CHANNEL.'/typing';
    // GET
    const CHANNEL_PINS = self::CHANNEL.'/pins';
    // PUT, DELETE
    const CHANNEL_PIN = self::CHANNEL.'/pins/:message_id';

    // GET, DELETE
    const MESSAGE_REACTION_ALL = self::CHANNEL.'/messages/:message_id/reactions';
    // GET, DELETE
    const MESSAGE_REACTION_EMOJI = self::CHANNEL.'/messages/:message_id/reactions/:emoji';
    // PUT, DELETE
    const OWN_MESSAGE_REACTION = self::CHANNEL.'/messages/:message_id/reactions/:emoji/@me';
    // DELETE
    const USER_MESSAGE_REACTION = self::CHANNEL.'/messages/:message_id/reactions/:emoji/:user_id';

    // GET, PATCH, DELETE
    const GUILD = 'guilds/:guild_id';
    // GET, POST, PATCH
    const GUILD_CHANNELS = self::GUILD.'/channels';

    // GET
    const GUILD_MEMBERS = self::GUILD.'/members';
    // GET, PATCH, PUT, DELETE
    const GUILD_MEMBER = self::GUILD.'/members/:user_id';
    // PATCH
    const GUILD_MEMBER_SELF_NICK = self::GUILD.'/members/@me/nick';
    // PUT, DELETE
    const GUILD_MEMBER_ROLE = self::GUILD.'/members/roles/:role_id';

    // GET
    const GUILD_BANS = self::GUILD.'/bans';
    // GET, PUT, DELETE
    const GUILD_BAN = self::GUILD.'/bans/:user_id';

    // GET, PATCH
    const GUILD_ROLES = self::GUILD.'/roles';
    // GET, POST, PATCH, DELETE
    const GUILD_ROLE = self::GUILD.'/roles/:role_id';

    // GET, POST
    const GUILD_INVITES = self::GUILD.'/invites';

    // GET, POST
    const GUILD_INTEGRATIONS = self::GUILD.'/integrations';
    // PATCH, DELETE
    const GUILD_INTEGRATION = self::GUILD.'/integrations/:integration_id';
    // POST
    const GUILD_INTEGRATION_SYNC = self::GUILD.'/integrations/:integration_id/sync';

    // GET, POST
    const GUILD_EMOJIS = self::GUILD.'/emojis';
    // GET, PATCH, DELETE
    const GUILD_EMOJI = self::GUILD.'/emojis/:emoji_id';

    // GET
    const GUILD_PREVIEW = self::GUILD.'/preview';
    // GET, POST
    const GUILD_PRUNE = self::GUILD.'/prune';
    // GET
    const GUILD_REGIONS = self::GUILD.'/regions';
    // GET, PATCH
    const GUILD_WIDGET_SETTINGS = self::GUILD.'/widget';
    // GET
    const GUILD_WIDGET = self::GUILD.'/widget.json';
    // GET
    const GUILD_WIDGET_IMAGE = self::GUILD.'/widget.png';
    // GET
    const GUILD_VANITY_URL = self::GUILD.'/vanity-url';
    // GET, PATCH
    const GUILD_MEMBERSHIP_SCREENING = self::GUILD.'/member-verification';

    // GET, DELETE
    const INVITE = 'invites/:invite_code';

    // GET, PATCH
    const USER_CURRENT = 'users/@me';
    // GET
    const USER = 'users/:user_id';
    // GET
    const USER_CURRENT_GUILDS = self::USER_CURRENT.'/guilds';
    // DELETE
    const USER_CURRENT_GUILD = self::USER_CURRENT.'/guilds/:guild_id';
    // GET, POST
    const USER_CURRENT_CHANNELS = self::USER_CURRENT.'/channels';
    // GET
    const USER_CURRENT_CONNECTIONS = self::USER_CURRENT.'/connections';

    const REGEX = '/:([^\/]*)/';
    const MAJOR_PARAMETERS = ['channel_id', 'guild_id', 'webhook_id'];

    /**
     * The string version of the endpoint, including all parameters.
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Array of placeholders to be replaced in the endpoint.
     *
     * @var string[]
     */
    protected $vars = [];

    /**
     * Array of arguments to substitute into the endpoint.
     *
     * @var string[]
     */
    protected $args = [];

    /**
     * Creates an endpoint class.
     *
     * @param string $endpoint
     */
    public function __construct(string $endpoint)
    {
        $this->endpoint = $endpoint;
    
        if (preg_match_all(self::REGEX, $endpoint, $vars)) {
            $this->vars = $vars[1] ?? [];
        }
    }

    /**
     * Binds a list of arguments to the endpoint.
     *
     * @param  string[] ...$args
     * @return this
     */
    public function bindArgs(...$args): self
    {
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $this->bindArgs(...$arg);
            } else {
                $this->args[] = $arg;
            }
        }

        return $this;
    }
    
    /**
     * Converts the endpoint into the absolute endpoint with
     * placeholders replaced.
     *
     * Passing a true boolean in will only replace the major parameters.
     * Used for rate limit buckets.
     *
     * @param  bool   $onlyMajorParameters
     * @return string
     */
    public function toAbsoluteEndpoint(bool $onlyMajorParameters = false): string
    {
        $args = $this->args;
        $endpoint = $this->endpoint;

        if (count($this->vars) > count($this->args)) {
            throw new InvalidArgumentException('There are more variables than there arguments.');
        }

        foreach ($this->vars as $var) {
            if ($onlyMajorParameters && ! $this->isMajorParameter($var)) {
                continue;
            }
            $endpoint = str_replace(":{$var}", array_shift($args), $endpoint);
        }

        return $endpoint;
    }

    /**
     * Converts the endpoint to a string.
     * Alias of ->toAbsoluteEndpoint();.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toAbsoluteEndpoint();
    }

    /**
     * Creates an endpoint class and binds arguments to
     * the newly created instance.
     *
     * @param  string   $endpoint
     * @param  string[] ...$args
     * @return Endpoint
     */
    public static function bind(string $endpoint, ...$args)
    {
        $endpoint = new Endpoint($endpoint);
        $endpoint->bindArgs(...$args);
        
        return $endpoint;
    }

    /**
     * Checks if a parameter is a major parameter.
     *
     * @param  string $param
     * @return bool
     */
    private static function isMajorParameter(string $param): bool
    {
        return in_array($param, self::MAJOR_PARAMETERS);
    }
}
