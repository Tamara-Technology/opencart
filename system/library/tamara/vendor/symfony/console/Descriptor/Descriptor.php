<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace TMS\Symfony\Component\Console\Descriptor;

use TMS\Symfony\Component\Console\Application;
use TMS\Symfony\Component\Console\Command\Command;
use TMS\Symfony\Component\Console\Exception\InvalidArgumentException;
use TMS\Symfony\Component\Console\Input\InputArgument;
use TMS\Symfony\Component\Console\Input\InputDefinition;
use TMS\Symfony\Component\Console\Input\InputOption;
use TMS\Symfony\Component\Console\Output\OutputInterface;
/**
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
abstract class Descriptor implements \TMS\Symfony\Component\Console\Descriptor\DescriptorInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * {@inheritdoc}
     */
    public function describe(\TMS\Symfony\Component\Console\Output\OutputInterface $output, $object, array $options = [])
    {
        $this->output = $output;
        switch (\true) {
            case $object instanceof \TMS\Symfony\Component\Console\Input\InputArgument:
                $this->describeInputArgument($object, $options);
                break;
            case $object instanceof \TMS\Symfony\Component\Console\Input\InputOption:
                $this->describeInputOption($object, $options);
                break;
            case $object instanceof \TMS\Symfony\Component\Console\Input\InputDefinition:
                $this->describeInputDefinition($object, $options);
                break;
            case $object instanceof \TMS\Symfony\Component\Console\Command\Command:
                $this->describeCommand($object, $options);
                break;
            case $object instanceof \TMS\Symfony\Component\Console\Application:
                $this->describeApplication($object, $options);
                break;
            default:
                throw new \TMS\Symfony\Component\Console\Exception\InvalidArgumentException(\sprintf('Object of type "%s" is not describable.', get_debug_type($object)));
        }
    }
    /**
     * Writes content to output.
     */
    protected function write(string $content, bool $decorated = \false)
    {
        $this->output->write($content, \false, $decorated ? \TMS\Symfony\Component\Console\Output\OutputInterface::OUTPUT_NORMAL : \TMS\Symfony\Component\Console\Output\OutputInterface::OUTPUT_RAW);
    }
    /**
     * Describes an InputArgument instance.
     */
    protected abstract function describeInputArgument(\TMS\Symfony\Component\Console\Input\InputArgument $argument, array $options = []);
    /**
     * Describes an InputOption instance.
     */
    protected abstract function describeInputOption(\TMS\Symfony\Component\Console\Input\InputOption $option, array $options = []);
    /**
     * Describes an InputDefinition instance.
     */
    protected abstract function describeInputDefinition(\TMS\Symfony\Component\Console\Input\InputDefinition $definition, array $options = []);
    /**
     * Describes a Command instance.
     */
    protected abstract function describeCommand(\TMS\Symfony\Component\Console\Command\Command $command, array $options = []);
    /**
     * Describes an Application instance.
     */
    protected abstract function describeApplication(\TMS\Symfony\Component\Console\Application $application, array $options = []);
}
