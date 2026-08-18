<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();
$movieTotal = (int) (db_select_one('SELECT COUNT(*) AS total FROM movies')['total'] ?? 0);
$userTotal = (int) (db_select_one('SELECT COUNT(*) AS total FROM users')['total'] ?? 0);

$monthlyRows = db_select(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS total
     FROM movies
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
     GROUP BY month_key
     ORDER BY month_key"
);
$monthlyMap = [];
foreach ($monthlyRows as $row) {
    $monthlyMap[$row['month_key']] = (int) $row['total'];
}

$monthLabels = [];
$monthValues = [];
for ($i = 11; $i >= 0; --$i) {
    $monthKey = date('Y-m', strtotime("-{$i} months"));
    $monthLabels[] = date('m/Y', strtotime($monthKey . '-01'));
    $monthValues[] = $monthlyMap[$monthKey] ?? 0;
}

$genreRows = db_select(
    'SELECT g.name, COUNT(mg.movie_id) AS total
     FROM genres g
     LEFT JOIN movie_genre mg ON mg.genre_id = g.id
     GROUP BY g.id, g.name
     HAVING total > 0
     ORDER BY total DESC'
);
$genreLabels = array_column($genreRows, 'name');
$genreValues = array_map('intval', array_column($genreRows, 'total'));

$watchlistRows = db_select(
    'SELECT m.title, COUNT(w.movie_id) AS total
     FROM watchlist w
     INNER JOIN movies m ON m.id = w.movie_id
     GROUP BY m.id, m.title
     ORDER BY total DESC, m.title ASC
     LIMIT 5'
);
$watchlistLabels = array_column($watchlistRows, 'title');
$watchlistValues = array_map('intval', array_column($watchlistRows, 'total'));

require_once '../includes/header.php';
?>
<div class="container">
    <h2>Admin Dashboard</h2>

    <div class="admin-stat-grid">
        <article class="card">
            <h3>Tổng số phim</h3>
            <p class="admin-stat-number"><?php echo $movieTotal; ?></p>
        </article>
        <article class="card">
            <h3>Tổng người dùng</h3>
            <p class="admin-stat-number"><?php echo $userTotal; ?></p>
        </article>
    </div>

    <nav class="admin-links" aria-label="Quản trị">
        <a href="movies.php">Quản lý phim</a>
        <a href="users.php">Quản lý người dùng</a>
        <a href="genres.php">Quản lý thể loại</a>
    </nav>

    <section class="admin-chart-grid">
        <article class="card chart-card">
            <h3>Phim được thêm theo tháng</h3>
            <canvas id="movies-by-month-chart"></canvas>
        </article>
        <article class="card chart-card">
            <h3>Tỷ lệ thể loại phim</h3>
            <canvas id="genres-chart"></canvas>
        </article>
        <article class="card chart-card" style="grid-column: 1 / -1;">
            <h3>Top 5 phim được lưu Watchlist nhiều nhất</h3>
            <canvas id="watchlist-chart"></canvas>
        </article>
    </section>
</div>
<!-- CDN chính thức Chart.js, không cần cài npm cho dự án PHP thuần. -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(() => {
    const monthLabels = <?php echo json_encode($monthLabels, JSON_UNESCAPED_UNICODE); ?>;
    const monthValues = <?php echo json_encode($monthValues); ?>;
    const genreLabels = <?php echo json_encode($genreLabels, JSON_UNESCAPED_UNICODE); ?>;
    const genreValues = <?php echo json_encode($genreValues); ?>;
    const watchlistLabels = <?php echo json_encode($watchlistLabels, JSON_UNESCAPED_UNICODE); ?>;
    const watchlistValues = <?php echo json_encode($watchlistValues); ?>;

    new Chart(document.getElementById('movies-by-month-chart'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Phim được thêm',
                data: monthValues,
                borderColor: '#ff7f50',
                backgroundColor: 'rgba(255, 127, 80, .18)',
                fill: true,
                tension: .35,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById('genres-chart'), {
        type: 'doughnut',
        data: {
            labels: genreLabels,
            datasets: [{
                data: genreValues,
                backgroundColor: ['#ff7f50', '#ffb347', '#66c2a5', '#8da0cb', '#e78ac3', '#a6d854', '#ffd92f', '#a6761d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    new Chart(document.getElementById('watchlist-chart'), {
        type: 'bar',
        data: {
            labels: watchlistLabels,
            datasets: [{
                label: 'Số lượt lưu Watchlist',
                data: watchlistValues,
                backgroundColor: '#ff7f50',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
})();
</script>
<?php require_once '../includes/footer.php'; ?>