<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Campus Navigator</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f4f6f9;
    }

    #map {
      height: 100vh;
      width: 100%;
    }

    .sidebar {
      position: absolute;
      top: 20px;
      right: 20px;
      background: white;
      padding: 15px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
      z-index: 1000;
      width: 260px;
    }

    .popup-btn {
      padding: 8px 12px;
      background: #4f46e5;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    .popup-btn:hover {
      background: #3730a3;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.4);
      left: 0;
      top: 0;
    }

    .modal-content {
      background: white;
      width: 90%;
      max-width: 500px;
      margin: 40px auto;
      padding: 20px;
      border-radius: 14px;
    }

    .form-group {
      margin-bottom: 12px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      box-sizing: border-box;
    }

    .user-box {
      margin-bottom: 12px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
    }

    .event-card {
      margin-bottom: 12px;
      padding-bottom: 10px;
      border-bottom: 1px solid #ddd;
    }

    .event-card:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }

    .popup-title {
      font-weight: bold;
      font-size: 16px;
      margin-bottom: 8px;
    }

    .popup-time {
      font-size: 13px;
      color: #555;
      margin-bottom: 6px;
    }

    .popup-desc {
      font-size: 13px;
      margin-top: 6px;
    }

    .coord-popup {
      font-size: 13px;
      line-height: 1.5;
    }

    h3 {
      margin-bottom: 8px;
    }

    #filter {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      box-sizing: border-box;
    }

    .sidebar a {
      color: #4f46e5;
      text-decoration: none;
    }

    .sidebar a:hover {
      text-decoration: underline;
    }

    .form-buttons {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
  </style>
</head>

<body>

<div class="sidebar">
  <div class="user-box">
    <strong><?php echo htmlspecialchars($username); ?></strong><br>
    <a href="logout.php">Logout</a>
  </div>

  <button class="popup-btn" type="button" onclick="openModal()">Add Event</button>

  <h3>Filter</h3>
  <select id="filter">
    <option value="all">All</option>
    <option value="sports">Sports</option>
    <option value="study">Study</option>
  </select>
</div>

<div id="map"></div>

<div id="eventModal" class="modal">
  <div class="modal-content">
    <h2>Add Event</h2>

    <form id="eventForm">
      <div class="form-group">
        <input type="text" name="title" placeholder="Title" required>
      </div>

      <div class="form-group">
        <input type="text" name="society" placeholder="Society">
      </div>

      <div class="form-group">
        <select name="building" required>
          <option value="">Select building</option>
          <option value="MD">MD</option>
          <option value="MX">MX</option>
          <option value="MI">MI</option>
          <option value="MC">MC</option>
          <option value="MB">MB</option>
          <option value="MA">MA</option>
        </select>
      </div>

      <div class="form-group">
        <input type="text" name="room" placeholder="Room">
      </div>

      <div class="form-group">
        <input type="date" name="event_date" required>
      </div>

      <div class="form-group">
        <input type="time" name="event_time" required>
      </div>

      <div class="form-group">
        <select name="type" required>
          <option value="study">Study</option>
          <option value="sports">Sports</option>
        </select>
      </div>

      <div class="form-group">
        <textarea name="description" placeholder="Description"></textarea>
      </div>

      <div class="form-buttons">
        <button class="popup-btn" type="submit">Save</button>
        <button class="popup-btn" type="button" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
const map = L.map('map').setView([52.5862, -2.1280], 16);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap'
}).addTo(map);

let markers = [];
let routeLine = null;

const buildingCoords = {
  "MD": [52.588283, -2.128188],
  "MX": [52.591504, -2.127473],
  "MI": [52.587824, -2.126665],
  "MC": [52.588687, -2.127442],
  "MB": [52.588355, -2.126842],
  "MA": [52.587530, -2.127357]
};

function openModal() {
  document.getElementById("eventModal").style.display = "block";
}

function closeModal() {
  document.getElementById("eventModal").style.display = "none";
}

map.on('click', function(e) {
  const lat = e.latlng.lat.toFixed(6);
  const lng = e.latlng.lng.toFixed(6);

  L.popup()
    .setLatLng(e.latlng)
    .setContent(`
      <div class="coord-popup">
        <strong>Coordinates</strong><br>
        Lat: ${lat}<br>
        Lng: ${lng}
      </div>
    `)
    .openOn(map);
});

async function loadEvents(filter = "all") {
  try {
    const res = await fetch(`get_events.php?type=${encodeURIComponent(filter)}`);
    const events = await res.json();

    if (!Array.isArray(events)) {
      console.error("Unexpected response:", events);
      return;
    }

    renderEvents(events);
  } catch (error) {
    console.error("Error loading events:", error);
  }
}

function clearMarkers() {
  markers.forEach(marker => map.removeLayer(marker));
  markers = [];

  if (routeLine) {
    map.removeLayer(routeLine);
    routeLine = null;
  }
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text ?? "";
  return div.innerHTML;
}

function renderEvents(events) {
  clearMarkers();

  const grouped = {};

  events.forEach(event => {
    const building = (event.building || "").trim();
    if (!building) return;

    if (!grouped[building]) {
      grouped[building] = [];
    }

    grouped[building].push(event);
  });

  for (const building in grouped) {
    if (!buildingCoords[building]) continue;

    let html = `<div class="popup-title">${escapeHtml(building)}</div>`;

    grouped[building].forEach(event => {
      html += `
        <div class="event-card">
          <div><strong>${escapeHtml(event.title || "")}</strong></div>
          <div class="popup-time">${escapeHtml(event.event_date || "")} ${escapeHtml(event.event_time || "")}</div>
          <div><strong>Society:</strong> ${escapeHtml(event.society || "N/A")}</div>
          <div><strong>Room:</strong> ${escapeHtml(event.room || "N/A")}</div>
          <div><strong>Type:</strong> ${escapeHtml(event.type || "N/A")}</div>
          <div class="popup-desc">${escapeHtml(event.description || "")}</div>
        </div>
      `;
    });

    html += `
      <button class="popup-btn" type="button" onclick="getDirections(${buildingCoords[building][0]}, ${buildingCoords[building][1]})">
        Get Directions
      </button>
    `;

    const marker = L.marker(buildingCoords[building]).addTo(map);
    marker.bindPopup(html);
    markers.push(marker);
  }
}

function getDirections(lat, lng) {
  if (!navigator.geolocation) {
    alert("Geolocation not supported");
    return;
  }

  navigator.geolocation.getCurrentPosition(
    pos => {
      const user = [pos.coords.latitude, pos.coords.longitude];

      if (routeLine) {
        map.removeLayer(routeLine);
      }

      routeLine = L.polyline([user, [lat, lng]], {
        weight: 5,
        dashArray: "8,8"
      }).addTo(map);

      map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
    },
    () => {
      alert("Could not get your location.");
    }
  );
}

document.getElementById("filter").addEventListener("change", e => {
  loadEvents(e.target.value);
});

document.getElementById("eventForm").addEventListener("submit", async e => {
  e.preventDefault();

  const formData = new FormData(e.target);

  try {
    const res = await fetch("add_event.php", {
      method: "POST",
      body: formData
    });

    const text = await res.text();
    let result;

    try {
      result = JSON.parse(text);
    } catch (err) {
      alert("add_event.php returned invalid JSON.");
      console.error(text);
      return;
    }

    alert(result.message);

    if (result.success) {
      closeModal();
      loadEvents(document.getElementById("filter").value);
      e.target.reset();
    }
  } catch (error) {
    console.error("Error saving event:", error);
    alert("Something went wrong while saving the event.");
  }
});

window.onclick = function(event) {
  const modal = document.getElementById("eventModal");
  if (event.target === modal) {
    closeModal();
  }
};

loadEvents();
</script>

</body>
</html>