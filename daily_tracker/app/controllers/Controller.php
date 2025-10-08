<?php

require_once ("app/models/Model.php");

class Controller
{
	private $model;
	public function __construct()
	{
		$this->model = new Model();
	}
	// Inside app/controllers/Controller.php

	public function login()
	{
		// session_start();
		// Check if the user is already logged in (simple "middleware")
		if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
			// Redirect to the home page or dashboard if already authenticated
			header('Location: index'); 
			exit();
		}

		$error_message = '';

		if (isset($_POST['submit_login'])) {
			$username = trim($_POST['username']);
			$password = $_POST['password'];
			$captcha  = trim($_POST['captcha']);
			$otp      = trim($_POST['otp']);


			// --- NEW: Read Lat/Lon from POST ---
			$latitude  = isset($_POST['lat']) ? (float)$_POST['lat'] : 0.00;
			$longitude = isset($_POST['lon']) ? (float)$_POST['lon'] : 0.00;



			// 1. Fetch user data based on username
			$user_data = $this->model->selectDataWithCondition('users', ['username' => $username]);

			if (empty($user_data)) {
				$error_message = 'Invalid credentials.';
			} else {
				$user = $user_data[0];

				// 2. Verify all three credentials
				// IMPORTANT: Replace 'password_verify' with plain comparison if you are NOT hashing passwords (NOT RECOMMENDED)
				// $password_match = password_verify($password, $user->password_hash);
				$password_match = ($password === $user->password);
				$captcha_match  = ($captcha === $user->static_captcha);
				$otp_match      = ($otp === $user->static_otp);

				if ($password_match && $captcha_match && $otp_match) {
					// 3. SUCCESS: Set session variables (the "middleware" key)
					// session_start();
					$_SESSION['logged_in'] = true;
					$_SESSION['user_id'] = $user->user_id;
					$_SESSION['username'] = $user->username;
					// $_SESSION['employee_name'] = $user->employee_name;

					$ip_address = $_SERVER['REMOTE_ADDR'];
                    
                    $history_data = [
                        'user_id'    => $user->user_id,
                        'login_time' => date('Y-m-d H:i:s'),
                        'ip_address' => $ip_address,

						// --- NEW: Include location in INSERT query ---
						'latitude'   => $latitude,
						'longitude'  => $longitude
						// ----------------------------------------------
                    ];

					$history_id = $this->model->insertData('user_history', $history_data);
					// $_SESSION['history_id'] is no longer needed since location is saved here.
					// You can remove it or keep it if other parts of the system rely on it.
					// For this change, we'll remove it:
					// $_SESSION['history_id'] = $history_id; // REMOVE

					// Redirect to the protected home page
					header('Location: index');
					exit();
				} else {
					$error_message = 'Invalid credentials or code.';
				}
			}
		}

		// Load the login view
		include ('app/views/login.php'); 
	}

	// -------------------------------------------------------------
	// You will also need a function to enforce the login ("middleware") 
	// at the start of every protected controller method (like index, home, etc.)

	private function enforceLogin() {
		if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
			// Redirect to login page if not logged in
			header('Location: login'); 
			exit();
		}
	}

	public function index()
	{
		$this->enforceLogin(); // Run the middleware check
		include ('app/views/index.php');
		
	}
	public function error404()
	{
		$this->enforceLogin(); // Run the middleware check
		include ('app/views/error404.php');
	}
	public function unit_list()
    {
        $this->enforceLogin(); // Ensure user is logged in
        $table = "daily_tracker_habit_units_list";

        // --- 1. DISPLAY DATA ---
        // Fetch all units from the database
        $viewdata = $this -> model -> selectData ($table);
        // Sort the units alphabetically by name
        usort($viewdata, function($a, $b) 
        {
            return strcasecmp($a->unit_name, $b->unit_name); // A-Z sort by unit name
        });


        // --- 2. EDIT/UPDATE UNIT ---
        if(isset($_REQUEST['edit_unit']))
        {
            $unit_id = $_REQUEST['edit_unit_id'];
            $unit_name = $_REQUEST['edit_unit_name'];
            $unit_notes0 = $_REQUEST['edit_unit_notes0'];
            $unit_notes1 = $_REQUEST['edit_unit_notes1'];
            
            $edit_data=[
                "unit_name"  => $unit_name, 
                "unit_notes0" => $unit_notes0, 
                "unit_notes1" => $unit_notes1,
            ];  
                
            $result = $this -> model -> updateData ($table, $edit_data, ['unit_id' => $unit_id]);

            if(isset($result))
            {
                header("Location: unit_list");
                exit();
            }
            else
            {
                echo "Error Updating Unit";
            }
        }

        // --- 3. ADD NEW UNIT ---
        if(isset($_REQUEST['add_unit']))
        {
            $unit_name = $_REQUEST['unit_name'];
            $unit_notes0 = $_REQUEST['unit_notes0'];
            $unit_notes1 = $_REQUEST['unit_notes1'];
            
            $data=[
                "unit_name"  => $unit_name, 
                "unit_notes0" => $unit_notes0, 
                "unit_notes1" => $unit_notes1,
            ];

            $result = $this -> model -> insertData ($table, $data);
            if(isset($result))
            {
                header("Location: unit_list");
                exit();
            }
            else
            {
                echo "Error Inserting Unit";
            }
        }

        // --- 4. DELETE UNIT ---
        if(isset($_REQUEST['del_unit']))
        {
            $unit_id = $_REQUEST['unit_id'];
            // Since this unit is likely a parent to the habits table, 
            // the database (Foreign Key) will handle the RESTRICT/CASCADE logic.
            
            $result = $this -> model -> deleteData ($table, ['unit_id' => $unit_id]);
            if(isset($result))
            {
                header("Location: unit_list");
                exit();
            }
            else
            {
                echo "Error Deleting Unit. Check if habits are still linked to it.";
            }
        }

        // --- 5. LOAD VIEW ---
        // Pass the $viewdata to the view file (unit_list.php)
        include ('app/views/unit_list.php'); 
    }


    public function habit_list()
    {
        $this->enforceLogin(); // 1. Ensure user is logged in
        
        $table_habits = "daily_tracker_habits";
        $table_units = "daily_tracker_habit_units_list";

        // --- A. DATA FETCH (READ) ---
        // Fetch all habits from the database
        $viewdata = $this->model->selectData($table_habits);
        // Fetch all units (required for the dropdown selectors in the view)
        $unit_list = $this->model->selectData($table_units);

        // Sort the habits alphabetically by name
        usort($viewdata, function($a, $b) 
        {
            return strcasecmp($a->habit_name, $b->habit_name); // A-Z sort by habit name
        });


        // --- B. ADD NEW HABIT (CREATE) ---
        if (isset($_REQUEST['add_habit']))
        {
            $data = [
                "habit_name"  => trim($_REQUEST['habit_name']),
                "unit_id"     => (int)$_REQUEST['unit_id'],
                "habit_notes0" => trim($_REQUEST['habit_notes0']),
                "habit_notes1" => trim($_REQUEST['habit_notes1']),
                // 'is_active' defaults to 1 (TRUE) in the database schema, so we don't need to send it.
            ];

            $result = $this->model->insertData($table_habits, $data);
            if ($result) {
                header("Location: habits");
                exit();
            } else {
                // Error handling (e.g., if habit name is not unique)
                $error = "Error Inserting Habit. Check if the name already exists."; 
            }
        }

        // --- C. EDIT/UPDATE HABIT (UPDATE) ---
        if (isset($_REQUEST['edit_habit']))
        {
            $habit_id = (int)$_REQUEST['edit_habit_id'];
            
            $edit_data = [
                "habit_name"  => trim($_REQUEST['edit_habit_name']), 
                "unit_id"     => (int)$_REQUEST['edit_unit_id'],
                "habit_notes0" => trim($_REQUEST['edit_habit_notes0']), 
                "habit_notes1" => trim($_REQUEST['edit_habit_notes1']),
                "is_active"   => (int)$_REQUEST['edit_is_active'],
            ]; 
                    
            $result = $this->model->updateData($table_habits, $edit_data, ['habit_id' => $habit_id]);

            if ($result) {
                header("Location: habits");
                exit();
            } else {
                $error = "Error Updating Habit.";
            }
        }

        // --- D. DELETE HABIT (DELETE) ---
        if (isset($_REQUEST['del_habit']))
        {
            $habit_id = (int)$_REQUEST['habit_id'];
            
            // Deleting the habit will automatically delete all linked logs 
            // because of the FOREIGN KEY (habit_id) ON DELETE CASCADE defined in the daily_tracker_logs table.
            $result = $this->model->deleteData($table_habits, ['habit_id' => $habit_id]);
            
            if ($result) {
                header("Location: habits");
                exit();
            } else {
                $error = "Error Deleting Habit.";
            }
        }

        // --- E. LOAD VIEW ---
        // Pass the fetched habit data ($viewdata) and unit list ($unit_list) to the view file
        include('app/views/habits.php'); 
    }

        
    public function daily_logs()
    {
        $this->enforceLogin(); // Run the middleware check
        // Determine the date to view, defaulting to today
        $selected_date = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d');
        
        // Table names
        $table_logs = "daily_tracker_logs";
        $table_habits = "daily_tracker_habits";
        $table_units = "daily_tracker_habit_units_list";

        // Initialize variables
        $active_habits = [];
        $logs_map = [];
        $units_map = [];
        $error = null; // Used to display error messages in the view

        // 1. Fetch all units and create a map (unit_id => unit_name)
        $all_units = $this->model->selectData($table_units);
        foreach ($all_units as $unit) {
            $units_map[$unit->unit_id] = $unit->unit_name;
        }

        // 2. Fetch all habits (active and inactive)
        $all_habits = $this->model->selectData($table_habits);
        
        // Sort all habits alphabetically by name
        usort($all_habits, function($a, $b) {
            return strcasecmp($a->habit_name, $b->habit_name); 
        });
        $active_habits = $all_habits; // Passed to the view

        // 3. Handle Data Submission (Saving Logs)
        if (isset($_POST['save_logs']) && isset($_POST['entries'])) {
            $data_to_save = $_POST['entries'];
            $has_error = false;

            foreach ($data_to_save as $habit_id => $entry) {
                
                $habit_id = (int)$habit_id;
                // Sanitize and cast input values
                $value = isset($entry['value']) ? (float)$entry['value'] : 0.00;
                $log_time = trim($entry['log_time'] ?? ''); // *** NEW: Capture the time input (HH:MM) ***
                $notes0 = trim($entry['notes0'] ?? '');
                $notes1 = trim($entry['notes1'] ?? '');
                
                // Optimization: Check if entry is completely empty (value is 0.00, no time, and no notes)
                if ($value == 0.00 && empty($log_time) && empty($notes0) && empty($notes1)) {
                    // If the entire entry is blank, skip it.
                    continue; 
                }

                // Prepare log data fields for C/U operation
                $log_fields = [
                    'value'      => $value,
                    // If log_time is an empty string, set it to NULL for the database.
                    'log_time'   => empty($log_time) ? null : $log_time, // *** NEW LINE ***
                    'log_notes0' => $notes0,
                    'log_notes1' => $notes1,
                ];
                
                // Check for existing record using the UNIQUE KEY (habit_id, log_date)
                $existing_records = $this->model->selectDataWithCondition(
                    $table_logs,
                    ['log_date' => $selected_date, 'habit_id' => $habit_id]
                );

                if (!empty($existing_records)) {
                    // UPDATE existing record
                    $result = $this->model->updateData(
                        $table_logs,
                        $log_fields,
                        ['log_id' => $existing_records[0]->log_id]
                    );
                } else {
                    // INSERT new record
                    $insert_data = array_merge($log_fields, [
                        'log_date' => $selected_date,
                        'habit_id' => $habit_id,
                    ]);
                    
                    $result = $this->model->insertData($table_logs, $insert_data);
                }
                
                if (!$result) {
                    $has_error = true;
                    // Note: In a production app, detailed error logging is necessary here.
                }
            }

            // Redirect back to the same page with the selected date and status
            if (!$has_error) {
                header("Location: daily_logs?date=" . $selected_date . "&status=saved");
                exit();
            } else {
                // Set error message to be displayed after re-fetching logs (Step 4)
                $error = "Error saving one or more daily logs. Please check your inputs.";
            }
        }

        // 4. Fetch Existing Logs for Display
        
        // Fetch existing log data for the selected date
        $existing_logs = $this->model->selectDataWithCondition(
            $table_logs,
            ['log_date' => $selected_date]
        );
        
        // Convert log records into an associative array keyed by habit_id for easy lookup in the view
        foreach ($existing_logs as $record) {
            $logs_map[$record->habit_id] = $record;
        }

        // 5. Load View
        include ('app/views/daily_logs.php');
    }

        public function monthly_habit_report()
    {
        $this->enforceLogin(); 
        
        // Table names
        $table_logs = "daily_tracker_logs";
        $table_habits = "daily_tracker_habits";
        $table_units = "daily_tracker_habit_units_list";

        // 1. Determine the selected month and year
        // Default to the current month/year if nothing is selected
        $selected_year_month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date('Y-m'); 
        
        // Split the 'YYYY-MM' string into separate year and month variables
        list($year, $month) = explode('-', $selected_year_month);
        
        // Calculate the total number of days in the selected month
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // Initialize variables
        $units_map = [];
        $active_habits = [];

        // 2. Fetch data (Habits and Units)
        
        // Fetch ALL habits (active or not) to include historical data in the report
        $all_habits = $this->model->selectData($table_habits); 

        // Sort habits by name (A-Z)
        usort($all_habits, function($a, $b) {
            return strcasecmp($a->habit_name, $b->habit_name); 
        });
        $active_habits = $all_habits;
        
        // Fetch all units and create a map (unit_id => unit_name)
        $all_units = $this->model->selectData($table_units);
        foreach ($all_units as $unit) {
            $units_map[$unit->unit_id] = $unit->unit_name;
        }

        // 3. Fetch ALL log records for the selected month/year
        // Note: This requires your Model's selectDataWithCondition to support LIKE
        $log_records = $this->model->selectDataWithCondition(
            $table_logs,
            ['log_date LIKE' => "{$selected_year_month}%"] 
        );
        
        // 4. Process data into a report map
        
        /* The report map will store summarized data:
        * [habit_id] => [
        * 'total_value' => 150.5,
        * 'max_value' => 10.2,
        * 'log_count' => 15,
        * 'dates' => ['2025-10-01' => {record}, '2025-10-02' => {record}, ...]
        * ]
        */
        $monthly_report_map = [];

        foreach ($log_records as $record) {
            $habit_id = $record->habit_id;
            $value = (float)$record->value;

            // Initialize map entry if it doesn't exist
            if (!isset($monthly_report_map[$habit_id])) {
                $monthly_report_map[$habit_id] = [
                    'total_value' => 0.00,
                    'max_value' => 0.00,
                    'log_count' => 0,
                    'dates' => []
                ];
            }

            // Aggregate totals
            $monthly_report_map[$habit_id]['total_value'] += $value;
            $monthly_report_map[$habit_id]['log_count']++;
            
            // Track maximum value
            if ($value > $monthly_report_map[$habit_id]['max_value']) {
                $monthly_report_map[$habit_id]['max_value'] = $value;
            }
            
            // Store the daily record by date for detailed view
            $monthly_report_map[$habit_id]['dates'][$record->log_date] = $record;
        }

        // 5. Load the view
        // The view file is 'monthly_habit_report.php'
        include ('app/views/monthly_habit_report.php');
    }

	public function logout()
	{
		// Start the session if not already started (just in case)
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		
		// Unset all session variables
		$_SESSION = array();

		// Destroy the session (deletes the session file on the server)
		session_destroy();

		// Clear the session cookie if possible (makes sure the browser forgets the old session ID)
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
		
		// Redirect the user to the login page
		header('Location: login'); 
		exit();
	}


}
?>