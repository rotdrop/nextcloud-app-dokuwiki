<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit2fd47f198c46be8c9372db8dd54d7d32
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PhpXmlRpc\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PhpXmlRpc\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpxmlrpc/phpxmlrpc/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit2fd47f198c46be8c9372db8dd54d7d32::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit2fd47f198c46be8c9372db8dd54d7d32::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit2fd47f198c46be8c9372db8dd54d7d32::$classMap;

        }, null, ClassLoader::class);
    }
}