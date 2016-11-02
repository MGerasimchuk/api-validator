<?php
namespace ElevenLabs\Api;

use ElevenLabs\Api\Definition\Info;
use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\RequestDefinitions;
use ElevenLabs\Api\Definition\SecurityDefinitions;
use ElevenLabs\Api\Definition\SecurityScheme;
use Rize\UriTemplate;

class Schema implements \Serializable
{
    /** @var Info */
    private $info;

    /** @var RequestDefinitions */
    private $requestDefinitions = [];

    /** @var string */
    private $host;

    /** @var string */
    private $basePath;

    /** @var array */
    private $schemes;

    /** @var SecurityDefinitions */
    private $securityDefinitions;

    /**
     * Schema constructor.
     * @param Info $info
     * @param RequestDefinitions $requestDefinitions
     * @param string $basePath
     * @param null $host
     * @param array $schemes
     * @param SecurityDefinitions $securityDefinitions
     */
    public function __construct(
        Info $info,
        RequestDefinitions $requestDefinitions,
        $basePath,
        $host,
        array $schemes = ['http'],
        SecurityDefinitions $securityDefinitions
    ) {
        foreach ($requestDefinitions as $request) {
            $this->addRequestDefinition($request);
        }
        $this->info = $info;
        $this->host = $host ?: '';
        $this->basePath = $basePath ?: '';
        $this->schemes = $schemes;
        $this->securityDefinitions = $securityDefinitions;
    }

    /**
     * @return SecurityDefinitions
     */
    public function getSecurityDefinitions()
    {
        return $this->securityDefinitions;
    }

    /**
     * Find the operationId associated to a given path and method
     *
     * @todo Implement a less expensive finder
     * @param string $method An HTTP method
     * @param string $path A path (ex: /foo/1)
     *
     * @return string The operationId
     */
    public function findOperationId($method, $path)
    {
        $uriTemplateManager = new UriTemplate();
        foreach ($this->requestDefinitions as $requestDefinition) {

            if ($requestDefinition->getMethod() !== $method) {
                continue;
            }
            $params = $uriTemplateManager->extract($requestDefinition->getPathTemplate(), $path, true);
            if ($params !== null) {
                return $requestDefinition->getOperationId();
            }
        }

        throw new \InvalidArgumentException('Unable to resolve the operationId for path ' . $path);
    }

    /**
     * @return Info
     */
    public function getInfo()
    {
        return $this->info;
    }

    public function getRequestDefinition($operationId)
    {
        if (!isset($this->requestDefinitions[$operationId])) {
            throw new \InvalidArgumentException('Unable to get the request definition for '.$operationId);
        }

        return $this->requestDefinitions[$operationId];
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @return array
     */
    public function getSchemes()
    {
        return $this->schemes;
    }

    /**
     * @return RequestDefinitions
     */
    public function getRequestDefinitions()
    {
        return $this->requestDefinitions;
    }

    public function serialize()
    {
        return serialize([
            'host' => $this->host,
            'basePath' => $this->basePath,
            'schemes' => $this->schemes,
            'requests' => $this->requestDefinitions
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->host = $data['host'];
        $this->basePath = $data['basePath'];
        $this->schemes = $data['schemes'];
        $this->requestDefinitions = $data['requests'];
    }

    private function addRequestDefinition(RequestDefinition $request)
    {
        $this->requestDefinitions[$request->getOperationId()] = $request;
    }
}