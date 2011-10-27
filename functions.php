<?php 
	require_once("config.php");
	
	// Converts $string to an encoded URL
	function encodeURL($string) {
		$url = str_replace(array("'","/","#","?",'"',"(",")",","),"",strtolower($string));
		$url = preg_replace("/ *& */"," and ",$url);
		$url = preg_replace("/  */"," ",$url);
		$url = str_replace(" ", "-", $url);
		$url = preg_replace("/--*/","-",$url);
		
		return $url."/";
	}
	
	function stpSeoUrl($brandname) {
		$url = str_replace("<p>", " p ", $brandname);
		$url = preg_replace("/ *<[^>]+> */"," ",$url);
		$url = str_replace(array(':','#','(R)','"','"',".",")","(","®",",","!","'"),"",$url);
		$url = str_replace(array("°")," ",$url);
		$url = str_replace(array("&","/"),"and",$url);
		$url = str_replace("%", " percent", $url);
		$url = str_replace(" ", "-", $url);
		$url = preg_replace("/--*/","-",$url);
		
		return $url;
	}	
	
	// Returns the URL for the given product.
	function encodeProductURL($name, $product_id) {
		$url = rtrim(encodeURL($name),"/")."-".$product_id;
		
		return $url."/";
	}
	function throwError($msg = "Internal Server Error") {
		global $error;
		$error = array('code'=>500, 'message'=>$msg);
	}
	
	function throw404() {
		global $error;
		$error = array('code'=>404, 'message'=>"Page not found");
	}
	
	function parseURL($uri) {
		if(preg_match("@/([^\?]+)@", $uri, $matches)) {
			return $matches[1];
		}
		else {
			return "/";
		}
	}
	
	function escapeDoubleQuotes($string) {
		return str_replace('"',"''",$string);
	}
	
	// Returns an associate array for the given department id
	function getDepartment($id) {
		global $conn, $db_prefix;
		
		if(!$conn) {
			$conn = db_connect();
		}
		if($res = mysql_query("SELECT department_id, dept_name, parent_id, level, urls.url FROM ".$db_prefix."departments as departments inner join ".$db_prefix."urls as urls on departments.department_id = urls.type_id WHERE urls.type = 'department' and department_id=".$id)) {
			if($row = mysql_fetch_assoc($res)) {
				return array('id'=>$row['department_id'],'name'=>$row['dept_name'], 'level'=>$row['level'], 'parent'=>$row['parent_id'], 'url'=>$row['url']);
			}
		}
		return array();
	}
		
	
	
	// Returns the HTML for the category 
	function writeCatListBox($department = array(), $brand = 0, $is_product = 0) {
		global $conn, $db_prefix;
		global $site_url;
		
		if(!$conn) {
			$conn = db_connect();
		}
		
		$breadcrumb = array();

		if($brand != 0 && !$is_product) {
			$crumb = array('name' => $brand['name'], 'url' => $brand['url']);
			array_unshift($breadcrumb, $crumb);
		}
		

		$current_dept = $department ? $department[count($department)-1]['id'] : 0;
		while($current_dept) {
			//echo $current_dept."<br>\n";
			if($dept = getDepartment($current_dept)) {
				$crumb = array('name' => $dept['name'], 'url' => $dept['url']);
				array_unshift($breadcrumb, $crumb);
				$current_dept = $dept['parent'];
			}
			else {
				throwError("department query failed:".mysql_error());
				$current_dept = 0;
			}
		}
		
		$string = '<div id="CatListBox"><div class="ListHeader">';

		//echo "<!--";print_r($breadcrumb);echo "-->";

		$n = count($breadcrumb) -1;
		for($i=0; $i <= $n; $i++) {
			if($i != 0) {
				$string.= ' <span class="divider">></span> ';
			}
			if($i == $n && !$is_product) {
				$string.= '<h1 class="ListTitleCurrent">'.$breadcrumb[$i]['name'].'</h1>';
			}
			else {
				$string.= '<h2 class="ListTitle"><a href="'.$site_url.$breadcrumb[$i]['url'].'" title="'.$breadcrumb[$i]['name'].'">'.$breadcrumb[$i]['name'].'</a></h2>';
			}
		}
		$string.= '</div>'."\n";
		
		$string.= '<div id="LinksTable">';
		if($brand == 0 && !$is_product) {
			$sql = "SELECT count(products.product_id) as cnt, brands.brand_name, urls.url ";
			$sql.= "FROM ".$db_prefix."departments d1 inner join ".$db_prefix."departments d2 on d1.department_id = d2.parent_id inner join ".$db_prefix."departments d3 on d2.department_id = d3.parent_id inner join ".$db_prefix."products as products on d3.department_id = products.department inner join ".$db_prefix."brands as brands on products.brand = brands.brand_id inner join ".$db_prefix."urls as urls on brands.brand_id = urls.type_id ";
			$sql.= "WHERE d".count($department).".department_id = ".$department[count($department)-1]['id']." and urls.type='brand' ";
			$sql.= "GROUP BY brands.brand_name, urls.url ";
			$sql.= "ORDER BY cnt desc LIMIT 6 ";
			
			if($res = mysql_query($sql)) {
				$string.= '<div><table border="0" width="100%"><tr><td colspan="3"><span class="LinkTitle">Top Brands</span></td></tr><tr>';
				$i=0;
				$base_url = $site_url . ($department?$department[count($department)-1]['url']:'');
				while($row = mysql_fetch_assoc($res)) {
					$i++;
					$string.= '<td><a href="'.$base_url.$row['url'].'" title="'.$row['brand_name'].'">'.$row['brand_name'].'</a></td>'."\n";
					if($i%3 == 0) {
						$string.= "</tr>\n<tr>";
					}
				}
				$string.= "</tr></table></div>\n";
			}
		}

		// Get appropriate categories/departments
		$sql = "SELECT distinct d2.dept_name, d2.department_id, urls.url ";
		if(count($department) == 0) {
			$sql.= "FROM ".$db_prefix."departments d2 inner join ".$db_prefix."departments d1 on d2.department_id = d1.parent_id inner join ".$db_prefix."departments d3 on d1.department_id = d3.parent_id inner join ".$db_prefix."products as products on d3.department_id = products.department inner join ".$db_prefix."urls as urls on d2.department_id = urls.type_id ";
			$sql.= "WHERE urls.type = 'department' and d2.parent_id = 0 ";
		} elseif(count($department) == 1) {
			$sql.= "FROM ".$db_prefix."departments d1 inner join ".$db_prefix."departments d2 on d1.department_id = d2.parent_id inner join ".$db_prefix."departments d3 on d2.department_id = d3.parent_id inner join ".$db_prefix."products as products on d3.department_id = products.department inner join ".$db_prefix."urls as urls on d2.department_id = urls.type_id ";
			$sql.= "WHERE urls.type = 'department' and d1.department_id = ".$department[0]['id']." ";
		} elseif(count($department) == 2) {
			$sql.= "FROM ".$db_prefix."departments d1 inner join ".$db_prefix."departments d2 on d1.department_id = d2.parent_id inner join ".$db_prefix."products products on d2.department_id = products.department inner join ".$db_prefix."urls as urls on d2.department_id = urls.type_id ";
			$sql.= "WHERE urls.type = 'department' and d1.department_id= ".$department[1]['id']." ";
		} else {
			$sql.= "FROM ".$db_prefix."departments d1 inner join ".$db_prefix."departments d2 on d1.department_id = d2.parent_id inner join ".$db_prefix."products products on d2.department_id = products.department inner join ".$db_prefix."urls as urls on d2.department_id = urls.type_id ";
			$sql.= "WHERE urls.type = 'department' and d2.parent_id=".$department[2]['parent']." ";
		}
		$sql.= ($brand?'AND products.brand='.$brand['id'].' ':'')."ORDER BY d2.sort_order, d2.dept_name";
		

		if($res = mysql_query($sql)) {
			$i=0;
			$string.= '<div><table border="0" width="100%"><tr><td colspan="3"><span class="LinkTitle">Categories</span></td></tr><tr>';
			while($row = mysql_fetch_assoc($res)) {
				$i++;
				if(count($department) == 3 && $row['department_id'] == $department[2]['id'] && !$is_product) {
					$string.= '<td><span class="activeCategory">'.$row['dept_name'].'</span></td>';
				}
				else {
					$string.= '<td><a href="'.$site_url.$row['url'].($brand?$brand['url']:'').'" title="'.$row['dept_name'].'">'.$row['dept_name'].'</a></td>';
				}
				if($i%3 == 0) {
					$string.= '</tr><tr>';
				}
			}
			$string.= "</tr></table></div>\n";
		}
		else {
			throwError("Cannot lookup departments: ".mysql_error(). " in $sql.");
		}
		$string.= "</div></div>\n";
		
		echo $string;
	}


	// Returns the HTML for the category 
	function showThumbnails($department = array(), $brand = 0, $page = 1) {
		global $conn, $db_prefix;
		global $site_url; 

		if(!$conn) {
			$conn = db_connect();
		}
		
		$sql = "SELECT products.name, products.product_id, products.thumbnail, products.price, products.retail, products.short_desc, d3.dept_name, urls.url ";
		$from = "FROM ".$db_prefix."departments d1 inner join ".$db_prefix."departments d2 on d1.department_id = d2.parent_id inner join ".$db_prefix."departments d3 on d2.department_id = d3.parent_id inner join ".$db_prefix."products products on d3.department_id = products.department inner join ".$db_prefix."urls urls on d3.department_id = urls.type_id ";
		
		$where = "WHERE urls.type = 'department' ";

		if($brand != 0) {
			$where.= "AND products.brand=".$brand['id']." ";
		}
		
		if($department) {
			$where.= "AND d".count($department).".department_id=".$department[count($department)-1]['id']." ";
		}
		
		if($res = mysql_query("SELECT count(products.product_id) ".$from.$where)) {
			if($row = mysql_fetch_array($res)) {
				$count = $row[0];
			}
			else {
				echo "No products found";
			}
		}
		else {
			throwError("Couldn't count products: ".mysql_error());
		}
		
		$sql.= $from.$where."LIMIT ".(($page-1)*20).",20";
		
		if($res = mysql_query($sql)) {
			ob_start();
			$base_url = $site_url.rtrim($_SERVER['REQUEST_URI'],"0123456789");
			echo '<div id="ItemToolbar"><span>Page ';
			$min = max(1,$page-5);
			$max = min(ceil($count/20),$page+5);
			if($max-$min < 10) {
				$min = max(1,$max-10);
			}
			if($min > 1) {
				echo "<a href='".$base_url."'>&lt;&lt;</a> ";
			}
			for($i=$min; $i <= $max; $i++) {
				if($i == $page) {
					echo "<b>$page</b> ";
				}
				else {
					echo "<a href='".$base_url."$i'>$i</a> ";
				}
			}
			if($max < ceil($count/20)) {
				echo "<a href='".$base_url.ceil($count/20)."'>&gt;&gt;</a> ";
			}
			echo '</span></div>';
			$page_bar = ob_get_contents();
			ob_end_flush();
			
			echo '<table class="ItemTable"><tr>';
			$i=0;
			while($row = mysql_fetch_assoc($res)) {
				$i++;
				if($i>1 && $i%2 == 1) {
					echo '</tr><tr>';
				}
				if($i%2 == 1) {
					echo '<td class="ItemL">';
				}
				else {
					echo '<td class="ItemR">';
				}
				echo '<div class="ItemName"><a href="'.$site_url.$row['url'].encodeProductUrl($row['name'],$row['product_id']).'" title="'.escapeDoubleQuotes($row['name']).'">'.$row['name'].'</a></div>';
				echo '<a href="'.$site_url.$row['url'].encodeProductUrl($row['name'],$row['product_id']).'" title="'.escapeDoubleQuotes($row['name']).'"><img src="'.$row['thumbnail'].'" alt="'.escapeDoubleQuotes($row['name']).'" class="ItemImage" width="120" height="120" /></a>';
				printf('<div class="ItemInfo"><span class="regpriceText">Retail:</span><span class="regprice">$%01.2f</span><br /><span class="price">$%01.2f</span><br /><span class="savings">Save %d%%</span><br /><span class="itemCode">#%s</span><br /></div>',$row['retail'], $row['price'], floor(100-(100*$row['price']/$row['retail'])), $row['product_id']);
				echo '<div class="ItemDescription">'.$row['short_desc'].'... <a class="More" href="'.$site_url.$row['url'].encodeProductUrl($row['name'],$row['product_id']).'">Click to Read More</a></div></td>';
			}
			if($i%2 == 1) {
				echo "<td>&nbsp;</td>";
			}
			echo "</tr></table>";
			echo $page_bar;
		}
		else {
			throwError("Product query failed:".mysql_error()." in $sql");
		}
	}
	
	function showProductPage($product) {
		global $site_url; 

		$current_dept = $product['department']['id'];
		$breadcrumb = array();
		while($current_dept != 0) {
			if($dept = getDepartment($current_dept)) {
				$crumb = array('name' => $dept['name'], 'url' => $dept['url']);
				array_unshift($breadcrumb, $crumb);
				$current_dept = $dept['parent'];
			}
			else {
				throwError("department query failed:".mysql_error());
				$current_dept = 0;
			}
		}
		
		echo '<div class="ListHeader"><div class="ListTitle" style="padding:3px;">';
		$n = count($breadcrumb) -1;
		for($i=0; $i <= $n; $i++) {
			if($i != 0) {
				echo ' <span class="divider">></span> ';
			}
			echo '<a href="'.$site_url.$breadcrumb[$i]['url'].'" title="'.$breadcrumb[$i]['name'].'">'.$breadcrumb[$i]['name'].'</a>';
		}
		
		echo '</div></div>'."\n";
		echo '<div class="Product"><h1 class="ProductName">'.$product['name'].'</h1>';
		echo '<a href="large-photo"><img src="'.$product['thumbnail'].'" alt="'.escapeDoubleQuotes($product['name']).' -- Click here for a larger image" name="MainImage" align="middle" class="ProductImage" /></a>';

		echo '<div class="PricingBox"><div class="brandRow"><a href="'.$site_url.$product['brand_url'].'"><img src="'.$product['brand_logo'].'" alt="'.escapeDoubleQuotes($product['brand']).'" /><br /><span>View more from '.$product['brand'].'</span></a></div>';
		printf('<span class="regpriceText">Retail Price: </span><span class="regprice">$%01.2f</span><br /><span class="price">Our Price: $%01.2f</span> <br /><span class="savings">You Save: $%01.2f (%d%%)</span><br />', $product['retail'], $product['price'], $product['retail']-$product['price'], floor(100-(100*$product['price']/$product['retail'])));
		echo '<span class="itemCode">Item #'.$product['id'].'</span></div>'."\n";

		echo '<div class="ProductDescription"><a href="'.$product['url'].'"><img src="'.$site_url.'images/buy-now.gif" style="float:right;" border="0" alt="Buy Now" /></a>';
        echo '<span class="ProdDescHead"><b>Spy Optics Blizzard Snow Goggles</b></span><br />';
        printf('<span class="ProductInfo"><span class="price">$%01.2f</span><span class="regprice">$%01.2f</span><span class="savings">Save %d%%</span><span class="ProdItemCode">Item #%s</span></span>',$product['price'], $product['retail'], floor(100-(100*$product['price']/$product['retail'])), $product['id']);
        echo '<br /><div class="ProdDescrText">'.$product['description'].'</div></div>';
		echo '<div class="bottomLinks"><span class="emailLink"><a href="javascript:history.go(-1);">Return to Product Listing</a></span></div>';

        echo '</div><img src="'.$product['track_url'].'" />';

	}

	function showProductImage($product) {
		
		echo '<h1 align="center">'.$product['name']."</h1>";
		
		$url = substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],'large-photo'));

		echo '<a href="'.$url.'"><img src="'.$product['large_image'].'" alt="'.escapeDoubleQuotes($product['name']).'" name="MainImage" align="center" /></a>';

	}

	
	function showLeftNav($department) {
		global $site_url, $db_prefix; 

		echo '<div id="leftNav"><div id="Categories">';
		if($res = mysql_query("SELECT department_id, dept_name, urls.url FROM ".$db_prefix."departments as departments inner join ".$db_prefix."urls as urls on departments.department_id = urls.type_id WHERE urls.type = 'department' and parent_id = 0 ORDER BY sort_order, dept_name")) {
			while($row = mysql_fetch_assoc($res)) {
				if($department && $row['department_id'] == $department[0]['id']) {
					printf('<div id="cat%1$d" class="SelectedCategory" ><a href="%3$s" title="%2$s">%2$s</a></div>',$row['department_id'],$row['dept_name'], $site_url.$row['url']);
					if($res2 = mysql_query("SELECT department_id, dept_name, urls.url FROM ".$db_prefix."departments as departments inner join ".$db_prefix."urls as urls on departments.department_id = urls.type_id WHERE urls.type = 'department' and parent_id = ".$row['department_id']." ORDER BY sort_order, dept_name")) {
						echo "<div>";
						while($row2 = mysql_fetch_assoc($res2)) {
							if(isset($department[1]) && $row2['department_id'] == $department[1]['id']) {
								printf('<span class="SelectedL1Item"><a href="%2$s" title="%1$s">%1$s</a></span>'."\n",$row2['dept_name'], $site_url.$row2['url']);
							}
							else {
								printf('<span class="L1Item"><a href="%2$s" title="%1$s">%1$s</a></span>'."\n",$row2['dept_name'], $site_url.$row2['url']);
							}
						}
						echo "</div>";
					}
				}
				else {
					printf('<div id="cat%1$d" class="Category" ><a href="%3$s" title="%2$s">%2$s</a></div>',$row['department_id'],$row['dept_name'], $site_url.$row['url']);
				}
			}
		}
		else {
			throwError("Could not retreive navigation:".mysql_error());
		}
		echo '</div>';
		
		$sql = "SELECT opt_value FROM ".$db_prefix."options WHERE opt_name = 'left_nav'";
		if($res = mysql_query($sql)){ 
			if($row = mysql_fetch_assoc($res)) {
				echo '<div class="LeftNavAd">';
				echo $row['opt_value'];
				echo '</div>';
			}
		}
		else {echo mysql_error();}
		echo '</div>';
	}

?>