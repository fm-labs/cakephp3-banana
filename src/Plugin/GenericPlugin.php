<?php
namespace Banana\Plugin;

use Banana\Application;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

class GenericPlugin implements PluginInterface
{

    public function bootstrap(Application $app)
    {
    }

    public function routes(RouteBuilder $routes)
    {
    }

    public function middleware(MiddlewareQueue $middleware)
    {
    }
}
