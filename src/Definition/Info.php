<?php

namespace ElevenLabs\Api\Definition;

class Info
{
    /** @var string */
    private $title;
    /** @var string */
    private $version;
    /** @var string|null */
    private $description;

    /**
     * @param $title
     * @param $version
     * @param $description
     */
    public function __construct($title, $version, $description)
    {
        $this->title = $title;
        $this->version = $version;
        $this->description = $description ?: '';
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }
}