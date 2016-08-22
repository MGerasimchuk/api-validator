<?php
namespace ElevenLabs\Api\Definition;

/**
 * Allow one vendor to provide a specific definition to a Schema
 */
interface VendorDefinition extends \Serializable
{
    /**
     * Return the name of a vendor provider
     *
     * @return string
     */
    public function getVendorName();
}