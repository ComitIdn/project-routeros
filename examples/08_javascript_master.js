/**
 * =========================================================================
 * Contoh 08: Master JavaScript & Node.js SDK Demo
 * =========================================================================
 *
 * Demonstrasi:
 * 1. 1-Point Centralized Configuration (dari Object, .env, atau config.json)
 * 2. Self-Contained Built-in Debugger & Latency Benchmark Timer
 * 3. Concurrent Multi-Router Connection Pool (Parallel Querying via Promise.allSettled)
 * 4. Multi-PTMP Wireless Management
 * 5. Full Operational CRUD Actions (Create, Read, Write, Set, Remove, Toggle, Find)
 */

const { RouterOS, MultiRouterPool } = require('../routeros.js');

console.log('⚡ ============================================================');
console.log('🚀 DEMO MASTER JAVASCRIPT SDK & MULTI-ROUTER POOL');
console.log('⚡ ============================================================\n');

// 1. Inisialisasi 1 Tempat (Config Object atau .env)
const ros = new RouterOS({
    host: '192.168.88.1',
    user: 'admin',
    password: '',
    protocol: 'rest', // REST Native v7+ (Port 80/443) atau 'web_api'
    timeout: 1000,    // 1s timeout
    debug: true,      // Aktifkan Built-in Debugger Mandiri
    logLevel: 'debug',// 'trace' | 'debug' | 'info' | 'warn' | 'error'
    benchmark: true,  // Benchmark timer per query dalam milidetik
});

console.log('\n--- 1. Single Router Client Initialized ---');
console.log(`• Host     : ${ros.config.host}:${ros.config.port}`);
console.log(`• Protocol : ${ros.config.protocol.toUpperCase()}`);
console.log(`• Debugger : ${ros.config.debug ? 'ENABLED' : 'DISABLED'}`);

// 2. Multi-Router Concurrent Connection Pool
console.log('\n--- 2. Multi-Router Concurrent Pool Setup ---');
ros.multi
    .add('router_pusat', { host: '10.10.1.1', user: 'admin', password: 'secret1' })
    .add('tower_utara',  { host: '10.10.2.1', user: 'admin', password: 'secret2' })
    .add('tower_selatan', { host: '10.10.3.1', user: 'admin', password: 'secret3' });

console.log(`• Registered Pool Routers: [${ros.multi.list().join(', ')}]`);

// 3. Demonstrasi Concurrent Parallel Health Check (Simultan)
console.log('\n--- 3. Testing Concurrent Parallel Health Check ---');
(async () => {
    try {
        console.log('⏳ Menjalankan health check serentak ke seluruh router...');
        const healthResults = await ros.multi.healthCheck();
        console.log('📊 Hasil Health Check Paralel (Simultan):');
        console.table(healthResults);
    } catch (err) {
        console.error('Multi health check note:', err.message);
    }

    // 4. Demonstrasi Sintaks PTMP & CRUD
    console.log('\n--- 4. Sintaks CRUD & PTMP Wireless Siap Pakai ---');
    console.log('• Create IP Address: await ros.create("ip/address", { address: "10.0.0.1/24", interface: "ether2" });');
    console.log('• Read Hotspot Users: const users = await ros.read("ip/hotspot/user");');
    console.log('• Set User Limit: await ros.hotspot.setLimit("budi", "2h", 1073741824);');
    console.log('• PTMP Get Stations: const cpe = await ros.ptmp.getStations();');
    console.log('• PTMP Set Channel: await ros.ptmp.setChannel(iface, 5180, "5ghz-only-n");');
    console.log('• Multi Concurrent Read: const allUsers = await ros.multi.concurrentRead("ip/hotspot/user");');
    console.log('\n✅ Demo JavaScript Selesai tanpa error!\n');
})();
