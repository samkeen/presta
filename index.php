<?php
/* 
 * Simple setup file, Runs requirements check.  Will add more detail later 
 */
require "lib/presta/Requirements.php";
$mm = new Presta_Requirements();
echo $mm->check();
