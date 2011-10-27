<?php
	require_once('db.php');
	require_once('functions.php'); // For URL functions
	require_once('config.php'); 

	if(! $conn = db_connect()) {
		header("HTTP/1.0 500 Internal Server Error");
		echo "Unable to connect to the database: " . mysql_error();
	}
	
	$title = "Brands | ".$site_name;
	$meta_keywords = "Top brands at ".$site_name;
	$meta_description = "Shop for Famous Name Brands at ".$site_name;
	
	$args = (isset($args) && $args) ? strtolower(substr($args,0,1)) : 'a';
	
	$sql = "SELECT distinct brands.brand_name, urls.url FROM ".$db_prefix."brands as brands inner join ".$db_prefix."products as products on brands.brand_id = products.brand inner join ".$db_prefix."urls as urls on brands.brand_id = urls.type_id WHERE urls.type = 'brand'";
	if(is_numeric($args)) {
		$letter = '#';
		$sql.= 	"AND (brands.brand_name > 0 AND brands.brand_name < 'a')";
	} else {
		$letter = $args;
		$sql.= 	"AND lcase(left(brands.brand_name,1)) = '$letter'";
	}
	$sql.= " ORDER BY brands.brand_name";
	
	// Write letter bar
	$bar = array('#','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$links = array('0','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
	echo "<div id='CatListBox'><div class='ListHeader'><div class='letterbar'>";
	for($i=0; $i<count($bar); $i++) {
		if($links[$i] == $letter) {
			echo "<b>".$bar[$i]."</b> ";
		}
		else {
			echo "<a href='".$site_url."brands/".$links[$i]."'>".$bar[$i]."</a> ";
		}
	}
	echo "</div></div>";	
	
	if($res = mysql_query($sql)) {
		echo "<div id='LinksTable'><table border='0' width='100%'><tr>";
		$i = 0;
		while($row = mysql_fetch_assoc($res)) {
			$i++;
			echo "<td><a href='".$site_url.$row['url']."'>".$row['brand_name']."</a></td>\n";
			if($i%3 == 0) {
				echo "</tr><tr>\n";
			}
		}
		echo "</table></div></div>";
	}
	else {
		throwError("Couldn't fetch brands: ".mysql_error());
	}
	
?>