<?php
namespace ElevenLabs\Api\Definition;

trait VendorProperties
{
    /**
     * @var array
     */
    private $vendorProperties = [];

    public function setVendorProperties(array $vendorProperties)
    {
        $this->vendorProperties = $vendorProperties;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasVendorProperty($name)
    {
        return isset($this->vendorProperties[$name]);
    }

    /**
     * @param string $name
     * @param bool $asArray
     *
     * @return mixed
     */
    public function getVendorProperty($name, $asArray = false)
    {
        if (!$this->hasVendorProperty($name)) {
            throw new \RuntimeException(sprintf('The vendor property "%s" does not exist', $name));
        }
        if ($asArray) {
            return $this->toArray($this->vendorProperties[$name]);
        }

        return $this->vendorProperties[$name];
    }

    public function toArray($data)
    {
        return json_decode(
            json_encode($data),
            true
        );
    }
}