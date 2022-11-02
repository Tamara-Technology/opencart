<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace TMS\Symfony\Component\Mime\DependencyInjection;

use TMS\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use TMS\Symfony\Component\DependencyInjection\ContainerBuilder;
use TMS\Symfony\Component\DependencyInjection\Reference;
/**
 * Registers custom mime types guessers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AddMimeTypeGuesserPass implements \TMS\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface
{
    private $mimeTypesService;
    private $mimeTypeGuesserTag;
    public function __construct(string $mimeTypesService = 'mime_types', string $mimeTypeGuesserTag = 'mime.mime_type_guesser')
    {
        $this->mimeTypesService = $mimeTypesService;
        $this->mimeTypeGuesserTag = $mimeTypeGuesserTag;
    }
    /**
     * {@inheritdoc}
     */
    public function process(\TMS\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        if ($container->has($this->mimeTypesService)) {
            $definition = $container->findDefinition($this->mimeTypesService);
            foreach ($container->findTaggedServiceIds($this->mimeTypeGuesserTag, \true) as $id => $attributes) {
                $definition->addMethodCall('registerGuesser', [new \TMS\Symfony\Component\DependencyInjection\Reference($id)]);
            }
        }
    }
}
