<?php

declare(strict_types=1);

namespace Core\DependencyInjection;

use Core\Response\Attribute\{ContentResponse, DocumentResponse};
use Core\Response\{Document, Parameters};
use Exception;
use Core\Service\{Headers};
use JetBrains\PhpStorm\Deprecated;
use Northrook\Latte;
use Northrook\Logger\Log;
use Northrook\Resource\URL;
use ReflectionException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, JsonResponse, RedirectResponse, Response, ResponseHeaderBag};
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class CoreController
{
    use ServiceContainer;

    final protected function response( ?string $string = null ) : Response
    {
        $this->controllerResponseMethods();

        if ( $string ) {
            return new Response( $string );
        }

        // $content ??= $this->renderLatte();
        // [$template, $content] = \array_values( $this->getResponseTemplate() );

        $template = $this->request->parameters( '_document_template' );
        $content  = $this->request->parameters( '_content_template' );

        dump( $template, $content );

        $this->parameters->set( 'content', $this->request->parameters( '_content_template' ) );

        // return $this->serviceLocator( Latte::class )->render( $template, $this->parameters->getParameters() );

        return new Response( $this->latte->templateToString(
            $this->request->parameters( '_document_template' ),
            $this->parameters->getParameters(),
        ) );
    }

    // :: Response Methods :::::::::

    /**
     * Forwards the request to another controller.
     *
     * @param string $controller The controller name (a string like "App\Controller\PostController::index" or "App\Controller\PostController" if it is invokable)
     * @param array  $path
     * @param array  $query
     *
     * @return Response
     */
    protected function forward( string $controller, array $path = [], array $query = [] ) : Response
    {
        $request             = $this->request->current;
        $path['_controller'] = $controller;
        $subRequest          = $request->duplicate( $query, null, $path );

        try {
            return $this->serviceLocator( HttpKernelInterface::class )->handle(
                $subRequest,
                HttpKernelInterface::SUB_REQUEST,
            );
        }
        catch ( Exception $e ) {
            throw $this->notFoundException();
        }
    }

    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @param non-empty-string|URL $url
     * @param int                  $status [302] The HTTP status code
     *
     * @return RedirectResponse
     */
    protected function redirectResponse(
        string|URL $url,
        int        $status = 302,
    ) : RedirectResponse {

        // TODO: [route] to URL
        // TODO: Validate $url->exists - update $status
        // TODO: Log failing redirects

        if ( \is_string( $url ) ) {
            $url = new URL( $url );
        }

        // if ( ! $url->exists ) {
        //     $this->throwNotFoundException(  );
        // }

        return new RedirectResponse( $url->path, $status );
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @param string $route
     * @param array  $parameters
     * @param int    $status     The HTTP status code (302 "Found" by default)
     *
     * @return RedirectResponse
     */
    protected function redirectToRoute( string $route, array $parameters = [], int $status = 302 ) : RedirectResponse
    {
        // TODO : Log redirects

        $url = $this->serviceLocator( RouterInterface::class )->generate( $route, $parameters );

        return $this->redirectResponse( $url, $status );
    }

    /**
     * Returns a {@see JsonResponse} using the {@see SerializerInterface} if available.
     *
     * - Will use the {@see SerializerInterface} assigned to {@see ServiceContainer} by default.
     * - Pass a custom {@see SerializerInterface} as the last argument to override the default.
     * - Pass `false` to use the {@see JsonResponse} built in `json_encode`.
     *
     * @param mixed                          $data
     * @param int                            $status
     * @param array                          $headers
     * @param array                          $context
     * @param null|false|SerializerInterface $serializer
     *
     * @return JsonResponse
     */
    protected function jsonResponse(
        mixed                          $data,
        int                            $status = Response::HTTP_OK,
        array                          $headers = [],
        array                          $context = [],
        SerializerInterface|null|false $serializer = null,
    ) : JsonResponse {

        if ( false !== $serializer ) {

            $serializer ??= $this->serviceLocator( SerializerInterface::class );
            $context = \array_merge( ['json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS], $context );
            $json    = $serializer->serialize( $data, 'json', $context );

            return new JsonResponse( $json, $status, $headers, true );
        }

        return new JsonResponse( $data, $status, $headers );
    }

    /**
     * Return {@see File} object with original or customized
     *  file name and disposition header.
     *
     * @param SplFileInfo|string $file
     * @param ?string            $fileName
     * @param string             $disposition
     *
     * @return BinaryFileResponse
     */
    protected function fileResponse(
        SplFileInfo|string $file,
        ?string            $fileName = null,
        string             $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    ) : BinaryFileResponse {
        $response = new BinaryFileResponse( $file );
        $fileName ??= $response->getFile()->getFilename();

        return $response->setContentDisposition( $disposition, $fileName );
    }

    // :: End :: Response Methods ::

    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route
     * @param array  $parameters
     * @param int    $referenceType
     *
     * @return string
     *
     * @see UrlGeneratorInterface
     */
    #[Deprecated]
    protected function generateUrl( string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH ) : string
    {
        return $this->serviceLocator( RouterInterface::class )->generate( $route, $parameters, $referenceType );
    }

    // !! Exceptions ::::::

    /**
     * Returns a NotFoundHttpException.
     *
     * This will result in a 404 response code. Usage example:
     *
     *     throw $this->createNotFoundException('Page not found!');
     *
     * @param string     $message
     * @param ?Throwable $previous
     *
     * @return NotFoundHttpException
     */
    final protected function notFoundException( string $message = 'Not Found', ?Throwable $previous = null ) : NotFoundHttpException
    {
        return new NotFoundHttpException( $message, $previous );
    }

    // !! End :: Exceptions

    private function controllerResponseMethods() : void
    {
        $responseType = $this->request->isHtmx ? ContentResponse::class : DocumentResponse::class ;

        $autowire = \array_keys( $this->serviceLocator->getProvidedServices() );
        // $autowire = [
        //     Headers::class,
        //     Parameters::class,
        //     Document::class,
        // ];

        foreach ( ( new ReflectionClass( $this ) )->getMethods() as $method ) {

            if ( ! $method->getAttributes( $responseType ) ) {
                continue;
            }

            $parameters = [];

            foreach ( $method->getParameters() as $parameter ) {
                $injectableClass = $parameter->getType()->__toString();
                if ( \in_array( $injectableClass, $autowire, true ) ) {
                    $parameters[] = $this->serviceLocator->get( $injectableClass );
                }
                else {
                    // TODO : Ensure appropriate exception is thrown on missing dependencies
                    //        nullable parameters will not throw; log in [dev], ignore in [prod]
                    dump( $method );
                }
            }

            try {
                $method->invoke( $this, ...$parameters );
            }
            catch ( ReflectionException $e ) {
                Log::exception( $e );

                continue;
            }
        }
    }
}
