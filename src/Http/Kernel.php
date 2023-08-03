<?php

namespace Nebula\Http;

use Nebula\Interfaces\Http\Response;
use Nebula\Interfaces\Routing\Router;
use Nebula\Interfaces\Framework\Kernel as NebulaKernel;
use Composer\ClassMapGenerator\ClassMapGenerator;
use Nebula\Middleware\Middleware;
use Nebula\Traits\Http\Response as HttpResponse;
use Nebula\Traits\Instance\Singleton;
use StellarRouter\Route;
use Throwable;

class Kernel implements NebulaKernel
{
    use HttpResponse;
    use Singleton;

    public Router $router;
    protected array $middleware = [];

    /**
     * Setup the application
     */
    public function setup(): void
    {
        $this->router = app()->get(Router::class);
        $this->registerMiddleware();
        $this->registerControllers();
    }

    /**
     * Register the controller classes
     */
    private function registerControllers(): void
    {
        // Register the controller classes
        $config = config("paths");
        foreach ($this->classMap($config['controllers']) as $class_name => $filename) {
            $this->router->registerClass($class_name);
        }
    }

    /**
     * Register the middleware classes
     */
    private function registerMiddleware(): void
    {
        foreach ($this->middleware as $i => $class) {
            $this->middleware[$i] = app()->get($class);
        }
    }

    /**
     * Generate a class map for the given directory
     * @return array<class-string,non-empty-string>
     */
    public function classMap(string $directory): array
    {
        if (!file_exists($directory)) {
            throw new \Exception("class map directory doesn't exist");
        }
        return ClassMapGenerator::createMap($directory);
    }

    /**
     * Resolve the route and execute controller method
     * @param Route|null $route
     * @return Response
     */
    public function resolveRoute(?Route $route): Response
    {
        $response = app()->get(Response::class);
        if ($route) {
            try {
                $handlerClass = $route->getHandlerClass();
                $handlerMethod = $route->getHandlerMethod();
                $routeParameters = $route->getParameters();
                $class = app()->get($handlerClass);

                $content = $class->$handlerMethod(...$routeParameters);
                $response->setContent($content ?? '');
            } catch (\Exception $ex) {
                return $this->handleException($ex);
            }
        } else {
            return $this->response(404, "Page not found");
        }
        return $response;
    }

    /**
     * Handle the request and return a response
     * @return Response
     */
    public function handle(): Response
    {
        $request = request();
        // Figure out the route
        $route = $this->router->handleRequest($request->getMethod(), $request->getUri());
        // Save the route to the request (e.g. use in middleware)
        $request->route = $route;
        $runner = app()->get(Middleware::class);
        $response = $runner
            ->layer($this->middleware)
            ->handle($request, fn () => $this->resolveRoute($route));
        return $response;
    }

    /**
     * Handle any application exceptions
     */
    public function handleException(Throwable $exception): Response
    {
        error_log($exception->getMessage());
        return $this->response(500, "Server error");
    }

    /**
     * Terminate the application
     */
    public function terminate(): void
    {
        logger('timeEnd', 'Nebula');
        exit;
    }
}
