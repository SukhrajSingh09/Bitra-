<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';
$isAdmin = ($role === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campus Navigator</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<link rel="stylesheet" href="style.css">
</head>

<body>

<nav class="navbar navbar-dark bg-dark px-3">
  <span class="navbar-brand">Campus Navigator</span>

  <div class="d-flex gap-2 align-items-center ms-auto">

    <input 
      type="text" 
      id="search" 
      class="form-control" 
      placeholder="Search..."
      style="width: 200px;"
    >
    <div class="dropdown">
      <button class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
        Menu
      </button>

      <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:260px;">
        <div class="mb-2">
          <strong><?php echo htmlspecialchars($username); ?></strong><br>
          <span class="badge bg-primary">
            <?php echo htmlspecialchars(ucfirst($role)); ?>
          </span>
        </div>

        <a href="logout.php" class="btn btn-outline-danger w-100 mb-2">Logout</a>

        <?php if ($isAdmin): ?>
          <a class="btn btn-dark w-100 mb-2" href="admin_dashboard.php">Dashboard</a>
          <a class="btn btn-dark w-100 mb-2" href="admin_requests.php">Admin Requests</a>
          <a class="btn btn-dark w-100 mb-2" href="event_requests.php">Event Requests</a>
          <button class="btn btn-primary w-100 mb-2" onclick="openModal()">Add Event</button>
        <?php else: ?>
          <button class="btn btn-primary w-100 mb-2" onclick="openAdminRequestModal()">Request Admin</button>
          <button class="btn btn-primary w-100 mb-2" onclick="openEventRequestModal()">Request Event</button>
        <?php endif; ?>

        <hr>
        <label class="form-label">Filter</label>
        <select id="filter" class="form-select">
          <option value="all">All</option>
          <option value="sports">Sports</option>
          <option value="study">Study</option>
        </select>

      </div>
    </div>
  </div>
</nav>
<div id="map" style="height: calc(100vh - 60px);"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const map = L.map('map').setView([52.5862, -2.1280], 16);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap'
}).addTo(map);
const buildingCoords = {
  "MD": [52.588283, -2.128188],
  "MX": [52.591504, -2.127473],
  "MI": [52.587824, -2.126665],
  "MC": [52.588687, -2.127442],
  "MB": [52.588355, -2.126842],
  "MA": [52.587530, -2.127357]
};

map.on('click', function(e) {
  const lat = e.latlng.lat.toFixed(6);
  const lng = e.latlng.lng.toFixed(6);

  L.popup()
    .setLatLng(e.latlng)
    .setContent(`Lat: ${lat}<br>Lng: ${lng}`)
    .openOn(map);
});

let markers = [];

function clearMarkers() {
  markers.forEach(marker => map.removeLayer(marker));
  markers = [];
}
function renderEvents(events) {
  clearMarkers();

  const searchValue = document.getElementById("search").value.toLowerCase();

  events.forEach(event => {
    const building = (event.building || "").trim();

    if (
      searchValue &&
      !event.title.toLowerCase().includes(searchValue) &&
      !building.toLowerCase().includes(searchValue) &&
      !(event.room || "").toLowerCase().includes(searchValue)
    ) return;

    if (!buildingCoords[building]) return;

    const marker = L.marker(buildingCoords[building]).addTo(map);

    marker.bindPopup(`
      <strong>${event.title}</strong><br>
       ${event.event_date}<br>
       ${event.event_time}<br>
       ${building} - Room ${event.room || 'N/A'}<br>
      ${event.description || ''}
    `);

    markers.push(marker);
  });
}

async function loadEvents(filter = "all") {
  const res = await fetch(`get_events.php?type=${encodeURIComponent(filter)}`);
  const events = await res.json();
  renderEvents(events);
}

document.getElementById("filter").addEventListener("change", e => {
  loadEvents(e.target.value);
});

document.getElementById("search").addEventListener("input", () => {
  loadEvents(document.getElementById("filter").value);
});
loadEvents();

</script>

</body>
</html>