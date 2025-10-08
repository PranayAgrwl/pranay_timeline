<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata'); // comment

	require_once 'app/controllers/Controller.php';

	$request = $_SERVER['REQUEST_URI'];
	$request = str_replace('/pranay_timeline/daily_tracker', '', $request);

	// if (substr($request, 0, 1) == '/') {
	// 	$request = substr($request, 1);
	// }

	$route = strtok($request, '?');
	// Clean up leading/trailing slashes (e.g., converts '/attendance' to 'attendance')
	$route = trim($route, '/');


	$Controller = new Controller ();
	
	switch ($route) {
		case 'login' :
			$Controller->login();
			break;
		case 'logout' :
			$Controller->logout();
			break;
		case '' :
			$Controller->index();
			break;
		case 'index' :
			$Controller->index();
			break;
		case 'unit_list' :
			$Controller->unit_list();
			break;
		case 'habits' :
			$Controller->habit_list();
			break;
		case 'daily_logs' :
			$Controller->daily_logs();
			break;
		case 'monthly_report' :
			$Controller->monthly_habit_report();
			break;

		// case 'salary_report' :
		// 	$Controller->salary_report();
		// 	break;
		// case 'export_salary_report' :
		// 	$Controller->export_salary_report();
		// 	break;
		default :
			http_response_code ( 404 );
			$Controller->error404();
			break;
	}
	
?>
