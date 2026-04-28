<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('../../config/db.php');
include('../header.php');

// PROTECT PAGE
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$company_id = $_SESSION['company_id'];

// GET categories
$query = "SELECT * FROM categories WHERE company_id = '$company_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("SQL ERROR: " . mysqli_error($conn));
}
?>

<h2>Category List</h2>

<a href="add.php" class="btn btn-add">+ Add Category</a>

<hr>

<?php if (mysqli_num_rows($result) > 0) { ?>
    <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <p>
            <?php echo $row['name']; ?>

            <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">
                Edit
            </a>

            <!-- DELETE BUTTON (OPEN MODAL) -->
            <a href="#"
               class="btn btn-delete"
               onclick="openModal('delete.php?id=<?php echo $row['id']; ?>')">
                Delete
            </a>
        </p>
    <?php } ?>
<?php } else { ?>
    <p>No categories found.</p>
<?php } ?>


<!-- ================= MODAL ================= -->
<div id="deleteModal" class="modal-overlay">

    <div class="modal-box">
        <h3>Delete Category</h3>
        <p>Are you sure you want to delete this category?</p>

        <div class="modal-actions">
            <button class="btn btn-delete" onclick="confirmDelete()">
                Yes, Delete
            </button>

            <button class="btn" onclick="closeModal()">
                Cancel
            </button>
        </div>
    </div>

</div>


<!-- ================= SCRIPT ================= -->
<script>
let deleteUrl = "";

// OPEN MODAL
function openModal(url) {
    deleteUrl = url;
    document.getElementById("deleteModal").style.display = "flex";
}

// CLOSE MODAL
function closeModal() {
    document.getElementById("deleteModal").style.display = "none";
}

// CONFIRM DELETE
function confirmDelete() {
    window.location.href = deleteUrl;
}
</script>