<?php

/*
 * MinimumsMet Class
 */

/**
 * Use once method to see if the PHP env supports the minimum requirements for
 * this Library
 *
 * @author samkeen
 */
class Presta_Requirements {
    
    const MIN_PHP_VERSION = '5.1.3';

    /**
     * If you are not sure what PHP calls your extention, run this:
     * <?php print_r(get_loaded_extensions()); ?>
     */
    private static $required_extensions = array('curl');

    /**
     * Checks if we have the minimum requirements for this library.
     * 
     * @return boolean Returns true is minimums are met, else throws exception
     */
    public static function check()
    {
        $loaded_extensions = array_map('strtolower',get_loaded_extensions());
        self::$required_extensions = array_map('strtolower',  self::$required_extensions);
        foreach (self::$required_extensions as $required_ext)
        {
            if (!in_array($required_ext, $loaded_extensions))
            {
                throw new Exception("Required PHP extention [{$required_ext}] not loaded");
            }
        }

        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION) < 0)
        {
            throw new Exception("min PHP version required: " . self::MIN_PHP_VERSION);
        }
        return true;
    }

}