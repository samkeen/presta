<?php
/* 
 * Utility Arr class
 */

/**
 * General purpose static utility methods
 *
 * @author samkeen
 */

class Util_Arr {

    /**
     * Safe array getter method for those of us how tire of isset(...)?...:...;
     * 
     * @param mixed $needle
     * @param array $haystack
     * @param boolean $default_return What is returned if key is not found
     * @return mixed The value at key==$needle
     */
    public static function get($needle, $haystack, $default_return=null) {
        return isset($haystack[$needle]) ? $haystack[$needle] : $default_return;
    }
}
