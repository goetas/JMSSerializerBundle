<?php

namespace JMS\SerializerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DIUtils
{
    private static function handleRef(ContainerBuilder $container, Reference $argument, string $instance): Reference
    {
        if (strpos($argument, 'jms')!==0) {
            return $argument;
        }

        if ($container->hasAlias($argument)) {
            $alias = $container->getAlias($argument);

            $def = $container->getDefinition($alias);
            if ($def->hasTag('jms_serializer.global')) {
                return $argument;
            }

            self::getDefinition($instance, (string)$alias, $container);

            $container->setAlias($instance . '.' . $argument, $instance . '.' . $alias);
        } else {
            self::getDefinition($instance, (string)$argument, $container);
        }

        return new Reference($instance . '.' . $argument, $argument->getInvalidBehavior());
    }


    public static function getDefinition(string $instance, string $id, ContainerBuilder $container):Definition
    {
        if ($instance === 'default') {
            $def = $container->getDefinition($id);
            if (!$def->hasTag('jms_serializer.instance')) {
                $def->addTag('jms_serializer.instance', ['name' => $instance]);
            }
            return $def;
        }
        $name = $instance.'.'.$id;
        if (!$container->hasDefinition($name)) {

            $parentDef = $container->getDefinition($id);

            $newDef = new ChildDefinition($id);

            $newDef->addTag('jms_serializer.instance', ['name' => $instance]);
            foreach ($parentDef->getTags() as $tagName => $attributes) {
                foreach ($attributes as $attributeData) {
                    if (strpos($name, 'jms') !== false) {
                        $attributeData['instance'] = $instance;
                    }
                    $newDef->addTag($tagName, $attributeData);
                }
            }

            $newArgs = self::handleArgs($parentDef->getArguments(), $container, $instance);



            foreach ($newArgs as $a => $n) {
                $newDef->replaceArgument($a, $n);
            }

            $calls = [];
            foreach ($parentDef->getMethodCalls() as $call) {
                $calls[] =  [
                    $call[0],
                    self::handleArgs($call[1], $container, $instance)
                ];
            }
            $newDef->setMethodCalls($calls);

            $container->setDefinition($name, $newDef);
        }
        return $container->getDefinition($name);
    }

    private static function handleArgs(array $args, ContainerBuilder $container, string $instance): array
    {
        foreach ($args as $n => $arg) {
            if (is_array($arg)) {
                $args[$n] = self::handleArgs($arg, $container, $instance);
            }elseif ($arg instanceof Reference) {
                $args[$n] = self::handleRef($container, $arg, $instance);
            }
        }
        return $args;
    }

    public static function findTaggedServiceIds(ContainerBuilder $container, string $tag, ?string $instance = null):array
    {
        $tags = $container->findTaggedServiceIds($tag);

        foreach ($tags as $name => $attributes) {
            if (strpos($name, 'jms')===0) {
                if (!empty($attributes['instance']) && $instance !== null && $instance!==$attributes['instance']) {
                    unset($tags[$name]);
                }
            }
        }
        return $tags;
    }
}
