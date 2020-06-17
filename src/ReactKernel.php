<?php declare(strict_types=1);

namespace App;

use React\EventLoop\Factory;
use React\Http\Response;
use React\Http\Server;
use RingCentral\Psr7\ServerRequest;
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
        $http = (new Server($socket))
            ->on('request', $this->runtimeCallback());
        $http->listen($socket);
        $loop->run();
    }

    public function runtimeCallback(): callable
    {
        $kernel = $this->defaultKernel();
        return function (ServerRequest $request, Response $response) use ($kernel) {
            $post = [];
            if (in_array(strtoupper($request->getMethod()), ReactKernel::supportedMethods())
                && isset($request->getHeaders()['Content-Type'])
                && (0 === strpos($request->getHeaders()['Content-Type'], 'application/x-www-form-urlencoded'))) {
                $post = $request->getParsedBody();
            }

            /** @var Request $symfonyRequest */
            $symfonyRequest = (new Request(
                $request->getQueryParams(),
                $request->getParsedBody(),
                [],
                [],
                $request->getUploadedFiles(),
                [],
                $request->getBody()
            ));
            $symfonyRequest->setMethod($request->getMethod());
            $symfonyRequest->headers->replace($request->getHeaders());
            $symfonyRequest->server->set('REQUEST_URI', $request->getUri()->getPath());
            if (key_exists('Host', $request->getHeaders()))
                $symfonyRequest->server->set('SERVER_NAME', explode(':', $request->getHeaders()['Host']));

            /** @var \Symfony\Component\HttpFoundation\Response $symfonyResponse */
            $symfonyResponse = $kernel->handle($symfonyRequest);
            $response = new Response(
                $symfonyResponse->getStatusCode(),
                $symfonyResponse->headers->all(),
                $symfonyResponse->getContent(),
                $symfonyResponse->getProtocolVersion()
            );
            $kernel->terminate($symfonyRequest, $symfonyResponse);
        };
    }

    public function defaultKernel() : HttpKernel
    {
        return new Kernel('prod', false);
    }

    public static function supportedMethods() : array
    {
        return [
          'POST', 'PUT', 'GET', 'DELETE', 'PATCH'
        ];
    }
}
