<?php

namespace ElevenLabs\Api\Definition;

class SecurityDefinitions
{
    /** @var array */
    private $securityDefinitions;

    /**
     * SecurityDefinitions constructor.
     * @param array $securityDefinitions
     */
    public function __construct(array $securityDefinitions)
    {
        foreach ($securityDefinitions as $securityScheme)
        {
            $this->addSecurityScheme($securityScheme);
        }
    }

    public function all()
    {
        return $this->securityDefinitions;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasSecurityScheme($key)
    {
        return isset($this->securityDefinitions[$key]);
    }

    /**
     * @param string $key
     *
     * @return SecurityScheme
     */
    public function getSecurityScheme($key)
    {
        if (!$this->hasSecurityScheme($key)) {
            throw new \InvalidArgumentException('No security scheme found for ' . $key);
        }

        return $this->securityDefinitions[$key];
    }

    /**
     * @param SecurityScheme $securityScheme
     */
    private function addSecurityScheme(SecurityScheme $securityScheme)
    {
        $this->securityDefinitions[$securityScheme->getKey()] = $securityScheme;
    }
}