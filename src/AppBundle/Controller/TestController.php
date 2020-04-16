<?php

namespace AppBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller
{
    public function xKeyAction(ContentView $view): ContentView
    {
        $response = new Response();

        $locationIds = [123, 456];
        //$response->headers->set('X-Location-Id', implode(',', $locationIds));
        $response->headers->set('xkey', 'l'.implode(' l', $locationIds));

        $view->setResponse($response);

        return $view;
    }
}
