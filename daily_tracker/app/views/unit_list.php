<?php 
include_once('header.php');
include_once('navbar.php');
// NOTE: You'll need to include your database connection and functions here (e.g., db_connect.php)
// The $viewdata variable will need to be populated by a function that SELECTs all rows from the daily_tracker_habit_units_list table.
?>

<div class="container-fluid mt-3">
    <div class="row justify-content-between align-items-center mb-4">
        <div class="col-md-8">
            <h1 class="display-5 fw-bold">Habit Tracking Units List</h1>
            <p class="text-muted">Manage all measurement units (e.g., minutes, pages, counts, kg) for your habits.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addUnitModal">
                ADD NEW UNIT
            </button>
        </div>
    </div>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>UNIT NAME</th>
                <th>NOTES (0)</th>
                <th>NOTES (1)</th>
                <th>CREATED AT</th>
                <th>EDIT</th>
                <th>DELETE</th>
            </tr>
        </thead>
        <tbody>
            <?php
                // Replace $viewdata with your actual unit data variable (SELECT * FROM daily_tracker_habit_units_list)
                if (isset($viewdata) && is_array($viewdata)) {
                    foreach($viewdata as $key)
                    {
            ?>
                <tr>
                    <td><?php echo $key->unit_name; ?></td>
                    <td><?php echo $key->unit_notes0; ?></td>
                    <td><?php echo $key->unit_notes1; ?></td>
                    <td><?php echo $key->created_at; ?></td>
                    <td>
                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#editUnitModal"
                            data-unit-id="<?php echo $key->unit_id; ?>"
                            data-unit-name="<?php echo $key->unit_name; ?>"
                            data-unit-notes0="<?php echo $key->unit_notes0; ?>"
                            data-unit-notes1="<?php echo $key->unit_notes1; ?>"
                        >
                            EDIT
                        </button>
                    </td>
                    <td>
                        <form method="post" onsubmit="return confirm('ARE YOU SURE YOU WANT TO DELETE THE UNIT: <?php echo $key->unit_name; ?>?\n\nNOTE: This may break habits linked to this unit!');">
                            <input type="hidden" name="unit_id" value="<?php echo $key->unit_id; ?>">
                            <button type="submit" class="btn btn-outline-danger" name="del_unit">
                                DELETE
                            </button>
                        </form>
                    </td>
                </tr>
            <?php
                    }
                } else {
                    echo '<tr><td colspan="6" class="text-center text-muted">No units found. Click "ADD NEW UNIT" to begin.</td></tr>';
                }
            ?>
        </tbody>
    </table>
    
    <p class="text-muted mt-3 mb-3 small">
        <strong>Note:</strong> Deleting a unit might affect any habits currently referencing it.
    </p>

</div>


<div class="modal fade" id="addUnitModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title">Add New Tracking Unit</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form method="POST">
                    <div class="form-floating mb-3 mt-3">
                        <input type="text" class="form-control" id="add_unit_name" placeholder="Enter Unit Name (e.g., pages, sets, times)" name="unit_name" required>
                        <label for="add_unit_name">Unit Name</label>
                    </div>
                    <div class="form-floating mt-3 mb-3">
                        <textarea class="form-control" id="add_unit_notes0" placeholder="Notes 0 (e.g., Short Description)" name="unit_notes0" style="height: 100px"></textarea>
                        <label for="add_unit_notes0">Notes 0</label>
                    </div>
                    <div class="form-floating mt-3 mb-3">
                        <textarea class="form-control" id="add_unit_notes1" placeholder="Notes 1 (e.g., Specific Usage)" name="unit_notes1" style="height: 100px"></textarea>
                        <label for="add_unit_notes1">Notes 1</label>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_unit">Submit Unit</button>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="editUnitModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title">Edit Unit Details</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="edit_unit_id" id="edit_unit_id">
                    <div class="mb-3 mt-3">
                        <label for="edit_unit_name">Unit Name:</label>
                        <input type="text" class="form-control" name="edit_unit_name" id="edit_unit_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_unit_notes0">Notes 0:</label>
                        <textarea class="form-control" name="edit_unit_notes0" id="edit_unit_notes0" style="height: 100px"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_unit_notes1">Notes 1:</label>
                        <textarea class="form-control" name="edit_unit_notes1" id="edit_unit_notes1" style="height: 100px"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" name="edit_unit">Save Changes</button>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<script>
    // JavaScript to populate the EDIT UNIT modal with current data
    var editUnitModal = document.getElementById('editUnitModal');
    editUnitModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;

        // Extract data attributes from the clicked button
        var unit_id = button.getAttribute('data-unit-id');
        var unit_name = button.getAttribute('data-unit-name');
        var unit_notes0 = button.getAttribute('data-unit-notes0');
        var unit_notes1 = button.getAttribute('data-unit-notes1');

        // Populate the modal fields
        document.getElementById('edit_unit_id').value = unit_id;
        document.getElementById('edit_unit_name').value = unit_name;
        document.getElementById('edit_unit_notes0').value = unit_notes0;
        document.getElementById('edit_unit_notes1').value = unit_notes1;
    });
</script>

<?php
include_once('footer.php');
?>