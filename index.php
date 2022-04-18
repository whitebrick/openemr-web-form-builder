<html>
<head>
  <link rel="stylesheet" href="css/bootstrap.css">
  <script>
    const urlParams = new URLSearchParams(window.location.search);
    var type = urlParams.get('type');
    var site = urlParams.get('site');
    function sendForm(){
      const formData = {
        type: type,
        site: site,
        cform: {}
      };
      const elements = document.querySelectorAll("#cforms-form textarea, #cforms-form input[type=text]");
      for (element of elements) {
        formData['cform'][element.id]={
          g: document.getElementById(`Group-${element.id}`).value.replace(/'/g, "").replace(/\n/g, ''),
          q: document.getElementById(`QuestionEn-${element.id}`).value.replace(/'/g, "").replace(/\n/g, ''),
          r: element.value,
          n: document.getElementById(`Narrative-${element.id}`).value.replace(/'/g, "").replace(/\n/g, '')
        };
      }
      postData('controller.php',formData);
    }
    function postData(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {
                'Content-Type': 'application/json'
            }, body: JSON.stringify(data)
            
        }).then(response => response.json())
            .then(data => {
              if(data["status"]=="success"){
                document.getElementById('form-success').style.display='block';
              } else {
                document.getElementById('form-error').style.display='block';
                document.getElementById('form-error-message').textContent=data["status"];
              }
            });
    }
    function checkDate(id,label){
      const value = document.getElementById(id).value;
      if(value && !(/^(0?[1-9]|1[012])[\/](0?[1-9]|[12][0-9]|3[01])[\/]\d{4}$/.test(value)) ){
        alert(`Please check the date format for field: ${label}`);
      }
    }
  </script>
  <title>
    Form
  </title>
</head>
<body style="background-color: #eee">
<div class="container mt-5 p-5" style="background-color: #fff; border-radius: 1rem">
  <?php if(!$_GET["site"]){ echo '<div class="alert alert-danger">ERROR: site parameter missing.</div>'; return; }?>
  <form id="cforms-form">
    <?php
      if(!file_exists('forms/'.$_GET["type"].'.tsv')){ echo '<div class="alert alert-danger">ERROR: type='.$_GET["type"].'.tsv form file not found.</div>'; return; }
      $QUESTION_FILE = fopen('forms/'.$_GET["type"].'.tsv',"r");
      $current_group="";
      $qCol = 2;
      if($_GET["es"]){ $qCol = 3; }
      if($_GET["header"]){ echo '<h1 class="text-center">'.$_GET["header"].'</h1>'; }
      while(! feof($QUESTION_FILE))  {
        $line = str_replace('"', "", explode("\t", fgets($QUESTION_FILE)));
        if($line[0]){ ?>
          <?php if(strlen($line[0])>5){ 
                  $current_group=$line[$qCol] ?>
            <h5><?php echo $line[$qCol]; ?></h5>
          <?php }else{ ?>
            <div class="form-group">
              <?php if($line[0]=="TITLE"){ ?>
                <h3 class="text-center mb-3"><?php echo $line[$qCol]; ?></h3>
                <hr/>
              <?php } else if($line[0]=="TEXT"){ ?>
                <p class="mb-5"><?php echo $line[$qCol]; ?></p>
              <?php } else if($line[1]=="short_text"){ ?>
                <label id="Label-<?php echo $line[0]; ?>" for="<?php echo $line[0]; ?>"><?php echo $line[$qCol]; ?></label>
                <input type="text" class="form-control" id="<?php echo $line[0]; ?>">
              <?php }elseif($line[1]=="date"){ ?>
                <label id="Label-<?php echo $line[0]; ?>" for="<?php echo $line[0]; ?>"><?php echo $line[$qCol]; ?> (MM/DD/YYYY)</label>
                <input type="text" class="form-control" id="<?php echo $line[0]; ?>" onblur="checkDate('<?php echo $line[0]; ?>','<?php echo $line[$qCol]; ?>')">
              <?php }else{ ?>
                <label id="Label-<?php echo $line[0]; ?>" for="<?php echo $line[0]; ?>"><?php echo $line[$qCol]; ?></label>
                <textarea class="form-control" id="<?php echo $line[0]; ?>" rows="3"></textarea>
              <?php } ?>
              <input type="hidden" id="Group-<?php echo $line[0]; ?>" value="<?php echo $current_group; ?>">
              <input type="hidden" id="Narrative-<?php echo $line[0]; ?>" value="<?php echo $line[4]; ?>">
              <input type="hidden" id="QuestionEn-<?php echo $line[0]; ?>" value="<?php echo $line[2]; ?>">
            </div>
    <?php }}} fclose($fn); ?>
  </form>
  <div style="display: none" class="alert alert-success" id="form-success">Form succesfully submitted.<br/>This page can be printed for your records or closed.</div>
  <div style="display: none" class="alert alert-danger" id="form-error">Form Error: <span id="form-error-message"></span><br/>Please print this page to a PDF or paper to save.</div>
  <button class="btn btn-success" onClick="sendForm()">Submit</button> <button class="btn btn-light" onClick="window.print()">Print</button>
</div>
</body>
</html>