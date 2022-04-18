<html>
<head>
  <link rel="stylesheet" href="css/bootstrap.css">
  <script src="js/jquery-3.2.1.slim.min.js"></script>
  <script src="js/bootstrap.bundle.js"></script>
  <title>
    CForm Response
  </title>
</head>
<body style="background-color: #fff">
<div class="container">
  <?php if(!$_GET["site"]){ echo '<div class="alert alert-danger">ERROR: site parameter missing. Add site=name to the URL</div>'; return; }?>
  <?php
    include '../sites/'.$_GET["site"].'/sqlconf.php';
    $pid = $_GET["pid"];
    $id = $_GET["id"];
    $form_type = $_GET["type"];
    $where = "pid=$pid AND form_type='$form_type'";
    if($id){ $where = "id=$id"; }
    $conn = new mysqli($host, $login, $pass, $dbase, $port) or die("mysqli connection failed: %s\n". $conn -> error);
    $sql = "SELECT pid, created_at, lname, fname, dob, data, form_type FROM cforms where $where ORDER BY created_at DESC LIMIT 1";
    $result = mysqli_query($conn,$sql);
    $row = mysqli_fetch_row($result);
    $json_obj = json_decode($row[5]);
    $cform = $json_obj;
    $conn -> close();
  ?>
  <pre class="mt-2">
<?php if(!$id && !$form_type){ echo "<b>Response Not Found for ".$where."</b>"; return; } ?>
<?php if($row==null){ echo "<b>Response Not Found for ".$where."</b>"; return; } ?>
<?php echo $row[6] ?> | <?php echo $row[2] ?> <?php echo $row[3] ?> <?php echo $row[4] ?> | Patient ID: <?php echo $row[0] ?> | Submitted: <?php echo $row[1] ?>
<?php ?>
  </pre>

<div id="accordion">
  <?php
    //var_dump($cform );
    // "A01": {
    //     "g": "Demographics",
    //     "n": "The patients last name is",
    //     "q": "Last Name",
    //     "r": "Smith"
    // },
    // "A02": {
    //     "g": "Demographics",
    //     "n": "The patients first name is",
    //     "q": "First Name",
    //     "r": "Tom"
    // },
    $previousGroup = '';
    $currentGroup = '';
    $groupCnt = 0;
    $currentGroupResponses = '';
    $allGroupResponses = '';
    foreach($cform as $id => $values){
      // if($values->{"g"}=='Demographics'){
      //   continue;
      // }
      if($values->{"g"}!=$currentGroup){
        $previousGroup = $currentGroup;
        $currentGroup = $values->{"g"};
          if($previousGroup!=''){ ?>
            <div class="card mb-2">
            <div class="card-header p-0 border border-primary" id="header-agg-grp<?php echo $groupCnt ?>">
              <p class="mb-0">
                <button class="btn btn-link btn-sm text-left" data-toggle="collapse" data-target="#agg-grp<?php echo $groupCnt ?>">
                  <?php echo $previousGroup ?> - All
                </button>
              </p>
            </div>
            <div id="agg-grp<?php echo $groupCnt ?>" class="collapse" data-parent="#accordion">
              <div class="card-body">
                <textarea class="form-control" rows="50"><?php echo $currentGroupResponses ?></textarea>
              </div>
            </div>
          </div>
          <?php $currentGroupResponses = '';
                $groupCnt++;
        } ?>

      <p class="mb-0 font-weight-bold"><?php echo $currentGroup; ?></p>

      <?php }
        if($values->{"r"}!=''){
          $currentGroupResponses = $currentGroupResponses.$values->{"n"}."\n".$values->{"r"}."\n\n";
          $allGroupResponses = $allGroupResponses.$values->{"n"}."\n".$values->{"r"}."\n\n";
        }
      ?>

      <div class="card mb-2">
        <div class="card-header p-0" id="header<?php echo $id ?>">
          <p class="mb-0">
            <button class="btn btn-link btn-sm text-left <?php if($values->{"r"}==''){ echo 'text-muted'; }?>" data-toggle="collapse" data-target="#<?php echo $id ?>">
              <?php echo $id ?>: <?php echo $values->{"q"} ?>
            </button>
          </p>
        </div>
        <div id="<?php echo $id ?>" class="collapse" data-parent="#accordion">
          <div class="card-body">
            <textarea class="form-control"><?php echo $values->{"n"} ?>

<?php echo $values->{"r"} ?></textarea>
          </div>
        </div>
      </div>

    <?php } ?>

    <div class="card mb-2">
      <div class="card-header p-0 border border-success" id="headerALL">
        <p class="mb-0">
          <button class="btn btn-link btn-sm text-left" data-toggle="collapse" data-target="#all-grp">
            All Responses
          </button>
        </p>
      </div>
      <div id="all-grp" class="collapse" data-parent="#accordion">
        <div class="card-body">
          <textarea class="form-control" rows="50"><?php echo $allGroupResponses ?></textarea>
        </div>
      </div>
    </div>

  </div>

</body>
</html>