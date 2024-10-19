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
        'offset-top'         => '--size-medium',
        'offset-left'        => '--size-medium',
        'offset-right'       => '--size-medium',
        'offset-bottom'      => '--size-medium',
    ],
    'typography' => [
        'font-family'  => 'Inter',
        'line-height'  => '1.6em',
        'line-spacing' => '--size-small', // spacing between inline elements
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
        'none'   => '0',
        'point'  => '.125rem', // 2px
        'tiny'   => '.25rem',  // 4px
        'small'  => '.5rem',   // 8px
        'medium' => '1rem',    // 16px
        'large'  => '1.5rem',  // 24px
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