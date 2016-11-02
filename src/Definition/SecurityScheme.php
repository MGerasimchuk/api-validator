<?php

namespace ElevenLabs\Api\Definition;

class SecurityScheme
{
    /** @var string */
    private $key;
    /** @var string */
    private $type;
    /** @var string|null */
    private $description;
    /** @var string|null */
    private $name;
    /** @var string|null */
    private $location;
    /** @var string|null */
    private $flow;
    /** @var string|null */
    private $authorizationUrl;
    /** @var string|null */
    private $tokenUrl;
    /** @var array|null */
    private $scopes;

    /**
     * SecurityScheme constructor.
     * @param string $key
     * @param string $type
     * @param null|string $description
     * @param null|string $name
     * @param null|string $location
     * @param null|string $flow
     * @param null|string $authorizationUrl
     * @param null|string $tokenUrl
     * @param array|null $scopes
     */
    public function __construct($key, $type, $description, $name, $location, $flow, $authorizationUrl, $tokenUrl, array $scopes)
    {
        $this->key = $key;
        $this->type = $type;
        $this->description = $description ?: '';
        if ($type === 'apiKey') {
            if ($name === null) {
                throw new \InvalidArgumentException('must provide a name for security scheme of type apiKey');
            }
            if ($location === null) {
                throw new \InvalidArgumentException('must provide a location for security scheme of type apiKey');
            }
            $this->name = $name;
            $this->location = $location;
        }

        if ($type === 'oauth2') {
            if ($flow === null) {
                throw new \InvalidArgumentException('must provide a flow for security scheme of type oauth2');
            }
            if ($authorizationUrl === null && in_array($flow, ['implicit', 'accessCode'])) {
                throw new \InvalidArgumentException('must provide an authorizationUrl for security scheme of type oauth2');
            }
            if ($tokenUrl === null && in_array($flow, ['password', 'application', 'accessCode'])) {
                throw new \InvalidArgumentException('must provide an tokenUrl for security scheme of type oauth2');
            }
            $this->flow = $flow;
            $this->authorizationUrl = $authorizationUrl;
            $this->tokenUrl = $tokenUrl;
            $this->scopes = $scopes;
        }
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return null|string
     */
    public function getFlow()
    {
        return $this->flow;
    }

    /**
     * @return null|string
     */
    public function getAuthorizationUrl()
    {
        return $this->authorizationUrl;
    }

    /**
     * @return null|string
     */
    public function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * @return array|null
     */
    public function getScopes()
    {
        return $this->scopes;
    }
}