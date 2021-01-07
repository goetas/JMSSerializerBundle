<?php

namespace JMS\SerializerBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AssignVisitorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('jms_serializer.serializer') as $serializerId => $serializerAttributes) {
            foreach ($serializerAttributes as $serializerAttribute) {
                $serializerDef = $container->getDefinition($serializerId);
                $serializerName = $serializerAttribute['name'];
                $defs = [];

                foreach ($container->findTaggedServiceIds('jms_serializer.serialization_visitor') as $id => $multipleTags) {
                    foreach ($multipleTags as $attributes) {

                        $instance = $attributes['instance'] ?? null;

                        if ($instance && $serializerName !== $instance) {
                            continue;
                        }
                        $defs[$attributes['format']] = new Reference($id);
                    }
                }

                $serializerDef->replaceArgument(2, $defs);

                foreach ($container->findTaggedServiceIds('jms_serializer.deserialization_visitor') as $id => $multipleTags) {
                    foreach ($multipleTags as $attributes) {

                        $instance = $attributes['instance'] ?? null;

                        if ($instance && $serializerName !== $instance) {
                            continue;
                        }
                        $defs[$attributes['format']] = new Reference($id);
                    }
                }

                $serializerDef->replaceArgument(3, $defs);
            }
        }
    }
}
