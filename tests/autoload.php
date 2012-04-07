<?php
require dirname(__DIR__) . '/src/autoload.php';

spl_autoload_register(
   function($class) {
      static $classes = null;
      if ($classes === null) {
         $classes = array(
            'presta\\prestatestbase' => '/PrestaTestBase.php',
                'presta\\requesttest' => '/RequestTest.php',
                'presta\\responsetest' => '/ResponseTest.php',
                'presta\\util\\arrtest' => '/util/ArrTest.php'
          );
      }
      $cn = strtolower($class);
      if (isset($classes[$cn])) {
         require __DIR__ . $classes[$cn];
      }
   }
);