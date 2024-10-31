<?php
/*
Plugin Name: myScoop Rank Tracker
Plugin URI: http://myscoop.co.za/
Description: This plugin will let you choose to display either a big button of your rank, or your rank history on myScoop
Version: 1.3
Author: Nick Duncan
Author URI: http://nickduncan.co.za
*/

//error_reporting(E_ALL);
add_action("widgets_init", array('myScoop', 'register'));
register_activation_hook( __FILE__, array('myScoop', 'activate'));
register_deactivation_hook( __FILE__, array('myScoop', 'deactivate'));
class myScoop {
  function activate(){
    $data = array( 'blogid' => '','bgcolour' => '#FFFFFF','lastdate' => '', 'dayqty' => '0', 'display' => 'chart');
    if ( ! get_option('myScoop')){
      add_option('myScoop' , $data);
    } else {
      update_option('myScoop' , $data);
    }
  }
  function deactivate(){
      delete_option('myScoop');
  }

  function control(){
    echo '<em>This widget will display the history of your Rank on myScoop</em>';
    $data = get_option('myScoop');
  ?>
  <script type="text/javascript" src="<? echo "/wp-content/plugins/myscoop-rank-display"; ?>/jscolor/jscolor.js"></script>
  <p><label>myScoop Blog ID<input name="blogid" size="5" type="text" value="<?php echo $data['blogid']; ?>" /></label></p>
  <p><label>Background Colour<input name="bgcolour" type="text" size="10" class="color" value="<?php echo $data['bgcolour']; ?>" /></label></p>
  <p><label>Display
          <select name="display">
              <option value='chart' <? if ($data['display'] == 'chart') { echo "selected=\"selected\""; } ?> >Rank Graph</option>
              <option value='rank' <? if ($data['display'] == 'rank') { echo "selected=\"selected\""; } ?> >Rank Only</option>
          </select>
      </label>
  <?php
   if (isset($_POST['blogid'])){
    $data['blogid'] = attribute_escape($_POST['blogid']);
    $data['bgcolour'] = attribute_escape(str_replace("#","",$_POST['bgcolour']));
    $data['display'] = attribute_escape($_POST['display']);
    $data['dayqty'] = 0;
    $data['lastdate'] = 0;
    update_option('myScoop', $data);
  }
  }
  function widget($args){
    echo $args['before_widget'];
    echo $args['before_title'] . 'myScoop' . $args['after_title'];


    $data = get_option('myScoop');
    $blogid = $data['blogid'];
    $bgcolour = str_replace("#","",$data['bgcolour']);
    $display = $data['display'];
    $lastdate = $data['lastdate'];
    $dayqty = $data['dayqty'];
    $getnewinfo = 1;


    if (!$bgcolour) { $bgcolour = "#FFFFFF"; }
    if (!$blogid) { echo '<em>This widget is not properly set up. Please make sure you have inputted your Blog ID in the widget control panel.</em>';  }
    else {


        $today = date("Y-m-d");
        if ($lastdate != $today) {
            $data['lastdate'] = $today;
            $data['dayqty'] = 0;
            update_option("myScoop", $data);
            $getnewinfo = 1;
        }
        elseif ($lastdate == $today) {
            if ($dayqty >= 1) {
                $getnewinfo = 0;
            }
            if ($dayqty < 1) {
                $getnewinfo = 1;
                $data['lastdate'] = $today;
                $data['dayqty'] = 1;
                update_option("myScoop", $data);
            }
        }
        


        if ($display == 'chart') {

            include_once 'php-ofc-library/open_flash_chart_object.php';
            if ($getnewinfo == 1) {
                $url = 'http://myscoop.co.za/chart_data_blog_rank_checker_widget.php?blog='.$blogid.'&bgcolour='.$bgcolour.'';
                $ch = curl_init();
                curl_setopt ($ch, CURLOPT_URL, $url);
                curl_setopt ($ch, CURLOPT_HEADER, 0);
                curl_setopt ($ch, CURLOPT_REFERER,$_SERVER['HTTP_HOST']);
                ob_start();
                curl_exec ($ch);
                curl_close ($ch);
                $string = ob_get_contents();
                ob_end_clean();

                $dir = $cwd = dirname(__FILE__);
                $myFile = $dir."/chartdata.mys";
                $fh = fopen($myFile, 'w') or die("can't open file");
                fwrite($fh, "<?php echo \" \n");
                fwrite($fh, $string);
                fwrite($fh, "\"; \n ?>");
                fclose($fh);
            }
            $blogdir = get_bloginfo('wpurl');
            echo '<div style="border: 2px solid #666666; background-color: #'.$bgcolour.'; padding: 0px; width: 100%; overflow:auto;">';
            open_flash_chart_object( '90%', 120, $blogdir.'/wp-content/plugins/myscoop-rank-display/chartdata.mys', false );
            echo '</div>';
            echo '<p align="center"><a href="http://myscoop.co.za" title="myScoop - A real-time South African blog aggregator" style="color:#850000; font-size:11px; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;">myScoop</a></p>';
            echo $args['after_widget'];
        }
        else if ($display == 'rank') {
            $dir = $cwd = dirname(__FILE__);
            $myFile = $dir."/chartdata-rank.mys";
            if ($getnewinfo == 1) {
                $url = 'http://myscoop.co.za/get_blog_rank.php?blog='.$blogid;
                $ch = curl_init();
                curl_setopt ($ch, CURLOPT_URL, $url);
                curl_setopt ($ch, CURLOPT_HEADER, 0);
                curl_setopt ($ch, CURLOPT_REFERER,$_SERVER['HTTP_HOST']);
                ob_start();
                curl_exec ($ch);
                curl_close ($ch);
                $string = ob_get_contents();
                ob_end_clean();
               
                
                $fh = fopen($myFile, 'w') or die("can't open file");
                fwrite($fh, $string);
                fclose($fh);
            }

            
            $get_rank = file_get_contents($myFile);
            echo "<div style=\"width:70%; border:3px solid #850000; padding:10px; font-family: Georgia, 'Lucida Grande', Verdana, Arial, sans-serif; font-size: 30px; padding-top:3px; height:60px; text-align:center; color:#850000; font-weight:bold; text-shadow:#666 1px 1px 1px;\">
            <span style=\"font-size:14px; color:#000;\">myScoop Rank:</span><br />
            $get_rank<br />

            <a href=\"http://myscoop.co.za/blog/$blogid/\" title=\"myScoop\" style=\"font-size:11px !important; color:#850000 !important;\">View my Profile</a>
            </div>
            ";
         }


        }
    }

  
  function register(){
    register_sidebar_widget('myScoop Rank Tracker', array('myScoop', 'widget'));
    register_widget_control('myScoop Rank Tracker', array('myScoop', 'control'));
  }
}


?>