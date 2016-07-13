<?php
/**
 * Class phpTzClass.
 * Author: Dirk Blanckenhorn, dirk@blanckenhorn.de
 * License: https://opensource.org/licenses/MIT
 *
 * Version: 0.0.1
 *
 * Usage:
 * $img = new TaktischeZeichen("Person","grün",200);
 * First Value can be: [[Taktische Formation|Taktische Einheit|Taktischer Verband|Dienststelle]|[Befehlsstelle]|[Stelle|Einrichtung]|[Person]|[Maßnahme]|[Anlass|Ereignis]|[Gefahr]|[ortsgebunden|ortsfest]|[Gebäude]]
 * Second Value can be: [rot|blau|weiß|gelb|grün|orange]
 * Third Value is the desired width in pixel. Each sign has a separate maximum value for that - see attribute "width" in node "grundzeichen/element" at file tz.xml included in this package
 * $img->output('svg'); // can be "svg", "jpg", "jpeg", "png", "gif", default is "svg"
 *
 * Examples:
 * Policeofficer:
 * $img = new TaktischeZeichen("Person","grün",200); $img->output('svg');
 * Fire Rescure Unit:
 * $img = new TaktischeZeichen("Taktische Einheit","rot",100); $img->output('svg');
 *
 * TODO : Adding additional structures within the signs and around of them as described in THW-DV1-102
 */

namespace de\gis4thw;
use Exception;
use SimpleXMLElement;

/**
 * Class TaktischeZeichen
 * @package de\gis4thw
 */
class TaktischeZeichen
{
    const BORDER_WIDTH = 10;
    const LINE_WIDTH = 10;
    const CONFIG_FILENAME = "tz.xml";

    protected $width, $height;

    private $svg;
    private $ratio;
    private $basecolour;
    private $bordercolour;
    private $linecolour;


    function draw_svg($shape = "rect", $startx = 0, $starty = 0, $max_width = 0, $max_height = 0, $width = "", $height = "", $fill = "#FFFFFF", $border_width = 0, $border_colour = "#000000")
    {
        if(substr($width,-1,1)=="%" && intval($max_width)>0) { $width=intval($width)/100*$max_width;} else { $width=intval($width); }
        if(substr($height,-1,1)=="%" && intval($max_height)>0) { $height=intval($height)/100*$max_height;} else { $height=intval($height); }
        return "<".$shape." x=\"".$startx."\" y=\"".$starty."\" width=\"".$width."\" height=\"".$height."\" fill=\"".$fill."\" stroke-width=\"".$border_width."\" stroke=\"".$border_colour."\" />";
    }

    /**
     * TaktischeZeichen constructor.
     * @param $basic_sign : String with the name of the basic sign element
     * @param $basecolour : Colour to be filled with
     * @param $targetwidth : Desired width of the image in pixel - the height is calculated dynamically through the ratio
     * @internal param $width
     * @throws Exception
     */
    function __construct($basic_sign, $basecolour, $targetwidth) {
        if(file_exists($this::CONFIG_FILENAME)) {

            $xml = new simpleXmlElement($this::CONFIG_FILENAME,0,TRUE);
            $this->base_sign_id = $xml->xpath("grundzeichen/element/bedeutungen[bedeutung='".$basic_sign."']/../@nr")[0]->__toString();
            $this->width = $xml->xpath("grundzeichen/element/bedeutungen[bedeutung='".$basic_sign."']/../@width")[0]->__toString();
            $this->height = $xml->xpath("grundzeichen/element/bedeutungen[bedeutung='".$basic_sign."']/../@height")[0]->__toString();
            if($this->width<$targetwidth) $targetwidth=$this->width; // Be sure the width is not bigger than the original
            $this->ratio = round($this->width/$this->height,3);
            $viewBox_width = round($this->width/$targetwidth*$this->width,0);
            $viewBox_height = round($viewBox_width/$this->ratio,0);
            $this->basecolour = $xml->xpath("farbgebung/element[@name='".$basecolour."']/@base-colour")[0]->__toString();
            $this->bordercolour = $xml->xpath("farbgebung/element[@name='".$basecolour."']/@border-colour")[0]->__toString();
            $this->linecolour = $xml->xpath("farbgebung/element[@name='".$basecolour."']/@line-colour")[0]->__toString();
            $this->organisation = $xml->xpath("farbgebung/element[@name='".$basecolour."']/@organisation")[0]->__toString();
            $this->svg = "<?xml version=\"1.0\" standalone=\"no\"?>";
            $this->svg .= "<svg viewBox=\"0 0 ".$viewBox_width." ".$viewBox_height."\" width=\"".$this->width."\" height=\"".$this->height."\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\">";
            $this->svg .= $xml->xpath("grundzeichen/element/bedeutungen[bedeutung='".$basic_sign."']/../svg/*")[0]->asXML();
            $this->svg .= "</svg>";
            $this->svg = str_replace("fill=\"white\"","fill=\"".$this->basecolour."\"",$this->svg);
            $this->svg = str_replace("stroke=\"black\"","stroke=\"".$this->bordercolour."\"",$this->svg);
            $this->svg = str_replace("stroke-width=\"10\"","stroke-width=\"".$this::BORDER_WIDTH."\"",$this->svg);
        }
        else
        {
            throw new Exception('Error: Configuration File '.$this::CONFIG_FILENAME.' not found.');
        }
        return $this;
    }

    /**Output of the generated Image file
     * @param string $output_Format (possible values are: "svg", "jpg", "jpeg", "png", "gif")
     * @throws Exception
     */
    function output($output_Format = "svg")
    {
        if($this->svg) {
            $im = new \Imagick();
            $im->readImageBlob($this->svg);
            switch (strtolower($output_Format)) {
                case 'gif':
                    $im->setImageFormat("gif");
                    $im->adaptiveResizeImage($this->width, $this->height);
                    $im->stripImage();
                    header('Content-type: image/gif');
                    echo $im;
                    break;
                case 'jpeg':
                case 'jpg':
                    $im->setImageFormat("jpeg");
                    $im->adaptiveResizeImage($this->width, $this->height);
                    $im->setImageCompressionQuality(80);
                    $im->stripImage();
                    header('Content-type: image/jpeg');
                    echo $im;
                    break;
                case 'png':
                    $im->setImageFormat("png");
                    $im->adaptiveResizeImage($this->width, $this->height);
                    $im->stripImage();
                    header('Content-type: image/png');
                    echo $im;
                    break;
                case 'svg':
                    header('Content-type: image/svg+xml');
                    echo($this->svg);
                    break;
                default:
                    throw new Exception('Unsupported image format. Supportet formats are: gif, jpg, jpeg, png, svg');
                    break;
            }
        }
        else
        {
            throw new Exception('No SVG data in output routine');
        }
    }


}

