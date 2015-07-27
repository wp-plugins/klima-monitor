<?php

global $wpdb;
global $cm_db_version;
$cm_db_version = '1.0';
global $cm_plugin_version;
$cm_plugin_version = '1.0.2';
// Store the IDs of the generated graphs
global $cm_graphs_id;
// Store the IDs of the generated graphs
$cm_graphs_id = array();
//setup of table name
global $cm_table_name;

// set tablename
$tablename        = 'cm_climadata';
//make it convertable for blog switching
//$wpdb->tables[]   = $tablename;
//prepare it for use in actual blog
$wpdb->$tablename = $wpdb->prefix . $tablename;
$cm_table_name    = $wpdb->$tablename;

// ------------------------------------------------------------------
// Add all your sections, fields and settings during admin_init
// ------------------------------------------------------------------
//

function cm_settings_api_init()
{
    // Add the section to general settings
    add_settings_section('cm_setting_section', 'Einstellungen des Plugin: Klima Monitor', 'cm_setting_section_callback_function', 'general');
    
    // Add the field with the names and function to use for settings
    add_settings_field('cm_db_delete', 'Datenbanktabelle löschen', 'cm_setting_callback_function', 'general', 'cm_setting_section');
    
    register_setting('general', 'cm_db_delete');
}


function cm_setting_section_callback_function()
{
    global $cm_table_name;
    global $cm_db_version;
    global $cm_plugin_version;
    echo '<p>Datenbanktabelle:          ' . $cm_table_name . '</p>';
    echo '<p>Datenbanktabellen Version: ' . $cm_db_version . '</p>';
    echo '<p>Plugin Version:            ' . $cm_plugin_version . '</p>';
    
}

function cm_setting_callback_function()
{
    echo '<input name="cm_db_delete" id="cm_db_delete" type="checkbox" value="1" class="code" ' . checked(1, get_option('cm_db_delete'), false) . ' /> Beim Deaktivieren des Plugin, wird die Tabelle gelöscht!';
}


function cm_climatemonitor_add_button($buttons)
{
    array_push($buttons, "separator", "climatemonitor");
    return $buttons;
}

function cm_climatemonitor_register($plugin_array)
{
    $url = plugins_url('editor_plugin.js', __FILE__);
	$plugin_array['climatemonitor'] = $url;
    return $plugin_array;
}
//create db Table
function cm_create_plugin_table()
{
    global $wpdb;
    global $cm_db_version;
    global $cm_plugin_version;
    global $cm_table_name;
    
    $charset_collate = $wpdb->get_charset_collate();

	if ("null" == get_option( 'cm_db_version','null')) {
	
		$sql = "CREATE TABLE " . $cm_table_name . "(
			`timeStamp` datetime NOT NULL,
			`dateMeasured` date NOT NULL,
			`forecast` VARCHAR(30) NOT NULL,
			`trend` VARCHAR(2) NOT NULL,
			`temperature` double NOT NULL,
			`humidity` varchar(20) NOT NULL,
			`btemp` double NOT NULL,
			`pressure` varchar(11) NOT NULL,
			`altitude` double NOT NULL,
			`moisture` varchar(20) NOT NULL,
			`dewPoint` VARCHAR(20) NOT NULL,
			`spezF` VARCHAR(20) NOT NULL,
			`sattF` VARCHAR(20) NOT NULL,
			PRIMARY KEY (`timeStamp`)
		) " . $charset_collate . ";";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option('cm_db_version', $cm_db_version);
		add_option('cm_plugin_version', $cm_plugin_version);
    }
	
	if ('cm_plugin_version' != get_option( 'cm_plugin_version','1.0.0')) {
		update_option('cm_plugin_version', $cm_plugin_version);
	}
	
   	
}
// delete db Table 
function cm_delete_plugin_table()
{
    
    global $wpdb;
    global $cm_table_name;
    if (1 == get_option('cm_db_delete')) {
        //Delete any options thats stored also
        delete_option('cm_plugin_version');
        delete_option('cm_db_version');
        delete_option('cm_db_delete');
        $wpdb->query("DROP TABLE IF EXISTS $cm_table_name");
    }
}

//Add JS loading to head
function cm_visualization_load_js()
{
    echo '<script type="text/javascript">';
    echo 'google.load(\'visualization\', \'1\', {packages: [\'corechart\'],\'language\':\'de\'});';
    echo '</script>';
}


function cm_visualization_new_div($id, $width, $height)
{
    return "<div id=\"" . $id . "\" style=\"width: " . $width . "; height: " . $height . ";\"></div>";
}

function cm_read_db($day_opt)
{
    global $wpdb;
    global $cm_table_name;
    // get data from db
    
    // create where condition depedence of date option
    $dateChosen = date('Y-m-d', esc_sql(strtotime($day_opt)));
	//echo $dateChosen, $day_opt;
    switch ($day_opt) {
        case "Today":
            $sql_where = "WHERE dateMeasured='" . $dateChosen . "'";
            break;
        case "Yesterday":
            $sql_where = "WHERE dateMeasured='" . $dateChosen . "'";
            break;
        case "Week":
            $dateToday   = date('Y-m-d');
            $dateWeekago = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));
            $sql_where   = "WHERE dateMeasured BETWEEN '" . $dateWeekago . "' and '" . $dateToday . "'";
            break;
        case "Month":
            $actMonth  = date('m');
            $sql_where = "WHERE MONTH(dateMeasured) = '" . $actMonth . "'";
            break;
        case "Year":
            $actYear   = date('Y');
            $sql_where = "WHERE YEAR(dateMeasured) = '" . $actYear . "'";
            break;
		case "latest":
            $sql_where = "WHERE dateMeasured='" . date('y-m-d') . "' ORDER BY timeStamp DESC LIMIT 1";
            break;	
    }
    $sql       = "SELECT * FROM " . $cm_table_name . " " . $sql_where;
    # read data from db	
    $resultSet = $wpdb->get_results($sql, ARRAY_A);
    //echo $sql, "nr:", $wpdb->num_rows;
    return $resultSet;
}
function cm_set_title($options)
{
    global $wpdb;
    $month = array(
        1 => "Januar",
        2 => "Februar",
        3 => "März",
        4 => "April",
        5 => "Mai",
        6 => "Juni",
        7 => "Juli",
        8 => "August",
        9 => "September",
        10 => "Oktober",
        11 => "November",
        12 => "Dezember"
    );
    
    $day_opt    = esc_sql($options[day]);
    $dateChosen = date('Y-m-d', esc_sql(strtotime($day_opt)));
    switch ($day_opt) {
        case "Today":
            $options['title'] = $options['title'] . " - " . date('d.m.Y', strtotime($day_opt));
            break;
        case "Yesterday":
            $options['title'] = $options['title'] . " - " . date('d.m.Y', strtotime($day_opt));
            break;
        case "Week":
            $dateToday        = date('Y-m-d');
            $dateWeekago      = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));
            $options['title'] = $options['title'] . " - " . date('d.m.Y', strtotime($dateWeekago)) . " bis " . date('d.m.Y');
            break;
        case "Month":
            $options['title'] = $options['title'] . " - " . $month[date("n")];
            break;
        case "Year":
            $actYear          = date('Y');
            $options['title'] = $options['title'] . " - " . $actYear;
            break;
    }
	
	return $options;
}

function cm_forecast_shortcode($atts, $content = null)
{
	
	//get data
	$resultSet = cm_read_db('latest');
	foreach ($resultSet as $row) {
        
        $dateMeasured = $row['dateMeasured'];
        $timeStamp    = $row['timeStamp'];
        $temperature  = $row['temperature'];
        $hum          = $row['humidity'];
        $btemp        = $row['btemp'];
        $pressure     = $row['pressure'];
        $forecast     = $row['forecast'];
		$trend        = $row['trend'];
		$dewPoint     = $row['dewPoint'];
		$spezF        = $row['spezF'];
		$sattF        = $row['sattF'];
	}	
	
	$day = $timeStamp;
	$jetzt = time();
	$beginn = mktime(18,0,0);
	$ende = mktime(18+12,0,0);

	if ($beginn <= $jetzt && $jetzt <=$ende) {
 		$night = 'n';
	} else {
		$night = '';
	} 
	$url = plugins_url('img/', dirname(__FILE__));
	$content = "<p><strong>Aktuelles Wetter</strong></p>";
	$content .= "<table>";
	$content .= "<tbody>";
	$content .= "<td>";
	$content .= "<p>Temperatur:</p>";
	$content .= "<p>Luftfeuchtigkeit:</p>";
	$content .= "<p>Luftdruck:</p>";
	$content .= "</td>";
	$content .= "<td>";
	$content .= "<p>" . $temperature . " °C</p>";
	$content .= "<p>" . $hum . " %</p>";
	$content .= "<p>" . $pressure . " hPa</p>";
	$content .= "</td>";
	$content .= "<td>";
	$content .= "<p>Taupunkt:</p>";
	$content .= "<p>spez. Luftfeuchte:</p>";
	$content .= "<p>Sättigungsfeuchte:</p>";
	$content .= "<td>";
	$content .= "<p>" . round($dewPoint,2) . " °C</p>";
	$content .= "<p>" . round($spezF,2) . " g/kg</p>";
	$content .= "<p>" . round($sattF,2) . " g/m^3</p>";
    $content .= "</td>";
	$content .= "</tbody>";
	$content .= "</table>";
	
	$content .= "<table>";
	$content .= "<tbody>";
	$content .= "<td>";
	$content .= "<p>Wettertrend:";
	switch ($forecast) {
		case "6";
			$content .= '<img src="' . $url . 'wetsym' . $night . '1.gif" title="sonnig">';
			break;
		case "5";
			$content .= '<img src="' . $url . 'wetsym' . $night . '2.gif" title="heiter">';
			break;
		case "4";
			$content .= '<img src="' . $url . 'wetsym' . $night . '3.gif" title="bewölkt">';
			break;	
		case "3";
			$content .= '<img src="' . $url . 'wetsym' . $night . '4.gif" title="bedeckt">';
			break;
		case "2";
			$content .= '<img src="' . $url . 'wetsym' . $night . '10.gif" title="wechselhaft">';
			break;
		case "1";
			$content .= '<img src="' . $url . 'wetsym' . $night . '6.gif" title="vereinzelt Regen">';
			break;
		case "0";
			$content .= '<img src="' . $url . 'wetsym' . $night . '7.gif" title="Regen">';
			break;
		case "-1";
			$content .= '<img src="' . $url . 'wetsym' . $night . '12.gif" title="Gewitter">';
			break;
	}
	$content .= "</td>";
	$content .= "<td>";
	$content .= 'Tendenz: ';
	switch ($trend) {
		case "+";
			$content .= '<img src="' . $url . 'trendpfeil_p.png" title="Tendenz steigend"></p>';
			break;
		case "=";
			$content .= '<img src="' . $url . 'trendpfeil_e.png" title="Tendenz gleichbleibend"></p>';
			break;
		case "-";
			$content .= '<img src="' . $url . 'trendpfeil_m.png" title="Tendenz fallend"></p>';
			break;	
	}
    $content .= "</td>";
	$content .= "</tbody>";
	$content .= "</table>";
	
	return $content;
}

//Generate a line chart
function cm_visualization_line_chart_shortcode($atts, $content = null)
{
    //use global variables
    global $cm_graphs_id;
    global $wpdb;
    global $cm_table_name;
    global $cm_graphs_id;

    $cm_options = shortcode_atts(array(
        'width' => "400px",
        'height' => "300px",
        'title' => "Graphx",
        'chart' => "Temp",
        'day' => "Today",
		'trendline' => "no",
        'display' => "Temperatur",
        'scale' => "Celsius",
        'h_title' => "",
        'v_title' => "",
        
        //By default give iterated id to the graph
        'id' => "linechart_" . count($cm_graphs_id)
    ), $atts);
    
    //Register the graph ID
    $cm_graphs_id[] = $cm_options['id'];
    
    //The content that will replace the shortcode
    $graph_content = "";
    
    //Generate the div
    $graph_content .= cm_visualization_new_div($cm_options['id'], $cm_options['width'], $cm_options['height']);
    
    //Generate the Javascript for the graph
    $graph_draw_js = "";
    
    $graph_draw_js .= '<script type="text/javascript">';
    $graph_draw_js .= 'function draw_' . $cm_options['id'] . '(){';
    
    // get data
	$day_opt    = esc_sql($cm_options[day]);
    
    $resultSet = cm_read_db($day_opt);
    //Create the graph
    $graph_draw_js .= 'var data = new google.visualization.DataTable();';
    $chart = esc_sql($cm_options[chart]);
	$trendline = esc_sql($cm_options[trendline]);
    if (0 == ($wpdb->num_rows)) {
        echo "no data in database";
    } else {
        if ('temp' == $chart) {
            $graph_draw_js .= 'data.addColumn("datetime","Zeit");';
            $graph_draw_js .= 'data.addColumn("number","Temperatur [C]");';
            $graph_draw_js .= 'data.addColumn("number","Barometer Temp [C]");';
        } elseif ('press' == $chart) {
            $graph_draw_js .= 'data.addColumn("datetime","Zeit");';
            $graph_draw_js .= 'data.addColumn("number","Luftdruck [hPa]");';
            //$graph_draw_js .= 'data.addColumn("number","Höhe [m]");';
        } elseif ('hum' == $chart) {
            $graph_draw_js .= 'data.addColumn("datetime","Zeit");';
            $graph_draw_js .= 'data.addColumn("number","Luftfeuchte [%]");';
        } elseif ('temphum' == $chart) {
            $graph_draw_js .= 'data.addColumn("datetime","Zeit");';
            $graph_draw_js .= 'data.addColumn("number","Temperatur [C]");';
            $graph_draw_js .= 'data.addColumn("number","Luftfeuchte [%]");';
            $graph_draw_js .= 'data.addColumn("number","Barometer Temp [C]");';
        } elseif ('dew' == $chart) {
            $graph_draw_js .= 'data.addColumn("datetime","Zeit");';
            $graph_draw_js .= 'data.addColumn("number","Taupunkt [C]");';
		} elseif ('hums' == $chart) {
            $graph_draw_js .= 'data.addColumn("datetime","Zeit");';
            $graph_draw_js .= 'data.addColumn("number","spez. Feuchte [g/m^3]");';
			$graph_draw_js .= 'data.addColumn("number","Sättigungsfeuchte [g/m^3]");';
		} elseif ('forecast' == $chart) {
            $graph_draw_js .= 'data.addColumn("datetime","Zeit");';
            $graph_draw_js .= 'data.addColumn("number","Verlauf der Vorhersage");';
		}
    }
    
    $graph_draw_js .= 'data.addRows([';
    $i = null;
    foreach ($resultSet as $row) {
        
        $dateMeasured = $row['dateMeasured'];
		$timeArray = split(" ",$row['timeStamp']);
		$date = split("-",($timeArray[0]));
		$time = split(":",($timeArray[1]));
		$timeStamp = $date[0] . "," . ($date[1]-1) . "," . $date[2] . "," . $time[0] . "," . $time[1] . "," . $time[2]; 
		$temperature  = $row['temperature'];
        $hum          = $row['humidity'];
        $btemp        = $row['btemp'];
        $pressure     = $row['pressure'];
        $altitude     = $row['altitude'];
		$forecast     = $row['forecast'];
		$dewPoint     = $row['dewPoint'];
		$spezF        = $row['spezF'];
		$sattF        = $row['sattF'];
        
        switch ($chart) {
            case "temp";
                $graph_draw_js .= '[new Date(' . $timeStamp . '),' . $temperature . ',' . $btemp . ']';
                break;
            case "temphum";
                $graph_draw_js .= '[new Date(' . $timeStamp . '),' . $temperature . ',' . $hum . ',' . $btemp . ']';
                break;
            case "hum";
                $graph_draw_js .= '[new Date(' . $timeStamp . '),' . $hum . ']';
                break;
            case "press";
                $graph_draw_js .= '[new Date(' . $timeStamp . '),' . $pressure . ']'; //',' . $altitude . ']';
                break;
			case "dew";
                $graph_draw_js .= '[new Date(' . $timeStamp . '),' . $dewPoint . ']'; 
                break;
			case "hums";
                $graph_draw_js .= '[new Date(' . $timeStamp . '),' . $spezF . ',' . $sattF . ']';
                break;
			case "forecast";
                $graph_draw_js .= '[new Date(' . $timeStamp . '),' . $forecast . ']';
                break;
		}
        
        $i = $i + 1;
        if ($i <> ($wpdb->num_rows)) {
            $graph_draw_js .= ',';
        }
    }

    $cm_options = cm_set_title($cm_options);
    $graph_draw_js .= ']);';
    //Create the options
    $graph_draw_js .= 'var options = {';
    $graph_draw_js .= 'curveType: "function", ';
    $graph_draw_js .= 'animation: {duration: 1200, easing:"in"}, ';
    $graph_draw_js .= 'title:"' . $cm_options['title'] . '",';
    $graph_draw_js .= 'width:\'' . $cm_options['width'] . '\',';
    $graph_draw_js .= 'height:\'' . $cm_options['height'] . '\',';
    $graph_draw_js .= 'legend:\'bottom\',';
    $graph_draw_js .= 'backgroundColor: "transparent",';
	if ( 'yes' == $trendline ) {
		$graph_draw_js .= 'trendlines: {
							0: {
								type: "exponential",
								color: "green",
								lineWidth: 1,
								opacity: 0.3,
								showR2: true,
								visibleInLegend: false
							}
						},';
	}
 
    if (!empty($cm_options['h_title']))
		$graph_draw_js .= 'hAxis: {title: "' . $cm_options['h_title'] . '", slantedText:true},';
    
	if ($chart == 'forecast')
	{
			$graph_draw_js .= "vAxis: { ticks: [
			 {v:-1,f:'Gewitter'},
			 {v:0,f:'Regen'},
			 {v:1,f:'vereinzelt Regen'},
			 {v:2,f:'wechselhaft'},
			 {v:3,f:'bedeckt'},
			 {v:4,f:'bewölkt'},
			 {v:5,f:'heiter'},
			 {v:6,f:'sonnig'}
			 ] }";
	} else {
	if (!empty($cm_options['v_title'])) {
        if (('temp' == $chart) or ('temphum' == $chart)) {
            $sql = "SELECT temperature FROM " . $cm_table_name . " WHERE dateMeasured='" . $dateChosen . "' ORDER BY temperature ASC LIMIT 1";
        } elseif ('hum' == $chart) {
            $sql = "SELECT humidity FROM " . $cm_table_name . " WHERE dateMeasured='" . $dateChosen . "' ORDER BY humidity ASC LIMIT 1";
        } elseif ('press' == $chart) {
            $sql = "SELECT pressure FROM " . $cm_table_name . " WHERE dateMeasured='" . $dateChosen . "' ORDER BY pressure ASC LIMIT 1";
        } elseif ('dew' == $chart) {
            $sql = "SELECT dewPoint FROM " . $cm_table_name . " WHERE dateMeasured='" . $dateChosen . "' ORDER BY dewPoint ASC LIMIT 1";
        } elseif ('hums' == $chart) {
            $sql = "SELECT sattF FROM " . $cm_table_name . " WHERE dateMeasured='" . $dateChosen . "' ORDER BY sattF ASC LIMIT 1";
	//	} elseif ('forecast' == $chart) {
    //        $sql = "SELECT forecast FROM " . $cm_table_name . " WHERE dateMeasured='" . $dateChosen . "' ORDER BY forecast ASC LIMIT 1";	
        }
	
		$resultSet = $wpdb->get_results($sql);
		//echo $sql;  
		$graph_draw_js .= 'vAxis: {title: "' . $cm_options['v_title'] . '", viewWindow: 			{min:".$resultSet."}}';
	} else {
		$graph_draw_js .= 'vAxis: {viewWindow: {min:-2}}';
	} 	
}
    
    $graph_draw_js .= '};';
    //Populate the data
    
    $graph_draw_js .= 'var formatter = new google.visualization.DateFormat({pattern: "dd.MM.yyyy H:mm"}).format(data, 0);';
    $graph_draw_js .= 'var graph = new google.visualization.LineChart(document.getElementById(\'' . $cm_options['id'] . '\'));';
    //$graph_draw_js .= 'var graph = new google.charts.Line(document.getElementById(\'' . $cm_options['id'] . '\'));';
    
    $graph_draw_js .= 'graph.draw(data, options);';
    
    $graph_draw_js .= '}';
    $graph_draw_js .= '</script>';
    $graph_content .= $graph_draw_js;
    define("QUICK_CACHE_ALLOWED", false); //Quick Cache will not be caching the site displaying the measurements!
    return $graph_content;
}

//Filter to add JS to load all the graphs previously entered as shortcodes

function cm_visualization_load_graphs_js($content)
{
    //use global variables
    global $cm_graphs_id;
    
    if (count($cm_graphs_id) > 0) {
        $graph_draw_js = "";
        $graph_draw_js .= '<script type="text/javascript">';
        $graph_draw_js .= 'function draw_visualization(){';
        
        foreach ($cm_graphs_id as $graph)
            $graph_draw_js .= 'draw_' . $graph . '();';
        
        $graph_draw_js .= '}';
        $graph_draw_js .= 'google.setOnLoadCallback(draw_visualization);';
        $graph_draw_js .= '</script>';
        
        //Add the graph drawing JS to the content of the post
        $content .= $graph_draw_js;
    }
    return $content;
}

?>
