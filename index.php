<?php
/**
 * Index.php as example frontend for the Class
 * Author: Dirk Blanckenhorn, dirk@blanckenhorn.de
 * License: https://opensource.org/licenses/MIT
 */
use de\gis4thw\TaktischeZeichen;

include("TaktischeZeichen.php");

$img = new TaktischeZeichen("Taktische Einheit","Sprengen","blau",400);

$img->output('svg');


?>