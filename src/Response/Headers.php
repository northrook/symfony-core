<?php

declare(strict_types=1);

namespace Core\Response;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class Headers extends ResponseHeaderBag
{
    /**
     * Set one or more response headers.
     *
     * - Assigned to the {@see ResponseHeaderBag::class}.
     *
     * @param array<string, null|array<string, string>|bool|string>|string $set
     * @param null|array<array-key,mixed>|bool|string                      $value
     * @param bool                                                         $replace [true]
     *
     * @return ResponseHeaderBag
     */
    public function __invoke( string|array $set, bool|string|array|null $value = null, bool $replace = true ) : ResponseHeaderBag
    {
        // Allows setting multiple values
        if ( \is_array( $set ) ) {
            foreach ( $set as $key => $value ) {
                $this->__invoke( $key, $value, $replace );
            }

            return $this;
        }

        if ( \is_bool( $value ) ) {
            $value = $value ? 'true' : 'false';
        }

        $this->set( $set, $value, $replace );

        return $this;
    }
}