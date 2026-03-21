<!DOCTYPE html>
<html>
<head>
  <title>Campus Navigator</title>
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

    .leaflet-popup-content-wrapper {
      border-radius: 12px;
      padding: 10px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    .popup-title {
      font-weight: bold;
      font-size: 16px;
      margin-bottom: 5px;
    }

    .popup-time {
      font-size: 13px;
      color: #555;
      margin-bottom: 8px;
    }

    .popup-desc {
      font-size: 13px;
      margin-bottom: 10px;
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

    .sidebar {
      position: absolute;
      top: 20px;
      left: 20px;
      background: white;
      padding: 15px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
      z-index: 1000;
      width: 220px;
    }

    .event-card {
      margin-bottom: 12px;
      padding-bottom: 10px;
      border-bottom: 1px solid #ddd;
    }

    .event-card:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }

    .coord-box {
      font-size: 13px;
      line-height: 1.5;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h3>Filter Events</h3>
  <select id="filter" class="popup-btn">
    <option value="all">All</option>
    <option value="sports">Sports</option>
    <option value="study">Study</option>
  </select>
</div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
  const map = L.map('map').setView([52.5862, -2.1280], 16);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map);

  let markers = [];
  let routeLine = null;

  const buildingCoords = {
    "MD": [52.588501, -2.128268],
    "MX": [52.591496, -2.127544],
    "Alan Turing Building": [52.587843, -2.126638],
    "Ambika Paul Building": [52.5855, -2.1292]
  };

  // Click on map to get coordinates
  map.on('click', function(e) {
    const lat = e.latlng.lat.toFixed(6);
    const lng = e.latlng.lng.toFixed(6);

    L.popup()
      .setLatLng(e.latlng)
      .setContent(`
        <div class="coord-box">
          <strong>Coordinates</strong><br>
          Lat: ${lat}<br>
          Lng: ${lng}
        </div>
      `)
      .openOn(map);

    console.log("Latitude:", lat, "Longitude:", lng);
  });

  async function loadEvents(filter = "all") {
    try {
      const response = await fetch(`get_events.php?type=${encodeURIComponent(filter)}`);
      const events = await response.json();
      renderEvents(events);
    } catch (error) {
      console.error("Error loading events:", error);
    }
  }

  function clearMarkers() {
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
  }

  function renderEvents(events) {
    clearMarkers();

    const groupedByBuilding = {};

    events.forEach(event => {
      const building = event.building.trim();

      if (!groupedByBuilding[building]) {
        groupedByBuilding[building] = [];
      }
      groupedByBuilding[building].push(event);
    });

    for (const building in groupedByBuilding) {
      if (!buildingCoords[building]) {
        console.warn(`No coordinates set for building: ${building}`);
        continue;
      }

      const buildingEvents = groupedByBuilding[building];
      const coords = buildingCoords[building];

      let popupHTML = `<div class="popup-title">${building}</div>`;

      buildingEvents.forEach(event => {
        popupHTML += `
          <div class="event-card">
            <div><strong>${event.title}</strong></div>
            <div class="popup-time">${event.event_date} at ${event.event_time}</div>
            <div><strong>Society:</strong> ${event.society || 'N/A'}</div>
            <div><strong>Room:</strong> ${event.room || 'N/A'}</div>
            <div class="popup-desc">${event.description || ''}</div>
          </div>
        `;
      });

      popupHTML += `
        <button class="popup-btn" onclick="getDirections(${coords[0]}, ${coords[1]})">
          Get Directions
        </button>
      `;

      const marker = L.marker(coords).addTo(map);
      marker.bindPopup(popupHTML);
      markers.push(marker);
    }
  }

  function getDirections(lat, lng) {
    if (!navigator.geolocation) {
      alert("Geolocation not supported");
      return;
    }

    navigator.geolocation.getCurrentPosition(pos => {
      const user = [pos.coords.latitude, pos.coords.longitude];

      if (routeLine) {
        map.removeLayer(routeLine);
      }

      routeLine = L.polyline([user, [lat, lng]], {
        weight: 5,
        dashArray: "8,8"
      }).addTo(map);

      map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
    }, () => {
      alert("Could not get your location.");
    });
  }

  document.getElementById("filter").addEventListener("change", (e) => {
    loadEvents(e.target.value);
  });

  loadEvents();
</script>

</body>
</html>