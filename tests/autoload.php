<?php
require dirname(__DIR__) . '/src/autoload.php';

spl_autoload_register(
   function($class) {
      static $classes = null;
      if ($classes === null) {
         $classes = array(
            'presta_curlertest' => '/CurlerTest.php',
                'presta_responsetest' => '/ResponseTest.php',
                'prestatestbase' => '/PrestaTestBase.php',
                'requesttest' => '/RequestTest.php',
                'util_arrtest' => '/Util_ArrTest.php'
          );
      }
      $cn = strtolower($class);
      if (isset($classes[$cn])) {
         require __DIR__ . $classes[$cn];
      }
   }
);