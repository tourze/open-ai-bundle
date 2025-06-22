<?php

namespace OpenAIBundle\Service;

use OpenAIBundle\Controller\ChatCreateController;
use OpenAIBundle\Controller\ChatIndexController;
use OpenAIBundle\Controller\ConversationChatController;
use OpenAIBundle\Controller\ConversationMessagesController;
use OpenAIBundle\Controller\ConversationPageController;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

#[AutoconfigureTag('routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->addCollection($this->controllerLoader->load(ChatIndexController::class));
        $collection->addCollection($this->controllerLoader->load(ChatCreateController::class));
        $collection->addCollection($this->controllerLoader->load(ConversationPageController::class));
        $collection->addCollection($this->controllerLoader->load(ConversationMessagesController::class));
        $collection->addCollection($this->controllerLoader->load(ConversationChatController::class));
        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }
}
