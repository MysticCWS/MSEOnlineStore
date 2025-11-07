<?php
session_start();
include 'dbcon.php';
include 'includes\header.php';
echo ' | Admin Profile';
include 'includes\header2.php';

if (!isset($_SESSION['verified_user_id'])) {
    // Not logged in redirect to login
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['verified_user_id'];

// Ensure only admin can access
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    $_SESSION['status'] = "Access denied. Admins only.";
    header("Location: login.php");
    exit();
}


if (isset($_POST['btnSave'])) {
    $user_name = $_POST['user_name'];
    $country_code = $_POST['country_code'];
    $user_contact_number = $_POST['user_contact_number'];
    $user_contact = $country_code . $user_contact_number;

    $userProperties = [
        'displayName' => $user_name,
        'phoneNumber' => $user_contact,
    ];

    $updatedUser = $auth->updateUser($uid, $userProperties);

    if ($updatedUser) {
        $_SESSION['status'] = "Profile Updated Successfully.";
        header("Location: admin_profile.php");
        exit();
    }
}

include 'includes\navbar_admin.php';
?>

<style>
    #countryCode.form-select.is-valid,
    #countryCode.form-select:valid {
        background-image: var(--bs-form-select-bg-img) !important;
        padding-right: .75rem !important;
    }
</style>

<div class="content">
    <?php
    if (isset($_SESSION['status'])) {
        echo "<h5 id='statusMessage' class='alert alert-success'>" . $_SESSION['status'] . "</h5>";
        unset($_SESSION['status']);
    }
    ?>
    <div class="title">
        <h2>Admin Profile</h2>
    </div>
    
    <div class="profile-container col-md mx-auto px-4 py-4 border rounded bg-white">
        <form id="profileForm" class="was-validated" method="POST">
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="form-floating">
                        <input id="loginEmail" class="form-control" type="text" 
                               name="user_email" placeholder="Email" 
                               value="<?php echo $user->email; ?>" required disabled>
                        <input type="hidden" name="user_email" value="<?php echo $user->email; ?>">
                        <label for="loginEmail">Email</label>
                    </div>
                </div>
            </div>

            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="form-floating">
                        <input id="displayName" class="form-control" type="text" 
                               name="user_name" placeholder="Display Name" 
                               value="<?php echo $user->displayName; ?>" required>
                        <label for="displayName">Display Name</label>
                    </div>
                </div>
            </div>

            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="input-group">
                        <?php
                        $phone = $user->phoneNumber ?? '';
                        preg_match('/^(\+\d{1,2})(\d+)$/', $phone, $matches);
                        $countryCode = $matches[1] ?? '+60';
                        $phone_no_code = $matches[2] ?? '';
                        ?>

                        <select class="form-select" id="countryCode" name="country_code" style="max-width: 100px;" required>
                            <option value="+60" <?php echo ($countryCode === '+60') ? 'selected' : ''; ?>>+60</option>
                            <option value="+65" <?php echo ($countryCode === '+65') ? 'selected' : ''; ?>>+65</option>
                            <option value="+62" <?php echo ($countryCode === '+62') ? 'selected' : ''; ?>>+62</option>
                            <option value="+91" <?php echo ($countryCode === '+91') ? 'selected' : ''; ?>>+91</option>
                        </select>

                        <div class="form-floating flex-grow-1">
                            <input id="contact" class="form-control" 
                                   type="text" 
                                   name="user_contact_number" 
                                   placeholder="1XXXXXXXX" 
                                   pattern="^1[0-9]{8,9}$" 
                                   maxlength="9"
                                   value="<?php echo htmlspecialchars($phone_no_code); ?>" 
                                   required>
                            <label for="contact">Contact Number (1XXXXXXXX)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="submit-login">
                <button id="btnSave" class="btnSave btn btn-outline-success my-2 my-sm-0" 
                        name="btnSave" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>
    
<?php
include 'includes\footer.php';
?>