<?php 
    include_once('header.php');
    include_once('navbar.php');

    // NOTE: This page expects the Controller to pass:
    // $selected_year_month: The month currently being viewed (Y-m string)
    // $days_in_month: The number of days in the selected month (integer)
    // $active_habits: Array of all habits (daily_tracker_habits), sorted by name.
    // $monthly_report_map: Aggregated monthly data keyed by habit_id.
?>

<div class="container-fluid mt-3">
    <div class="col-md-12">
        <h1 class="display-5 fw-bold">Monthly Habit Report for: <?php echo htmlspecialchars($selected_year_month); ?></h1>
        <p class="text-muted">Review daily logs and aggregated metrics for all habits.</p>
    </div>

    <!-- Date Selection Form (GET) -->
    <form action="monthly_habit_report" method="GET">
        <div class="row align-items-end mb-4">
            <div class="col-auto">
                <label for="inputMonth" class="form-label">Select Month:</label>
                <input type="month" class="form-control" id="inputMonth" name="month" value="<?php echo htmlspecialchars($selected_year_month); ?>" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </div>
        </div>
    </form>
    
    <hr>
    
    <div class="table-responsive sheet-container">
        <table class="table table-bordered table-hover align-middle monthly-report-table">
            <thead>
                <tr>
                    <th rowspan="2" style="min-width: 200px;">Habit / Unit</th>
                    <th colspan="<?php echo $days_in_month; ?>" class="text-center">Daily Logged Value (by Date)</th>
                    <th colspan="3" class="text-center">Monthly Summary</th> 
                </tr>
                <tr>
                    <?php 
                        // Generate table headers for each day of the month
                        for ($d = 1; $d <= $days_in_month; $d++) {
                            // Display day number
                            echo "<th>" . $d . "</th>";
                        }
                    ?>
                    <th style="min-width: 120px;">Total Value</th>
                    <th style="min-width: 120px;">Max Value</th>
                    <th style="min-width: 120px;">Average Value</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($active_habits)) {
                    foreach ($active_habits as $habit) {
                        $habit_id = $habit->habit_id;
                        $report = $monthly_report_map[$habit_id] ?? null;

                        // Only show the habit if they have records OR are currently active
                        if ($report === null && $habit->is_active != 1) {
                            continue;
                        }

                        // --- Data Aggregation ---
                        $total_value = $report['total_value'] ?? 0.00;
                        $max_value = $report['max_value'] ?? 0.00;
                        $log_count = $report['log_count'] ?? 0;
                        $avg_value = ($log_count > 0) ? ($total_value / $log_count) : 0.00;
                        // ------------------------

                        $row_class = ($habit->is_active == 1) ? 'table-light' : 'table-secondary'; 
                        
                        echo "<tr class='{$row_class}'>";
                        
                        // 1. Habit Name / Unit
                        echo "<td class='fw-bold'>";
                        echo htmlspecialchars($habit->habit_name);
                        echo "<small class='text-muted d-block'>(" . htmlspecialchars($units_map[$habit->unit_id] ?? 'N/A') . ")</small>";
                        echo "</td>";

                        // 2. Daily Data (Loop through all days of the month)
                        for ($d = 1; $d <= $days_in_month; $d++) {
                            $date_key = $selected_year_month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                            $display_value = '-';
                            
                            if (isset($report['dates'][$date_key])) {
                                $record = $report['dates'][$date_key];
                                $daily_value = (float)($record->value ?? 0.00);

                                // Format value if greater than zero
                                $display_value = ($daily_value > 0) ? number_format($daily_value, 2) : '-';
                            }
                            
                            echo "<td class='text-center'>" . $display_value . "</td>";
                        }

                        // 3. Monthly Summary Totals
                        echo "<td class='text-end fw-bold'>" . number_format($total_value, 2) . "</td>";
                        echo "<td class='text-end fw-bold'>" . number_format($max_value, 2) . "</td>";
                        echo "<td class='text-end fw-bold'>" . number_format($avg_value, 2) . "</td>";
                        
                        echo "</tr>";
                    } 
                } else {
                    // colspan is days_in_month + 4 (Habit/Unit + Total + Max + Average)
                    echo '<tr><td colspan="' . ($days_in_month + 4) . '" class="text-center text-muted">
                            No habits found or no data logged for the selected month.
                          </td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
    include_once('footer.php');
?>
