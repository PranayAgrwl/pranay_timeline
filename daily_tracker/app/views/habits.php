<?php
include_once('header.php');
include_once('navbar.php');
// NOTE: This page expects the Controller to pass two variables:
// 1. $viewdata: Array of habit objects (daily_tracker_habits)
// 2. $unit_list: Array of unit objects (daily_tracker_habit_units_list) to populate the dropdowns
?>

<div class="container-fluid mt-3">
    <div class="row justify-content-between align-items-center mb-4">
        <div class="col-md-8">
            <h1 class="display-5 fw-bold">Habit Definition List</h1>
            <p class="text-muted">Manage all trackable habits, their goals, and their measurement units.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-outline-success btn-lg" data-bs-toggle="modal" data-bs-target="#addHabitModal">
                ADD NEW HABIT
            </button>
        </div>
    </div>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>HABIT NAME</th>
                <th>UNIT</th>
                <th>IS ACTIVE</th>
                <th>NOTES (0)</th>
                <th>CREATED AT</th>
                <th>ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php
                if (isset($viewdata) && is_array($viewdata)) {
                    foreach($viewdata as $habit)
                    {
                        // Helper to find the unit name from the unit_id
                        $unit_name = 'N/A';
                        if (isset($unit_list)) {
                            // Find the unit object matching the habit's unit_id
                            $unit = array_filter($unit_list, function($u) use ($habit) {
                                return $u->unit_id == $habit->unit_id;
                            });
                            $unit = reset($unit); // Get the first (and only) matching object
                            if ($unit) {
                                $unit_name = $unit->unit_name;
                            }
                        }
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($habit->habit_name); ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($unit_name); ?></span></td>
                    <td>
                        <?php if ($habit->is_active) : ?>
                            <span class="badge bg-success">Active</span>
                        <?php else : ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($habit->habit_notes0); ?></td>
                    <td><?php echo htmlspecialchars($habit->created_at); ?></td>
                    <td>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary me-2"
                            data-bs-toggle="modal"
                            data-bs-target="#editHabitModal"
                            data-habit-id="<?php echo $habit->habit_id; ?>"
                            data-habit-name="<?php echo htmlspecialchars($habit->habit_name); ?>"
                            data-unit-id="<?php echo $habit->unit_id; ?>"
                            data-habit-notes0="<?php echo htmlspecialchars($habit->habit_notes0); ?>"
                            data-habit-notes1="<?php echo htmlspecialchars($habit->habit_notes1); ?>"
                            data-is-active="<?php echo $habit->is_active; ?>"
                        >
                            <i class="fas fa-edit"></i> EDIT
                        </button>
                        
                        <form method="post" class="d-inline" onsubmit="return confirm('ARE YOU SURE YOU WANT TO DELETE THE HABIT: <?php echo htmlspecialchars($habit->habit_name); ?>?\n\nWARNING: This will delete ALL associated daily logs!');">
                            <input type="hidden" name="habit_id" value="<?php echo $habit->habit_id; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" name="del_habit">
                                <i class="fas fa-trash"></i> DELETE
                            </button>
                        </form>
                    </td>
                </tr>
            <?php
                    }
                } else {
                    echo '<tr><td colspan="6" class="text-center text-muted">No habits found. Click "ADD NEW HABIT" to begin.</td></tr>';
                }
            ?>
        </tbody>
    </table>
    
    <p class="text-muted mt-3 mb-3 small">
        <strong>Warning:</strong> Deleting a habit will automatically remove all associated entries from the Daily Logs table.
    </p>

</div>

<div class="modal fade" id="addHabitModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title">Add New Habit</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form method="POST">
                    <div class="form-floating mb-3 mt-3">
                        <input type="text" class="form-control" id="add_habit_name" placeholder="Enter Habit Name (e.g., Reading)" name="habit_name" required>
                        <label for="add_habit_name">Habit Name</label>
                    </div>

                    <div class="mb-3">
                        <label for="add_unit_id" class="form-label">Measurement Unit:</label>
                        <select class="form-select" id="add_unit_id" name="unit_id" required>
                            <option value="" selected disabled>Select a Unit...</option>
                            <?php 
                                if (isset($unit_list) && is_array($unit_list)) {
                                    foreach($unit_list as $unit) {
                                        echo '<option value="' . $unit->unit_id . '">' . htmlspecialchars($unit->unit_name) . '</option>';
                                    }
                                }
                            ?>
                        </select>
                    </div>

                    <div class="form-floating mt-3 mb-3">
                        <textarea class="form-control" id="add_habit_notes0" placeholder="Goal/Notes 0 (e.g., Target 30 minutes daily)" name="habit_notes0" style="height: 100px"></textarea>
                        <label for="add_habit_notes0">Notes 0</label>
                    </div>
                    
                    <div class="form-floating mt-3 mb-3">
                        <textarea class="form-control" id="add_habit_notes1" placeholder="Notes 1 (e.g., Specific App/Book)" name="habit_notes1" style="height: 100px"></textarea>
                        <label for="add_habit_notes1">Notes 1</label>
                    </div>

                    <button type="submit" class="btn btn-success" name="add_habit">Submit Habit</button>
                </form>
            </div>
            
        </div>
    </div>
</div>

<div class="modal fade" id="editHabitModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title">Edit Habit Details</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="edit_habit_id" id="edit_habit_id">
                    
                    <div class="mb-3 mt-3">
                        <label for="edit_habit_name">Habit Name:</label>
                        <input type="text" class="form-control" name="edit_habit_name" id="edit_habit_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_unit_id" class="form-label">Measurement Unit:</label>
                        <select class="form-select" id="edit_unit_id" name="edit_unit_id" required>
                            <?php 
                                if (isset($unit_list) && is_array($unit_list)) {
                                    foreach($unit_list as $unit) {
                                        // The JavaScript will set the 'selected' attribute below
                                        echo '<option value="' . $unit->unit_id . '">' . htmlspecialchars($unit->unit_name) . '</option>';
                                    }
                                }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_is_active">Status:</label>
                        <select class="form-select" name="edit_is_active" id="edit_is_active" required>
                            <option value="1">Active (Currently Tracking)</option>
                            <option value="0">Inactive (Not Tracking)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_habit_notes0">Notes 0:</label>
                        <textarea class="form-control" name="edit_habit_notes0" id="edit_habit_notes0" style="height: 100px"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_habit_notes1">Notes 1:</label>
                        <textarea class="form-control" name="edit_habit_notes1" id="edit_habit_notes1" style="height: 100px"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" name="edit_habit">Save Changes</button>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<script>
    // JavaScript to populate the EDIT HABIT modal with current data
    var editHabitModal = document.getElementById('editHabitModal');
    editHabitModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;

        // Extract data attributes from the clicked button
        var habit_id = button.getAttribute('data-habit-id');
        var habit_name = button.getAttribute('data-habit-name');
        var unit_id = button.getAttribute('data-unit-id');
        var habit_notes0 = button.getAttribute('data-habit-notes0');
        var habit_notes1 = button.getAttribute('data-habit-notes1');
        var is_active = button.getAttribute('data-is-active'); // '1' or '0'

        // Populate the modal fields
        document.getElementById('edit_habit_id').value = habit_id;
        document.getElementById('edit_habit_name').value = habit_name;
        document.getElementById('edit_habit_notes0').value = habit_notes0;
        document.getElementById('edit_habit_notes1').value = habit_notes1;

        // Populate the dropdowns by setting the correct value
        document.getElementById('edit_unit_id').value = unit_id;
        document.getElementById('edit_is_active').value = is_active;
    });
</script>

<?php
include_once('footer.php');
?>