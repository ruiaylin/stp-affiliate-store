<?php 

	$success = 0;
	
	if(isset($_POST['site_name'])) {
		$site_name = $_POST['site_name'];
		$site_url = $_POST['site_url'];
		$linkshare_id = $_POST['linkshare_id'];
		$db_host = $_POST['db_host'];
		$db_name = $_POST['db_name'];
		$db_user = $_POST['db_user'];
		$db_pass = $_POST['db_pass'];
		$db_prefix = $_POST['db_prefix'];
		
		if(mysql_connect($db_host,$db_user,$db_pass)) {
			$message = "";
			if(mysql_select_db($db_name)) {
				if(! mysql_query("create table ".$db_prefix."products ( product_id int, name varchar(255), url varchar(150), thumbnail varchar(255), large_image varchar(255), description text, retail float(7,2), price float(7,2), brand int, department int, track_url varchar(255), short_desc varchar(200), primary key (product_id), key (brand), key (department))")){
					$mesage.= "Problem creating products table: ".mysql_error();
				}
				if(! mysql_query("create table ".$db_prefix."brands ( brand_id int, brand_name varchar(100), image varchar(255), primary key (brand_id))")) {
					$mesage.= "Problem creating brands table: ".mysql_error();
				}
				if(! mysql_query("create table ".$db_prefix."departments ( department_id int, dept_name varchar(255), parent_id int, level int, sort_order int, primary key (department_id))")) {
					$mesage.= "Problem creating departments table: ".mysql_error();
				}
				if(! mysql_query("create table ".$db_prefix."urls ( url varchar(100), type varchar(50), type_id int, primary key (url), unique key (type,type_id))")) {
					$mesage.= "Problem creating urls table: ".mysql_error();
				}
				if(! mysql_query("create table ".$db_prefix."options ( opt_name varchar(100), opt_value text, primary key (opt_name))")) {
					$mesage.= "Problem creating options table: ".mysql_error();
				}
				mysql_query("insert into ".$db_prefix."options(opt_name,opt_value) values('site_name','".mysql_real_escape_string($site_name)."')");
				mysql_query("insert into ".$db_prefix."options(opt_name,opt_value) values('site_url','".mysql_real_escape_string($site_url)."')");
				mysql_query("insert into ".$db_prefix."options(opt_name,opt_value) values('linkshare_id','".mysql_real_escape_string($linkshare_id)."')");
				mysql_query("insert into ".$db_prefix."options(opt_name,opt_value) values('footer_text','')");
				mysql_query("insert into ".$db_prefix."options(opt_name,opt_value) values('header_text','')");
				mysql_query("insert into ".$db_prefix."options(opt_name,opt_value) values('show_brand_links','true')");

				$message = $message ? $message : "Database setup successfully";
				$success = 1;
			}
			else {
				$message = "Couldn't connect to database:".mysql_error();
			}
		}
		else {
			$message = "Couldn't connect to server:".mysql_error();
		}
	}
	else {
		$site_name = '';
		$site_url = "http://".$_SERVER['HTTP_HOST'].str_replace("install.php","",$_SERVER['REQUEST_URI']);
		$linkshare_id = '';
		$db_host = '';
		$db_name = '';
		$db_user = '';
		$db_pass = '';
		$db_prefix = 'stp_';
	}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<title>SierraTradingPost Store Installer</title>
	<script>
		function showInfo(sText,obj)
		{
			var oDiv = document.getElementById("divInfo");
			oDiv.innerHTML = sText;
			oDiv.style.position='absolute';
			oDiv.style.top=obj.y+'px';
			oDiv.style.left=(obj.x+23)+'px';
			oDiv.style.display='block';
		}
		function hideInfo() {
			var oDiv = document.getElementById("divInfo");
			oDiv.style.display='none';
		}			
	</script>
</head>

<body>

<?php 
if(isset($message)) {
	echo $message."<br><br>\n";
}

if($success) {
	echo "Please saving the following as <em>config.php</em> in the current directory.<br /><br />";
	echo "<textarea cols='80' rows='10'>&lt;?php\n\t// Database Configuration\n\t\$db_host = '$db_host';	// Hostname or IP address for database server\n";
	echo "\t\$db_user = '$db_user';		// Username to connect to database\n\t\$db_pass = '$db_pass';	// Password to connect to database\n";
	echo "\t\$db_name = '$db_name';		// Name of the database on the server\n\t\$db_prefix = '$db_prefix';    // Prefix for table names\n";
	echo "\n\t\$admin_username = 'admin'; // Username to log into the admin system.\n\t\$admin_password = 'admin'; // Password to log into the admin system\n?&gt;</textarea>";
	
	echo "<br><br>You will now need to download the Affiliate Datafeed from <a href='http://www.sierratradingpostaffiliates.com/product-datafeeds.htm'>";
	echo "http://www.sierratradingpostaffiliates.com/product-datafeeds.htm</a> if you haven't already.  You can put it in any directory, but you will need to change the location in <em>parse_feed.php</em>.";
	echo "You will need to download the datafeed on a regular basis (daily is recommended) and run parse_feed.php each time after you download it.";
}
else {	
?>
Requirements:
<ul>
	<li>MySQL 4.0 or later database,</li>
	<li>PHP 4.0 or later</li>
</ul>
<br />

<form action='install.php' method='post'>
	<table align="center">
		<tr><th colspan='3'>Site Configuration Options</th></tr>
		<tr><td>Site Name</td>
			<td align="right"><input type='text' name='site_name' size="50" maxlength="100" value="<?php echo $site_name; ?>"></td>
			<td><img src="images/i.gif" onmouseover="showInfo('shown in the page titles, etc.',this)" onmouseout="hideInfo();"></td></tr>
		<tr><td>Site URL</td>
			<td align="right"><input type='text' name='site_url' size="50" maxlength="100" value="<?php echo $site_url; ?>"></td>
			<td><img src="images/i.gif" onmouseover="showInfo('including any directories',this)" onmouseout="hideInfo();"></td></tr>
		<tr><td>Encrypted Linkshare ID</td>
			<td align="right"><input type='text' name='linkshare_id' size="50" maxlength="100" value="<?php echo $linkshare_id; ?>"></td>
			<td>&nbsp;</td></tr>
		<tr><td colspan='3'>&nbsp;</td></tr>
		<tr><th colspan='3'>DB Configuration Options</th></tr>
		<tr><td>Database Hostname</td>
			<td align="right"><input type='text' name='db_host' size="50" maxlength="100" value="<?php echo $db_host; ?>"></td>
			<td><img src="images/i.gif" onmouseover="showInfo('IP address or hostname for the database server',this)" onmouseout="hideInfo();"></td></tr>
		<tr><td>Database Name</td>
			<td align="right"><input type='text' name='db_name' size="50" maxlength="100" value="<?php echo $db_name; ?>"></td>
			<td><img src="images/i.gif" onmouseover="showInfo('The name of the database',this)" onmouseout="hideInfo();"></td></tr>
		<tr><td>Database Username</td>
			<td align="right"><input type='text' name='db_user' size="50" maxlength="100" value="<?php echo $db_user; ?>"></td>
			<td>&nbsp;</td></tr>
		<tr><td>Database Password</td>
			<td align="right"><input type='text' name='db_pass' size="50" maxlength="100" value="<?php echo $db_pass; ?>"></td>
			<td>&nbsp;</td></tr>
		<tr><td>Table Name Prefix</td>
			<td><input type='text' name='db_prefix' size="10" maxlength="10" value="<?php echo $db_prefix; ?>"></td>
			<td>&nbsp;</td></tr>
		<tr><td colspan='3'>&nbsp;</td></tr>
		<tr><td colspan='3' align="center"><input type="submit" value="Next >>"></td></tr>
	</table>
</form>
<?php 
}
?>

<div id="divInfo" align="center" style="font-family: Verdana; font-size: 9pt;"></div>
</body>
</html>