<?php
// ===========================================
// Admin Dashboard (with JWT & Analytics)
// ===========================================
require_once "auth/check_auth.php";
require_once "config/config.php";
include "includes/header.php";

// Basic stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Low stock alerts
$lowStockProducts = $pdo->query("SELECT name, stock FROM products WHERE stock < 5 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Sales data (last 7 days)
$salesData = $pdo->query("
    SELECT DATE(order_date) as date, SUM(total_amount) as total
    FROM orders
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(order_date)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for chart
$dates = [];
$totals = [];
foreach ($salesData as $row) {
    $dates[] = date('M d', strtotime($row['date']));
    $totals[] = (float)$row['total'];
}

// Fetch recent orders
$orders = $pdo->query("
    SELECT o.order_id, u.full_name, o.total_amount, o.status, o.order_date
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f8f9fb;
    margin: 0;
    padding: 0;
  }

  .dashboard-container {
    max-width: 1100px;
    margin: 60px auto;
    padding: 20px;
  }

  .dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .dashboard-header h1 {
    font-size: 26px;
    font-weight: 600;
    color: #333;
  }

  .logout-btn {
    background-color: #ff4d4d;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    transition: 0.3s;
  }

  .logout-btn:hover {
    background-color: #e60000;
  }

  .card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 30px;
  }

  .stat-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 25px;
    text-align: center;
    transition: transform 0.2s ease;
  }

  .stat-card:hover {
    transform: translateY(-4px);
  }

  .stat-title {
    font-size: 15px;
    color: #666;
  }

  .stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #111;
    margin-top: 5px;
  }

  .section {
    margin-top: 50px;
  }

  .section h2 {
    font-size: 20px;
    margin-bottom: 15px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  }

  th, td {
    padding: 14px;
    border-bottom: 1px solid #f1f1f1;
    text-align: left;
  }

  th {
    background-color: #f6f6f6;
    font-weight: 600;
    color: #333;
  }

  tr:hover {
    background-color: #f9f9f9;
  }

  .alert-box {
    background: #fff3cd;
    color: #856404;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid #ffeeba;
  }

  .chart-container {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  }

  canvas {
    max-width: 100%;
    height: 300px;
  }
</style>

<div class="dashboard-container">
  <div class="dashboard-header">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['admin_role'] ?? 'Admin') ?></h1>
    <a href="auth/logout.php" class="logout-btn">Logout</a>
  </div>

  <!-- Stats -->
  <div class="card-grid">
    <div class="stat-card">
      <div class="stat-title">Customers</div>
      <div class="stat-value"><?= $totalUsers ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-title">Admins</div>
      <div class="stat-value"><?= $totalAdmins ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-title">Products</div>
      <div class="stat-value"><?= $totalProducts ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-title">Orders</div>
      <div class="stat-value"><?= $totalOrders ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-title">Pending Orders</div>
      <div class="stat-value"><?= $pendingOrders ?></div>
    </div>
  </div>

  <!-- Alerts -->
  <div class="section">
    <h2>⚠️ Alerts</h2>
    <?php if (count($lowStockProducts) > 0): ?>
      <div class="alert-box">
        <strong>Low Stock Alert:</strong><br>
        <?php foreach ($lowStockProducts as $p): ?>
          <?= htmlspecialchars($p['name']) ?> — <b><?= $p['stock'] ?> left</b><br>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="alert-box" style="background:#d4edda; color:#155724; border-color:#c3e6cb;">
        ✅ All products have sufficient stock.
      </div>
    <?php endif; ?>
  </div>

  <!-- Sales Chart -->
  <div class="section">
    <h2>📊 Sales Analytics (Last 7 Days)</h2>
    <div class="chart-container">
      <canvas id="salesChart"></canvas>
    </div>
  </div>

  <!-- Recent Orders -->
  <div class="section">
    <h2>🧾 Recent Orders</h2>
    <table>
      <tr>
        <th>ID</th>
        <th>Customer</th>
        <th>Total</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
      <?php if ($orders): foreach ($orders as $order): ?>
        <tr>
          <td>#<?= $order['order_id'] ?></td>
          <td><?= htmlspecialchars($order['full_name']) ?></td>
          <td>₹<?= number_format($order['total_amount'], 2) ?></td>
          <td><?= ucfirst($order['status']) ?></td>
          <td><?= date("d M Y", strtotime($order['order_date'])) ?></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="5" style="text-align:center;">No orders found.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [{
            label: 'Sales (₹)',
            data: <?= json_encode($totals) ?>,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            borderWidth: 3,
            tension: 0.3,
            fill: true,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value;
                    }
                }
            }
        }
    }
});
</script>

<?php include "includes/footer.php"; ?>
