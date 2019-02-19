<?
/**
 * Class fkml
 * This class allows generate dynamic kml file to publish in google earth
 * Need google earth installed to view kml file
 *
 * @author pablo kogan
 *
 *
 */
class fkml {
    public $sBody;
    public $sHeader;
    public $sFooter;
    /**
     * Constructor
     */
    public function __construct($sName)
    {
        $this->sName = $sName;
        $this->sHeader = '<?xml version="1.0" encoding="UTF-8"?>';
        $this->sHeader .= '<kml xmlns="http://www.opengis.net/kml/2.2"
  xmlns:gx="http://www.google.com/kml/ext/2.2">';

        $this->sHeader .= "<Document>
                    <name>$sName</name>";
        /**
         * To change the style generate kml in google eath y paste in the next
         * line the header style.
         */
        $this->sHeader .= " ";

        $this->sFooter .= " </Document>";
        $this->sFooter .= '</kml>';
    }
    /**
     * Add element to kml file
     */
    public function addElement($sElement)
    {
        $this->sBody .= $sElement;
    }
    /**
     * Print kml, change the header to open Google earth
     */
    public function export()
    {
        header('Content-type: application/keyhole');
        //Para que modifique el nombre de lo que baja se agrega la siguiente linea.
        header('Content-Disposition:atachment; filename="' . $this->sName . '.kml"');
        //Para que cuando se baje el archivo el cliente IE y/o FireFox pregunte si bajar o guardar el archivo.
        //Hay que agregar al header Content-Disposition:atachment
        $sKml = $this->sHeader . $this->sBody . $this->sFooter;
        header('Content-Length: ' . strlen($sKml));
        header('Expires: 0');
        header('Pragma: cache');
        header('Cache-Control: private');
        echo $sKml;
    }

    public function save($filename)
    {
        $sKml = $this->sHeader . $this->sBody . $this->sFooter;
        $f = fopen($filename,"w");
        fputs($f, $sKml);
        fclose($f);
    }
    /**
     * Add point to kml file
     * @param int $lat latitude
     * @param int $lon longitude
     * @param int $alt altitude
     * @param string $tit title of point
     * @param string $des description of point
     * @param string $sLayer style of point default ''
     */

    public function addPoint($lon, $lat, $alt, $tit, $des, $sLayer = '')
    {
        $sResponse = '<Placemark>';
        $sResponse .= "<description>$des</description>";
        $sResponse .= "<name>$tit</name>";
        $sResponse .= '<visibility>1</visibility>';
        $sResponse .= "<styleUrl>#$sLayer</styleUrl>";
        $sResponse .= '<Point>';
        $sResponse .= "<coordinates>$lon,$lat,$alt</coordinates>";
        $sResponse .= '</Point>';
        $sResponse .= '</Placemark>';
        $this->addElement($sResponse);
    }
    /**
     * Add line to kml file
     * @param array $puntos   Points of line array of array('lat'=>num,'lon'=>num,'alt'=num)
     * @param string $tit     Title of line
     * @param string $des     Description of line
     * @param string $sLayer  Style of line default ''
     */
    public function addLine($puntos, $tit, $des = '', $sLayer = '')
    {
        $sResponse = "<Placemark>";
        $sResponse .= "<name>$tit</name>";
        $sResponse .= "<description>$des</description>";
        $sResponse .= "<styleUrl>#$sLayer</styleUrl>";
        $sResponse .= "<LineString>";
        $sResponse .= "<tessellate>1</tessellate>";
        $sResponse .= "<coordinates>";
        $first = TRUE;
        foreach ($puntos as $punto)
        {
            if ($first)
            {
                $sResponse .= $punto['lon'] . "," . $punto['lat'] . "," . $punto['alt'];
                $first = FALSE;
            }
            else
            {
                $sResponse .= " " . $punto['lon'] . "," . $punto['lat'] . "," . $punto['alt'];
            }
        }
        $sResponse .= "</coordinates>";
        $sResponse .= "</LineString>";
        $sResponse .= "</Placemark>";
        $this->addElement($sResponse);
    }
    /**
     * Add Polygon
     * @param array   $puntos  Points of polygon array of array('lat'=>num,'lon'=>num,'alt'=num)
     * @param string  $tit     Title of polygon
     * @param string  $des     Description of polygon
     * @param string  $sLayer  Style of polygon default ''
    */
    public function addPolygon($puntos, $tit, $des = '', $sLayer = '')
    {
        $sResponse = "<Placemark>";
        $sResponse .= "<name>$tit</name>";
        $sResponse .= "<styleUrl>#$sLayer</styleUrl>";
        $sResponse .= "<Polygon>";
        $sResponse .= "<tessellate>1</tessellate>";
        $sResponse .= "<outerBoundaryIs>
                                    <LinearRing>
                                        <coordinates>
                    ";
        $first = TRUE;
        foreach ($puntos as $punto)
        {
            if ($first)
            {
                $sResponse .= $punto['lon'] . "," . $punto['lat'] . "," . $punto['alt'];
                $first = FALSE;
            }
            else
            {
                $sResponse .= " " . $punto['lon'] . "," . $punto['lat'] . "," . $punto['alt'];
            }
        }
        $sResponse .= "</coordinates>
                                  </LinearRing>
                                </outerBoundaryIs>
                            </Polygon>
                        </Placemark>
                    ";
        $this->addElement($sResponse);
    }
    /**
     * Add Link
     * @param string $link link to file
     * @param string $tit title of link
     * @param string $sLayer style of link default ''
    */
    public function addLink($link, $tit)
    {
        $aScript = explode('/', $_SERVER[SCRIPT_NAME]);
        array_pop($aScript);
        $sScript = implode('/', $aScript);
        $sLink = "http://" . $_SERVER[SERVER_NAME] . "/" . $sScript . "/$link";
        $sResponse = "<NetworkLink>";
        $sResponse .= "<name>$tit</name>";
        $sResponse .= "<Url>
                    <href>$sLink</href>
                    <refreshMode>onInterval</refreshMode>
                    <viewRefreshMode>onRequest</viewRefreshMode>
                </Url>
                </NetworkLink>";
        //echo $sResponse;
        $this->addElement($sResponse);
    }
    /**
     * Add SreenOverlay
     * @param string $link link to logo file
     * @param string $tit title of logo
    */
    public function addScreenOverlay($link, $tit)
    {
        $aScript = explode('/', $_SERVER[SCRIPT_NAME]);
        array_pop($aScript);
        $sScript = implode('/', $aScript);
        $sLink = "http://" . $_SERVER[SERVER_NAME] . "/" . $sScript . "/$link";
        $sResponse = "<ScreenOverlay>";
        $sResponse .= "<name>$tit</name>";
        $sResponse .= "<Icon>
                    <href>$sLink</href>
                </Icon>
    <overlayXY x=\"1\" y=\"1\" xunits=\"fraction\" yunits=\"fraction\"/>
    <screenXY x=\"1\" y=\"1\" xunits=\"fraction\" yunits=\"fraction\"/>
    <rotationXY x=\"0\" y=\"0\" xunits=\"fraction\" yunits=\"fraction\"/>
    <size x=\"0.1\" y=\"0.1\" xunits=\"fraction\" yunits=\"fraction\"/>
    </ScreenOverlay>";
        //echo $sResponse;
        $this->addElement($sResponse);
    }

    public function addTour($name, $nodes)
    {
        $sResponse = '<open>1</open>
        <gx:Tour>
    <name>'.$name.'</name>
    <gx:Playlist>
    ';
        // Tour steps
        foreach ($nodes as $node_data) {
            $sResponse .= '
            <gx:FlyTo>
        <gx:duration>6.0</gx:duration>
        <Camera>
          <longitude>'.$node_data['lng'].'</longitude>
          <latitude>'.$node_data['lat'].'</latitude>
          <altitude>28275</altitude>
          <heading>-4.921</heading>
          <altitudeMode>absolute</altitudeMode>
        </Camera>
      </gx:FlyTo>

      <gx:AnimatedUpdate>
        <gx:duration>0.0</gx:duration>
        <Update>
          <targetHref/>
          <Change>
            <Placemark targetId="pin'.$node_data['hostname'].'">
              <gx:balloonVisibility>1</gx:balloonVisibility>
            </Placemark>
          </Change>
        </Update>
      </gx:AnimatedUpdate>

      <gx:Wait>
        <gx:duration>2.0</gx:duration>
      </gx:Wait>

      <gx:AnimatedUpdate>
        <gx:duration>0.0</gx:duration>
        <Update>
          <targetHref/>
          <Change>
            <Placemark targetId="pin'.$node_data['hostname'].'">
              <gx:balloonVisibility>0</gx:balloonVisibility>
            </Placemark>
          </Change>
        </Update>
      </gx:AnimatedUpdate>
      ';
        }

    $sResponse .= '</gx:Playlist>
  </gx:Tour>
  ';
      // Define here the CSS Styles
        $sResponse .= '<Folder>
        <name>Points and polygons</name>
    <Style id="pushpin">
      <IconStyle>
        <Icon>
          <href>http://maps.google.com/mapfiles/kml/pushpin/ylw-pushpin.png</href>
        </Icon>
      </IconStyle>
      <BalloonStyle>
      <!-- styling of the balloon text -->
      <text><![CDATA[
      <b><font face="arial">$[name]</font></b>
      <br/><br/>
      <font face="arial">$[description]</font>
      <br/><br/>
      <!-- insert the to/from hyperlinks -->
      $[geDirections]
      ]]></text>
      </BalloonStyle>
    </Style>
        ';

        // Baloons content
        foreach ($nodes as $node_data) {
            $sResponse .= '<Placemark id="pin'.$node_data['hostname'].'">
      <name>'.$node_data['hostname'].' ('.$node_data['city'].', '.$node_data['country'].')</name>
      <description>

      </description>
      <styleUrl>pushpin</styleUrl>
      <Point>
        <coordinates>'.$node_data['lng'].','.$node_data['lat'].',0</coordinates>
      </Point>
    </Placemark>
            ';
        }
        $sResponse .= '</Folder>
        ';
        $this->addElement($sResponse);
    }
}
?>