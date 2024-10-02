<?php

namespace Core;

use Northrook\Logger\Log;

final class App
{
    private static string $env;

    private static bool $debug;

    public function __construct(
        string $env,
        ?bool  $debug = null,
    ) {
        if ( isset( App::$env ) ) {
            return;
        }

        self::$env   = $env;
        self::$debug = $debug ?? false;

    }

    /**
     * Check if the current environment is production.
     *
     * Will Log a notice leven entry if {@see Env::$debug} mode is enabled in {@see Env::PRODUCTION}.
     *
     * @return bool
     */
    public static function isProduction() : bool
    {
        if ( App::isDebug() ) {
            Log::Notice(
                message : '{debug} is enabled in {environment}',
                context : ['debug' => 'debug', 'environment' => App::$env],
            );
        }
        return 'prod' === App::$env;
    }

    /**
     * @return bool
     */
    public static function isDevelopment() : bool
    {
        return 'dev' === App::$env;
    }

    /**
     * @return bool
     */
    public static function isStaging() : bool
    {
        return 'staging' === App::$env;
    }

    /**
     * @return bool
     */
    public static function isDebug() : bool
    {
        return App::$debug;
    }
}