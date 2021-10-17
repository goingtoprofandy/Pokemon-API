<?php
class Antares_helper {
  // ==============
  // SET KEY ACCESS
  // ==============
  function set_key($accesskey) {
    // $this->key = $accesskey;
    $this->key = $accesskey;
  }

  function get_key() {
    return $this->key;
  }

  // ==============================
  // CREATE a Application Antares.id
  // ==============================
  function appCreate($projectName){
    $keyacc = "{$this->key}";

    $header = array(
      "X-M2M-Origin: $keyacc",
      // "X-M2M-Origin: ",
      "Content-Type: application/json;ty=3",
      "Accept: application/json"
    );

    $curl = curl_init();
    $dataSend = array(("m2m:ae") => array("rn" => $projectName));
    $data_encode = json_encode($dataSend);
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName.".",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS =>$data_encode,
      CURLOPT_HTTPHEADER => $header,
    ));
    $response = curl_exec($curl);
    // CHECK response status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return array(
      "res" => $response,
      "code" => $httpCode
    );
  }

  // ==========================
  // CREATE a device Antares.id
  // ===========================
  function deviceCreate($deviceName,$projectName){
    $keyacc = "{$this->key}";

    $header = array(
      "X-M2M-Origin: $keyacc",
      // "X-M2M-Origin: ",
      "Content-Type: application/json;ty=3",
      "Accept: application/json"
    );

    $curl = curl_init();
    $dataSend = array(("m2m:cnt") => array(
      "xmlns:m2m" => "http://www.onem2m.org/xml/protocols",
      "rn" => $deviceName
    ));
    $data_encode = json_encode($dataSend);
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName."",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS =>$data_encode,
      CURLOPT_HTTPHEADER => $header,
    ));
    $response = curl_exec($curl);
    
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return array(
      "res" => $response,
      "code" => $httpCode
    );
  }

  // ==========================
  // UPDATE a device Antares.id by name [ON WORKING]
  // ===========================
  function updateDevice($deviceNew,$deviceName,$projectName){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=3",
      "Accept: application/json" 
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/$projectName/$deviceName"."/?ty=3",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    //GET json Respone -> String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
      //REMOVE header
      $temp_url = $raw[0]["m2m:cnt"]["rn"];
      //$count_temp = count($temp_url);
      $raw_data =$temp_url;
      var_dump($raw_data);
      die();
    }
    $curl = curl_init();
    $dataSend = array(("m2m:cnt") => array("rn" => ($deviceNew)));
    $data_encode = json_encode($dataSend);
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName."/".$deviceName."",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "UPDATE",
      CURLOPT_POSTFIELDS =>$data_encode,
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    //   return $raw_data; //-> Array
    // }else{
    //   echo "ERROR[001] : Application Name or Device Name is Wrong";
    // }
    // curl_close($curl);
  }

  // ============================
  // RETRIEVE a device Antares.id
  // ============================
  function getDevice($deviceName,$projectName){
    $keyacc = "{$this->key}";

    $header = array(
      "X-M2M-Origin: $keyacc",
      // "X-M2M-Origin: ",
      "Content-Type: application/json;ty=3",
      "Accept: application/json"
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName."/".$deviceName."",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      // CURLOPT_POSTFIELDS =>$data_encode,
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    $response = curl_exec($curl);
    var_dump($response);
    die();
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode == "404") {
      return "ERROR[404] : Something WRONG when GET device";
    }
    curl_close($curl);
    return $response;
  }

  // ==============================
  // SEND data to server Antares.id
  // ==============================
  function send($data,$deviceName,$projectName){
    $keyacc = "{$this->key}";

    $header = array(
      "X-M2M-Origin: $keyacc",
      // "X-M2M-Origin: ",
      "Content-Type: application/json;ty=4",
      "Accept: application/json"
    );

    $curl = curl_init();
    $dataSend = array(("m2m:cin") => array("con" => ($data)));
    $data_encode = json_encode($dataSend);
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName."/".$deviceName."",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS =>$data_encode,
      CURLOPT_HTTPHEADER => $header,
    ));
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode == "404") {
      echo "ERROR[003] : Something WRONG when SEND data";
    }
    curl_close($curl);
    return $response;
  }

  // ==========================================
  // GET data with LIMIT from server Antares.id
  // ==========================================
  function get_limit($limit,$deviceName,$projectName){
    //!!!! $limit type STRING  !!!!
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json"
    );
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName."/".$deviceName."?fu=1&ty=4&lim=".$limit, //$limit type STRING 
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    //GET json Respone -> String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp =  count($temp_url);
      $raw_data = [];
      
      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
        $raw_json = json_decode($raw[0]["m2m:cin"]["con"],true);
        //ADD data to array
        array_push($raw_data,$raw_json);
        curl_close($cin);  
      }
      return $raw_data; //-> Array
    }else{
      echo "ERROR[004] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }

  // ===================================
  // GET ALL data from server Antares.id
  // ===================================
  function get_all($deviceName,$projectName){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json"
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName."/".$deviceName."?fu=1&ty=4",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    //GET json Respone -> String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp =  count($temp_url);
      $raw_data = [];
      
      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
        $raw_json = json_decode($raw[0]["m2m:cin"]["con"],true);
        //ADD data to array
        array_push($raw_data,$raw_json);
        curl_close($cin);  
      }
      return $raw_data; //-> Array
    }else{
      echo "ERROR[001] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }

  // ====================================
  // GET LAST data from server Antares.id
  // ====================================
  function get($deviceName,$projectName){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json"
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName."/".$deviceName."/la",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    //GET json String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);

      //REMOVE header
      $temp_url = $raw[0]["m2m:cin"]["con"];
      $JSON = json_decode('['.$temp_url.']',true);
      curl_close($curl);
      return $JSON; //-> Array
    }else{
      echo "ERROR[002] : Application Name or Device Name is Wrong";
    }
  }

  // ===============================
  // DELETE a Application Antares.id
  // ===============================
    function appDelete($projectName){
    $keyacc = "{$this->key}";

    $header = array(
      "X-M2M-Origin: $keyacc",
      // "X-M2M-Origin: ",
      "Content-Type: application/json;ty=3",
      "Accept: application/json"
    );

    $curl = curl_init();
    //$dataSend = array(("m2m:cnt") => array("rn" => $projectName));
    //$data_encode = json_encode($dataSend);
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName."",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "DELETE",
      //CURLOPT_POSTFIELDS =>$data_encode,
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    // CHECK response status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode == "200") {
      echo "Your app has been deleted";
    }
    else if($httpCode == "400") {
      echo "Something wrong is happen, API not found";
    }
    curl_close($curl);
  }

  // ==========================
  // Delete a Device Antares.id
  // ==========================
  function deviceDelete($deviceName,$projectName){
    $keyacc = "{$this->key}";

    $header = array(
      "X-M2M-Origin: $keyacc",
      // "X-M2M-Origin: ",
      "Content-Type: application/json;ty=3",
      "Accept: application/json"
    );

    $curl = curl_init();
    $dataSend = array(("m2m:cnt") => array("rn" => $projectName));
    $data_encode = json_encode($dataSend);
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName."/".$deviceName."",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "DELETE",
      //CURLOPT_POSTFIELDS =>$data_encode,
      CURLOPT_HTTPHEADER => $header,
    ));
    // CHECK response status
    curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      if($httpCode == "200") {
        echo "Your device has been deleted";
      }
      else if($httpCode == "400") {
        echo "Something wrong is happen, API not found";
      }
    curl_close($curl);
  }

  // ==============================
  // Discover all device Antares.id
  // ==============================
  function dscAllDevice($projectName){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json"
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/".$projectName."/?fu=1&ty=3",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    //GET json Respone -> String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp =  count($temp_url);
      $raw_data = [];
      
      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
        //ADD data to array
        array_push($raw_data,$raw[0]["m2m:cnt"]["ol"]);
        curl_close($cin);  
      }
      //var_dump($raw_data);
      //die();
      return $raw_data; //-> Array
    }else{
      echo "ERROR[001] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }

  // ===================================
  // Discover all application Antares.id
  // ===================================
  function dscAllApp($email){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json"
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/?ty=2&fu=1&lbl=User/".$email."",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    //GET json Respone -> String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);

      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp =  count($temp_url);
      $raw_data = [];

      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
        //var_dump($raw);
        //die();

        //ADD data to array
        array_push($raw_data,$raw[0]["m2m:ae"]["rn"]);
        curl_close($cin);  
      }
      return $raw_data; //-> Array
    }else{
      echo "ERROR[001] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }

  // ===============================
  // Discover all data ID Antares.id
  // ===============================
  function dscAllDataID($deviceName,$projectName){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json" 
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/$projectName/$deviceName"."/?fu=1&ty=4",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    //GET json Respone -> String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
      
      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp = count($temp_url);
      $raw_data = [];
      
      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
        //ADD data to array
        array_push($raw_data,$raw[0]["m2m:cin"]["rn"]);
        // var_dump($raw_data);
        // die();
        curl_close($cin);  
      }
      return $raw_data; //-> Array
    }else{
      echo "ERROR[001] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }

  // ==========================================
  // Discover all data ID with limit Antares.id
  // ==========================================
  function dscAllDataIDLimit($limit,$deviceName,$projectName){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json" 
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/$projectName/$deviceName"."/?fu=1&ty=4&lim=".$limit,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    //GET json Respone -> String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
      
      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp = count($temp_url);
      $raw_data = [];
      
      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
        //ADD data to array
        array_push($raw_data,$raw[0]["m2m:cin"]["rn"]);
        //var_dump($raw_data);
        //die();
        curl_close($cin);  
      }
      return $raw_data; //-> Array
    }else{
      echo "ERROR[001] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }

  // ============================================
  // Discover all Subsribers on device Antares.id
  // ============================================
  function dscAllSubDevice($deviceName,$projectName){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json" 
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/$projectName/$deviceName"."/?fu=1&ty=23",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    //GET json Respone -> String
    $response = curl_exec($curl);
    // var_dump($response);
    // die();
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
      
      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp = count($temp_url);
      $raw_data = [];
      
      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
        //var_dump($raw);
        //die();
        //ADD data to array
        array_push($raw_data,$raw[0]["m2m:sub"]["nu"]);
        curl_close($cin);  
      }
      return $raw_data[0]; //-> Array
    }else{
      echo "ERROR[001] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }

  // =================================================
  // Discover all Subsribers on application Antares.id
  // =================================================
  function dscAllSubApp($projectName){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json" 
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/$projectName"."/?fu=1&ty=23",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    //GET json Respone -> String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
      
      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp = count($temp_url);
      $raw_data = [];
      
      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
         // var_dump($raw);
         // die();
        //ADD data to array
        array_push($raw_data,$raw[0]["m2m:sub"]["nu"]);

        curl_close($cin);  
      }
      return $raw_data[0]; //-> Array
    }else{
      echo "ERROR[001] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }

  // ==================================================
  // Discover all data ID Antares.id in Particular Time
  // ==================================================
  function dscAllDataIDTime($datetime,$deviceName,$projectName){
    $time = time();
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json" 
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/$projectName/$deviceName"."/?fu=1&ty=4&lim=5&crb=".$datetime,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    //GET json Respone -> String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
      //var_dump($raw);
      //die();
      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp = count($temp_url);
      $raw_data = [];

      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
        //ADD data to array
        array_push($raw_data,$raw[0]["m2m:cin"]["rn"]);
        // var_dump($raw_data);
        // die();
        //print $time;
        curl_close($cin);  
      }
      return $raw_data; //-> Array
    }else{
      echo "ERROR[001] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }

  // ========================================
  // Delete Subcribers to a Device Antares.id [ON WORKING]
  // ========================================

  //Ada feedback dari mas najmi, cek di task 29/07/2020
  function deleteSubDevice($deviceName,$projectName){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json" 
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/$projectName/$deviceName"."/?fu=1&ty=23",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    //GET json Respone -> String
    $response = curl_exec($curl);
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
         //var_dump($raw);
         //die();      
      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp = count($temp_url);
      $raw_data = [];
      
      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "DELETE",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
         //var_dump($raw);
         //die();
        //ADD data to array
        //array_push($raw_data,$raw[0]["m2m:sub"]["rn"]);
        //echo "Your subrcibers on devices has been deleted";
        curl_close($cin);  
      }
      return $raw_data; //-> Array
    }else{
      echo "ERROR[001] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }

  // ==============================
  // Subscribe to device data
  // ==============================
  function subDevice($subs,$deviceName,$projectName){
    $keyacc = "{$this->key}";

    $header = array(
      "X-M2M-Origin: $keyacc",
      // "X-M2M-Origin: ",
      "Content-Type: application/json;ty=23",
      "Accept: application/json"
    );
    
    $curl = curl_init();
    $dataSend = array(("m2m:sub") => array(
      "xmlns:m2m" => "http://www.onem2m.org/xml/protocols",
      "nu" => ($subs)
    ));
    $data_encode = json_encode($dataSend);
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/$projectName/$deviceName"."/?ty=23&cnt=2&rn=$deviceName&nu=$subs",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS =>$data_encode,
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);

    $response = curl_exec($curl);
    // var_dump($response);
    // die();
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode == "404") {
      echo "ERROR[003] : Something WRONG when SEND data";
    }
    curl_close($curl);
    return array(
      "res" => $response,
      "code" => $httpCode
    );
  }
  
   // ========================================
  // Update Subcribers to a Device Antares.id [ON WORKING]
  // ========================================
  function updateSubDevice($newSubs,$deviceName,$projectName){
    $keyacc = "{$this->key}";
    $header = array(
      "X-M2M-Origin: $keyacc",
      "Content-Type: application/json;ty=4",
      "Accept: application/json" 
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/$projectName/$deviceName"."/?fu=1&ty=23",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => $header,
    ));
    curl_exec($curl);
    //GET json Respone -> String
    $response = curl_exec($curl);
    var_dump($response);
    die();
    // CHECK respone status
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($httpCode != "404") {
      //CONVERT to array
      $raw = json_decode('['.$response.']', true);
         //var_dump($raw);
         //die();      
      //REMOVE header
      $temp_url = $raw[0]["m2m:uril"];
      $count_temp = count($temp_url);
      $raw_data = [];
      
      //GET data
      for($i = 0; $i < $count_temp; $i++){
        $cin = curl_init();
        curl_setopt_array($cin, array(
          CURLOPT_URL => "https://platform.antares.id:8443/~/antares-cse/antares-id/$projectName/$deviceName"."/?ty=23&cnt=2&rn=$deviceName&nu=$newSubs".$temp_url[$i],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "UPDATE",
          CURLOPT_HTTPHEADER => $header,
        ));
        //GET json Respone -> String
        $cin_res = curl_exec($cin);
        //CONVERT to array
        $raw = json_decode('['.$cin_res.']', true);
        //ADD data to array
        //array_push($raw_data,$raw[0]["m2m:sub"]["rn"]);
        //echo "Your subrcibers on devices has been updated";
        curl_close($cin);  
      }
      return $raw_data; //-> Array
    }else{
      echo "ERROR[001] : Application Name or Device Name is Wrong";
    }
    curl_close($curl);
  }
}
?>