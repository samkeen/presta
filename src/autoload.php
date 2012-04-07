<?php
spl_autoload_register(
   function($class) {
      static $classes = null;
      if ($classes === null) {
         $classes = array(
            'presta_curler' => '/Curler.php',
                'presta_request' => '/Request.php',
                'presta_response' => '/Response.php',
                'util_arr' => '/util/Arr.php'
          );
      }
      $cn = strtolower($class);
      if (isset($classes[$cn])) {
         require __DIR__ . $classes[$cn];
      }
   }
);