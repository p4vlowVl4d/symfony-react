<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 */
class DefaultController
{
    /**
     * @Route(name="default_index", path="/")
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        return new Response('Hello World!', 200);
    }
}
