<?php

declare( strict_types = 1 );

namespace Northrook\Symfony\Configurator;

use Northrook\Logger\Log;
use Northrook\Symfony\Console\Output;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use function Northrook\normalizePath;

abstract class AutoConfigure
{
    /** @var bool Has this been instantiated anywhere? */
    private static bool $instantiated = false;

    private static string         $configDirectory;
    protected readonly Filesystem $file;

    final public function __construct( string $configDirectory ) {

        $this::$configDirectory ??= normalizePath( $configDirectory );
        $this->file             = new Filesystem();

        if ( !AutoConfigure::$instantiated ) {
            AutoConfigure::$instantiated = true;
            Output::init( 'AutoConfigure: Initialized' );
        }
    }

    final protected function path( string $name ) : string {
        return normalizePath( "{$this::$configDirectory}/{$name}" );
    }

    final protected function removeConfigFile( string $name ) : void {

        $path = $this->path( $name );

        if ( !$this->file->exists( $path ) ) {
            return;
        }

        try {
            $this->file->remove( $path );
            Output::info( "AutoConfigure: Removed config/$name." );
        }
        catch ( IOException $e ) {
            $message = "AutoConfigure: Could not remove config/$name. {$e->getMessage()}";
            Log::Error( message : $message, context : [ 'exception' => $e ] );
            Output::error( $message );
        }

    }

    final protected function createConfigFile( string $name, string $config ) : void {

        $path = $this->path( $name );

        if ( $this->file->exists( $path ) ) {
            return;
        }

        if ( !str_starts_with( $config, '<?php' ) ) {
            Output::error( 'AutoConfigure: Could not create ' . $name . ', it is not a valid PHP file.' );
            return;
        }

        $content = preg_replace(
            pattern     : '#<\?php\s+?(?=\S)#A',
            replacement : "<?php\n\n// Generated by " . $this::class . "\n\n",
            subject     : $config,
        );

        try {
            $this->file->dumpFile( $path, $content );
            Output::OK( "AutoConfigure: Generated config/$name." );
        }

        catch
        ( IOException $exception ) {
            $message = "AutoConfigure: Could not generate config/$name. {$exception->getMessage()}";
            Log::Error(
                message : $message,
                context : [ 'exception' => $exception, 'path' => $path ],
            );
            Output::error( $message );
        }
    }

}