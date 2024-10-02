<?php

declare(strict_types=1);

namespace Core\Console;

use Northrook\Trait\StaticClass;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Provides a simple output formatter.
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
final class Output
{
    use StaticClass;

    /**
     * Print an [ INIT ] message to the console.
     *
     * @param string ...$messages
     */
    public static function init( string ...$messages ) : void
    {
        $output = new OutputFormatterStyle();
        $output->setBackground( 'magenta' );
        $output->setForeground( 'black' );
        $output->setOption( 'bold' );

        Output::print( $output->apply( ' [ INIT ] ' ), $messages );
    }

    /**
     * Print an [ INFO ] message to the console.
     *
     * @param string ...$messages
     */
    public static function info( string ...$messages ) : void
    {
        $output = new OutputFormatterStyle();
        $output->setBackground( 'cyan' );
        $output->setForeground( 'black' );
        $output->setOption( 'bold' );

        Output::print( $output->apply( ' [ INFO ] ' ), $messages );
    }

    /**
     * Print an [ OK ] message to the console.
     *
     * @param string ...$messages
     */
    public static function OK( string ...$messages ) : void
    {
        $output = new OutputFormatterStyle();
        $output->setBackground( 'green' );
        $output->setForeground( 'black' );
        $output->setOption( 'bold' );

        Output::print( $output->apply( ' [ OK ] ' ), $messages );
    }

    /**
     * Print an [ WARNING ] message to the console.
     *
     * @param string ...$messages
     */
    public static function warning( string ...$messages ) : void
    {
        $output = new OutputFormatterStyle();
        $output->setBackground( 'yellow' );
        $output->setForeground( 'black' );
        $output->setOption( 'bold' );

        Output::print( $output->apply( ' [ WARNING ] ' ), $messages );
    }

    /**
     * Print an [ ERROR ] message to the console.
     *
     * @param string ...$messages
     */
    public static function error( string ...$messages ) : void
    {
        $output = new OutputFormatterStyle();
        $output->setBackground( 'red' );
        $output->setForeground( 'black' );
        $output->setOption( 'bold' );

        Output::print( $output->apply( ' [ ERROR ] ' ), $messages );
    }

    /**
     * @internal
     *
     * @param string   $output
     * @param string[] $messages
     */
    private static function print( string $output, array $messages ) : void
    {
        \array_walk(
            $messages,
            static function( $message ) use ( &$print ) {
                $print[] = " {$message}";
            },
        );

        echo $output.\implode( PHP_EOL, $print ).PHP_EOL.PHP_EOL;
    }
}
