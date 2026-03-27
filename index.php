<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'user';
$isAdmin = ($role === 'admin');

$userStmt = $mysqli->prepare("SELECT points FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userRow = $userResult->fetch_assoc();
$points = (int) ($userRow['points'] ?? 0);
$userStmt->close();
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

      <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:280px;">
        <div class="mb-2">
          <strong><?php echo htmlspecialchars($username); ?></strong><br>
          <span class="badge bg-primary">
            <?php echo htmlspecialchars(ucfirst($role)); ?>
          </span>
          <div class="mt-2">
            <strong>Points:</strong> <?php echo $points; ?>
          </div>
        </div>

        <a href="my_rewards.php" class="btn btn-outline-primary w-100 mb-2">My Rewards</a>
        <a href="leaderboard.php" class="btn btn-outline-primary w-100 mb-2">Leaderboard</a>
        <a href="logout.php" class="btn btn-outline-danger w-100 mb-2">Logout</a>

        <?php if ($isAdmin): ?>
          <a class="btn btn-dark w-100 mb-2" href="admin_dashboard.php">Dashboard</a>
          <a class="btn btn-dark w-100 mb-2" href="admin_requests.php">Admin Requests</a>
          <a class="btn btn-dark w-100 mb-2" href="event_requests.php">Event Requests</a>
          <button type="button" class="btn btn-primary w-100 mb-2" onclick="openModal()">Add Event</button>
        <?php else: ?>
          <button type="button" class="btn btn-primary w-100 mb-2" onclick="openAdminRequestModal()">Request Admin</button>
          <button type="button" class="btn btn-primary w-100 mb-2" onclick="openEventRequestModal()">Request Event</button>
        <?php endif; ?>

        <hr>
        <label class="form-label" for="filter">Filter</label>
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
        <label for="reward_points">Reward Points *</label>
        <input type="number" id="reward_points" name="reward_points" min="1" value="10" required>
      </div>

      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"></textarea>
      </div>

      <div class="form-buttons">
        <button type="submit" class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
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
        <button type="submit" class="btn btn-primary">Send Request</button>
        <button type="button" class="btn btn-secondary" onclick="closeAdminRequestModal()">Cancel</button>
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
        <button type="submit" class="btn btn-primary">Send Request</button>
        <button type="button" class="btn btn-secondary" onclick="closeEventRequestModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;

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

let markers = [];

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
    .setContent(`Lat: ${lat}<br>Lng: ${lng}`)
    .openOn(map);
});

function clearMarkers() {
  markers.forEach(marker => map.removeLayer(marker));
  markers = [];
}

function escapeHtml(value) {
  const div = document.createElement("div");
  div.textContent = value ?? "";
  return div.innerHTML;
}

async function attendEvent(eventId) {
  console.log("Attend clicked, eventId =", eventId);

  if (!eventId || eventId <= 0) {
    alert("Invalid event id.");
    return;
  }

  try {
    const formData = new FormData();
    formData.append("event_id", eventId);

    const res = await fetch("mark_attendance.php", {
      method: "POST",
      body: formData
    });

    const text = await res.text();
    console.log("RAW mark_attendance.php RESPONSE:", text);

    let result;
    try {
      result = JSON.parse(text);
    } catch (error) {
      alert("mark_attendance.php returned invalid JSON:\n" + text);
      return;
    }

    alert(result.message);

    if (result.success) {
      window.location.reload();
    }
  } catch (error) {
    console.error(error);
    alert("Something went wrong while recording attendance.");
  }
}

function renderEvents(events) {
  clearMarkers();

  const searchValue = document.getElementById("search").value.toLowerCase().trim();
  const groupedByBuilding = {};

  events.forEach(event => {
    const title = (event.title || "").toLowerCase();
    const building = (event.building || "").trim();
    const room = (event.room || "").toLowerCase();
    const description = (event.description || "").toLowerCase();
    const society = (event.society || "").toLowerCase();

    if (
      searchValue &&
      !title.includes(searchValue) &&
      !building.toLowerCase().includes(searchValue) &&
      !room.includes(searchValue) &&
      !description.includes(searchValue) &&
      !society.includes(searchValue)
    ) {
      return;
    }

    if (!building || !buildingCoords[building]) {
      return;
    }

    if (!groupedByBuilding[building]) {
      groupedByBuilding[building] = [];
    }

    groupedByBuilding[building].push(event);
  });

  for (const building in groupedByBuilding) {
    const coords = buildingCoords[building];
    const buildingEvents = groupedByBuilding[building];

    let popupContent = `<div class="popup-title">${escapeHtml(building)}</div>`;

    buildingEvents.forEach(event => {
      const eventId = Number(event.id) || 0;
      const rewardPoints = Number(event.reward_points) || 10;

      popupContent += `
        <div class="event-card">
          <strong>${escapeHtml(event.title || "Untitled Event")}</strong><br>
          ${escapeHtml(event.event_date || "")}<br>
          ${escapeHtml(event.event_time || "")}<br>
          Room: ${escapeHtml(event.room || "N/A")}<br>
          ${event.society ? `Society: ${escapeHtml(event.society)}<br>` : ""}
          ${event.description ? `${escapeHtml(event.description)}<br>` : ""}
          <strong>Reward:</strong> ${rewardPoints} points<br>
          <button class="btn btn-sm btn-success mt-2" onclick="attendEvent(${eventId})">
            Attend
          </button>
        </div>
      `;
    });

    const marker = L.marker(coords).addTo(map);
    marker.bindPopup(popupContent);
    markers.push(marker);
  }
}

async function loadEvents(filter = "all") {
  try {
    const res = await fetch(`get_events.php?type=${encodeURIComponent(filter)}`);
    const text = await res.text();
    console.log("RAW get_events.php RESPONSE:", text);

    let events;
    try {
      events = JSON.parse(text);
    } catch (error) {
      console.error("Invalid JSON from get_events.php:", text);
      return;
    }

    if (!Array.isArray(events)) {
      console.error("Unexpected response:", events);
      return;
    }

    renderEvents(events);
  } catch (error) {
    console.error("Error loading events:", error);
  }
}

document.getElementById("filter").addEventListener("change", e => {
  loadEvents(e.target.value);
});

document.getElementById("search").addEventListener("input", () => {
  loadEvents(document.getElementById("filter").value);
});

if (isAdmin) {
  const eventForm = document.getElementById("eventForm");

  if (eventForm) {
    eventForm.addEventListener("submit", async e => {
      e.preventDefault();

      const formData = new FormData(eventForm);

      try {
        const res = await fetch("add_event.php", {
          method: "POST",
          body: formData
        });

        const text = await res.text();
        console.log("RAW add_event.php RESPONSE:", text);

        let result;
        try {
          result = JSON.parse(text);
        } catch (error) {
          alert("add_event.php returned invalid JSON.");
          return;
        }

        alert(result.message);

        if (result.success) {
          closeModal();
          eventForm.reset();
          loadEvents(document.getElementById("filter").value);
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
      console.log("RAW request_admin.php RESPONSE:", text);

      let result;
      try {
        result = JSON.parse(text);
      } catch (error) {
        alert("request_admin.php returned invalid JSON.");
        return;
      }

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
      console.log("RAW request_event.php RESPONSE:", text);

      let result;
      try {
        result = JSON.parse(text);
      } catch (error) {
        alert("request_event.php returned invalid JSON:\n" + text);
        return;
      }

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