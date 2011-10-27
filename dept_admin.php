<?php
	require_once('db.php');
	
	// Require Authentication
	if (!(isset($_SERVER['PHP_AUTH_USER']) && ($_SERVER['PHP_AUTH_USER'] == $admin_username) && ($_SERVER['PHP_AUTH_PW'] == $admin_password) )) {
		header('WWW-Authenticate: Basic realm="Store Admin"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'You must log in to use the store admin interface!';
		exit;
	}

	if(! $conn = db_connect()) {
		echo "Unable to connect to the database: " . mysql_error();
	}
	
	if(isset($_POST['action'])) {
		$new = array();
		$old = array();
		foreach ($_POST as $key => $value) {
			if(preg_match('/^sort_(.*)$/',$key, $matches)) {
				mysql_query(@sprintf("UPDATE ".$db_prefix."departments SET sort_order = %d WHERE department_id = %d", $value, $matches[1]));
			}
		}
    		
	}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

<head>
  <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
  <title>Affiliate Store Admin</title>
</head>

<body>

<br />
<a href="admin.php">Admin Home</a>
<br /><br />


<form action='<?php echo $_SERVER['REQUEST_URI']; ?>' method='post'>
	<input type='hidden' name='action' value='sort'>
	<table>
		<tr><th colspan="2">Department Admin</th></tr>
		<tr><td>Department</td><td>Sort Order</td></tr>
<?php 
	
	if(isset($_GET['dept']) && is_numeric($_GET['dept'])) {
		$parent = $_GET['dept'];
	} else {
		$parent = 0;
	}

	if($res = mysql_query("SELECT department_id, dept_name, sort_order FROM ".$db_prefix."departments WHERE parent_id = ".$parent." ORDER BY sort_order")) {
		while($row = mysql_fetch_assoc($res)) {
			echo "\t\t\t<tr><td><a href='dept_admin.php?dept=".$row['department_id']."'>".$row['dept_name']."</a></td><td align='right'><input type='text' size='2' maxlength='2' value='".$row['sort_order']."' name='sort_".$row['department_id']."'></td></tr>\n";
		}
	}
	else {
		echo "Query failed:".mysql_error();
	}
?>
		<tr><td colspan="2" align="center"><input type="submit" value="Save Changes"></td></tr>
	</table>
</form>

</body>
</html>