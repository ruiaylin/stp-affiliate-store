<?php
	require_once('db.php');
	
	// Require Authentication
	if (!(isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] == $admin_username && $_SERVER['PHP_AUTH_PW'] == $admin_password )) {
		header('WWW-Authenticate: Basic realm="Store Admin"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'You must log in to use the store admin interface!';
		exit;
	}

	if(! $conn = db_connect()) {
		echo "Unable to connect to the database: " . mysql_error();
	}
	
	if(isset($_POST)) {
		foreach ($_POST as $key => $value) {
			if(! mysql_query(@sprintf("REPLACE INTO ".$db_prefix."options(opt_value,opt_name) values('%s','%s')", mysql_real_escape_string($value),mysql_real_escape_string($key)))) {
				echo mysql_error();
			}
		}
    		
	}
	
	// Load options
	$options = array();
	if($res = mysql_query("SELECT opt_name, opt_value FROM ".$db_prefix."options")) {
		while($row = mysql_fetch_assoc($res)) {
			$options[$row['opt_name']] = $row['opt_value'];
		}
	}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

<head>
  <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
  <title>SierraTradingPost Store Admin</title>
</head>

<body>

<br />
<a href="dept_admin.php">Department Admin</a>
<br /><br />

<form action='admin.php' method='post'>
	<table>
		<tr><th colspan="2">Store Options</th></tr>
		<tr>
			<td>Site Name</td>
			<td><input type='text' name='site_name' size="50" maxlength="100" value="<?php echo isset($options['site_name']) ? $options['site_name'] : ''; ?>"></td>
		</tr>
		<tr>
			<td>Site URL</td>
			<td><input type='text' name='site_url' size="50" maxlength="100" value="<?php echo isset($options['site_url']) ? $options['site_url'] : ''; ?>"></td>
		</tr>
		<tr>
			<td>Linkshare ID (encrypted ID)</td>
			<td><input type='text' name='linkshare_id' size="15" maxlength="15" value="<?php echo isset($options['linkshare_id']) ? $options['linkshare_id'] : ''; ?>"></td>
		</tr>
		<tr>
			<td>Show Brand Review Links?</td>
			<td><select name='show_brand_links'><option value='true'<?php if(isset($options['show_brand_links']) && $options['show_brand_links'] == 'true') {echo " selected";} ?>>True</option><option value='false'<?php if(isset($options['show_brand_links']) && $options['show_brand_links'] == 'false') {echo " selected";} ?>>False</option></select></td>
		</tr>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td colspan="2">Header HTML</td>
		</tr>
		<tr>
			<td colspan="2"><textarea name="header_text" rows="5" cols="60"><?php echo isset($options['header_text']) ? $options['header_text'] : ''; ?></textarea></td>
		</tr>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td colspan="2">Footer HTML</td>
		</tr>
		<tr>
			<td colspan="2"><textarea name="footer_text" rows="5" cols="60"><?php echo isset($options['footer_text']) ? $options['footer_text'] : ''; ?></textarea></td>
		</tr>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td colspan="2">Left-Side Ad Space</td>
		</tr>
		<tr>
			<td colspan="2"><textarea name="left_nav" rows="5" cols="60"><?php echo isset($options['left_nav']) ? $options['left_nav'] : ''; ?></textarea></td>
		</tr>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td colspan="2">Homepage HTML</td>
		</tr>
		<tr>
			<td colspan="2"><textarea name="homepage_text" rows="5" cols="60"><?php echo isset($options['homepage_text']) ? $options['homepage_text'] : ''; ?></textarea></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="submit" value="Save Options"></td>
		</tr>
	</table>
</form>

</body>
</html>