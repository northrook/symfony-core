<?php

namespace Core\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * ## Auto-routing controller.
 */
abstract class Controller
{
    final public function router( ?string $route ) : Response
    {
        dump( $this);
        return new Response( __METHOD__.' rendering route: '.$route );
    }
}