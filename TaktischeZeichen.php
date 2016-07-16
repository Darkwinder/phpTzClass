<?php
/**
 * Class phpTzClass.
 * Author: Dirk Blanckenhorn, dirk@blanckenhorn.de
 * License: https://opensource.org/licenses/MIT
 *
 * Version: 0.0.1
 *
 * Requirements: Installed PHP-Extension ImageMagick for every image format except SVG itself
 *
 * Usage:
 * $img = new TaktischeZeichen("Person","grün",200);
 * First Value can be: [[Taktische Formation|Taktische Einheit|Taktischer Verband|Dienststelle]|[Befehlsstelle]|[Stelle|Einrichtung]|[Person]|[Maßnahme]|[Anlass|Ereignis]|[Gefahr]|[ortsgebunden|ortsfest]|[Gebäude]]
 * Second Value can be: [rot|blau|weiß|gelb|grün|orange]
 * Third Value is the desired width in pixel. Each sign has a separate maximum value for that - see attribute "width" in node "grundzeichen/element" at file tz.xml included in this package
 * $img->output('svg'); // can be "svg", "jpg", "jpeg", "png", "gif", default is "svg"
 *
 * Examples:
 * Police officer:
 * $img = new TaktischeZeichen("Person","grün",200); $img->output('svg');
 * Fire Rescue Unit:
 * $img = new TaktischeZeichen("Taktische Einheit","rot",100); $img->output('svg');
 *
 * TODO : Adding additional structures within the signs and around of them as described in THW-DV1-102
 */

namespace de\gis4thw;
use Exception;
use Imagick;
use SimpleXMLElement;
use DOMDocument;


class svgDOMDocument extends DOMDocument
{
    /**
     * svgDOMDocument constructor.
     * @param int $height
     * @param int $width
     * @param string $version
     * @param string $encoding
     */
    function __construct($height = 0, $width = 0, $version='1.0', $encoding='UTF-8') {
        parent::__construct($version, $encoding);
        $this->xmlStandalone=false;
        $dom=$this->createElement('svg');
        $this->appendChild($dom);
        $dom->setAttribute("version","1.1");
        $dom->setAttribute("xmlns","http://www.w3.org/2000/svg");
        $dom->setAttribute("width",$width);
        $dom->setAttribute("height",$height);
    }

    /**Function scale scales every scaleable attribute in the whole document
     * @param $factor
     * @return $this
     */
    function scale($factor)
    {
        $attributesToScale=array("width","height","stroke-width","x","y","x1","y1","x2","y2","rx","ry","cx","cy","r","d","points"); // SVG attributes containing pixel values to be scaled
        foreach($attributesToScale as $attribute) {
            $svgNode = $this->getElementsByTagName("*");  // You can filter, if needed - but you don't need
            foreach ($svgNode as $node) {
                if ($node->hasAttribute($attribute)) {
                    if($attribute!="d" && $attribute!="points") {  // If its not that complicated ...
                        $value = intval($node->getAttribute($attribute));
                        if ($value > 0) {
                            $node->setAttribute($attribute, round($value * $factor,0));
                        }
                    }
                    else  // If its getting complicated due to scaleable comma separated values in one string
                    {
                        $string = (string) $node->getAttribute($attribute);
                        $new_attribute=""; // The string with the new values multiplied by factor $scalefactor
                        // Example:  d="M94,169 L195,69 L195,27 L236,27 L236,69 L195,69", points="350,75  379,161 469,161 397,215 423,301 350,250 277,301 303,215 231,161 321,161"
                        $d=explode(" ",trim($string));
                        foreach($d as $block)
                        {
                            $block=trim($block);
                            //echo $block;
                            if(!is_numeric(substr($block,0,1)))
                            {
                                $new_attribute.=substr($block,0,1); // take the prefix
                                $block=substr(trim($block),1); // take just the rest
                            }
                            if(strlen($block)>0) {
                                $coords = explode(",", $block);
                                if (count($coords) != 2) {
                                    throw new \InvalidArgumentException("Fehler: Es dürfen in den Attributen d und point nur Koordinaten im 2D-System angegeben werden - unzulässiges Komma gefunden (z.B. 100,200,300):\"" . $string."\"" );
                                } else {
                                    $new_attribute .= intval(intval($coords[0]) * $factor) . "," . intval(intval($coords[1]) * $factor) . " "; // take new value
                                }
                            }
                        }
                        $node->setAttribute($attribute,trim($new_attribute));
                    }
                }
            }
        }
        return $this;
    }
}


/**
 * Class TaktischeZeichen
 * @package de\gis4thw
 */
class TaktischeZeichen
{
    const CONFIG_FILENAME = "tz.xml";
    public  $width, $height;

    private $svg;
    private $ratio;
    private $basecolour;
    private $bordercolour;
    private $linecolour;

    /**
     * TaktischeZeichen constructor.
     * @param $basic_sign : String with the name of the basic sign element
     * @param $fachaufgabe : Specific Mission
     * @param $basecolour : Colour to be filled with
     * @param $targetwidth : Desired width of the image in pixel - the height is calculated dynamically through the ratio
     * @throws Exception
     * @internal param $width
     */
    function __construct($basic_sign, $fachaufgabe, $basecolour, $targetwidth) {
        $this->transparent=true;
        if(file_exists($this::CONFIG_FILENAME)) {
            $xml = new simpleXmlElement($this::CONFIG_FILENAME,0,TRUE);
            $this->base_sign_id = $xml->xpath("grundzeichen/element/bedeutungen[bedeutung='".$basic_sign."']/../@nr")[0]->__toString();
            $this->width = $xml->xpath("grundzeichen/element/bedeutungen[bedeutung='".$basic_sign."']/../@width")[0]->__toString();
            $this->height = $xml->xpath("grundzeichen/element/bedeutungen[bedeutung='".$basic_sign."']/../@height")[0]->__toString();
            $this->ratio = round($this->width/$this->height,3);
            $this->scalefactor = $targetwidth/$this->width;
            $this->basecolour = $xml->xpath("farbgebung/element[@name='".$basecolour."']/@base-colour")[0]->__toString();
            $this->bordercolour = $xml->xpath("farbgebung/element[@name='".$basecolour."']/@border-colour")[0]->__toString();
            $this->linecolour = $xml->xpath("farbgebung/element[@name='".$basecolour."']/@line-colour")[0]->__toString();
            $this->organisation = $xml->xpath("farbgebung/element[@name='".$basecolour."']/@organisation")[0]->__toString();
            $this->svgdom = new svgDOMDocument($this->height,$this->width);
            $svg="";
            foreach($xml->xpath("grundzeichen/element/bedeutungen[bedeutung='".$basic_sign."']/../svg/*") as $svgbasicsign) { $svg .= $svgbasicsign->asXML(); }
            foreach($xml->xpath("fachaufgaben/element/bedeutungen[bedeutung='".$fachaufgabe."']/../svg/*") as $svgfachaufgabe) { $svg .= $svgfachaufgabe->asXML(); }
            $svg = str_replace("fill=\"\"","fill=\"".$this->basecolour."\"",$svg);
            $svg = str_replace("stroke=\"\"","stroke=\"".$this->bordercolour."\"",$svg);
            $this->svgelement=$this->svgdom->createDocumentFragment(); // create nothing
            $this->svgelement->appendXML($svg); // Insert svg-element from config
            $this->svgdom->getElementsByTagName('svg')->item(0)->appendChild($this->svgelement); // Insert into first occurance of svg
            $this->svgdom->scale($this->scalefactor);
            //print_r($this->svg=$this->svgdom->saveXML());
            $this->svg=$this->svgdom->saveXML();
        }
        else
        {
            throw new Exception('Error: Configuration File '.$this::CONFIG_FILENAME.' not found.');
        }
        return $this;
    }

    function array_map_recursive(callable $func, array $array) {
        return filter_var($array, \FILTER_CALLBACK, ['options' => $func]);
    }

    /**Output of the generated Image file
     * @param string $output_Format (possible values are: "svg", "jpg", "jpeg", "png", "gif")
     * @throws Exception
     */
    function output($output_Format = "svg")
    {
        if($this->svg) {
            $im = new \Imagick();
            if($this->transparent && $output_Format=="png") $im->setBackgroundColor(new \ImagickPixel('transparent'));
            $im->readImageBlob($this->svg);
            switch (strtolower($output_Format)) {
                case 'gif':
                    $im->setImageFormat("gif");
                    $im->adaptiveResizeImage($this->width*$this->scalefactor, $this->height*$this->scalefactor);
                    $im->stripImage();
                    header('Content-type: image/gif');
                    echo $im;
                    $im->destroy();
                    break;
                case 'jpeg':
                case 'jpg':
                    $im->setImageFormat("jpeg");
                    $im->adaptiveResizeImage($this->width*$this->scalefactor, $this->height*$this->scalefactor);
                    $im->setImageCompressionQuality(80);
                    $im->stripImage();
                    header('Content-type: image/jpeg');
                    echo $im;
                    $im->destroy();
                    break;
                case 'png':
                    $im->setImageFormat("png32");
                    $im->resizeImage($this->width*$this->scalefactor, $this->height*$this->scalefactor, Imagick::FILTER_LANCZOS, 1);
                    $im->stripImage();
                    header('Content-type: image/png');
                    echo $im;
                    $im->destroy();
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

