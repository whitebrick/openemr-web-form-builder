<html>
<head>
  <link rel="stylesheet" href="css/bootstrap.css">
  <title>
    CForm Responses
  </title>
  <script>
    function deleteResponse(id){
      if(window.confirm("Permanently delete this response from the database?")){
        document.location = '?delete='+id+'&site=<?php echo $_GET["site"]; ?>';
      }
    }
  </script>
</head>
<body style="background-color: #eee">
<div class="container mt-5 p-5" style="background-color: #fff; border-radius: 1rem">
  <?php if(!$_GET["site"]){ echo '<div class="alert alert-danger">ERROR: site parameter missing. Add site=name to the URL</div>'; return; }?>
  <h3 class="text-center mb-3">Form Responses</h3>
  <table class="table">
    <tr>
      <th>Response ID</th>
      <th>Pateint ID</th>
      <th>Form Type</th>
      <th class="text-center">Action</th>
      <th class="text-center">Created At</th>
      <th class="text-center">Last Name</th>
      <th class="text-center">First Name</th>
      <th class="text-center">DOB</th>
    </tr>
  <?php
    include '../sites/'.$_GET["site"].'/sqlconf.php';
    $conn = new mysqli($host, $login, $pass, $dbase, $port) or die("mysqli connection failed: %s\n". $conn -> error);
    if(isset($_GET['delete']) && is_numeric($_GET['delete']) && intval($_GET['delete']) > 0){
      $sql = "DELETE FROM cforms WHERE id=".$_GET['delete'];
      $result = mysqli_query($conn,$sql);
    }
    $sql = "SELECT id, form_type, pid, created_at, lname, fname, dob FROM cforms ORDER BY created_at desc";
    $result = mysqli_query($conn,$sql);
    while($row = $result->fetch_assoc()) {
    ?>
      <tr>
        <td><?php echo $row["id"] ?></td>
        <td><?php echo $row["pid"] ?></td>
        <td><?php echo $row["form_type"] ?></td>
        <td class="text-center" style="font-size: 0.7rem">[<a href="response.php?id=<?php echo $row["id"] ?>&site=<?php echo $_GET["site"]; ?>" target="_blank">Vew Response</a>] 
          [<a href="report.php?id=<?php echo $row["id"] ?>&site=<?php echo $_GET["site"]; ?>" target="_blank">View Report</a>]
          [<a href="#" onclick="deleteResponse(<?php echo $row["id"]; ?>)">Delete</a>]
        <td><?php echo $row["created_at"] ?></td>
        <td><?php echo $row["lname"] ?></td>
        <td><?php echo $row["fname"] ?></td>
        <td><?php echo $row["dob"] ?></td>
      </tr>
    <?php
    }
    $conn -> close();
  ?>
  </table>
</body>
</html>