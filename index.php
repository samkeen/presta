<?php
/* 
 * Simple setup file, Runs requirements check.  Will add more detail later 
 */
echo "Welcome to Presta, running the requirements check...\n";

require "lib/presta/Requirements.php";
$mm = new Presta_Requirements();
echo $mm->check();
