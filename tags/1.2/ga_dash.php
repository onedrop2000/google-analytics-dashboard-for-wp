<?php
/* 
Plugin Name: Google Analytics Dashboard for WP
Plugin URI: http://www.deconf.com
Description: This plugin will display Google Analytics data and statistics into Admin Dashboard. 
Author: Deconf.com
Version: 1.2 
Author URI: http://www.deconf.com
*/  

function ga_dash_admin() {  
    include('ga_dash_admin.php');  
} 
	
function ga_dash_admin_actions() {  
    add_options_page("Google Analytics Dashboard", "GA Dashboard", 1, "Google_Analytics_Dashboard", "ga_dash_admin");     
}  
  
add_action('admin_menu', 'ga_dash_admin_actions'); 
add_action( 'wp_dashboard_setup', 'ga_dash_setup' );

wp_register_style( 'ga_dash', plugins_url('ga_dash.css', __FILE__) );
wp_enqueue_style( 'ga_dash' );

function ga_dash_setup() {
	if ( current_user_can( 'manage_options' ) ) {
		wp_add_dashboard_widget(
			'ga-dash-widget',
			'Google Analytics Dashboard',
			'ga_dash_content',
			$control_callback = null
		);
	}
}

function ga_dash_content() {
	
	require_once 'functions.php';
	require_once 'src/apiClient.php';
		
	require_once 'src/contrib/apiAnalyticsService.php';
	
	$scriptUri = "http://".$_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF'];

	$client = new apiClient();
	$client->setAccessType('offline'); // default: offline
	$client->setApplicationName('GA Dashboard');
	$client->setClientId(get_option('ga_dash_clientid'));
	$client->setClientSecret(get_option('ga_dash_clientsecret'));
	$client->setRedirectUri($scriptUri);
	$client->setDeveloperKey(get_option('ga_dash_APIKEY')); // API key
	//$client->setUseObjects(true);
	if ((!get_option('ga_dash_clientid')) OR (!get_option('ga_dash_clientsecret')) OR (!get_option('ga_dash_apikey'))){
		
		echo "<div style='padding:20px;'>Client ID, Client Secret or API Key is missing</div>";
		return;
		
	}	
	// $service implements the client interface, has to be set before auth call
	$service = new apiAnalyticsService($client);

	if (isset($_GET['code'])) { // we received the positive auth callback, get the token and store it in session
		$client->authenticate();
		ga_dash_store_token($client->getAccessToken());

	}

	if (ga_dash_get_token()) { // extract token from session and configure client
		$token = ga_dash_get_token();
		$client->setAccessToken($token);
	}

	if (!$client->getAccessToken()) { // auth call to google
		
		$authUrl = $client->createAuthUrl();
		
		if (!isset($_REQUEST['authorize'])){
			echo '<div style="padding:20px;"><form name="input" action="#" method="get">
			<input type="submit" class="button button-primary" name="authorize" value="Authorize Google Analytics Dashboard"/>
		</form></div>';
			return;
		}		
		else{
			echo '<script> window.location="'.$authUrl.'"; </script> ';
			return;
		}

	}
	
	$projectId = get_option('ga_dash_tableid');
	
	$query = ($_REQUEST['query']=="") ? "visits" : $_REQUEST['query'];
	$period = ($_REQUEST['period']=="") ? "last30days" : $_REQUEST['period'];

	switch ($period){

		case 'today'	:	$from = date('Y-m-d'); 
							$to = date('Y-m-d');
							break;

		case 'yesterday'	:	$from = date('Y-m-d', time()-24*60*60);
								$to = date('Y-m-d', time()-24*60*60);
								break;
		
		case 'last7days'	:	$from = date('Y-m-d', time()-7*24*60*60);
							$to = date('Y-m-d');
							break;	

		case 'last14days'	:	$from = date('Y-m-d', time()-14*24*60*60);
							$to = date('Y-m-d');
							break;	
							
		default	:	$from = date('Y-m-d', time()-30*24*60*60);
					$to = date('Y-m-d');
					break;

	}

	switch ($query){

		case 'visitors'	:	$title="Visitors"; break;

		case 'pageviews'	:	$title="Page Views"; break;
		
		case 'visitBounceRate'	:	$title="Bounce Rate"; break;	

		case 'organicSearches'	:	$title="Organic Searches"; break;
		
		default	:	$title="Visits";

	}

	$metrics = 'ga:'.$query;
	$dimensions = 'ga:year,ga:month,ga:day';
	
	try{		
		$data = $service->data_ga->get('ga:'.$projectId, $from, $to, $metrics, array('dimensions' => $dimensions));
	}  
		catch(exception $e) {
		echo "<br />ERROR LOG:<br /><br />".$e; 
	}
	for ($i=0;$i<$data['totalResults'];$i++){

	$chart1_data.="['".$data['rows'][$i][0]."-".$data['rows'][$i][1]."-".$data['rows'][$i][2]."',".round($data['rows'][$i][3],2)."],";

	}

	$metrics = 'ga:visits,ga:visitors,ga:pageviews,ga:visitBounceRate,ga:organicSearches,ga:timeOnSite';
	$dimensions = 'ga:year';
	$data = $service->data_ga->get('ga:'.$projectId, $from, $to, $metrics, array('dimensions' => $dimensions));	

    $code='<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart1);

      function drawChart1() {
        var data = google.visualization.arrayToDataTable(['."
          ['Date', '".$title."'],"
		  .$chart1_data.
		"  
        ]);

        var options = {
		  legend: {position: 'none'},	
		  pointSize: 3,
          title: '".$title."',
		  chartArea: {width: '80%', height: '50%'},
          hAxis: { title: 'Date',  titleTextStyle: {color: 'darkblue'}, showTextEvery: 5}
		};

        var chart = new google.visualization.AreaChart(document.getElementById('chart1_div'));
		chart.draw(data, options);
		
      }

    </script>".'
	<div id="ga-dash">
	<center>
		<div id="buttons_div">
		
			<input class="gabutton" type="button" value="Today" onClick="window.location=\'?period=today&query='.$query.'\'" />
			<input class="gabutton" type="button" value="Yesterday" onClick="window.location=\'?period=yesterday&query='.$query.'\'" />
			<input class="gabutton" type="button" value="Last 7 days" onClick="window.location=\'?period=last7days&query='.$query.'\'" />
			<input class="gabutton" type="button" value="Last 14 days" onClick="window.location=\'?period=last14days&query='.$query.'\'" />
			<input class="gabutton" type="button" value="Last 30 days" onClick="window.location=\'?period=last30days&query='.$query.'\'" />
		
		</div>
		
		<div id="chart1_div"></div>
		
		<div id="details_div">
			
			<table class="gatable" cellpadding="4">
			<tr>
			<td width="24%">Visits:</td>
			<td width="12%" class="gavalue"><a href="?query=visits&period='.$period.'" class="gatable">'.$data['rows'][0][1].'</td>
			<td width="24%">Visitors:</td>
			<td width="12%" class="gavalue"><a href="?query=visitors&period='.$period.'" class="gatable">'.$data['rows'][0][2].'</a></td>
			<td width="24%">Page Views:</td>
			<td width="12%" class="gavalue"><a href="?query=pageviews&period='.$period.'" class="gatable">'.$data['rows'][0][3].'</a></td>
			</tr>
			<tr>
			<td>Bounce Rate:</td>
			<td class="gavalue"><a href="?query=visitBounceRate&period='.$period.'" class="gatable">'.round($data['rows'][0][4],2).'%</a></td>
			<td>Organic Search:</td>
			<td class="gavalue"><a href="?query=organicSearches&period='.$period.'" class="gatable">'.$data['rows'][0][5].'</a></td>
			<td>Pages per Visit:</td>
			<td class="gavalue"><a href="#" class="gatable">'.(($data['rows'][0][1]) ? round($data['rows'][0][3]/$data['rows'][0][1],2) : '0').'</a></td>
			</tr>
			</table>
					
		</div>
	</center>		
	</div>';

	echo $code;
    
}	
?>