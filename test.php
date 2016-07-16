<?php
/**
 * Index.php as example frontend for the Class
 * Author: Dirk Blanckenhorn, dirk@blanckenhorn.de
 * License: https://opensource.org/licenses/MIT
 */
use de\gis4thw\TaktischeZeichen;

include("TaktischeZeichen.php");

$img = new TaktischeZeichen($_GET["basic_sign"],$_GET["specialized_task"],$_GET["basecolour"],$_GET["targetwidth"]);

$img->output('svg');


?>