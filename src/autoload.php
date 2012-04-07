<?php
spl_autoload_register(
   function($class) {
      static $classes = null;
      if ($classes === null) {
         $classes = array(
            'presta\\curler' => '/Curler.php',
                'presta\\request' => '/Request.php',
                'presta\\response' => '/Response.php',
                'presta\\util\\arr' => '/util/Arr.php'
          );
      }
      $cn = strtolower($class);
      if (isset($classes[$cn])) {
         require __DIR__ . $classes[$cn];
      }
   }
);