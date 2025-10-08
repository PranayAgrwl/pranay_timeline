<?php 
    include_once('header.php');
    include_once('navbar.php');

    // NOTE: This page expects the Controller to pass:
    // $selected_date: The date currently being viewed (Y-m-d string)
    // $active_habits: Array of all habits (daily_tracker_habits), sorted by name.
    // $logs_map: Associative array of existing daily_tracker_logs, keyed by habit_id.
    // $units_map: Associative array of unit names (unit_id => unit_name) for display.
?>


<div class="container-fluid mt-3">
    <div class="col-md-8">
        <h1 class="display-5 fw-bold">Daily Habit Log Entry</h1>
        <p class="text-muted">Enter tracked values for all active habits on the selected date. Use the <b>Time</b> column for logging specific times (like wake-up) or context (e.g., morning/night).</p>
        
        <?php if (isset($_GET['status']) && $_GET['status'] === 'saved'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Habit Entries for <b><?php echo htmlspecialchars($selected_date); ?></b> saved successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                **Error:** <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <form action="daily_logs" method="GET">
        <div class="row align-items-end">
            <div class="col-auto mb-3">
                <label for="inputDate" class="form-label">Select Date:</label>
                <input type="date" class="form-control" id="inputDate" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" required>
            </div>
            <div class="col-auto mb-3">
                <button type="submit" class="btn btn-primary">Go</button>
            </div>
        </div>
    </form>

    <hr>
    
    <form action="daily_logs" method="POST">
    
        <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
        <input type="hidden" name="save_logs" value="1">
    
        <div class="table-responsive sheet-container">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width: 5%;">SR.</th>
                        <th>HABIT NAME / GOAL</th>
                        <th>UNIT</th>
                        <th style="width: 12%;">TIME / CONTEXT</th> <th style="width: 15%;">VALUE LOGGED</th>
                        <th style="width: 25%;">NOTES (0)</th>
                        <th style="width: 25%;">NOTES (1)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Check if $active_habits is set and is an array
                    if (!empty($active_habits)) {
                        $i = 1;
                        foreach ($active_habits as $habit) {
                            
                            // Lookup existing log for this habit and date
                            $log = $logs_map[$habit->habit_id] ?? null;

                            // Display IF: 1. Habit is currently active (is_active == 1) 
                            // OR 2. A log record exists for this date ($log is not null)
                            if ($habit->is_active != 1 && $log === null) {
                                continue; 
                            }
                            
                            // Lookup unit name
                            $unit_name = $units_map[$habit->unit_id] ?? 'N/A';

                            // Get existing values or default to empty
                            $value = $log ? htmlspecialchars($log->value) : '';
                            $log_time = $log ? htmlspecialchars($log->log_time) : ''; // *** NEW: Get existing time value ***
                            $log_notes0 = $log ? htmlspecialchars($log->log_notes0) : '';
                            $log_notes1 = $log ? htmlspecialchars($log->log_notes1) : '';
                    ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td>
                            <?php echo htmlspecialchars($habit->habit_name); ?>
                            <small class="text-muted d-block"><?php echo htmlspecialchars($habit->habit_notes0); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($unit_name); ?></span>
                        </td>
                        <td>
                            <input 
                                type='time' 
                                class='form-control' 
                                name='entries[<?php echo $habit->habit_id; ?>][log_time]' 
                                value="<?php echo $log_time; ?>"
                                step="60"
                                >
                        </td>
                        <td>
                            <input 
                                type='number' 
                                class='form-control' 
                                name='entries[<?php echo $habit->habit_id; ?>][value]' 
                                value="<?php echo $value; ?>" 
                                step="0.01"
                                >
                        </td>
                        <td>
                            <input 
                                type='text' 
                                class='form-control' 
                                name='entries[<?php echo $habit->habit_id; ?>][notes0]' 
                                value="<?php echo $log_notes0; ?>"
                                >
                        </td>
                        <td>
                            <input 
                                type='text' 
                                class='form-control'
                                name='entries[<?php echo $habit->habit_id; ?>][notes1]' 
                                value="<?php echo $log_notes1; ?>"
                                >
                        </td>
                    </tr>

                    <?php 
                                $i++;
                            } 
                        } else {
                            // Display a message if no active habits are found
                            echo '<tr><td colspan="7" class="text-center text-muted">
                                    No active habits found. Please define habits on the <a href="habits">Habits Definition page</a>.
                                    </td></tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>
        
        <button type="submit" class="btn btn-success btn-lg mt-3">💾 Save Daily Logs</button>
    </form>
</div>


<?php
    include_once('footer.php');
?>