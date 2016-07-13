<?php
/**
 * Created by PhpStorm.
 * User: dirk
 * Date: 12.07.2016
 * Time: 13:29
 */

namespace de\gis4thw;
use Exception;
use SimpleXMLElement;

/*
 * System der Taktischen Zeichen:
 * 1.) Art des Grundzeichens (Stelle, Person, Gebiet etc.), Pflicht
 * 2.) Füllfarbe (rot, blau, weiß, gelb, grün, orange), Pflicht
 * 3.) Kurzbezeichnung der Organisation rechts unten, Eigentlich optional, hier Pflicht
 * 4.) Symbol der Fachaufgabe innen, Optional
 * 5.) Funktion, Typ der Einheit innen links oben, optional
 * 6.) Größenordnung (Zug, Trupp, Verband) oben, optional
 * 7.) Zeitangaben links, optional
 * 8.) Beweglichkeit, Richtung, Mannschaftsstärke unten, optional
 * 9.) Herkunft und Gliederung rechts, Optional
 *
 *
 *
 *
 */


/**
 * Class tzNode
 * @property \SimpleXMLElement[] grundzeichen
 * @package de\gis4thw
 */
class tzXmlElement extends SimpleXMLElement {
    /**
     * @param $basic_sign
     * @return mixed
     */
    public function get_element_by_basic_sign_id($basic_sign)
    {
        return $this->xpath("grundzeichen/element/bedeutungen[bedeutung='".$basic_sign."']/parent::*");
    }

}

class TaktischeZeichen
{
    const BORDER_WIDTH = 10;
    const LINE_WIDTH = 10;
    const CONFIG_FILENAME = "tz.xml";

    protected $width, $height;

    private $basic_sign_id;
    private $basecolour_id;
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
     */
    function __construct($basic_sign, $basecolour, $targetwidth) {
        if(file_exists($this::CONFIG_FILENAME)) {

            $xml = new tzXmlElement($this::CONFIG_FILENAME,0,TRUE);
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
           //echo("<pre>");

            //print_r($xml->xpath("farbgebung/element[@name='".$basecolour."']/@base-colour")[0]->__toString());
            //die();
        }
        else
        {
            die('Fehler: Konnte Steuerdatei '.$this::CONFIG_FILENAME.' nicht laden.');
        }
        return $this;
    }

    function create($basic_sign_id = 1, $basecolour_id = 2, $width = 200) {
        if($basic_sign_id==0) $basic_sign_id=1;
        if($basecolour_id==0) $basecolour_id=2;
        $this->basic_sign_id = $basic_sign_id;
        $this->basecolour_id = $basecolour_id;
        $this->basecolour = TaktischeZeichen::$system[2][$basecolour_id]['base-colour'];
        $this->linecolour = TaktischeZeichen::$system[2][$basecolour_id]['line-colour'];
        $this->bordercolour = TaktischeZeichen::$system[2][$basecolour_id]['border-colour'];
        $this->width = $width;
        $this->ratio = TaktischeZeichen::$system[1][$basic_sign_id]['ratio'];
        $this->height = round($width/$this->ratio);
        $this->svg = $this->convert_svg_template(TaktischeZeichen::$system[1][$basic_sign_id]['svg_template']);
        return $this;
    }

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
            exit("<br>Fehler: Kein SVG an Output übergeben");
        }
    }


}

