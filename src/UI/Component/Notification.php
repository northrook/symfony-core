<?php

namespace Core\UI\Component;

use Core\UI\{AbstractComponent, RenderRuntime};
use Core\Latte\Compiler\NodeCompiler;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\HTML\Element\Attributes;
use Northrook\HTML\{Element, Format};
use Support\{Normalize, Time};
use function String\filterHtml;

/*
 # Accessibility
 : https://github.com/WICG/accessible-notifications
 : https://inclusive-components.design/notifications/

    - Don't use aria-atomic="true" on live elements, as it will announce any change within it.
    - Be judicious in your use of visually hidden live regions. Most content should be seen and heard.
    - Distinguish parts of your interface in content or with content and style, but never just with style.
    - Do not announce everything that changes on the page.
    - Be very wary of Desktop notifications, may cause double announcements etc.


 : https://atlassian.design/components/flag/examples
    Used for confirmations, alerts, and acknowledgments
    that require minimal user interaction.

 : https://atlassian.design/components/banner/examples
    Banner displays a prominent message at the top of the screen.

    We may want to create a separate component, or have types
    such as 'floating' using the Toast system, or 'static'
    using fixed positioning 'top|bottom' with left/right/center.



 */

class Notification extends AbstractComponent
{
    private array $instances = [];

    public readonly string $type;

    public readonly string $message;

    public readonly ?string $description;

    public readonly Attributes $attributes;

    public ?int $timeout = null;

    protected readonly Element $component;

    public function __construct(
        string          $type = 'notice',
        ?string         $message = null,
        ?string         $description = null,
        null|int|string $timeout = null,
        array           $attributes = [],
    ) {
        $this->type        = filterHtml( $type );
        $this->message     = filterHtml( $message ?? \ucfirst( $this->type ) );
        $this->description = $description ? filterHtml( $description ) : null;

        if ( $timeout ) {
            $this->setTimeout( $timeout );
        }

        $this->component  = new Element( 'toast', $attributes );
        $this->attributes = $this->component->attributes;

        $this->component->class( 'notification', "intent:{$this->type()}", prepend : true );

        $this->instances[] = new Time();
    }

    public function setTimeout( null|int|string $timeout ) : self
    {
        if ( \is_string( $timeout ) ) {
            $timeout = ( \strtotime( $timeout, 0 ) ) * 100;
        }
        $this->timeout = $timeout;
        return $this;
    }

    protected function build() : string
    {
        $type = Normalize::key( $this->type );
        $icon = $this->iconPack()->get( $this->type(), fallback: 'notice' );
        // $message     = Format::inline( $this->message );
        $message = Format::inline( $this->message );
        if ( $this->description ) {
            $details     = Format::newline( $this->description );
            $description = <<<HTML
                <details>
                  <summary>Description</summary>
                  {$details}
                </details>;
                HTML;
        }
        else {
            $description = null;
        }

        $content = <<<HTML
            <button class="close" aria-label="Close" type="button">
            </button>
            <output role="status">
              <i class="status">
                {$icon}
                <span class="status-type">
                  {$type}
                </span>
                <time datetime="{$this->timestamp()}">
                  {$this->timestampWhen()}
                </time>
              </i>
              <span class="message">
                {$message}
              </span>
            </output>
            {$description}
            HTML;

        if ( $this->timeout ) {
            $this->component->attributes(
                'timeout',
                ( $this->timeout < 3_500 )
                            ? 3_500 : $this->timeout,
            );
        }

        return (string) $this->component->content( $content );
    }

    private function type() : string
    {
        return match ( $this->type ) {
            'error' => 'danger',
            default => $this->type,
        };
    }

    /**
     * Retrieve the {@see Timestamp} object.
     *
     * @internal
     * @return Time
     */
    private function timestamp() : Time
    {
        return $this->instances[\array_key_last( $this->instances )];
    }

    private function timestampWhen() : string
    {
        $now       = \time();
        $unix      = $this->timestamp()->unixTimestamp;
        $timestamp = $this->timestamp()->format( Time::FORMAT_HUMAN, true );

        // If this occurred less than 5 seconds ago, count it as now
        if ( ( $now - $unix ) < 5 ) {
            return '<span class="datetime-when">Now</span><span class="datetime-timestamp">'.$timestamp.'</span>';
        }
        // If this occurred less than 12 hours ago, it is 'today'
        if ( ( $now - $unix ) < 43_200 ) {
            return '<span class="datetime-when">Today</span><span class="datetime-timestamp">'.$timestamp.'</span>';
        }
        // Otherwise print the whole day
        return $timestamp;
    }

    public static function nodeCompiler( NodeCompiler $node ) : AuxiliaryNode
    {
        return RenderRuntime::auxiliaryNode(
            renderName : Notification::class,
            arguments  : $node->properties(
                ['type' => 'notice'],
                'message',
                'description',
                'timeout',
            ),
        );
    }

    public static function runtimeRender(
        string          $type = 'notice',
        ?string         $message = null,
        ?string         $description = null,
        null|int|string $timeout = null,
        array           $attributes = [],
    ) : string {
        return (string) new Notification( ...\get_defined_vars() );
    }
}
