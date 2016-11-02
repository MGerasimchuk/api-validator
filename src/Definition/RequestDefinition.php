<?php
namespace ElevenLabs\Api\Definition;

class RequestDefinition implements \Serializable, MessageDefinition, ProvideVendorProperties
{
    use VendorProperties;

    /** @var string */
    private $method;

    /** @var string */
    private $operationId;

    /** @var string */
    private $summary;

    /** @var string */
    private $description;

    /** @var string */
    private $pathTemplate;

    /** @var Parameters */
    private $parameters;

    /** @var array */
    private $contentTypes;

    /** @var ResponseDefinition[] */
    private $responses;

    /** @var Security[] */
    private $supportedSecurities = [];

    /** @var array */
    private $tags;

    /**
     * RequestDefinition constructor.
     * @param $method
     * @param $operationId
     * @param $summary
     * @param $description
     * @param $pathTemplate
     * @param Parameters $parameters
     * @param array $contentTypes
     * @param ResponseDefinition[] $responses
     * @param Security[] $supportedSecurities
     * @param array $tags
     */
    public function __construct(
        $method,
        $operationId,
        $summary,
        $description,
        $pathTemplate,
        Parameters $parameters,
        array $contentTypes,
        array $responses,
        array $supportedSecurities,
        array $tags
    ) {
        $this->method = $method;
        $this->operationId = $operationId;
        $this->summary = $summary ?: $operationId;
        $this->description = $description ?: '';
        $this->pathTemplate = $pathTemplate;
        $this->parameters = $parameters;
        $this->contentTypes = $contentTypes;
        $this->tags = $tags;
        foreach ($responses as $response) {
            $this->addResponseDefinition($response);
        }
        foreach ($supportedSecurities as $security) {
            $this->addSecurity($security);
        }
    }

    private function addSecurity(Security $security)
    {
        $this->supportedSecurities[$security->getKey()] = $security;
    }

    public function supportSecurityType($type)
    {
        foreach ($this->supportedSecurities as $security) {
            if ($type === $security->getScheme()->getType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getOperationId()
    {
        return $this->operationId;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getPathTemplate()
    {
        return $this->pathTemplate;
    }

    /**
     * @return Parameters
     */
    public function getRequestParameters()
    {
        return $this->parameters;
    }

    /**
     * Supported content types
     *
     * @return array
     */
    public function getContentTypes()
    {
        return $this->contentTypes;
    }

    /**
     * @param int $statusCode
     *
     * @return bool
     */
    public function hasResponseDefinition($statusCode)
    {
        return isset($this->responses[$statusCode]);
    }

    /**
     * @param int $statusCode
     *
     * @return ResponseDefinition
     */
    public function getResponseDefinition($statusCode)
    {
        if (!$this->hasResponseDefinition($statusCode)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'No response definition for %s %s is available for status code %s',
                    $this->method,
                    $this->pathTemplate,
                    $statusCode
                )
            );
        }

        return $this->responses[$statusCode];
    }

    public function hasBodySchema()
    {
        return $this->parameters->hasBodySchema();
    }

    public function getBodySchema()
    {
        return $this->parameters->getBodySchema();
    }

    public function hasHeadersSchema()
    {
        return $this->parameters->hasHeadersSchema();
    }

    public function getHeadersSchema()
    {
        return $this->parameters->getHeadersSchema();
    }

    public function hasQueryParametersSchema()
    {
        return $this->parameters->hasQueryParametersSchema();
    }

    public function getQueryParametersSchema()
    {
        return $this->parameters->getQueryParametersSchema();
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    private function addResponseDefinition(ResponseDefinition $response)
    {
        $this->responses[$response->getStatusCode()] = $response;
    }

    public function serialize()
    {
        return serialize([
            'method' => $this->method,
            'operationId' => $this->operationId,
            'pathTemplate' => $this->pathTemplate,
            'parameters' => $this->parameters,
            'contentTypes' => $this->contentTypes,
            'responses' => $this->responses
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->method = $data['method'];
        $this->operationId = $data['operationId'];
        $this->pathTemplate = $data['pathTemplate'];
        $this->parameters = $data['parameters'];
        $this->contentTypes = $data['contentTypes'];
        $this->responses = $data['responses'];
    }
}
