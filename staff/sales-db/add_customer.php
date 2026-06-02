<?php
require_once 'config.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['accurate_access_token']) || empty($_SESSION['accurate_session'])) {
    header('Location: index.php');
    exit();
}

$error = null;
$success = null;
$currentDbId = $_SESSION['accurate_db_id'];
$currentDbAlias = $_SESSION['accurate_db_alias'] ?? 'Selected Database';

$customerData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'customerNo' => '',
    'billStreet' => '',
    'billCity' => '',
    'billProvince' => '',
    'billZipCode' => '',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerData = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'customerNo' => trim($_POST['customer_no'] ?? ''),
        'billStreet' => trim($_POST['bill_street'] ?? ''),
        'billCity' => trim($_POST['bill_city'] ?? ''),
        'billProvince' => trim($_POST['bill_province'] ?? ''),
        'billZipCode' => trim($_POST['bill_zip'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'transDate' => date('Y-m-d') // Format tanggal yang benar
    ];

    if (empty($customerData['name'])) {
        $error = 'Customer name is required';
    } else {
        $response = addCustomer($customerData);

        file_put_contents('debug_add_customer.log', 
            "Response:\n" . print_r($response, true) . "\n",
            FILE_APPEND
        );

        if ($response['success']) {
            $success = 'Customer successfully added!';
            $customerData = array_fill_keys(array_keys($customerData), '');
        } else {
            $error = $response['error'] ?? 'Failed to add customer';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Customer | Accurate Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .required-field::after {
            content: " *";
            color: red;
        }

        .card-header {
            font-weight: 600;
            background-color: #f8f9fa;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="fas fa-user-plus me-2 text-primary"></i>Add New Customer
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Add Customer</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="alert alert-info mb-3">
            <i class="fas fa-database me-2"></i>
            Currently working on: <strong><?= htmlspecialchars($currentDbAlias) ?></strong>
            <a href="index.php" class="float-end">
                <i class="fas fa-exchange-alt"></i> Change Database
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>Customer Information
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <h5 class="mb-3 border-bottom pb-2">Basic Information</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label required-field">Customer Name</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="<?= htmlspecialchars($customerData['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="customer_no" class="form-label">Customer Number</label>
                                    <input type="text" class="form-control" id="customer_no" name="customer_no"
                                        value="<?= htmlspecialchars($customerData['customerNo']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?= htmlspecialchars($customerData['email']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?= htmlspecialchars($customerData['phone']) ?>">
                                </div>
                            </div>

                            <h5 class="mb-3 border-bottom pb-2">Billing Address</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label for="bill_street" class="form-label">Street</label>
                                    <input type="text" class="form-control" id="bill_street" name="bill_street"
                                        value="<?= htmlspecialchars($customerData['billStreet']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="bill_city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="bill_city" name="bill_city"
                                        value="<?= htmlspecialchars($customerData['billCity']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="bill_province" class="form-label">Province</label>
                                    <input type="text" class="form-control" id="bill_province" name="bill_province"
                                        value="<?= htmlspecialchars($customerData['billProvince']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="bill_zip" class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" id="bill_zip" name="bill_zip"
                                        value="<?= htmlspecialchars($customerData['billZipCode']) ?>">
                                </div>
                            </div>

                            <h5 class="mb-3 border-bottom pb-2">Additional Information</h5>
                            <div class="mb-3">
                                <label for="description" class="form-label">Notes</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($customerData['description']) ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Customer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                bootstrap.Alert.getInstance(alert)?.close();
            }, 5000);
        });
    </script>
</body>

</html>