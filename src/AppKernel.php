<?php declare(strict_types=1);

namespace App;

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Response;
use React\Http\Server;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Class AppKernel
 * @package App
 */
class AppKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @var string
     */
    private string $host;

    /**
     * @var string
     */
    private string $port;

    /**
     * AppKernel constructor.
     * @param string $host
     * @param string $port
     */
    public function __construct(string $host = 'localhost', string $port = '80')
    {
        parent::__construct('prod', true);
        $this->host = $host;
        $this->port = $port;
    }

    public function run(): void
    {
        $loop = Factory::create();
        $socket = new \React\Socket\Server(
            sprintf('%s:%s', $this->host, $this->port),
            $loop,
            ['tcp', 'tls']
        );
        $http = new Server($this->runtimeCallback());
        $http->listen($socket);
        $loop->run();
    }

    /**
     * @return callable
     */
    public function runtimeCallback(): callable
    {
        return function (ServerRequestInterface $request) {
            /** @var Request $symfonyRequest */
            $symfonyRequest = AppKernel::adaptiveRequest($request);
            /** @var \Symfony\Component\HttpFoundation\Response $symfonyResponse */
            $symfonyResponse = $this->handle($symfonyRequest);
            $this->terminate($symfonyRequest, $symfonyResponse);
            return new Response(
                $symfonyResponse->getStatusCode(),
                $symfonyResponse->headers->all(),
                $symfonyResponse->getContent(),
                $symfonyResponse->getProtocolVersion()
            );
        };
    }

    /**
     * @param ContainerConfigurator $container
     */
    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');

        if (file_exists(dirname(__DIR__) . '/config/services.yaml')) {
            $container->import('../config/{services}.yaml');
            $container->import('../config/{services}_'.$this->environment.'.yaml');
        } else {
            $path = \dirname(__DIR__).'/config/services.php';
            (require $path)($container->withPath($path), $this);
        }
    }

    /**
     * @param RoutingConfigurator $routes
     */
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}/*.yaml');
        if (file_exists(dirname(__DIR__).'/config/routes.yaml')) {
            $routes->import('../config/{routes}.yaml');
        } else {
            $path = dirname(__DIR__).'/config/routes.php';
            (require $path)($routes->withPath($path), $this);
        }
    }

    /**
     * @TODO remote this method, after adaptive Symfony\Component\HttpKernel
     *
     * @param ServerRequestInterface $reactRequest
     * @return Request
     */
    public static function adaptiveRequest(ServerRequestInterface $reactRequest): Request
    {
        return new Request(
            $reactRequest->getQueryParams(),
            $reactRequest->getParsedBody() ?? [],
            $reactRequest->getAttributes(),
            $reactRequest->getCookieParams(),
            $reactRequest->getUploadedFiles(),
            $reactRequest->getServerParams(),
            $reactRequest->getParsedBody()
        );
    }
}
