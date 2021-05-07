<?php

declare (strict_types=1);
namespace TMS\Buzz\Client;

use TMS\Buzz\Configuration\ParameterBag;
use TMS\Buzz\Exception\InvalidArgumentException;
use TMS\Http\Message\ResponseFactory;
use TMS\Psr\Http\Message\ResponseFactoryInterface;
use TMS\Symfony\Component\OptionsResolver\OptionsResolver;
abstract class AbstractClient
{
    /**
     * @var OptionsResolver|null
     */
    private $optionsResolver;
    /**
     * @var ParameterBag
     */
    private $options;
    /**
     * @var ResponseFactoryInterface|ResponseFactory
     */
    protected $responseFactory;
    /**
     * @param ResponseFactoryInterface|ResponseFactory $responseFactory
     */
    public function __construct($responseFactory, array $options = [])
    {
        if (!$responseFactory instanceof \TMS\Psr\Http\Message\ResponseFactoryInterface && !$responseFactory instanceof \TMS\Http\Message\ResponseFactory) {
            throw new \TMS\Buzz\Exception\InvalidArgumentException(\sprintf('First argument of %s must be an instance of %s or %s.', __CLASS__, \TMS\Psr\Http\Message\ResponseFactoryInterface::class, \TMS\Http\Message\ResponseFactory::class));
        }
        $this->options = new \TMS\Buzz\Configuration\ParameterBag();
        $this->options = $this->doValidateOptions($options);
        $this->responseFactory = $responseFactory;
    }
    protected function getOptionsResolver() : \TMS\Symfony\Component\OptionsResolver\OptionsResolver
    {
        if (null !== $this->optionsResolver) {
            return $this->optionsResolver;
        }
        $this->optionsResolver = new \TMS\Symfony\Component\OptionsResolver\OptionsResolver();
        $this->configureOptions($this->optionsResolver);
        return $this->optionsResolver;
    }
    /**
     * Validate a set of options and return a new and shiny ParameterBag.
     */
    protected function validateOptions(array $options = []) : \TMS\Buzz\Configuration\ParameterBag
    {
        if (empty($options)) {
            return $this->options;
        }
        return $this->doValidateOptions($options);
    }
    /**
     * Validate a set of options and return a new and shiny ParameterBag.
     */
    private function doValidateOptions(array $options = []) : \TMS\Buzz\Configuration\ParameterBag
    {
        $parameterBag = $this->options->add($options);
        try {
            $parameters = $this->getOptionsResolver()->resolve($parameterBag->all());
        } catch (\Throwable $e) {
            // Wrap any errors.
            throw new \TMS\Buzz\Exception\InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
        return new \TMS\Buzz\Configuration\ParameterBag($parameters);
    }
    protected function configureOptions(\TMS\Symfony\Component\OptionsResolver\OptionsResolver $resolver) : void
    {
        $resolver->setDefaults(['allow_redirects' => \false, 'max_redirects' => 5, 'timeout' => 0, 'verify' => \true, 'proxy' => null]);
        $resolver->setAllowedTypes('allow_redirects', 'boolean');
        $resolver->setAllowedTypes('verify', 'boolean');
        $resolver->setAllowedTypes('max_redirects', 'integer');
        $resolver->setAllowedTypes('timeout', ['integer', 'float']);
        $resolver->setAllowedTypes('proxy', ['null', 'string']);
    }
}
