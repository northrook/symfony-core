<?php

declare(strict_types=1);

namespace Core\DependencyInjection\Compiler;

use Override;
use Northrook\ArrayStore;
use Northrook\Exception\CompileException;
use Northrook\Resource\Path;
use Symfony\Component\DependencyInjection\{ContainerBuilder};
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class AssetManifestPass implements CompilerPassInterface
{
    private ArrayStore $inventory;

    public function __construct( private ParameterBagInterface $parameterBag ) {}

    #[Override]
    public function process( ContainerBuilder $container ) : void
    {
        $this->inventory = new ArrayStore( $this->parameterBag->get( 'path.asset_inventory' ) );

        $this->inventory->clear();
        $this->initializeManifestInventory();
    }

    private function initializeManifestInventory() : void
    {
        $appAssets  = $this->parameterBag->get( 'dir.assets' );
        $coreAssets = $this->parameterBag->get( 'dir.core.assets' );

        $this->getAssetGroup( 'core', 'style', \glob( "{$appAssets}\styles\*.css" ) );
        $this->getAssetGroup( 'core', 'script', \glob( "{$appAssets}\scripts\*.js" ) );
        $this->getAssetGroup( 'admin', 'style', \glob( "{$appAssets}\styles\admin\*.css" ) );
    }

    private function getAssetGroup( string $label, string $type, array $glob ) : void
    {
        $inventory = [];

        foreach ( $glob as $path ) {
            $asset = new Path( $path );

            if ( ! $asset->isReadable ) {
                throw new CompileException( "The asset at '{$asset->path}' is not readable.'" );
            }

            $basename = \strrchr( $asset->basename, '.', true );

            $inventory["{$label}.{$type}.{$basename}"] = $asset->path;
        }

        $this->inventory->set( $inventory );
    }
}