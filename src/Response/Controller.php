<?php

namespace Core\Response;

/**
 * ## Auto-routing controller.
 */
abstract class Controller
{
    public function __construct(
        protected readonly Document        $document,
        protected readonly ResponseHandler $response,
    ) {}
}
