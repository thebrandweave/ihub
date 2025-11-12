<?php
// ===========================================
// Admin Dashboard (with JWT & Analytics)
// ===========================================
require_once "../auth/check_auth.php";
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
<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
      <div class="text-sm font-medium text-gray-500">Customers</div>
      <div class="text-3xl font-bold text-gray-800 mt-1"><?= $totalUsers ?></div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
      <div class="text-sm font-medium text-gray-500">Admins</div>
      <div class="text-3xl font-bold text-gray-800 mt-1"><?= $totalAdmins ?></div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
      <div class="text-sm font-medium text-gray-500">Products</div>
      <div class="text-3xl font-bold text-gray-800 mt-1"><?= $totalProducts ?></div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
      <div class="text-sm font-medium text-gray-500">Orders</div>
      <div class="text-3xl font-bold text-gray-800 mt-1"><?= $totalOrders ?></div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
      <div class="text-sm font-medium text-gray-500">Pending Orders</div>
      <div class="text-3xl font-bold text-gray-800 mt-1"><?= $pendingOrders ?></div>
    </div>
</div>
<!-- Alerts -->
<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-700 mb-3">⚠️ Alerts</h2>
    <?php if (count($lowStockProducts) > 0): ?>
      <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert">
        <strong>Low Stock Alert:</strong><br>
        <?php foreach ($lowStockProducts as $p): ?>
          <?= htmlspecialchars($p['name']) ?> — <b><?= $p['stock'] ?> left</b><br>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
        ✅ All products have sufficient stock.
      </div>
    <?php endif; ?>
</div>
<!-- Main content grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Sales Chart -->
  <div class="lg:col-span-2">
    <h2 class="text-xl font-semibold text-gray-700 mb-3">📊 Sales Analytics (Last 7 Days)</h2>
    <div class="bg-white p-6 rounded-lg shadow-md">
      <canvas id="salesChart"></canvas>
    </div>
  </div>
  <!-- Recent Orders -->
  <div>
    <h2 class="text-xl font-semibold text-gray-700 mb-3">🧾 Recent Orders</h2>
    <div class="bg-white rounded-lg shadow-md">
      <div class="divide-y divide-gray-200">
        <?php if ($orders): foreach ($orders as $order): ?>
          <div class="p-4 flex justify-between items-center">
            <div>
              <p class="font-semibold text-gray-800"><?= htmlspecialchars($order['full_name']) ?></p>
              <p class="text-sm text-gray-500">#<?= $order['order_id'] ?> - <?= date("d M Y", strtotime($order['order_date'])) ?></p>
            </div>
            <div class="text-right">
              <p class="font-semibold text-gray-800">₹<?= number_format($order['total_amount'], 2) ?></p>
              <p class="text-sm text-gray-500"><?= ucfirst($order['status']) ?></p>
            </div>
          </div>
        <?php endforeach; else: ?>
          <p class="p-4 text-center text-gray-500">No recent orders found.</p>
        <?php endif; ?>
      </div>
    </div>
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