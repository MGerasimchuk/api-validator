<?php
namespace ElevenLabs\Api\Factory;

use ElevenLabs\Api\Definition\Info;
use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\Parameter;
use ElevenLabs\Api\Definition\Parameters;
use ElevenLabs\Api\Definition\RequestDefinitions;
use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Definition\Security;
use ElevenLabs\Api\Definition\SecurityDefinitions;
use ElevenLabs\Api\Definition\SecurityScheme;
use ElevenLabs\Api\Schema;
use ElevenLabs\Api\JsonSchema\Uri\YamlUriRetriever;
use JsonSchema\RefResolver;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use Symfony\Component\Yaml\Yaml;

/**
 * Create a schema definition from a Swagger file
 */
class SwaggerSchemaFactory implements SchemaFactory
{
    /**
     * @param string $schemaFile (must start with a scheme: file://, http://, https://, etc...)
     * @return Schema
     */
    public function createSchema($schemaFile)
    {
        $schema = $this->resolveSchemaFile($schemaFile);

        $host = (isset($schema->host)) ? $schema->host : null;
        $basePath = (isset($schema->basePath)) ? $schema->basePath : '';
        $schemes = (isset($schema->schemes)) ? $schema->schemes : ['http'];

        $info = new Info(
            $schema->info->title,
            $schema->info->version,
            $schema->info->description
        );

        $securityDefinitions = [];
        if ($schema->securityDefinitions !== null) {
            foreach ($schema->securityDefinitions as $key => $securityScheme) {
                $securityDefinitions[] = new SecurityScheme(
                    $key,
                    $securityScheme->type,
                    $securityScheme->description,
                    $securityScheme->name,
                    $securityScheme->in,
                    $securityScheme->flow,
                    $securityScheme->authorizationUrl,
                    $securityScheme->tokenUrl,
                    $securityScheme->scopes ?: []
                );
            }
        }
        $securityDefinitions = new SecurityDefinitions($securityDefinitions);

        return new Schema(
            $info,
            $this->createRequestDefinitions($schema, $securityDefinitions),
            $basePath,
            $host,
            $schemes,
            $securityDefinitions
        );
    }

    /**
     *
     * @param string $schemaFile
     *
     * @return object
     */
    protected function resolveSchemaFile($schemaFile)
    {
        $extension = pathinfo($schemaFile, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'yml':
            case 'yaml':
                if (!class_exists(Yaml::class)) {
                    throw new \InvalidArgumentException(
                        'You need to require the "symfony/yaml" component in order to parse yml files'
                    );
                }
                $uriRetriever = new YamlUriRetriever();
                break;
            case 'json';
                $uriRetriever = new UriRetriever();
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'file "%s" does not provide a supported extension choose either json, yml or yaml',
                        $schemaFile
                    )
                );
        }

        $refResolver = new RefResolver(
            $uriRetriever,
            new UriResolver()
        );

        return $refResolver->resolve($schemaFile);
    }

    /**
     * @param \stdClass $schema
     * @param SecurityDefinitions $securityDefinitions
     *
     * @return RequestDefinitions
     */
    protected function createRequestDefinitions(\stdClass $schema, SecurityDefinitions $securityDefinitions)
    {
        $definitions = [];
        $defaultConsumedContentTypes = [];
        $defaultProducedContentTypes = [];
        $defaultSecurities = [];

        if (isset($schema->consumes)) {
            $defaultConsumedContentTypes = $schema->consumes;
        }
        if (isset($schema->produces)) {
            $defaultProducedContentTypes = $schema->produces;
        }

        if ($schema->security !== null) {
            foreach ($schema->security as $security) {
                $key = key($security);
                $defaultSecurities[] = new Security(
                    $key,
                    $security->{$key},
                    $securityDefinitions->getSecurityScheme($key)
                );
            }
        }

        $basePath = (isset($schema->basePath)) ? $schema->basePath : '';

        foreach ($schema->paths as $pathTemplate => $methods) {

            foreach ($methods as $method => $definition) {
                $method = strtoupper($method);

                $contentTypes = $defaultConsumedContentTypes;
                if (isset($definition->consumes)) {
                    $contentTypes = $definition->consumes;
                }

                $supportedSecurities = [];
                if ($definition->security !== null) {
                    foreach ($definition->security as $security) {
                        $key = key($security);
                        $supportedSecurities[] = new Security(
                            $key,
                            $security->{$key},
                            $securityDefinitions->getSecurityScheme($key)
                        );
                    }
                } else {
                    $supportedSecurities = $defaultSecurities;
                }

                if (!isset($definition->operationId)) {
                    throw new \LogicException(
                        sprintf(
                            'You need to provide an operationId for %s %s',
                            $method,
                            $pathTemplate
                        )
                    );
                }

                if (empty($contentTypes)) {
                    throw new \LogicException(
                        sprintf(
                            'You need to specify at least one ContentType for %s %s',
                            $method,
                            $pathTemplate
                        )
                    );
                }

                if (!isset($definition->responses)) {
                    throw new \LogicException(
                        sprintf(
                            'You need to specify at least one response for %s %s',
                            $method,
                            $pathTemplate
                        )
                    );
                }

                if (!isset($definition->parameters)) {
                    $definition->parameters = [];
                }

                $requestParameters = [];
                foreach ($definition->parameters as $parameter) {
                    $requestParameters[] = $this->createParameter($parameter);
                }

                $responseDefinitions = [];
                foreach ($definition->responses as $statusCode => $response) {
                    $responseDefinitions[] = $this->createResponseDefinition(
                        $statusCode,
                        $defaultProducedContentTypes,
                        $response
                    );
                }

                $requestDefinition = new RequestDefinition(
                    $method,
                    $definition->operationId,
                    $definition->summary,
                    $definition->description,
                    $basePath.$pathTemplate,
                    new Parameters($requestParameters),
                    $contentTypes,
                    $responseDefinitions,
                    $supportedSecurities,
                    $definition->tags ?: []
                );
                $requestDefinition->setVendorProperties($this->getVendorProperties($definition));

                $definitions[] = $requestDefinition;
            }
        }

        return new RequestDefinitions($definitions);
    }

    protected function createResponseDefinition($statusCode, array $defaultProducedContentTypes, \stdClass $response)
    {
        $schema = null;
        $allowedContentTypes = $defaultProducedContentTypes;
        $parameters = [];
        $vendorProperties = [];

        foreach ($response as $key => $value) {
            if (strpos($key, 'x-') === 0) {
                $vendorProperties[$key] = $value;
            }
        }

        if (isset($response->schema)) {
            $parameters[] = $this->createParameter((object) [
                'in' => 'body',
                'name' => 'body',
                'required' => true,
                'schema' => $response->schema
            ]);
        }
        if (isset($response->headers)) {
            foreach ($response->headers as $headerName => $schema) {
                $schema->in = 'header';
                $schema->name = $headerName;
                $schema->required = true;
                $parameters[] = $this->createParameter($schema);
            }
        }
        if (isset($response->produces)) {
            $allowedContentTypes = $defaultProducedContentTypes;
        }

        $responseDefinition = new ResponseDefinition($statusCode, $allowedContentTypes, new Parameters($parameters));
        $responseDefinition->setVendorProperties($this->getVendorProperties($response));

        return $responseDefinition;
    }

    /**
     * Create a Parameter from a swagger parameter
     *
     * @param \stdClass $parameter
     *
     * @return Parameter
     */
    protected function createParameter(\stdClass $parameter)
    {
        $parameter = get_object_vars($parameter);
        $location = $parameter['in'];
        $name = $parameter['name'];
        $schema = (isset($parameter['schema'])) ? $parameter['schema'] : new \stdClass();
        $required = (isset($parameter['required'])) ? $parameter['required'] : false;

        unset($parameter['in']);
        unset($parameter['name']);
        unset($parameter['required']);
        unset($parameter['schema']);

        // Every remaining parameter may be json schema properties
        foreach ($parameter as $key => $value) {
            $schema->{$key} = $value;
        }

        // It's not relevant to validate file type
        if (isset($schema->format) && $schema->format === 'file') {
            $schema = null;
        }

        $aParameter = new Parameter($location, $name, $required, $schema);
        $aParameter->setVendorProperties($this->getVendorProperties($parameter));

        return $aParameter;
    }

    /**
     * @param array|\stdClass $object
     * @return array
     */
    protected function getVendorProperties($object)
    {
        $vendorProperties = [];
        foreach ($object as $key => $value) {
            if (strpos($key, 'x-') === 0) {
                $vendorProperties[$key] = $value;
            }
        }

        return $vendorProperties;
    }
}