<?php

declare(strict_types=1);

namespace Core\Service\ThemeManager;

use Northrook\ArrayStore;

// : Not intended to be called directly
// . All generated config files in app/var/themes/{name}.theme.php

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class Config extends ArrayStore {

    public const array DEFAULT = [
        // :root
        'document' => [
                'offset_top'         => '--size-medium',
                'offset-left'        => '--size-medium',
                'offset-right'       => '--size-medium',
                'offset-bottom'      => '--size-medium',
                'scroll-padding-top' => '--offset-top', // maybe +--gap?
                'gutter'             => '2ch',    // left|right padding for elements
                'gap'                => '--size-medium',
                'gap-h'              => '--size-medium',
                'gap-v'              => '--size-medium',
                'min-width'          => '20rem',  // 320px
                'max-width'          => '75rem', // 1200px
        ],
        // :root
        'typography' => [
                'font-family'  => 'Arial, Helvetica, sans-serif',
                'line-height'  => '1.6em',
                'line-spacing' => '1em', // spacing between elements
                'line-length'  => '64ch', // limits inline text elements, like p and h#
        ],
        // :root
        'palette' => [
                'baseline' => [
                        'name'        => 'Baseline', // [optional] ucFirst of key if undefined
                        'description' => 'For text and surfaces.',       // [optional] used in the editor
                        'var'         => 'baseline', // [optional] based on key if undefined
                        'seed'        => [222, 9],
                ],
                'system' => [
                        'shadow'  => 'baseline-600',
                        'info'    => '#579dff',
                        'notice'  => '#9f8fef',
                        'success' => '#4bce97',
                        'warning' => '#f5cd47',
                        'danger'  => '#f87268',
                ],
        ],
        // :root
        'sizes' => [
                'none'   => '0',
                'point'  => '.125rem', // 2px
                'tiny'   => '.25rem',  // 4px
                'small'  => '.5rem',   // 8px
                'medium' => '1rem',    // 16px
                'large'  => '1.5rem',  // 24px
                'flow'   => '1em',

                // typography
                'text'       => '1rem',    // default body text size
                'text-small' => '.875rem', // small and .small classes
                'text-large' => '--size-h4', // small and .small classes
                // min-max :: use typography -> max-inline-size as a middle, from minimum till site width
                'h1' => ['1.8rem', '3.05rem'], // min-max
                'h2' => ['1.6rem', '2rem'],
                'h3' => ['1.25rem', '1.8rem'],
                'h4' => ['1.1rem', '1.5rem'],
        ],
        //
        // .class
        // should have .card, .box, .button, .tag, .meta, .media(image/video/etc), ..
        'box' => [
        ],
        'rules' => [
                'small' => ['font-size' => '.875rem'],
        ],
    ];

}