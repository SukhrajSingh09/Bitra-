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
      width: 300px;
    }

    .popup-btn {
      padding: 8px 12px;
      background: #4f46e5;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
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
      max-height: 90vh;
      overflow-y: auto;
      box-sizing: border-box;
    }

    .form-group {
      margin-bottom: 12px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-size: 14px;
      font-weight: bold;
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
      margin-top: 16px;
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
      margin-top: 14px;
    }

    .role-badge {
      display: inline-block;
      margin-top: 6px;
      padding: 4px 8px;
      border-radius: 999px;
      background: #eef2ff;
      color: #3730a3;
      font-size: 12px;
      font-weight: bold;
    }

    .info-note {
      margin-top: 12px;
      margin-bottom: 12px;
      font-size: 13px;
      color: #555;
      line-height: 1.5;
    }

    .sidebar-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 12px;
      margin-bottom: 12px;
    }

    .admin-link {
      display: inline-block;
      margin-top: 8px;
      font-size: 14px;
    }
  </style>
</head>

<body>

<div class="sidebar">
  <div class="user-box">
    <strong><?php echo htmlspecialchars($username); ?></strong><br>
    <span class="role-badge"><?php echo htmlspecialchars(ucfirst($role)); ?></span><br><br>
    <a href="logout.php">Logout</a>

    <?php if ($isAdmin): ?>
      <br><a class="admin-link" href="admin_requests.php">View Admin Requests</a>
      <br><a class="admin-link" href="event_requests.php">View Event Requests</a>
    <?php endif; ?>
  </div>

  <div class="sidebar-actions">
    <?php if ($isAdmin): ?>
      <button class="popup-btn" type="button" onclick="openModal()">Add Event</button>
    <?php else: ?>
      <button class="popup-btn" type="button" onclick="openAdminRequestModal()">Request Admin Access</button>
      <button class="popup-btn" type="button" onclick="openEventRequestModal()">Request Event</button>
    <?php endif; ?>
  </div>

  <?php if (!$isAdmin): ?>
    <div class="info-note">
      You are logged in as a normal user. Admins can add events directly, and you can submit an event request for approval.
    </div>
  <?php endif; ?>

  <h3>Filter</h3>
  <select id="filter">
    <option value="all">All</option>
    <option value="sports">Sports</option>
    <option value="study">Study</option>
  </select>
</div>

<div id="map"></div>

<?php if ($isAdmin): ?>
<div id="eventModal" class="modal">
  <div class="modal-content">
    <h2>Add Event</h2>

    <form id="eventForm">
      <div class="form-group">
        <label for="title">Title *</label>
        <input type="text" id="title" name="title" required>
      </div>

      <div class="form-group">
        <label for="society">Society</label>
        <input type="text" id="society" name="society">
      </div>

      <div class="form-group">
        <label for="building">Building *</label>
        <select id="building" name="building" required>
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
        <label for="room">Room</label>
        <input type="text" id="room" name="room">
      </div>

      <div class="form-group">
        <label for="event_date">Date *</label>
        <input type="date" id="event_date" name="event_date" required>
      </div>

      <div class="form-group">
        <label for="event_time">Time *</label>
        <input type="time" id="event_time" name="event_time" required>
      </div>

      <div class="form-group">
        <label for="type">Type *</label>
        <select id="type" name="type" required>
          <option value="study">Study</option>
          <option value="sports">Sports</option>
        </select>
      </div>

      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"></textarea>
      </div>

      <div class="form-buttons">
        <button class="popup-btn" type="submit">Save</button>
        <button class="popup-btn" type="button" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if (!$isAdmin): ?>
<div id="adminRequestModal" class="modal">
  <div class="modal-content">
    <h2>Request Admin Access</h2>

    <form id="adminRequestForm">
      <div class="form-group">
        <label for="request_message">Why do you need admin access?</label>
        <textarea id="request_message" name="message" rows="5" placeholder="Explain why you need admin access..."></textarea>
      </div>

      <div class="form-buttons">
        <button class="popup-btn" type="submit">Send Request</button>
        <button class="popup-btn" type="button" onclick="closeAdminRequestModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<div id="eventRequestModal" class="modal">
  <div class="modal-content">
    <h2>Request Event</h2>

    <form id="eventRequestForm">
      <div class="form-group">
        <label for="req_title">Title *</label>
        <input type="text" id="req_title" name="title" required>
      </div>

      <div class="form-group">
        <label for="req_society">Society</label>
        <input type="text" id="req_society" name="society">
      </div>

      <div class="form-group">
        <label for="req_building">Building *</label>
        <select id="req_building" name="building" required>
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
        <label for="req_room">Room</label>
        <input type="text" id="req_room" name="room">
      </div>

      <div class="form-group">
        <label for="req_event_date">Date *</label>
        <input type="date" id="req_event_date" name="event_date" required>
      </div>

      <div class="form-group">
        <label for="req_event_time">Time *</label>
        <input type="time" id="req_event_time" name="event_time" required>
      </div>

      <div class="form-group">
        <label for="req_type">Type *</label>
        <select id="req_type" name="type" required>
          <option value="study">Study</option>
          <option value="sports">Sports</option>
        </select>
      </div>

      <div class="form-group">
        <label for="req_description">Description</label>
        <textarea id="req_description" name="description" rows="4"></textarea>
      </div>

      <div class="form-buttons">
        <button class="popup-btn" type="submit">Send Request</button>
        <button class="popup-btn" type="button" onclick="closeEventRequestModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;

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
  const modal = document.getElementById("eventModal");
  if (modal) modal.style.display = "block";
}

function closeModal() {
  const modal = document.getElementById("eventModal");
  if (modal) modal.style.display = "none";
}

function openAdminRequestModal() {
  const modal = document.getElementById("adminRequestModal");
  if (modal) modal.style.display = "block";
}

function closeAdminRequestModal() {
  const modal = document.getElementById("adminRequestModal");
  if (modal) modal.style.display = "none";
}

function openEventRequestModal() {
  const modal = document.getElementById("eventRequestModal");
  if (modal) modal.style.display = "block";
}

function closeEventRequestModal() {
  const modal = document.getElementById("eventRequestModal");
  if (modal) modal.style.display = "none";
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

if (isAdmin) {
  const eventForm = document.getElementById("eventForm");

  if (eventForm) {
    eventForm.addEventListener("submit", async e => {
      e.preventDefault();

      const formData = new FormData(e.target);

      try {
        const res = await fetch("add_event.php", {
          method: "POST",
          body: formData
        });

        const text = await res.text();
        const result = JSON.parse(text);

        alert(result.message);

        if (result.success) {
          closeModal();
          loadEvents(document.getElementById("filter").value);
          e.target.reset();
        }
      } catch (error) {
        console.error(error);
        alert("Something went wrong while saving the event.");
      }
    });
  }
}

const adminRequestForm = document.getElementById("adminRequestForm");
if (adminRequestForm) {
  adminRequestForm.addEventListener("submit", async e => {
    e.preventDefault();

    const formData = new FormData(adminRequestForm);

    try {
      const res = await fetch("request_admin.php", {
        method: "POST",
        body: formData
      });

      const text = await res.text();
      const result = JSON.parse(text);

      alert(result.message);

      if (result.success) {
        closeAdminRequestModal();
        adminRequestForm.reset();
      }
    } catch (error) {
      console.error(error);
      alert("Something went wrong while sending your admin request.");
    }
  });
}

const eventRequestForm = document.getElementById("eventRequestForm");
if (eventRequestForm) {
  eventRequestForm.addEventListener("submit", async e => {
    e.preventDefault();

    const formData = new FormData(eventRequestForm);

    try {
      const res = await fetch("request_event.php", {
        method: "POST",
        body: formData
      });

      const text = await res.text();
      const result = JSON.parse(text);

      alert(result.message);

      if (result.success) {
        closeEventRequestModal();
        eventRequestForm.reset();
      }
    } catch (error) {
      console.error(error);
      alert("Something went wrong while sending your event request.");
    }
  });
}

window.onclick = function(event) {
  const eventModal = document.getElementById("eventModal");
  const adminRequestModal = document.getElementById("adminRequestModal");
  const eventRequestModal = document.getElementById("eventRequestModal");

  if (eventModal && event.target === eventModal) closeModal();
  if (adminRequestModal && event.target === adminRequestModal) closeAdminRequestModal();
  if (eventRequestModal && event.target === eventRequestModal) closeEventRequestModal();
};

loadEvents();
</script>

</body>
</html>