<?php

namespace ElevenLabs\Api\Definition;

class Security
{
    /** @var string */
    private $key;

    /** @var array */
    private $scopes;

    /**
     * @var SecurityScheme
     */
    private $scheme;

    /**
     * Security constructor.
     * @param $key
     * @param array $scopes
     * @param SecurityScheme $scheme
     */
    public function __construct($key, array $scopes, SecurityScheme $scheme)
    {
        $this->key = $key;
        $this->scopes = $scopes ?: [];
        $this->scheme = $scheme;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @return SecurityScheme
     */
    public function getScheme(): SecurityScheme
    {
        return $this->scheme;
    }
}