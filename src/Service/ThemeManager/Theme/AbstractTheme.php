<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Core\Service\ThemeManager\Theme;

// ? Generates the theme
// : Injected into ThemeManager
// * Available as ThemeManager->compiler
// * Resulting theme as serialized ThemeManager\Theme,
//   available as ThemeManager->theme for current active
//   available as ThemeManager->theme( $name ) locate and retrieve/generate
//   available as ThemeManager->user( $config ) users custom changes (admin only)

use Core\Service\ThemeManager\Compiler\{Typography};
use Northrook\Exception\E_Value;
use Support\Str;
use function Support\toString;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class AbstractTheme
{
    protected array $generated = [];


    /**
     *
     */
    abstract public function parseConfig() : void;

    final public function parse() : array
    {
        $this->parseConfig();
        return $this->generated;
    }

    final protected function set( string $var, string $value ) : void
    {
        $this->generated[$var] = $value;
    }

    // final public function getConfig(): array
    // {
    //     return $this->config;
    // }

    /**
     * @param string $string
     *
     * @return string Selector
     */
    final protected function selector( string $string ) : string
    {
        if ( ! $string ) {
            E_Value::error( 'The provided {selector} is empty.', throw : true );
        }

        // Enforce characters
        if ( ! \preg_match( '#^[a-zA-Z0-9_-]+$#', $string, $matches ) ) {
            E_Value::error(
                'The provided {selector} {value} contains illegal characters. It only accepts ASCII letters, numbers, hyphens, and underscores.',
                ['value' => $string],
                throw : true,
            );
        }

        // Ensure we don't accidentally have an invalid selector
        if ( \is_numeric( $string[0] ) ) {
            E_Value::error(
                'The provided {selector} {value} starts with a number. Selectors cannot start with a number.',
                ['value' => $string],
                throw : true,
            );
        }

        return $string;
    }

    /**
     * @param string[] $string
     * @param ?string  $prefix
     *
     * @return string Var
     */
    final protected function var( string|array $string, ?string $prefix = null ) : string
    {
        $var = [];

        foreach ( [$prefix, ...$string] as $value ) {
            $var[] = \trim( $string, " \n\r\t\v\0-" );
        }

        // Convert to lowercase
        $string = \strtolower( \implode( '-', $var ) );

        return "--{$this->selector( $string )}";
    }

    /**
     * @param array{0: string, 1: string}|string $value
     * @param string                             ...$unit
     *
     * @return string Value
     */
    final protected function value( string|array $value, string ...$unit ) : string
    {
        dump( 'can auto-responsiveValue', \array_intersect( ['em', 'rem'], $unit ), '---' );

        if ( \is_array( $value ) && \array_intersect( ['em', 'rem'], $unit ) ) {
            return $this->responsiveValue( $value );
        }

        $value = \strtolower( \trim( $value ) );

        if ( \str_starts_with( $value, '--' ) ) {
            return $value;
        }

        if ( $unit && '0' !== $value && ! Str::endsWith( $value, $unit ) ) {
            E_Value::error(
                'The variable value {value} was expected to be one of {type}.',
                [
                    'value' => $value,
                    'type'  => toString( $unit, '|' ),
                ],
                throw : true,
            );
        }

        return $value;
    }

    /**
     * Calculate an auto-scaling css rule, based on [https://websemantics.uk/tools/fluid-responsive-property-calculator/](Mike Foskett's tool), using [https://www.madebymike.com.au/writing/precise-control-responsive-typography/](Mike Riethmuller's equation).
     *
     * @param ResponsiveValue $value
     *
     * @return Value `min(max(...))`
     *
     * @link https://websemantics.uk/tools/fluid-responsive-property-calculator/ Fluid calculator
     * @link https://precise-type.com/modular-scale.html Auto scaling
     */
    final protected function responsiveValue( array $value ) : string
    {
        \assert( \count( $value ) === 2, 'Variable font sizes must only contain a minimum and maximum font size.' );

        $font = [
            'min'  => $this->config['document']['min-width'],
            'max'  => $this->config['document']['max-width'],
            'from' => \array_shift( $value ),
            'to'   => \array_shift( $value ),
        ];

        foreach ( $font as $key => $property ) {
            if ( ! \str_ends_with( $property, 'em' ) ) {
                E_Value::error(
                    'Variable fonts ranges must be {em} or {rem}. The {property} is invalid.',
                    ['property' => $key],
                    throw : true,
                );
            }
        }

        [$min, $max, $from, $to] = \array_values( $font );

        $unit = \preg_replace( '#.+?([a-z].+)#', '$1', $min );
        $view = \trim( \floatval( $min ) / 100 .$unit, '0' );

        $mod = 100 * ( \floatval( $to ) - \floatval( $from ) ) / ( \floatval( $max ) - \floatval( $min ) );

        $diff = \rtrim( \ltrim( \number_format( $mod, 4 ), '0' ), '.0' );

        return "min(max({$from},calc({$from} + ((1vw - {$view}) * {$diff}))), {$to})";
    }
}