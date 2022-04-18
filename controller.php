<?php
  $LAST_NAME_INPUT_ID  = "A01";
  $FIRST_NAME_INPUT_ID = "A02";
  $DOB_INPUT_ID        = "A03";
  $SEX_INPUT_ID        = "A04";

  $json_content = file_get_contents('php://input');
  $json_obj = json_decode($json_content);
  $form_type = $json_obj->{'type'};
  $cform = $json_obj->{'cform'};
  $cform_json = json_encode($cform);

  include '../sites/'.$json_obj->{'site'}.'/sqlconf.php';

  $conn = new mysqli($host, $login, $pass, $dbase, $port) or die("mysqli connection failed: %s\n". $conn -> error);
  // r = response
  $lname = $cform->{$LAST_NAME_INPUT_ID}->{'r'};
  $fname = $cform->{$FIRST_NAME_INPUT_ID}->{'r'};
  $sex = $cform->{$SEX_INPUT_ID}->{'r'};
  // format dob for mysql
  $dob = date_parse($cform->{$DOB_INPUT_ID}->{'r'});
  $dob = $dob[year].'-'.str_pad($dob[month],2,'0',STR_PAD_LEFT).'-'.str_pad($dob[day],2,'0',STR_PAD_LEFT);
  $cform->{$DOB_INPUT_ID}->{'r'} = $dob;

  $upper_lname = strtoupper($lname);  
  $upper_fname = strtoupper($fname);


  // Check if patient already exists
  $pid = '';
  $sql = "SELECT pid FROM patient_data WHERE UPPER(lname)='$upper_lname' AND UPPER(fname)='$upper_fname' AND DOB like '$dob%'";
  $result = mysqli_query($conn, $sql);
  if(!result){
    echo "{\"status\":\"SQL Error 1: ".mysqli_error($conn)."\"}";
    http_response_code(500);
    return;
  }
  $row = mysqli_fetch_row($result);
  if($row){
    $pid = $row[0];
  }
  
  // Create a new record if patient doesnt exist
  if($pid==''){
    // Get the next pid seq
    $sql = "SELECT pid FROM patient_data ORDER BY pid DESC LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if(!result){
      echo "{\"status\":\"SQL Error 2: ".mysqli_error($conn)."\"}";
      http_response_code(500);
      return;
    }
    $row = mysqli_fetch_row($result);
    $last_pid = $row[0];
    $pid = intval($last_pid) + 1;

    // Save the patient_data record
    $sql = <<<EOT
      INSERT INTO patient_data(pid, fname, lname, DOB, sex)
      VALUES(
        $pid,
        '$fname',
        '$lname',
        '$dob',
        '$sex'
      );
    EOT;
    
    if(!mysqli_query($conn, $sql)){
      echo "{\"status\":\"SQL Error 3: ".$sql.' '.mysqli_error($conn)."\"}";
      http_response_code(500);
      return;
    }

    // Save the patient_history and insurance_data stubs
    $sql = <<<EOT
      INSERT INTO history_data(pid, date)
      VALUES(
        $pid,
        curdate()
      );
    EOT;
    
    if(!mysqli_query($conn, $sql)){
      echo "{\"status\":\"SQL Error 4: ".$sql.' '.mysqli_error($conn)."\"}";
      http_response_code(500);
      return;
    }

    $sql = <<<EOT
      INSERT INTO insurance_data(type,subscriber_DOB,pid,date,accept_assignment)
      VALUES(
        'primary',
        CURDATE(),
        $pid,
        CURDATE(),
        TRUE
      )
    EOT;
    
    if(!mysqli_query($conn, $sql)){
      echo "{\"status\":\"SQL Error 5: ".$sql.' '.mysqli_error($conn)."\"}";
      http_response_code(500);
      return;
    }

  }

  // Save the cform data
  $sql = <<<EOT
    INSERT INTO cforms(form_type, pid, fname, lname, dob, data)
    VALUES (
      '$form_type',
      $pid,
      '$fname',
      '$lname',
      '$dob',
      '$cform_json'
    )
  EOT;
  
  if(!mysqli_query($conn, $sql)){
    echo "{\"status\":\"SQL Error 6: ".mysqli_error($conn)."\"}";
    http_response_code(500);
    return;
  }

  // Get the cform record ID
  $sql = "SELECT LAST_INSERT_ID()";
  $result = mysqli_query($conn, $sql);
  if(!result){
    echo "{\"status\":\"SQL Error 7: ".mysqli_error($conn)."\"}";
    http_response_code(500);
    return;
  }
  $row = mysqli_fetch_row($result);
  $record_id = $row[0];

  $dob = str_replace("-","",$dob);
  $filename=str_replace(" ","_",("report-".$record_id."_".$lname."_".$fname."_".$dob.".pdf"));
  $retval = 0;

  // Create PDF for patient update and uncomment below
  //$cmd = "chrome --headless --disable-gpu --print-to-pdf=/var/www/html/openemr/cforms/reports/".$filename http://localhost/cforms/report.php?id=$record_id";
  //$cmd = "echo 'test' > /var/www/html/openemr/cforms/reports/".$filename;
  //system($cmd, $retval);

  header('Content-Type: application/json; charset=utf-8');
  if($retval!=0){
    echo "{\"status\":\"File Write Error $retval\"}";
    http_response_code(500);
  }else{
    echo "{\"status\":\"success\"}";
    http_response_code(200);
  }
  $conn -> close();
?>