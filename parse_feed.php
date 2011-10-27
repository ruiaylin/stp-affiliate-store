<?php
	require_once('db.php');
	require_once('functions.php');

	$filename = 'datafeed.txt';		// Location of the datafeed file.

	if($conn = db_connect()) {
		// Load options
		$options = array();
		if($res = mysql_query("SELECT opt_name, opt_value FROM ".$db_prefix."options WHERE opt_name='linkshare_id'")) {
			if($row = mysql_fetch_assoc($res)) {
				$linkshare_id = $row['opt_value'];
			}
			else {
				echo "Could not load encrypted Linkshare ID from database - make sure this is set in the admin interface!\n";
				die;
			}
		}
		else {
			echo "There was a problem loading the encrypted Linkshare ID from database:".mysql_error()."\n";
			die;
		}

		if(file_exists($filename)) {

			// Create arrays to store departments and brands so we dont hit the database too many times
			$depts = array();
			$brands = array();

			// Delete all the products from the database so we can get rid of sold-out products
			mysql_query("DELETE FROM ".$db_prefix."products");

			// Loop through the datafeed
			$handle = fopen($filename, "r");
			while (!feof($handle)) {
				$line = fgets($handle, 8192);
				$line = str_replace("[SITE.CODE]",$linkshare_id,chop($line));

				// Skip the first and last lines
				if(preg_match("/^(HDR)|(TRL)/", $line)) {
					continue;
				}

				$fields = explode("|",$line);

				// Skip the gift cards and anything else without a numeric product id
				if(! is_numeric($fields[2])) {
					continue;
				}

				if($brandid = intval($fields[30])) {
					$brandname = $fields[16];
					$brandimage = $fields[29];
				}

				$deptnames = explode("~~", $fields[4]);
				array_unshift($deptnames, $fields[3]);
				$dept_top_id = $fields[31];
				$dept_top_name = $deptnames[0];
				$dept_mid_id = $fields[32];
				$dept_mid_name = $deptnames[1];
				$dept_end_id = $fields[33];
				$dept_end_name = $deptnames[2];

				// Push the brand info into the array
				if($brandid && !isset($brands[$brandid])) {
					$brands[$brandid] = array();
					$brands[$brandid]['name'] = $brandname;
					$brands[$brandid]['image'] = $brandimage;
				}

				// Push the dept info into the array
				if($dept_end_id && (! isset($depts[$dept_end_id]))) {
					$depts[$dept_end_id] = array();
					$depts[$dept_end_id]['name'] = $dept_end_name;
					$depts[$dept_end_id]['parent'] = $dept_mid_id;
					$depts[$dept_end_id]['level'] = 2;

					if($dept_mid_id && (! isset($depts[$dept_mid_id]))) {
						$depts[$dept_mid_id] = array();
						$depts[$dept_mid_id]['name'] = $dept_mid_name;
						$depts[$dept_mid_id]['parent'] = $dept_top_id;
						$depts[$dept_mid_id]['level'] = 1;

						if($dept_top_id && (! isset($depts[$dept_top_id]))) {
							$depts[$dept_top_id] = array();
							$depts[$dept_top_id]['name'] = $dept_top_name;
							$depts[$dept_top_id]['parent'] = 0;
							$depts[$dept_top_id]['level'] = 0;
						}
					}
				}


				// Insert into the products table
				$query = sprintf("INSERT INTO ".$db_prefix."products(product_id, name, url, thumbnail, large_image, description, retail, price, brand, department, track_url, short_desc) VALUES(%d,'%s','%s','%s','%s','%s',%d,%d,%d,%d,'%s','%s')",
									mysql_real_escape_string($fields[2]), // product_id
									mysql_real_escape_string($fields[1]), // name
									mysql_real_escape_string($fields[5]), // url
									mysql_real_escape_string($fields[6]), // thumbnail
									mysql_real_escape_string($fields[35]), // large_image
									mysql_real_escape_string($fields[9]), // description
									$fields[13], // retail
									$fields[12], // price
									$fields[30], // brand
									$fields[33], // department
									mysql_real_escape_string($fields[27]), // track_url
									mysql_real_escape_string(substr($fields[9],0,strrpos($fields[9]," ",-(strlen($fields[9]) - 180))))); // short_desc

				if(! mysql_query($query)) { echo __FILE__.":".__LINE__." ".mysql_error()." in $query\n"; }
			}
			fclose($handle);

			// Insert into the brands table if the entry doesnt exist yet.
			foreach($brands as $id => $data) {
				$found = 0;
				$res = mysql_query("SELECT brand_id, urls.url FROM ".$db_prefix."brands as brands left join ".$db_prefix."urls as urls on brands.brand_id = urls.type_id WHERE (urls.type='brand' or urls.type IS NULL) AND brand_id = ".$id);
				if($res) {
					if($row = mysql_fetch_row($res)) {
						$found = 1;
						if($row[1]) {
							$query = sprintf("UPDATE ".$db_prefix."urls SET url='%s' WHERE type_id=%d and type='brand'", mysql_real_escape_string(encodeURL($data['name'])), $id);
							if(! mysql_query($query)) { echo __FILE__.":".__LINE__." ".mysql_error()." in $query\n"; }
						}
						else {
							$query = sprintf("INSERT INTO ".$db_prefix."urls(url, type, type_id) VALUES('%s','brand',%d)", mysql_real_escape_string(encodeURL($data['name'])), $id);
							if(! mysql_query($query)) { echo __FILE__.":".__LINE__." ".mysql_error()." in $query\n"; }
						}
					}
				}
				
				if(! $found) {
					$query = sprintf("INSERT INTO ".$db_prefix."brands(brand_id, brand_name, image) VALUES(%s,'%s','%s')", $id, mysql_real_escape_string($data['name']), mysql_real_escape_string($data['image']));
					if(! mysql_query($query)) { echo __FILE__.":".__LINE__." ".mysql_error()." in $query\n"; }
					$query = sprintf("INSERT INTO ".$db_prefix."urls(url, type, type_id) VALUES('%s','brand',%d)", mysql_real_escape_string(encodeURL($data['name'])), $id);
					if(! mysql_query($query)) { echo __FILE__.":".__LINE__." ".mysql_error()." in $query\n"; }
				}
			}

			// Insert into the departments table if the entries dont exist yet.
			foreach($depts as $id => $data) {
				$found = 0;
				$res = mysql_query("SELECT department_id FROM ".$db_prefix."departments WHERE department_id = ".$id);
				if($res) {
					if($row = mysql_fetch_row($res)) {
						$found = 1;
						$query = sprintf("UPDATE ".$db_prefix."departments SET dept_name='%s', parent_id=%d, level=%d WHERE department_id = %d", mysql_real_escape_string($data['name']), $data['parent'], $data['level'], $id);
						if(! mysql_query($query)) { echo __FILE__.":".__LINE__." ".mysql_error()." in $query\n"; }
						$query = sprintf("UPDATE ".$db_prefix."urls SET url='%s' WHERE type_id=%d and type='department'", mysql_real_escape_string(encodeURL($data['name'])), $id);
						$n = 0;
						while(! mysql_query($query)) { $n++; $query = sprintf("UPDATE ".$db_prefix."urls SET url='%s' WHERE type_id=%d and type='department'", mysql_real_escape_string(encodeURL($data['name']."-".$n)), $id); }
					}
				}
				
				if(! $found) {
					$query = sprintf("INSERT INTO ".$db_prefix."departments(department_id, dept_name, parent_id, level) VALUES(%s,'%s',%d, %d)", $id, mysql_real_escape_string($data['name']), $data['parent'], $data['level']);
					if(! mysql_query($query)) { echo __FILE__.":".__LINE__." ".mysql_error()." in $query\n"; }
					$query = sprintf("INSERT INTO ".$db_prefix."urls(url, type, type_id) VALUES('%s','department',%d)", mysql_real_escape_string(encodeURL($data['name'])), $id);
					if(! mysql_query($query)) { echo __FILE__.":".__LINE__." ".mysql_error()." in $query\n"; }
				}
			}

		}
	}
	else {
		echo "Unable to connect to the database: " . mysql_error();
	}
?>