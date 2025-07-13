<?php
require_once 'config.php';
requireAuth();
requirePermission('fuel_logs_view');

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
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
            
            $success = "Fuel log added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding fuel log: " . $e->getMessage();
        }
    }
    
    if ($action === 'edit') {
        try {
            $stmt = $pdo->prepare("
                UPDATE fuel_logs 
                SET vehicle_id = ?, date = ?, mileage = ?, fuel_quantity = ?, cost = ?, notes = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$_POST['vehicle_id'],
                $_POST['date'],
                (int)$_POST['mileage'],
                (float)$_POST['fuel_quantity'],
                (float)$_POST['cost'],
                $_POST['notes'],
                (int)$_POST['log_id']
            ]);
            
            // Update vehicle mileage if needed
            $stmt = $pdo->prepare("UPDATE vehicles SET current_mileage = ? WHERE id = ?");
            $stmt->execute([(int)$_POST['mileage'], (int)$_POST['vehicle_id']]);
            
            $success = "Fuel log updated successfully!";
        } catch(PDOException $e) {
            $error = "Error updating fuel log: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM fuel_logs WHERE id = ?");
            $stmt->execute([(int)$_POST['log_id']]);
            $success = "Fuel log deleted successfully!";
        } catch(PDOException $e) {
            $error = "Error deleting fuel log: " . $e->getMessage();
        }
    }
}

// Get fuel logs with vehicle details
try {
    $stmt = $pdo->query("
        SELECT fl.*, v.registration_number, v.make, v.model, vc.name as category_name 
        FROM fuel_logs fl 
        JOIN vehicles v ON fl.vehicle_id = v.id 
        JOIN vehicle_categories vc ON v.category_id = vc.id 
        ORDER BY fl.date DESC, fl.id DESC
    ");
    $fuelLogs = $stmt->fetchAll();

    // Get vehicles for form
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
    <title>Fuel Logs - Fleet Fuel Management</title>
    <meta name="description" content="Track and manage fuel consumption logs for fleet vehicles with detailed cost and efficiency tracking">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Fuel Logs</h1>
            <p>Track fuel consumption and costs</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Fuel Log Form -->
        <div class="section">
            <h2>Add Fuel Log</h2>
            <div class="form-container">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="vehicle_id">Vehicle</label>
                            <select id="vehicle_id" name="vehicle_id" class="form-control" required>
                                <option value="">Select Vehicle</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo $vehicle['id']; ?>">
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
                            <input type="number" id="fuel_quantity" name="fuel_quantity" class="form-control" step="0.001" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cost">Total Fuel Cost (KSH)</label>
                            <input type="number" id="cost" name="cost" class="form-control" step="0.01" min="0" required>
                        </div>
						
						<div class="form-group">
                            <label for="notes">Notes (Optional) </label>
                            <input type="text" id="notes" name="notes" class="form-control" placeholder="e.g., Refill at Rubis">
                        </div>
						
                        

                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Fuel Log</button>
                </form>
            </div>
        </div>

        <!-- Fuel Logs List -->
        <div class="section">
            <h2>Fuel Log History</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>Mileage</th>
                            <th>Fuel (L)</th>
                            <th>Cost</th>
                            <th>Cost/Liter</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fuelLogs)): ?>
                            <tr>
                                <td colspan="8" class="no-data">No fuel logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fuelLogs as $log): ?>
                                <tr>
                                    <td><?php echo formatDate($log['date']); ?></td>
                                    <td>
                                        <div class="vehicle-info">
                                            <span class="registration"><?php echo htmlspecialchars($log['registration_number']); ?></span>
                                            <span class="vehicle-details"><?php echo htmlspecialchars($log['make'] . ' ' . $log['model']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($log['mileage']); ?> km</td>
                                    <td><?php echo number_format($log['fuel_quantity'], 1); ?>L</td>
                                    <td><?php echo formatCurrency($log['cost']); ?></td>
                                    <td><?php echo formatCurrency($log['cost'] / $log['fuel_quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($log['notes'] ?: '-'); ?></td>
                                    <td>
                                        <button onclick="editFuelLog(<?php echo $log['id']; ?>, <?php echo $log['vehicle_id']; ?>, '<?php echo $log['date']; ?>', <?php echo $log['mileage']; ?>, <?php echo $log['fuel_quantity']; ?>, <?php echo $log['cost']; ?>, '<?php echo htmlspecialchars($log['notes'], ENT_QUOTES); ?>')" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; margin-right: 0.5rem;">Edit</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this fuel log?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Fuel Log Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 600px;">
            <h3>Edit Fuel Log</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="log_id" id="editLogId">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="editVehicleId">Vehicle</label>
                        <select id="editVehicleId" name="vehicle_id" class="form-control" required>
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>">
                                    <?php echo htmlspecialchars($vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editDate">Date</label>
                        <input type="date" id="editDate" name="date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editMileage">Current Mileage (km)</label>
                        <input type="number" id="editMileage" name="mileage" class="form-control" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editFuelQuantity">Fuel Quantity (Liters)</label>
                        <input type="number" id="editFuelQuantity" name="fuel_quantity" class="form-control" step="0.1" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editCost">Cost (KSH)</label>
                        <input type="number" id="editCost" name="cost" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editNotes">Notes (Optional)</label>
                        <input type="text" id="editNotes" name="notes" class="form-control" placeholder="e.g., Shell Station, Regular refuel">
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Update Fuel Log</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-fill mileage based on selected vehicle
        document.getElementById('vehicle_id').addEventListener('change', function() {
            const vehicleId = this.value;
            if (vehicleId) {
                const vehicles = <?php echo json_encode($vehicles); ?>;
                const selectedVehicle = vehicles.find(v => v.id == vehicleId);
                if (selectedVehicle) {
                    document.getElementById('mileage').value = selectedVehicle.current_mileage;
                }
            }
        });

        function editFuelLog(id, vehicleId, date, mileage, fuelQuantity, cost, notes) {
            document.getElementById('editLogId').value = id;
            document.getElementById('editVehicleId').value = vehicleId;
            document.getElementById('editDate').value = date;
            document.getElementById('editMileage').value = mileage;
            document.getElementById('editFuelQuantity').value = fuelQuantity;
            document.getElementById('editCost').value = cost;
            document.getElementById('editNotes').value = notes;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>