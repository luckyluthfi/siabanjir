<?php
session_start();
require("./test/database.php");

$conn = Database::connect();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['logout'])) {
    unset($_SESSION['user']);
    session_destroy();
    header("Location: login.php");
    exit;
}

// ambil data terakhir untuk tampilan utama
$stmt = $conn->prepare("SELECT * FROM tbl_hulu ORDER BY id DESC LIMIT 1");
$stmt->execute();
$dataHuluNow = $stmt->fetch(PDO::FETCH_ASSOC);

$datasHilir = $conn->prepare("SELECT * FROM tbl_hilir ORDER BY id DESC LIMIT 1");
$datasHilir->execute();
$dataHilirNow = $datasHilir->fetch(PDO::FETCH_ASSOC);

// data grafik hulu (10 data terakhir, urut naik waktu)
$stmtChartHulu = $conn->prepare("SELECT waktu, ket_hulu FROM tbl_hulu ORDER BY id DESC LIMIT 10");
$stmtChartHulu->execute();
$resultHulu = array_reverse($stmtChartHulu->fetchAll(PDO::FETCH_ASSOC));
$labelsHulu = array_column($resultHulu, 'waktu');
$valuesHulu = array_column($resultHulu, 'ket_hulu');

// data grafik hilir (10 data terakhir, urut naik waktu)
$stmtChartHilir = $conn->prepare("SELECT waktu, ket_hilir FROM tbl_hilir ORDER BY id DESC LIMIT 10");
$stmtChartHilir->execute();
$resultHilir = array_reverse($stmtChartHilir->fetchAll(PDO::FETCH_ASSOC));
$labelsHilir = array_column($resultHilir, 'waktu');
$valuesHilir = array_column($resultHilir, 'ket_hilir');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="https://unpkg.com/@material-tailwind/html@latest/styles/material-tailwind.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <title>Monitoring SIABANJIR</title>
  <style>
    .pulse-danger { animation: pulse 1s infinite; }
    @keyframes pulse { 0%{transform:scale(1);}50%{transform:scale(1.05);}100%{transform:scale(1);} }
    #toast {
        position: fixed; top: 20px; right: 20px;
        background: #333; color: white; padding: 12px 18px; border-radius: 8px;
        opacity: 0; transition: 0.4s; z-index: 99999;
    }
  </style>
</head>
<body>
  <div id="toast"></div>
  <main class="min-h-screen bg-gray-100 w-full pt-2">
    <div class="p-4">
      <form action="" method="POST">
        <button type="submit" name="logout" class="py-2 px-3 bg-blue-700 rounded-lg text-white hover:bg-blue-800">Logout</button>
      </form>
    </div>

    <div class="max-w-7xl mx-auto">
      <div class="grid grid-cols-1">
        <div class="w-full text-center py-1">
          <h1 class="text-4xl font-bold">Monitoring SIABANJIR Sungai Kesambi & Tenggeles</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
          <!-- HULU -->
          <div class="grid grid-cols-1">
            <div class="border border-gray-300 p-5">
              <h1 class="text-2xl font-bold text-blue-900 text-center">Lokasi 1 Sungai Hulu Tenggeles</h1>
              <p class="text-center mt-1"><i class="bi bi-geo-alt-fill"></i> Desa Tenggeles</p>
            </div>

            <div class="bg-gradient-to-r from-stone-900 to-green-700 w-full h-[150px] mb-1">
              <div class="w-full p-5">
                <p class="text-2xl text-white">Ketinggian Air Hulu</p>
                <div class="flex justify-end">
                  <div>
                    <i class="bi bi-rulers text-4xl text-green-900/80 flex justify-center"></i>
                    <p class="text-4xl font-bold text-white" id="hulu_ketinggian"><?= htmlspecialchars($dataHuluNow['ket_hulu'] ?? '-') ?> cm</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-1">
              <div id="box_status_hulu" class="w-full bg-gradient-to-r from-indigo-900 to-slate-500 py-2">
                <h1 class="text-2xl font-semibold text-white text-center py-2 border-b border-gray-300">
                  <i class="bi bi-water"></i> Status Air
                </h1>
                <div>
                  <div id="hulu_status" class="text-3xl text-white text-center"><?= htmlspecialchars($dataHuluNow['sta_hulu'] ?? '-') ?></div>
                </div>
              </div>
            
              <div class="w-full bg-gradient-to-r from-sky-400 to-cyan-200 py-2">
                <h1 class="text-2xl font-semibold text-white text-center py-2 border-b border-gray-300">
                  <i class="bi bi-cloud-drizzle"></i> Curah Hujan
                </h1>
                <div id="hulu_cuaca" class="text-3xl text-white text-center"><?= htmlspecialchars($dataHuluNow['cuaca'] ?? '-') ?></div>
              </div>
            </div>

            <div class="w-full bg-white rounded-xl shadow-lg p-6 mt-3">
              <h1 class="text-2xl font-semibold mb-2">Grafik Ketinggian Air Hulu</h1>
              <p class="text-sm text-slate-500 mb-6">10 data terakhir</p>
              <canvas id="chartHulu" height="80"></canvas>
            </div>
          </div>

          <!-- HILIR -->
          <div class="grid grid-cols-1">
            <div class="border border-gray-300 p-5">
              <h1 class="text-2xl font-bold text-blue-900 text-center">Lokasi 2 Sungai Hilir Kesambi</h1>
              <p class="text-center mt-1"><i class="bi bi-geo-alt-fill"></i> Desa Kesambi</p>
            </div>

            <div class="bg-gradient-to-r from-stone-900 to-green-700 w-full h-[150px] mb-1">
              <div class="w-full p-5">
                <p class="text-2xl text-white">Ketinggian Air Hilir</p>
                <div class="flex justify-end">
                  <div>
                    <i class="bi bi-rulers text-4xl text-green-900/80 flex justify-center"></i>
                    <p class="text-4xl font-bold text-white" id="hilir_ketinggian"><?= htmlspecialchars($dataHilirNow['ket_hilir'] ?? '-') ?> cm</p>
                  </div>
                </div>
              </div>
            </div>

            <div id="box_status_hilir" class="w-full bg-gradient-to-r from-indigo-900 to-slate-500 py-2">
              <h1 class="text-2xl font-semibold text-white text-center py-2 border-b border-gray-300">
                <i class="bi bi-water"></i> Status Air
              </h1>
              <div>
                <div id="hilir_status" class="text-3xl text-white text-center"><?= htmlspecialchars($dataHilirNow['sta_hilir'] ?? '-') ?></div>
              </div>
            </div>

            <div class="w-full bg-white rounded-xl shadow-lg p-6 mt-3">
              <h1 class="text-2xl font-semibold mb-2">Grafik Ketinggian Air Hilir</h1>
              <p class="text-sm text-slate-500 mb-6">10 data terakhir</p>
              <canvas id="chartHilir" height="80"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

<script>
/* ===== SETTINGS ===== */
const API_HULU = "api_hulu.php";
const API_HILIR = "api_hilir.php";
const RELOAD_MS = 3000; // interval realtime

/* ===== UTIL ===== */
function showToast(msg) {
  const t = document.getElementById("toast");
  t.innerText = msg;
  t.style.opacity = 1;
  clearTimeout(t._hideTimer);
  t._hideTimer = setTimeout(()=> t.style.opacity = 0, 3500);
}

/* alarm only when status changes to SIAGA/BAHAYA */
const alarmAudio = new Audio("https://www.soundjay.com/buttons/beep-07a.mp3");
let lastStatusHulu = null;
let lastStatusHilir = null;

/* ===== INITIAL CHARTS (data from PHP variables) ===== */
const labelsHulu = <?= json_encode($labelsHulu) ?>;
const valuesHulu = <?= json_encode($valuesHulu) ?>;
const labelsHilir = <?= json_encode($labelsHilir) ?>;
const valuesHilir = <?= json_encode($valuesHilir) ?>;

const ctxHulu = document.getElementById("chartHulu").getContext("2d");
const chartHulu = new Chart(ctxHulu, {
  type: "line",
  data: {
    labels: labelsHulu,
    datasets: [{
      label: "Ketinggian Hulu (cm)",
      data: valuesHulu,
      borderColor: "rgb(54, 162, 235)",
      backgroundColor: "rgba(54,162,235,0.2)",
      fill: true, tension: 0.3
    }]
  },
  options: {
    responsive: true,
    animation: false,
    scales: { y: { beginAtZero: true } }
  }
});

const ctxHilir = document.getElementById("chartHilir").getContext("2d");
const chartHilir = new Chart(ctxHilir, {
  type: "line",
  data: {
    labels: labelsHilir,
    datasets: [{
      label: "Ketinggian Hilir (cm)",
      data: valuesHilir,
      borderColor: "rgb(75, 192, 192)",
      backgroundColor: "rgba(75,192,192,0.2)",
      fill: true, tension: 0.3
    }]
  },
  options: {
    responsive: true,
    animation: false,
    scales: { y: { beginAtZero: true } }
  }
});

/* ===== FETCH & UPDATE HULU ===== */
async function updateHulu() {
  try {
    const res = await fetch(API_HULU, {cache: "no-store"});
    if (!res.ok) throw new Error("HTTP " + res.status);
    const j = await res.json();

    // safety: ensure numeric when possible
    const ket = (j.ket_hulu !== undefined) ? j.ket_hulu : j.ketinggian ?? null;
    const sta = j.sta_hulu ?? j.status ?? "";
    const cuaca = j.cuaca ?? "";

    document.getElementById("hulu_ketinggian").innerText = (ket !== null ? ket : "-") + " cm";
    document.getElementById("hulu_status").innerText = sta;
    document.getElementById("hulu_cuaca").innerText = cuaca ? ( (cuaca.includes("Hujan") ? "🌧️ " : "") + cuaca ) : "-";

    // status box styling & alarm on status change
    const box = document.getElementById("box_status_hulu");
    box.classList.remove("pulse-danger");
    if (sta === "AMAN") {
      box.style.background = "linear-gradient(to right, #0f0, #0a0)";
    } else if (sta === "SIAGA") {
      box.style.background = "linear-gradient(to right, #ffa500, #cc8400)";
      if (lastStatusHulu !== "SIAGA" && lastStatusHulu !== "BAHAYA") alarmAudio.play();
    } else if (sta === "BAHAYA") {
      box.style.background = "linear-gradient(to right, #ff0000, #a00000)";
      box.classList.add("pulse-danger");
      if (lastStatusHulu !== "BAHAYA") alarmAudio.play();
    }

    if (lastStatusHulu !== null && lastStatusHulu !== sta) {
      showToast("Hulu: STATUS berubah → " + sta);
    }

    lastStatusHulu = sta;

    // update chart (push new point)
    if (j.waktu !== undefined && ket !== null) {
      chartHulu.data.labels.push(j.waktu);
      chartHulu.data.datasets[0].data.push(Number(ket));
      if (chartHulu.data.labels.length > 20) {
        chartHulu.data.labels.shift();
        chartHulu.data.datasets[0].data.shift();
      }
      chartHulu.update();
    }
  } catch (err) {
    console.error("updateHulu error:", err);
  }
}

/* ===== FETCH & UPDATE HILIR ===== */
async function updateHilir() {
  try {
    const res = await fetch(API_HILIR, {cache: "no-store"});
    if (!res.ok) throw new Error("HTTP " + res.status);
    const j = await res.json();

    const ket = (j.ket_hilir !== undefined) ? j.ket_hilir : j.ketinggian ?? null;
    const sta = j.sta_hilir ?? j.status ?? "";

    document.getElementById("hilir_ketinggian").innerText = (ket !== null ? ket : "-") + " cm";
    document.getElementById("hilir_status").innerText = sta;

    const box = document.getElementById("box_status_hilir");
    box.classList.remove("pulse-danger");
    if (sta === "AMAN") {
      box.style.background = "linear-gradient(to right, #0f0, #0a0)";
    } else if (sta === "SIAGA") {
      box.style.background = "linear-gradient(to right, #ffa500, #cc8400)";
      if (lastStatusHilir !== "SIAGA" && lastStatusHilir !== "BAHAYA") alarmAudio.play();
    } else if (sta === "BAHAYA") {
      box.style.background = "linear-gradient(to right, #ff0000, #a00000)";
      box.classList.add("pulse-danger");
      if (lastStatusHilir !== "BAHAYA") alarmAudio.play();
    }

    if (lastStatusHilir !== null && lastStatusHilir !== sta) {
      showToast("Hilir: STATUS berubah → " + sta);
    }

    lastStatusHilir = sta;

    // update chart hilir
    if (j.waktu !== undefined && ket !== null) {
      chartHilir.data.labels.push(j.waktu);
      chartHilir.data.datasets[0].data.push(Number(ket));
      if (chartHilir.data.labels.length > 20) {
        chartHilir.data.labels.shift();
        chartHilir.data.datasets[0].data.shift();
      }
      chartHilir.update();
    }
  } catch (err) {
    console.error("updateHilir error:", err);
  }
}

/* first run and interval */
updateHulu();
updateHilir();
setInterval(()=>{
  updateHulu();
  updateHilir();
}, RELOAD_MS);
</script>
</body>
</html>
