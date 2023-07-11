<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitec6eb406c68fc9f829dd3379b64ed86f
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInitec6eb406c68fc9f829dd3379b64ed86f', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitec6eb406c68fc9f829dd3379b64ed86f', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitec6eb406c68fc9f829dd3379b64ed86f::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
