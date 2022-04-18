<html>
<head><link rel="stylesheet" href="css/bootstrap.css"></head>
<body class="p-5">
<?php if(!$_GET["site"]){ echo '<div class="alert alert-danger">ERROR: site parameter missing. Add site=name to the URL</div>'; return; }?>
<?php
  include '../sites/'.$_GET["site"].'/sqlconf.php';
  $conn = new mysqli($host, $login, $pass, $dbase, $port) or die("mysqli connection failed: %s\n". $conn -> error);
  $id = $_GET["id"];
  if(!$id){
    echo '<div class="alert alert-danger">ERROR: id parameter missing. Add id=number to the URL</div>';
    http_response_code(500);
    return;
  }
  $sql = <<<EOT
    SELECT data, form_type FROM cforms
    WHERE id=$id
    ORDER BY created_at DESC
    LIMIT 1
  EOT;
  $result = mysqli_query($conn,$sql);
  $row = mysqli_fetch_row($result);
  $conn -> close();
  $data = json_decode($row[0]);
  $form_type = $row[1];
?>

<!-- START TEMPLATE 
    
  To print template values:
    Lookup the question id from the spreadsheet, eg A01

    ?php echo $data->{"A01"}->{"g"}    This prints the group, eg Demographics
    ?php echo $data->{"A01"}->{"q"}    This prints the question, eg Last Name
    ?php echo $data->{"A01"}->{"r"}    This prints the response, eg Smith
    ?php echo $data->{"A01"}->{"n"}    This prints the narative, eg The patient's last name is

-->

<?php if($row==null){ echo '<div class="alert alert-danger mb-5">Form Not Found for Patient ID '.$pid.'</div>'; } ?>

<h3>This is a <?php echo $form_type?></h3>

Encounter Physician<br/>
Date: <?php echo date('Y-m-d')?><br/>

<b><?php echo $data->{"A01"}->{"r"} ?>, <?php echo $data->{"A02"}->{"r"} ?></b>
<hr class="mt-5 mb-5">
<p>Dear <?php echo $data->{"A02"}->{"r"} ?>, thank you for allowing me...</p>

<b><?php echo $data->{"B01"}->{"g"} ?>:</b><br/>
<?php echo $data->{"B01"}->{"n"} ?> <?php echo $data->{"B01"}->{"r"} ?>


<p><b><?php echo $data->{"A07"}->{"g"} ?>:</b><br/>
<?php echo $data->{"A07"}->{"n"} ?> <?php echo $data->{"A07"}->{"r"} ?>


</body>
</html>