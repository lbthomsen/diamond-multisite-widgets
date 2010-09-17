<?php
class DiamondBL {

	function DiamondBL() {
		add_action('widgets_init', array($this, 'init_diamondBL'));
	}
		 
		function init_diamondBL() {	
		
		add_shortcode('diamond-bloglist', array($this, 'diamond_bloglist_handler'));
		
		if ( !function_exists('register_sidebar_widget') ||
		!function_exists('register_widget_control') )
		return;
		 
		register_sidebar_widget(array(__('Diamond Bloglist', 'diamond'),'widgets'),array($this, 'widget_endView'));
		register_widget_control(array(__('Diamond Bloglist', 'diamond'), 'widgets'), array($this, 'widget_controlView'));		
		 
	}

	function diamond_bloglist_handler(  $atts, $content = null  ) {
				
		 extract( shortcode_atts( array(
		'exclude' => '',
		'count' => '',
		'format'	 => '',
		'logo_size' => '',
		'default_logo' => '',
		'date_format' => '',
		'before_item' =>'',
		'after_item' => '',
		'before_content' => '',
		'more_text' => '',
		'after_content' => '',
		'order_by' => '',
		'order' => ''
		), $atts ) );
			
		return $this->render_output(split(',',$exclude), $count, html_entity_decode($format), $logo_size, $default_logo, $date_format, html_entity_decode($before_item), html_entity_decode($after_item), html_entity_decode($before_content), html_entity_decode($after_content), $more_text, $order_by, $order);
	}
	

	function widget_endView($args)
	{		
		$bloglist_options = get_option('diamond_bloglist_options');
		extract($bloglist_options);
		$wgt_title = $diamond_bloglist_title;
		$wgt_count = $diamond_bloglist_count;		
		$wgt_miss = split(';', $diamond_bloglist_miss);		
		$wgt_format = $diamond_bloglist_format;		
		$wgt_avsize = $diamond_bloglist_avsize;		
		$wgt_mtext = $diamond_bloglist_mtext;		
		$wgt_defav = $diamond_bloglist_defav;		
		$wgt_dt = $diamond_bloglist_dt;	
	
	//print_r($args);
		
		extract($args);
		
		$output = '';
		
		$output .= $before_widget.$before_title.$wgt_title. $after_title;		
	
		$output .= $this->render_output($wgt_miss, $wgt_count, $wgt_format, $wgt_avsize, $wgt_defav, $wgt_dt, '<li>', '</li>', '<ul>', '</ul>', $wgt_mtext, $diamond_bloglist_order, $diamond_bloglist_order_by) ;
		
		$output .=  $after_widget;
		
		echo $output;
	}
	
	
	function render_output($wgt_miss, $wgt_count, $wgt_format, $wgt_avsize, $wgt_defav, $wgt_dt, $before_item, $after_item, $before_cont, $after_cont, $wgt_mtext, $ord, $ordb)	 {		
		
	
		global $switched;		
		global $wpdb;
		$table_prefix = $wpdb->base_prefix;
		
		if (!isset($wgt_dt) || trim($wgt_dt) =='') 
			$wgt_dt = __('M. d. Y.', 'diamond');
		
		if (!isset($wgt_avsize) || $wgt_avsize == '')
			$wgt_avsize = 96;
			
		if (!isset($before_item) || $before_item == '')
			$before_item = '<li>';	
			
		if (!isset($after_item) || $after_item == '')
			$after_item = '</li>';			
			
		if (!isset($before_cont) || $before_cont == '')
			$before_cont = '<ul>';	
			
		if (!isset($after_cont) || $after_cont == '')
			$after_cont = '</ul>';			
			
		if (!isset($wgt_miss) || $wgt_miss == '' || (count($wgt_count) == 1 &&  $wgt_miss[0] == ''))
			$wgt_miss = array ();					
		
		$sqlstr = '';
		
		$felt = '';$sep = '';
		if (count($wgt_miss) > 0) {
			$felt = ' AND blog_id NOT IN (';
			foreach ($wgt_miss AS $m) {
				$felt .= $sep . $m;			
				$sep = ', ';
			}
			$felt .= ') ';
		}
		
		//print_r($wgt_miss);
		
		$sqlstr = "SELECT blog_id, registered, last_updated from ".$table_prefix ."blogs where  public = 1	AND spam = 0 AND archived = '0' AND deleted = 0 "	. $felt;	
		
		$limit = '';
		if ((int)$wgt_count > 0)
			$limit = ' LIMIT 0, '. (int)$wgt_count;
		
		
		 
		if (!$ord || $ord=='')
			$ord = 0;
			
		$sqlstr .= " ORDER BY ";
		switch ($ord)	 {
			case 0:  $sqlstr .= "path ";
				break;
			case 1:  $sqlstr .= "registered ";
				break;
			case 2:  $sqlstr .= "last_updated ";
				break;	
		}
		
		if (!$ordb || $ordb=='')
			$ordb = 0;
		
		
		switch ($ordb)	 {
			case 0:  $sqlstr .= "asc ";
				break;
			case 1:  $sqlstr .= "desc ";
				break;		
		}
		
		$sqlstr .= $limit;
		
		// echo $sqlstr; 
		$blog_list = $wpdb->get_results($sqlstr, ARRAY_A);
		echo $wpdb->print_error(); 
		//print_r($blog_list);
		
		$output = '';
		$output .=  $before_cont;
		foreach ($blog_list AS $blog) {
			$output .=  $before_item;
			
			$txt = ($wgt_format == '') ? '<b>{title}<b>' : $wgt_format;			
			
			$title = '';$desc = '';$burl = '';$pcount = 0;
			switch_to_blog($blog['blog_id']);					
				if (strpos($txt, '{title}') !== false || strpos($txt, '{title_txt}') !== false)
					$title = get_bloginfo('name');
				if (strpos($txt, '{description}') !== false)
					$desc = get_bloginfo('description');	
				$burl = get_bloginfo('url');
				if (strpos($txt, '{postcount}') !== false)
					$pcount = wp_count_posts()->publish;
			restore_current_blog();

			
			$txt = str_replace('{title}', '<a href="' . $burl .'">'. $title .'</a>' , $txt);
			$txt = str_replace('{more}', '<a href="' . $burl .'">'.$wgt_mtext.'</a>' , $txt);
			$txt = str_replace('{title_txt}', $title , $txt);
			$txt = str_replace('{reg}', date_i18n($wgt_dt, strtotime($blog['registered'])), $txt);
			$txt = str_replace('{last_update}', date_i18n($wgt_dt, strtotime($blog['last_updated'])), $txt);
			$txt = str_replace('{description}', $desc, $txt);
			$txt = str_replace('{postcount}', $pcount , $txt);
			
			
			$output .=  $txt;
			$output .=  $after_item;
		}
		$output .=  $after_cont;
		
		$output .=  $wpdb->print_error();
		
		return $output; 
		
	}
	
	 
	function widget_controlView($is_admin = false)
	{
		$options = get_option('diamond_bloglist_options');
		// Title
		if ($_POST['diamond_bloglist_hidden']) {
			$option=$_POST['wgt_title'];
			$options['diamond_bloglist_title'] = $option;		
		} 
		$wgt_title = $options['diamond_bloglist_title'];
		
		echo '<input type="hidden" name="diamond_bloglist_hidden" value="success" />';
		
		echo '<label for="wgt_title">' . __('Widget Title', 'diamond') . ':<br /><input id="wgt_title" name="wgt_title" type="text" value="'.$wgt_title.'" /></label>';
		
		// Count
		if ($_POST['diamond_bloglist_hidden'])	 {
			$option=$_POST['wgt_count'];
			$options['diamond_bloglist_count'] = $option;
		}
		$wgt_count = $options['diamond_bloglist_count'];
		echo '<br /><label for="wgt_number">' .__('Blogs count', 'diamond') . ':<br /><input id="wgt_count" name="wgt_count" type="text" value="'.$wgt_count.'" /></label>';		
		
		// miss blogs
		if ($_POST['diamond_bloglist_hidden']) {		
			$option=$_POST['wgt_miss'];
			$tmp = '';
			$sep = '';
			if (isset($option) && $option != '')
			foreach ($option AS $op) {			
				$tmp .= $sep .$op;
				$sep = ';';
			}
			$options['wgt_miss'] = $tmp;		
		}
		
		$wgt_miss=$options['diamond_bloglist_miss'];
		$miss = split(';',$wgt_miss);
		echo '<br /><label for="wgt_miss">' . __('Exclude blogs: (The first 50 blogs)','diamond');
		$blog_list = get_blog_list( 0, 50 ); 
		echo '<br />';
		foreach ($blog_list AS $blog) {
			echo '<input id="wgt_miss_'.$blog['blog_id'].'" name="wgt_miss[]" type="checkbox" value="'.$blog['blog_id'].'" ';
			if (in_array($blog['blog_id'], $miss)) echo ' checked="checked" ';
			echo ' />';
			echo get_blog_option( $blog['blog_id'], 'blogname' );
			echo '<br />';
		}
		echo '</label>';		
		
		
		// Format
		if ($_POST['diamond_bloglist_hidden']) {
			$option=$_POST['wgt_format'];
			if (!isset($option) || $option == '')
				$option = '<b>{title}<b>';
			$options['diamond_bloglist_format'] = $option;
		}
		$wgt_format= $options['diamond_bloglist_format'];
		echo '<label for="wgt_number">' . __('Format string', 'diamond') .':<br /><input id="wgt_format" name="wgt_format" type="text" value="'.$wgt_format.'" /></label><br />';		
		echo '{title} - '. __('The blog\'s title', 'diamond').'<br />';
		echo '{title_txt} - '. __('The blog\'s title', 'diamond').' '.__('(without link)', 'diamond').'<br />';
		echo '{description} - '. __('The blog\'s description', 'diamond').'<br />';		
		echo '{reg} - ' . __('The registration\'s date', 'diamond') .'<br />';
		echo '{last_update} - ' . __('The blog\'s last update date', 'diamond') .'<br />';
		echo '{postcount} - ' . __('The blog\'s posts count', 'diamond') .'<br />';		
		echo '{more} - '. __('The "Read More" link', 'diamond') .'<br />';
		echo '<br />';
		
		if ($_POST['diamond_bloglist_hidden'])	
			$options['diamond_bloglist_order'] = $_POST['diamond_bloglist_order'];			
		
		if (!$options['diamond_bloglist_order'] || $options['diamond_bloglist_order']=='')	
			$options['diamond_bloglist_order'] = 0;
		$dor=$options['diamond_bloglist_order'];	
		
		if ($_POST['diamond_bloglist_hidden'])	
			$options['diamond_bloglist_order_by'] = $_POST['diamond_bloglist_order_by'];			
		
		if (!$options['diamond_bloglist_order_by'] || $options['diamond_bloglist_order_by']=='')	
			$options['diamond_bloglist_order_by'] = 0;
		$dorb=$options['diamond_bloglist_order_by'];	
		
		
		echo '<label for="diamond_bloglist_order">' . __('Sort Order', 'diamond') . ':<br />';
		echo'</label>';
		echo '<select id="diamond_bloglist_order" name="diamond_bloglist_order">';
		echo '<option value="0" '. (($dor == 0)? 'selected="selected"' : '') . '>'.__('By Domain', 'diamond').'</option>';
		echo '<option value="1" '. (($dor == 1)? 'selected="selected"' : '') . '>'.__('By Reg. Date', 'diamond').'</option>';
		echo '<option value="2" '. (($dor == 2)? 'selected="selected"' : '') . '>'.__('By Last Update', 'diamond').'</option>';
		echo '</select>';
		
		echo '<select id="diamond_bloglist_order_by" name="diamond_bloglist_order_by">';
		echo '<option value="0" '. (($dorb == 0)? 'selected="selected"' : '') . '>'.__('Ascending', 'diamond').'</option>';
		echo '<option value="1" '. (($dorb == 1)? 'selected="selected"' : '') . '>'.__('Descending', 'diamond').'</option>';
		echo '</select>';
		
		echo '<br />';
		echo '<br />';
		
		
		if ($_POST['diamond_bloglist_hidden'])	 {
			$option=$_POST['wgt_mtext'];
			if (!isset($option) || $option == '')
				$option = __('Read More', 'diamond');
			$options['diamond_bloglist_mtext'] = $option;		
		}
		$wgt_mtext= $options['diamond_bloglist_mtext'];	
		
		echo '<label for="wgt_mtext">' . __('"Read More" link text', 'diamond') . 
		':<br /><input id="wgt_mtext" name="wgt_mtext" type="text" value="'.
		$wgt_mtext.'" /></label>';
		echo '<br />';	
		
		if ($_POST['diamond_bloglist_hidden'])	 {
			$option=$_POST['wgt_dt'];			
			$options['diamond_bloglist_dt'] = $option;		
		}
		$wgt_dt= $options['diamond_bloglist_dt'];	
		if (!isset($wgt_dt) || trim($wgt_dt) =='') {
			$wgt_dt = __('M. d. Y.', 'diamond');
			$options['diamond_bloglist_dt'] = $wgt_dt;				
		}
		
		if ($_POST['diamond_bloglist_hidden'])
			update_option('diamond_bloglist_options', $options);				
		
		echo '<label for="wgt_dt">' . __('DateTime format (<a href="http://php.net/manual/en/function.date.php" target="_blank">manual</a>)', 'diamond') . 
		':<br /><input id="wgt_dt" name="wgt_dt" type="text" value="'.
		$wgt_dt.'" /></label>';
		echo '<br />';	
		
		
		if (!$is_admin) {
			echo '<br />';
			_e('if you like this widget then', 'diamond');
			echo ': <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=paypal%40amegrant%2ehu&lc=HU&item_name=Diamond%20Multisite%20WordPress%20Widget&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" target="_blank">';
			_e('Buy me a beer!', 'diamond');
			echo '</a><br />';
		}
	}
	
	}
	
	$bloglistObj = new DiamondBL ();
 ?>