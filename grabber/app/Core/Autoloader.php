<?php
/**
 * Loader for Grabber.
 *
 * @package Grabber
 */

class Grabber_AutoLoader
{
    private static $paths         = array();
    private static $namespaces     = array();

    public static function spl_register($dir = '')
    {
        spl_autoload_register(array( 'Grabber_AutoLoader', 'load_class' ), false);

        spl_autoload_register(array( 'Grabber_AutoLoader', 'load_class_from_namespace' ), false);

        Grabber_AutoLoader::register_namespace($dir.'app', 'Grabber');
    }

    public static function register_namespace($dirName, $namespace)
    {
        Grabber_AutoLoader::$namespaces[] = array(
            'path'     => $dirName,
            'name'     => $namespace,
        );
    }

    protected static function get_class_relative_path($class)
    {
        $class = str_replace('..', '', $class);
        if (strpos($class, '_') !== false) {
            $class = str_replace('_', DIRECTORY_SEPARATOR, $class);
        } else {
            $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        }

        return $class;
    }

    public static function load_class_from_namespace($class)
    {
        $class = self::get_class_relative_path($class);

        foreach (Grabber_AutoLoader::$namespaces as $namespace) {
            if (strpos($class, $namespace[ 'name' ]) !== false) {
                $relative_path     = str_replace($namespace[ 'name' ], '', $class);
                $relative_path     = trim($relative_path, DIRECTORY_SEPARATOR);
                $file             = $namespace[ 'path' ].DIRECTORY_SEPARATOR.$relative_path.'.php';
                if (file_exists($file)) {
                    require_once $file;
                    break;
                }
            }
        }
    }

    public static function register_directory($dirName)
    {
        Grabber_AutoLoader::$paths[] = $dirName;
        set_include_path(
        implode(
        PATH_SEPARATOR, array(
            realpath($dirName),
            get_include_path(),
        )
        )
        );
    }

    public static function load_class($class)
    {
        $class = self::get_class_relative_path($class);

        foreach (Grabber_AutoLoader::$paths as $path) {
            $file = $path.DIRECTORY_SEPARATOR.$class.'.php';
            if (file_exists($file)) {
                require_once $file;
                break;
            }
        }
    }
}
