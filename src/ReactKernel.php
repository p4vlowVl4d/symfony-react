<?php declare(strict_types=1);

namespace App;

use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Response;
use React\Http\Server;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpKernel\Kernel as HttpKernel;

/**
 * Class ReactKernel
 * @package App
 */
class ReactKernel
{
    /**
     * @var string
     */
    private string $port;

    /**
     * @var string
     */
    private string $host;

    /**
     * ReactKernel constructor.
     * @param string $host
     * @param string $port
     */
    public function __construct(string $host, string $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function listen() : void
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
        $kernel = $this->defaultKernel();
        return function (ServerRequestInterface $request) use ($kernel) {
            /** @var Request $symfonyRequest */
            $symfonyRequest = ReactKernel::adaptiveRequest($request);
            /** @var \Symfony\Component\HttpFoundation\Response $symfonyResponse */
            $symfonyResponse = $kernel->handle($symfonyRequest);
            $kernel->terminate($symfonyRequest, $symfonyResponse);
            return new Response(
                $symfonyResponse->getStatusCode(),
                $symfonyResponse->headers->all(),
                $symfonyResponse->getContent(),
                $symfonyResponse->getProtocolVersion()
            );
        };
    }

    /**
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

    public function defaultKernel() : HttpKernel
    {
        return new Kernel('prod', false);
    }
}
