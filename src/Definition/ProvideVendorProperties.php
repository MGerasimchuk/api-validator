<?php

namespace ElevenLabs\Api\Definition;

interface ProvideVendorProperties
{
    public function setVendorProperties(array $vendorProperties);
    public function hasVendorProperty($name);
    public function getVendorProperty($name);
}