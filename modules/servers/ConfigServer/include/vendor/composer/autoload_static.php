<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite501d89c3a0bbec5b7b44cc06a1c38c7
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'ConfigServer\\' => 13,
            'ConfigServerUI\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ConfigServer\\' => 
        array (
            0 => __DIR__ . '/../..' . '/ConfigServer',
        ),
        'ConfigServerUI\\' => 
        array (
            0 => __DIR__ . '/../..' . '/../../../addons/ConfigServer/include/ConfigServerUI',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite501d89c3a0bbec5b7b44cc06a1c38c7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite501d89c3a0bbec5b7b44cc06a1c38c7::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
