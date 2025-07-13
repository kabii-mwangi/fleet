<?php
require_once 'config.php';
requireAuth();

// Handle form submission
if ($_POST) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO fuel_logs (vehicle_id, date, mileage, fuel_quantity, cost, notes) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)$_POST['vehicle_id'],
            $_POST['date'],
            (int)$_POST['mileage'],
            (float)$_POST['fuel_quantity'],
            (float)$_POST['cost'],
            $_POST['notes']
        ]);
        
        // Update vehicle mileage
        $stmt = $pdo->prepare("UPDATE vehicles SET current_mileage = ? WHERE id = ?");
        $stmt->execute([(int)$_POST['mileage'], (int)$_POST['vehicle_id']]);
        
        header('Location: fuel-logs.php?success=1');
        exit;
    } catch(PDOException $e) {
        $error = "Error adding fuel log: " . $e->getMessage();
    }
}

// Get vehicles for form
try {
    $stmt = $pdo->query("
        SELECT v.*, vc.name as category_name 
        FROM vehicles v 
        JOIN vehicle_categories vc ON v.category_id = vc.id 
        WHERE v.status = 'active'
        ORDER BY v.registration_number
    ");
    $vehicles = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Fuel Log - Fleet Fuel Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Add Fuel Log</h1>
            <p>Record fuel consumption for a vehicle</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="vehicle_id">Vehicle</label>
                        <select id="vehicle_id" name="vehicle_id" class="form-control" required>
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>" data-mileage="<?php echo $vehicle['current_mileage']; ?>">
                                    <?php echo htmlspecialchars($vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mileage">Current Mileage (km)</label>
                        <input type="number" id="mileage" name="mileage" class="form-control" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fuel_quantity">Fuel Quantity (Liters)</label>
                        <input type="number" id="fuel_quantity" name="fuel_quantity" class="form-control" step="0.1" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cost">Cost (KSH)</label>
                        <input type="number" id="cost" name="cost" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <input type="text" id="notes" name="notes" class="form-control" placeholder="e.g., Shell Station, Regular refuel">
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Add Fuel Log</button>
                    <a href="fuel-logs.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-fill mileage based on selected vehicle
        document.getElementById('vehicle_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const currentMileage = selectedOption.getAttribute('data-mileage');
            if (currentMileage) {
                document.getElementById('mileage').value = currentMileage;
            }
        });
    </script>
</body>
</html>