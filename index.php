<?php
/**
 * Created by PhpStorm.
 * User: dirk
 * Date: 12.07.2016
 * Time: 13:39
 */
//echo phpinfo();
include("TaktischeZeichen.php");

$img = new \de\gis4thw\TaktischeZeichen("Person","grün",20);
//echo $img->get_id_from_grundzeichen("Person");
//echo "<br>";
//echo $img->get_ratio_from_grundzeichen("Person");
//echo "<br>";
//echo $img->get_id_from_grundfarbe("orange");

$img->output('svg');


?>