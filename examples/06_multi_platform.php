<?php
/**
 * 06 - Multi-Platform Integration Guide
 * How to use RouterOS Framework in Laravel, CodeIgniter, Frontend JavaScript, and REST API.
 */

require __DIR__ . '/../vendor/autoload.php';

echo "=== MIKROTIK ROUTEROS FRAMEWORK - MULTI-PLATFORM GUIDE ===\n\n";

/*
|--------------------------------------------------------------------------
| A. LARAVEL INTEGRATION (8.x - 11.x+)
|--------------------------------------------------------------------------
| Package Auto-Discovery is fully supported!
| In any Laravel Controller, Model, or Job:
|
| use RouterOS;
|
| class HotspotController extends Controller
| {
|     public function index()
|     {
|         $users = RouterOS::hotspot()->getUsers();
|         $summary = RouterOS::hotspot()->getSummary();
|         return view('hotspot.index', compact('users', 'summary'));
|     }
|
|     public function updateLimit(Request $request)
|     {
|         RouterOS::setLimit($request->username, $request->limit_uptime);
|         return response()->json(['message' => 'Success']);
|     }
| }
*/
echo "A. Laravel: Facade RouterOS::hotspot(), RouterOS::setLimit() ready.\n";

/*
|--------------------------------------------------------------------------
| B. CODEIGNITER INTEGRATION (CI3 & CI4)
|--------------------------------------------------------------------------
| 1. CodeIgniter 3:
|    $this->load->library('RouterOSLibrary');
|    $users = $this->routeroslibrary->hotspot()->getUsers();
|    $this->routeroslibrary->setLimit('budi', '2h');
|
| 2. CodeIgniter 4:
|    $ros = new \RouterOSFramework\Integrations\CodeIgniter\RouterOSLibrary();
|    $vouchers = $ros->voucher()->getFromDb();
*/
echo "B. CodeIgniter: RouterOSLibrary for CI3 and CI4 ready.\n";

/*
|--------------------------------------------------------------------------
| C. JAVASCRIPT / FRONTEND SDK (assets/js/routeros.js)
|--------------------------------------------------------------------------
| Include assets/js/routeros.js in your frontend project (Vue, React, HTML):
|
| <script src="assets/js/routeros.js"></script>
| <script>
| const client = new RouterOSClient({ endpoint: '/api.php' });
|
| async function loadDashboard() {
|     const users = await client.hotspot.getUsers();
|     await client.hotspot.setLimit('budi', '3h');
|     await client.pppoe.suspend('client01');
| }
| loadDashboard();
| </script>
*/
echo "C. JavaScript SDK: async/await client ready at assets/js/routeros.js\n";

/*
|--------------------------------------------------------------------------
| D. UNIVERSAL RESTful API (api.php)
|--------------------------------------------------------------------------
| Send JSON or POST requests from any HTTP client:
|
| curl -X POST "http://localhost:8000/api.php?action=set_limit" \
|      -H "Content-Type: application/json" \
|      -d '{"username":"budi","limit_uptime":"2h"}'
*/
echo "D. REST API: JSON endpoints ready at api.php\n";

/*
|--------------------------------------------------------------------------
| E. INTERACTIVE TERMINAL CLI (console.php)
|--------------------------------------------------------------------------
| php console.php status
| php console.php setlimit budi 2h 1G
| php console.php setcomment budi "Langganan VIP"
| php console.php setemail budi budi@gmail.com
| php console.php setname budi budi_baru
| php console.php findid budi
| php console.php suspend client01
| php console.php unsuspend client01
*/
echo "E. CLI Console: Run terminal commands via console.php\n";
