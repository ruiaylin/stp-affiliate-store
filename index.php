<?php
	require_once('db.php');
	require_once('functions.php'); // For URL functions
	require_once('config.php'); 

	// Ensure we have a connection to the database
	if(! $conn = db_connect()) {
		throwError("Unable to connect to the database: " . mysql_error());
	}

	// Load options
	$options = array();
	if($res = mysql_query("SELECT opt_name, opt_value FROM ".$db_prefix."options")) {
		while($row = mysql_fetch_assoc($res)) {
			$options[$row['opt_name']] = $row['opt_value'];
		}
	}

	$site_name = isset($options['site_name']) ? $options['site_name'] : "Sierra Trading Post Affiliate Store"; // Set the name of the site, shown in the page titles, etc.
	$site_url = isset($options['site_url']) ? $options['site_url'] : "/"; // Set the root directory for the store - this can be a directory within your site.


	// Figure out which page to show.
	if(isset($_SERVER['REQUEST_URI'])) {
		if(preg_match('@http://[^/]+/(.*)$@',$site_url,$matches)) {
			$site_directory = $matches[1];
			$len = strlen($site_directory);
			$url = parseURL(substr($_SERVER['REQUEST_URI'],strlen($site_directory)));
		}
		else {
			$url = parseURL($_SERVER['REQUEST_URI']);
		}
	}
	else {
		$url = "/";
	}
	//echo $url;
	
	$error = null; // Default to no error, obviously.
	$robots = "index, follow"; // allow indexing by default
	$department = array();
	$brand = 0;
	$product = 0;
	$body = $meta_description = $meta_keywords = $title = null;

	if($url == "/") {  // Display the homepage
		$title = $site_name;
		$meta_keywords = $site_name;
		$meta_description = "Shop for Overstocks and Closeouts on famous name brands at ".$site_name;
		$body = $options['homepage_text'];
	}
	// See if the url matches a file in the /pages/ directory. If so, grab the content.
	elseif(preg_match("@^([^/]+)/(.*)$@",$url,$matches)) {
		$page_temp = $matches[1].".php";
		if(is_readable("pages/$page_temp")) {
			$include_page = "pages/".$page_temp;
			//echo 'includeing'.$include_page;
			$args = $matches[2];
			ob_start();
			include $include_page;
			$body = ob_get_contents();
			ob_end_clean();
		}
	}
	
	// If no pages have matched so far, do further parsing of the URL
	if(!$body) {
		if(preg_match("@(.+/)[^/]+([0-9]{5})/(large-photo)?$@",$url,$matches)) { // URL matches a product or larger image URL
			$large_photo = isset($matches[3]);
			$product_id = $matches[2];
			$directories = array($matches[1]);
		}
		else { // No match yet, try to parse out departments and brands
			$product_id = "";
			$page = 1;
			if(preg_match("@^(.*/)([0-9]+)$@",$url,$matches)) {
				$page = $matches[2];
				$url = $matches[1];
			}

			if(preg_match("@([^/]+/)([^/]+/)$@",$url,$matches)) {
				array_shift($matches);
				$directories = $matches;
			}
			else {
				$directories = array($url);
			}
		}


		// Fetch brand and/or department info as necessary
		foreach($directories as $directory) {
			$res = mysql_query("SELECT type, type_id FROM ".$db_prefix."urls WHERE url = '".mysql_real_escape_string($directory)."'");
			if($res) {
				if($row = mysql_fetch_row($res)) {
					if($row[0] == 'brand') {
						$sql = "SELECT brand_id, brand_name, image, urls.url FROM ".$db_prefix."brands as brands inner join ".$db_prefix."urls as urls on brands.brand_id = urls.type_id WHERE urls.type = 'brand' and brand_id = ".$row[1];
						if($res = mysql_query($sql)) {
							if($brand_data = mysql_fetch_assoc($res)) {
								$brand = array('id'=>$brand_data['brand_id'], 'name'=>$brand_data['brand_name'], 'image'=>$brand_data['image'], 'url'=>$brand_data['url']);
							}
							else {
								throw404(); // No data found for the brand;
							}
						}
						else {
							throwError("Error looking up brand:".mysql_error());
						}
					} else  {
						// Add the department to the array and all departments above it.
						array_push($department,getDepartment($row[1]));
						while($department[0]['level'] != 0) {
							array_unshift($department,getDepartment($department[0]['parent']));
						}
					}
				}
			}
		}

		// If this is a brand or department page, then setup the page title and meta tags
		if($brand || $department) {
			$title = "";
			$title = ($brand) ? $brand['name']." " : "";
			$title.= ($department) ? $department[count($department)-1]['name']." " : "";
			$meta_keywords = $title;
			$meta_description = "Save on ".$title." from ".$site_name;
			$title.= "| ".$site_name;
		}

		// Fetch product info if this is a product page
		if($product_id) {
			$sql = "SELECT p.name, p.product_id, p.url, p.description, p.track_url, p.retail, p.price, p.large_image, p.thumbnail, p.department, p.brand, b.brand_name, b.image, u.url as brand_url  ";
			$sql.= "FROM ".$db_prefix."products as p inner join ".$db_prefix."brands as b on p.brand = b.brand_id inner join ".$db_prefix."urls as u on b.brand_id = u.type_id WHERE u.type = 'brand' and p.product_id = $product_id";
			$res = mysql_query($sql);
			if($res) {
				if($row = mysql_fetch_assoc($res)) {
					$product = array('id'=>$product_id, 'name'=>$row['name'], 'url'=>$row['url'], 'description'=>$row['description'], 'retail'=>$row['retail'], 'thumbnail'=>$row['thumbnail'],
						'price'=>$row['price'], 'department'=>getDepartment($row['department']), 'large_image'=>$row['large_image'], 'track_url'=>$row['track_url'], 'brand'=>$row['brand_name'], 'brand_logo'=>$row['image'], 'brand_url'=>$row['brand_url']);
					$title = $product['name']." | ".$site_name;
					$meta_keywords = $product['name'];
					$meta_description = "Save on ".$product['name']." from ".$site_name;
				}
				else {
					throw404(); // No products found during lookup - could have sold out.
				}
			}
			else {
				throwError("Product lookup failed:".mysql_error());
			}
		}
	}
	
	// If an error has been generated
	if($error) {
		$robots = "noindex, nofollow"; // We don't index error pages.
		$meta_keywords = ""; // Not indexed, so meta-tags don't matter.
		$meta_description = "";
		switch($error['code']) {
			case 404:
				header("HTTP/1.0 404 Not Found");
				$title = "Page Not Found | ".$site_name;
				$body = $error['message'];
				break;
			case 500:
				header("HTTP/1.0 500 Internal Server Error");
				$title = "Internal Server Error | ".$site_name;
				$body = $error['message'];
				break;
			default:
				$title = "Unknown Error | ".$site_name;
				$body = $error;
		}
		// Clear department and brand data so the navigation is correct.
		$department = null;
		$brand = null;
	}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<meta name="description" content="<?php echo str_replace('"',"",$meta_description); ?>" />
	<meta name="keywords" content="<?php echo str_replace('"',"",$meta_keywords); ?>" />
	<meta name="robots" content="<?php echo $robots; ?>" />
	<meta name="googlebot" content="<?php echo $robots; ?>" />
	<title><?php echo $title; ?></title>
	<link type="text/css" rel="stylesheet" href="<?php echo $site_url; ?>style.css">
</head>

<body>

<div id="headerbar">
<?php if(isset($options['header_text'])) { echo $options['header_text']; } ?>
</div>


<?php showLeftNav($department); ?>

<div id="mainBody">
  <div id="BodyAd">
  </div>
<?php

	if($error) {
		echo $body; // For error pages, just show the content.
	}
	else {
		if($product) {
			if($large_photo) {
				showProductImage($product);
			}
			else {
				showProductPage($product);
				writeCatListBox($department,$brand, 1);
			}
		}
		elseif ($department || $brand) {
			writeCatListBox($department,$brand);
			showThumbnails($department, $brand, $page);
			// If this is a brand page with no department, see if we're allowed to show the brand review links
			if($brand && (!$department) && (isset($options['show_brand_links'])?$options['show_brand_links']=='true':1)) {
				echo "<div class='brandlink'><a href='http://www.sierratradingpost.com/Reviews/Brand/".$brand['id']."_".stpSeoUrl($brand['name']).".html'>Read product reviews for ".$brand['name']."</a></div>\n";
			}
		}
		else {
			echo $body;
		}
	}
?>
</div>

<div id="pagefooter">
<?php if(isset($options['footer_text'])) { echo $options['footer_text']; } ?>
</div>

</body>
</html>
