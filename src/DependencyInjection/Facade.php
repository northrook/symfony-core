<?php

namespace Core\DependencyInjection;

/**
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class Facade
{
    abstract protected static function facade() : object;
}
