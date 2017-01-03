<?

class Awido extends IPSModule
{
  /**
   * (bekannte) Client IDs - Array
   *
   * @access private
   * @var array Key ist die clientID, Value ist der Name
   */
  static $Clients = array(
    "awld"              => "Lahn-Dill-Kreis",
    "awb-ak"            => "Landkreis Altenkirchen",
    "awb-duerkheim"     => "Landkreis Bad D�rkheim",
    "wgv"               => "Landkreis Bad T�lz-Wolfratshausen",
    "awv-nordschwaben"  => "Landkreis Dillingen a.d. Donau und Donau-Ries",
    "Erding"            => "Landkreis Erding",
    "kaw-guenzburg"     => "Landkreis G�nzburg",
    "azv-hef-rof"       => "Landkreis Hersfeld-Rotenburg",
    "kelheim"           => "Landkreis Kelheim",
    "landkreisbetriebe" => "Landkreis Neuburg-Schrobenhausen",
    "eww-suew"          => "Landkreis S�dliche Weinstra�e",
    "lra-dah"           => "Landratsamt Dachau",
    "neustadt"          => "Neustadt a.d. Waldnaab",
    "rmk"               => "Rems-Murr-Kreis",
    "memmingen"         => "Stadt Memmingen"
    //"???"             => "Landratsamt Aichach-Friedberg"
  );

  /**
   * Create.
   *
   * @access public
   */
  public function Create()
  {
    //Never delete this line!
    parent::Create();

    $this->RegisterPropertyString("clientID", "null");
    // Places
    $this->RegisterPropertyString("placeGUID", "null");
    // Street
    $this->RegisterPropertyString("streetGUID", "null");
    // Addon
    $this->RegisterPropertyString("addonGUID", "null");
    // Fraction

    // Update daily timer
    $this->RegisterTimer("UpdateTimer",0,"AWIDO_Update(\$_IPS['TARGET']);");
  }

  /**
   * Configuration Form.
   *
   * @access public
   * @return JSON configuration string.
   */
  public function GetConfigurationForm()
  {
    $clientId = $this->ReadPropertyString("clientID");
    $placeId  = $this->ReadPropertyString("placeGUID");
    $streetId = $this->ReadPropertyString("streetGUID");
    $addonId  = $this->ReadPropertyString("addonGUID");
    $this->SendDebug("GetConfigurationForm", "clientID=".$clientId.", placeId=".$placeId.", streetId=".$streetId.", addonId=".$addonId, 0);

    $formclient = $this->FormClient($clientId);
    $formplaces = $this->FormPlaces($clientId, $placeId);
    $formstreet = $this->FormStreet($clientId, $placeId, $streetId);
    $formaddons = $this->FormAddons($clientId, $placeId, $streetId, $addonId);
    $formstatus = $this->FormStatus();

    return '{ "elements": [' . $formclient . $formplaces . $formstreet . $formaddons . '], "status": [' . $formstatus . ']}';
  }

  public function ApplyChanges()
  {
    //Never delete this line!
    parent::ApplyChanges();

    $clientId = $this->ReadPropertyString("clientID");
    $placeId  = $this->ReadPropertyString("placeGUID");
    $streetId = $this->ReadPropertyString("streetGUID");
    $addonId  = $this->ReadPropertyString("addonGUID");
    $this->SendDebug("ApplyChanges", "clientID=".$clientId.", placeId=".$placeId.", streetId=".$streetId.", addonId=".$addonId, 0);

    $status = 102;
    if($clientId == "null") {
      $status = 201;
    }
    else if($placeId == "null") {
      $status = 202;
    }
    else if($streetId == "null") {
      $status = 203;
    }
    else if($addonId == "null") {
      $status = 204;
    }

    $this->SetStatus($status);
    //$this->SetTimerInterval("UpdateTimer", 0);

  }

  /**
  * This function will be available automatically after the module is imported with the module control.
  * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
  *
  * AWIDO_Update($id);
  *
  */
  public function Update()
  {
  }

  /**
   * Erstellt ein DropDown-Men� mit den ausw�hlbaren Client IDs (Abfallwirtschaften).
   *
   * @access protected
   * @param  string $cId Client ID .
   * @return string Client ID Elemente.
   */
  protected function FormClient($cId)
  {
    $form = '{ "type": "Select", "name": "clientID", "caption": "Refuse management:", "options": [';
    $line = array();

    // Reset key
    $line[] = '{"label": "Please select ...","value": "null"}';

    foreach (static::$Clients as $Client => $Name)
    {
      if ($cId == "null") {
        $line[] = '{"label": "' . $Name . '","value": "' . $Client . '"}';
      }
      else if ($Client == $cId) {
          $line[] = '{"label": "' . $Name . '","value": "' . $Client . '"}';
      }
    }
    return $form . implode(',', $line) . ']}';
  }

  /**
   * Erstellt ein DropDown-Men� mit den ausw�hlbaren Orte im Entsorkungsgebiet.
   *
   * @access protected
   * @param  string $cId Client ID .
   * @param  string $pId Place GUID  .
   * @return string Places Elemente.
   */
  protected function FormPlaces($cId, $pId)
  {
    $url = "http://awido.cubefour.de/WebServices/Awido.Service.svc/getPlaces/client=".$cId;

    if($cId == "null") {
      return '';
    }

    $json = file_get_contents($url);
    $data = json_decode($json);

    $form = ',{ "type": "Select", "name": "placeGUID", "caption": "Location:", "options": [';
    $line = array();
    // Reset key
    $line[] = '{"label": "Please select ...","value": "null"}';

    foreach($data as $place) {
      if($pId == "null") {
        $line[] = '{"label": "' . $place->value . '","value": "' . $place->key . '"}';
      }
      else if ($pId == $place->key) {
        $line[] = '{"label": "' . $place->value . '","value": "' . $place->key . '"}';
      }
    }
    return $form . implode(',', $line) . ']}';
  }

  /**
   * Erstellt ein DropDown-Men� mit den ausw�hlbaren OT/Strassen im Entsorkungsgebiet.
   *
   * @access protected
   * @param  string $cId Client ID.
   * @param  string $pId Place GUID.
   * @param  string $sId Street GUID.
   * @return string Ortsteil/Strasse Elemente.
   */
  protected function FormStreet($cId, $pId, $sId)
  {
    $url = "http://awido.cubefour.de/WebServices/Awido.Service.svc/getGroupedStreets/".$pId."?selectedOTId=null&client=".$cId;

    if($cId == "null" || $pId == "null") {
      return '';
    }

    $json = file_get_contents($url);
    $data = json_decode($json);

    $form = ',{ "type": "Select", "name": "streetGUID", "caption": "District/Street:", "options": [';
    $line = array();
    // Reset key
    $line[] = '{"label": "Please select ...","value": "null"}';

    foreach($data as $street) {
      if($sId == "null") {
        $line[] = '{"label": "' . $street->value . '","value": "' . $street->key . '"}';
      }
      else if ($sId == $street->key) {
        $line[] = '{"label": "' . $street->value . '","value": "' . $street->key . '"}';
      }
    }
    return $form . implode(',', $line) . ']}';
  }

  /**
   * Erstellt ein DropDown-Men� mit den ausw�hlbaren Hausnummern im Entsorkungsgebiet.
   *
   * @access protected
   * @param  string $cId Client ID .
   * @param  string $pId Place GUID.
   * @param  string $sId Street GUID .
   * @param  string $aId Addon GUID .
   * @return string Client ID Elements.
   */
  protected function FormAddons($cId, $pId, $sId, $aId)
  {
    $url = "http://awido.cubefour.de/WebServices/Awido.Service.svc/getStreetAddons/".$sId."?client=".$cId;

    if($cId == "null" || $pId == "null" || $sId == "null") {
      return '';
    }

    $json = file_get_contents($url);
    $data = json_decode($json);

    $form = ',{ "type": "Select", "name": "addonGUID", "caption": "Street number:", "options": [';
    $line = array();
    // Reset key
    $line[] = '{"label": "Please select ...","value": "null"}';

    foreach($data as $addon) {
      if($aId == "null") {
        $line[] = '{"label": "' . $addon->value . '","value": "' . $addon->key . '"}';
      }
      else if ($aId == $addon->key) {
        $line[] = '{"label": "' . $addon->value . '","value": "' . $addon->key . '"}';
      }
    }
    return $form . implode(',', $line) . ']}';
  }

  /**
   * Pr�ft den Parent auf vorhandensein und Status.
   *
   * @access protected
   * @return string Status Elemente.
   */
  protected function FormStatus()
  {
    $form =  '{"code": 101, "icon": "inactive", "caption": "Creating instance."},
              {"code": 102, "icon": "active",   "caption": "AWIDO active."},
              {"code": 104, "icon": "inactive", "caption": "AWIDO inactive."},
              {"code": 201, "icon": "inactive", "caption": "Select a valid refuse management!"},
              {"code": 202, "icon": "inactive", "caption": "Select a valid place!"},
              {"code": 203, "icon": "inactive", "caption": "Select a valid location/street!"},
              {"code": 204, "icon": "inactive", "caption": "Select a valid street number!"}';
    return $form;
  }

}

?>