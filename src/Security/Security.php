<?php

namespace Core\Security;

use Core\DependencyInjection\ServiceContainer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use LogicException;
use SensitiveParameter;
use Symfony\Component\Security\Csrf\{CsrfToken, CsrfTokenManagerInterface};
use Symfony\Component\Security\Core\User\UserInterface;

final class Security
{
    use ServiceContainer;

    /**
     * Get a user from the Security Token Storage.
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser() : ?UserInterface
    {
        // TODO : Allow passing userID/username to fetch entity

        $userToken = $this->serviceLocator( TokenStorageInterface::class )->getToken();

        return $userToken?->getUser();
    }

    /**
     * Checks the validity of a CSRF token.
     *
     * @param string      $id    The id used when generating the token
     * @param null|string $token The actual token sent with the request that should be validated
     */
    public function isCsrfTokenValid( string $id, #[SensitiveParameter] ?string $token ) : bool
    {
        try {
            return $this->serviceLocator( CsrfTokenManagerInterface::class )
                ->isTokenValid( new CsrfToken( $id, $token ) );
        }
        catch ( LogicException $e ) {
            throw new LogicException( 'CSRF protection is not enabled. Enable it with the "csrf_protection" key in "config/packages/framework.yaml".' );
        }
    }

    public function isGranted( $attribute, $subject = null ) : bool
    {
        return $this->serviceLocator( AuthorizationCheckerInterface::class )->isGranted( $attribute, $subject );
    }

    public function requiresAccess( mixed $attribute, mixed $subject = null, string $message = 'Access Denied.' ) : void
    {
        if ( ! $this->isGranted( $attribute, $subject ) ) {
            $exception = new AccessDeniedException( $message );
            $exception->setAttributes( [$attribute] );
            $exception->setSubject( $subject );

            throw $exception;
        }
    }
}