<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require "lib/presta/Requirements.php";
$mm = new Presta_Requirements();
echo $mm->check();