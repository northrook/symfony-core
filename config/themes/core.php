<?php

// Theme configuration file
// .php version may contain functions, but *must* return an array.
// Returned array *must* be convertable to; .json, .yaml.

return [
    'document' => [
        // Expect a horizontal gutter of 1rem or 2ch (test which covers most cases)
        // max-width is purely for restricting the content, it will not overflow
        'gutter'             => '2ch',    // left|right padding for elements
        'min-width'          => '20rem',  // 320px
        'max-width'          => '75rem', // 1200px
        'scroll-padding-top' => '--offset-top', // maybe +--gap?
        'offset-top'         => '--size:m',
        'offset-left'        => '--size:m',
        'offset-right'       => '--size:m',
        'offset-bottom'      => '--size:m',
    ],
    'typography' => [
            'font-family'     => 'Inter',
            'line-height'     => '1.6em',
            'line-spacing'    => '--size:small', // spacing between inline elements
            'line-length'  => '64ch', // limits inline text elements, like p and h#
            // Create .h#, .small/small, etc
            // use respective --size-{type}
            // .h# may override --line-height and --spacing or --gap
    ],
    'palette' => [
        // string: hue/hsl/hex seed, or array: [string=>string<color>]
        // `baseline` palette is *required*
        // `primary` palette will be generated unless defined
        // `system` palette will be generated unless defined, *must* contain: shadow, info, notice, success, warning, danger
        'baseline' => [
            'name'        => 'Baseline', // [optional] ucFirst of key if undefined
            'description' => null,       // [optional] used in the editor
            'var'         => 'baseline', // [optional] based on key if undefined
            [

            ],
        ],
        // 'baseline' => 222, // seed
        'primary' => [222, 50, 50], // seed
        // System is a little unique, as it _cannot_ be prefixed using a `var` value
        'system' => [
            'name'        => null, // [optional] ucFirst of key if undefined
            'description' => 'Provides status colours for messages and visual feedback.',
            [
                'shadow'  => 'baseline-600',
                'info'    => '#579dff',
                'notice'  => '#9f8fef',
                'success' => '#4bce97',
                'warning' => '#f5cd47',
                'danger'  => '#f87268',
            ],
        ],
    ],

    // :: Sizing
    // prefixed --size-{key}: {value};
    'sizes' => [
        // agnostic
        't'  => '.125rem', // 2px
        'es' => '.25rem',  // 4px
        's'  => '.5rem',   // 8px
        'm'  => '1rem',    // 16px
        'l'  => '1.5rem',  // 24px
        'xl' => '2rem',    // 32px
        // typography
        'body'  => '1rem',    // default body text size
        'small' => '.875rem', // small and .small classes
        // min-max :: use typography -> max-inline-size as a middle, from minimum till site width
        'h1' => ['1.8rem', '3.05rem'], // min-max
        'h2' => ['1.6rem', '2rem'],
        'h3' => ['1.25rem', '1.8rem'],
        'h4' => ['1.1rem', '1.5rem'],
    ],
    //
    // .class
    'box' => [
            'card'   => [],
            'box'    => [],
            'button' => [],
            'tag'    => [],
            'meta'   => [],
            'media'  => [],
    ],
];