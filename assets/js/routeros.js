/**
 * =========================================================================
 * Project RouterOS - Master Universal JavaScript SDK & Multi-Router Engine
 * =========================================================================
 *
 * Modern, zero-dependency, ultra-fast JavaScript client compatible with:
 * - Node.js (CommonJS & ES Modules)
 * - Browsers (Vanilla JS, React, Vue, Svelte, Next.js)
 * - React Native & Mobile WebViews
 *
 * Features:
 * - 1-Point Centralized Configuration (from Object, .env file, or config.json)
 * - Self-Contained Built-in Debugger & Benchmark Timer (no external logger needed)
 * - Concurrent Multi-Router Connection Pool (parallel async querying via Promise.allSettled)
 * - Full Operational CRUD Actions (Create, Read, Write, Set, Remove, Toggle, Find)
 * - Multi-PTMP Wireless Management (NV2, Nstreme, 60GHz, Sinyal TX/RX, CCQ, Access-List)
 * - Hotspot, Voucher Generator, PPPoE ISP Billing, and Real-time Telemetry
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        const exportsObj = factory();
        root.RouterOS = exportsObj.RouterOS;
        root.RouterOSClient = exportsObj.RouterOS;
        root.MultiRouterPool = exportsObj.MultiRouterPool;
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    /**
     * Environment & Platform Detector
     */
    const isNode = typeof process !== 'undefined' && process.versions && !!process.versions.node;

    /**
     * Minimalist Self-Contained Built-in Debugger & Performance Benchmark Engine
     */
    class Debugger {
        constructor(options = {}) {
            this.enabled = !!options.debug;
            this.level = options.logLevel || 'info'; // 'trace' | 'debug' | 'info' | 'warn' | 'error' | 'none'
            this.benchmark = options.benchmark !== false;
            this.customHandler = options.onLog || null;

            this.levels = { trace: 0, debug: 1, info: 2, warn: 3, error: 4, none: 5 };
        }

        shouldLog(lvl) {
            if (!this.enabled || this.level === 'none') return false;
            return (this.levels[lvl] ?? 2) >= (this.levels[this.level] ?? 2);
        }

        log(level, message, meta = null) {
            if (!this.shouldLog(level)) return;

            const timestamp = new Date().toISOString().substring(11, 23);
            if (typeof this.customHandler === 'function') {
                this.customHandler(level, message, meta, timestamp);
                return;
            }

            const prefix = `[RouterOS ${timestamp}]`;
            if (isNode) {
                const colors = {
                    trace: '\x1b[90m',
                    debug: '\x1b[36m',
                    info:  '\x1b[32m',
                    warn:  '\x1b[33m',
                    error: '\x1b[31m',
                    reset: '\x1b[0m',
                };
                const color = colors[level] || '';
                const tag = `[${level.toUpperCase()}]`;
                if (meta) {
                    console.log(`${color}${prefix} ${tag} ${message}${colors.reset}`, meta);
                } else {
                    console.log(`${color}${prefix} ${tag} ${message}${colors.reset}`);
                }
            } else {
                const styles = {
                    trace: 'color: gray;',
                    debug: 'color: teal;',
                    info:  'color: green; font-weight: bold;',
                    warn:  'color: orange; font-weight: bold;',
                    error: 'color: red; font-weight: bold;',
                };
                if (meta) {
                    console.log(`%c${prefix} [${level.toUpperCase()}] ${message}`, styles[level] || '', meta);
                } else {
                    console.log(`%c${prefix} [${level.toUpperCase()}] ${message}`, styles[level] || '');
                }
            }
        }

        trace(msg, meta) { this.log('trace', msg, meta); }
        debug(msg, meta) { this.log('debug', msg, meta); }
        info(msg, meta)  { this.log('info', msg, meta); }
        warn(msg, meta)  { this.log('warn', msg, meta); }
        error(msg, meta) { this.log('error', msg, meta); }

        startTimer(label) {
            const startTime = Date.now();
            return {
                startTime,
                end: () => {
                    const duration = Date.now() - startTime;
                    if (this.benchmark && this.shouldLog('debug')) {
                        this.debug(`⏱️ ${label} completed in ${duration}ms`);
                    }
                    return duration;
                }
            };
        }
    }

    /**
     * Pure JavaScript .env & Config Parser (Zero external npm dependencies)
     */
    class ConfigHelper {
        static parseEnv(content) {
            const config = {};
            if (!content || typeof content !== 'string') return config;

            const lines = content.split('\n');
            for (let line of lines) {
                line = line.trim();
                if (!line || line.startsWith('#')) continue;

                const eqIdx = line.indexOf('=');
                if (eqIdx !== -1) {
                    const key = line.substring(0, eqIdx).trim();
                    let val = line.substring(eqIdx + 1).trim();
                    if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
                        val = val.substring(1, val.length - 1);
                    }
                    config[key] = val;
                }
            }
            return config;
        }

        static normalize(cfg = {}) {
            const host = cfg.host || cfg.MIKROTIK_HOST || cfg.ROUTEROS_HOST || '192.168.88.1';
            const user = cfg.user || cfg.username || cfg.MIKROTIK_USER || cfg.ROUTEROS_USER || 'admin';
            const password = cfg.pass || cfg.password || cfg.MIKROTIK_PASSWORD || cfg.ROUTEROS_PASSWORD || '';
            const port = parseInt(cfg.port || cfg.MIKROTIK_PORT || cfg.ROUTEROS_PORT || (cfg.ssl ? 443 : 80), 10);
            const protocol = (cfg.protocol || cfg.ROUTEROS_PROTOCOL || (cfg.apiUrl ? 'web_api' : 'rest')).toLowerCase();
            const apiUrl = cfg.apiUrl || cfg.endpoint || cfg.ROUTEROS_API_URL || 'api.php';
            const timeout = parseInt(cfg.timeout || cfg.ROUTEROS_TIMEOUT || 5000, 10);
            const debug = cfg.debug !== undefined ? !!cfg.debug : (cfg.ROUTEROS_DEBUG === 'true');
            const logLevel = cfg.logLevel || cfg.ROUTEROS_LOG_LEVEL || 'info';

            return {
                host,
                user,
                password,
                port,
                protocol,
                apiUrl,
                timeout,
                ssl: !!(cfg.ssl || cfg.ROUTEROS_SSL === 'true' || port === 443 || port === 8443),
                verifySsl: !!(cfg.verifySsl || cfg.ROUTEROS_VERIFY_SSL === 'true'),
                debug,
                logLevel,
                benchmark: cfg.benchmark !== false,
                routers: cfg.routers || {},
                headers: cfg.headers || {},
            };
        }
    }

    /**
     * Main Unified RouterOS Class
     */
    class RouterOS {
        /**
         * Initialize single RouterOS connection or Multi-Router pool.
         * @param {Object} options Configuration parameters or .env parsed object
         */
        constructor(options = {}) {
            this.config = ConfigHelper.normalize(options);
            this.logger = new Debugger({
                debug: this.config.debug,
                logLevel: this.config.logLevel,
                benchmark: this.config.benchmark,
                onLog: options.onLog,
            });

            this.logger.info(`Initialized RouterOS client for [${this.config.host}] via [${this.config.protocol.toUpperCase()}]`);

            // Domain Sub-modules
            this.hotspot = new HotspotModule(this);
            this.voucher = new VoucherModule(this);
            this.pppoe = new PPPoEModule(this);
            this.ptmp = new PtmpModule(this);
            this.system = new SystemModule(this);

            // Multi-Router Pool
            this.multi = new MultiRouterPool(this);
            if (options.routers && typeof options.routers === 'object') {
                for (const [name, rCfg] of Object.entries(options.routers)) {
                    this.multi.add(name, rCfg);
                }
            }
        }

        /**
         * Instantiate RouterOS by reading a local .env file (Node.js only).
         * @param {string} envPath Path to .env file (default: '.env')
         */
        static fromEnv(envPath = '.env') {
            if (!isNode) {
                throw new Error("RouterOS.fromEnv() is only supported in Node.js environment");
            }
            try {
                const fs = require('fs');
                const path = require('path');
                const fullPath = path.resolve(process.cwd(), envPath);
                if (fs.existsSync(fullPath)) {
                    const content = fs.readFileSync(fullPath, 'utf8');
                    const parsed = ConfigHelper.parseEnv(content);
                    return new RouterOS(parsed);
                }
            } catch (err) {
                console.warn(`[RouterOS] Warning: Unable to load .env from ${envPath}:`, err.message);
            }
            return new RouterOS({});
        }

        /**
         * Instantiate RouterOS by reading a JSON config file (Node.js only).
         * @param {string} configPath Path to config.json
         */
        static fromFile(configPath = 'config.json') {
            if (!isNode) {
                throw new Error("RouterOS.fromFile() is only supported in Node.js environment");
            }
            const fs = require('fs');
            const path = require('path');
            const fullPath = path.resolve(process.cwd(), configPath);
            const content = fs.readFileSync(fullPath, 'utf8');
            return new RouterOS(JSON.parse(content));
        }

        /**
         * Quick factory method.
         */
        static connect(options = {}) {
            return new RouterOS(options);
        }

        /**
         * Set debug logging at runtime.
         */
        setDebug(enabled = true, level = 'debug') {
            this.config.debug = !!enabled;
            this.config.logLevel = level;
            this.logger.enabled = !!enabled;
            this.logger.level = level;
            return this;
        }

        /**
         * Switch active protocol at runtime ('rest', 'web_api', 'ssh', 'telnet').
         */
        setProtocol(protocol, port = null) {
            this.config.protocol = protocol.toLowerCase();
            if (port) this.config.port = port;
            this.logger.info(`Switched protocol to [${this.config.protocol.toUpperCase()}]`);
            return this;
        }

        /**
         * Universal Low-Level Request Dispatcher
         */
        async request(endpoint, method = 'GET', data = null, where = null) {
            const timer = this.logger.startTimer(`${method} ${endpoint}`);
            try {
                let result;
                if (this.config.protocol === 'web_api') {
                    result = await this._requestWebApi(endpoint, method, data, where);
                } else {
                    // Default: Native RouterOS v7 REST API
                    result = await this._requestRest(endpoint, method, data, where);
                }
                timer.end();
                return result;
            } catch (err) {
                timer.end();
                this.logger.error(`Request failed [${method} ${endpoint}]: ${err.message}`);
                throw err;
            }
        }

        async _requestRest(endpoint, method = 'GET', data = null, where = null) {
            let cleanPath = endpoint.trim().replace(/^\/+/, '');
            // Strip print / add suffixes for REST
            cleanPath = cleanPath.replace(/\/(print|add)$/, '');

            const scheme = this.config.ssl ? 'https' : 'http';
            let url = `${scheme}://${this.config.host}:${this.config.port}/rest/${cleanPath}`;

            if (where && Object.keys(where).length > 0) {
                const q = new URLSearchParams(where).toString();
                url += `?${q}`;
            }

            const authHeader = 'Basic ' + (isNode
                ? Buffer.from(`${this.config.user}:${this.config.password}`).toString('base64')
                : btoa(`${this.config.user}:${this.config.password}`));

            const headers = Object.assign({
                'Authorization': authHeader,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }, this.config.headers);

            const fetchOpts = {
                method: method.toUpperCase(),
                headers: headers,
            };

            if (data && ['POST', 'PUT', 'PATCH'].includes(method.toUpperCase())) {
                fetchOpts.body = JSON.stringify(data);
            }

            // AbortController timeout handling
            let controller = null;
            let timeoutId = null;
            if (typeof AbortController !== 'undefined') {
                controller = new AbortController();
                fetchOpts.signal = controller.signal;
                timeoutId = setTimeout(() => controller.abort(), this.config.timeout);
            }

            this.logger.trace(`HTTP ${method} ${url}`);

            let response;
            try {
                response = await fetch(url, fetchOpts);
            } finally {
                if (timeoutId) clearTimeout(timeoutId);
            }

            if (!response.ok) {
                let errText = await response.text();
                try {
                    const parsed = JSON.parse(errText);
                    errText = parsed.detail || parsed.message || errText;
                } catch (_) {}
                throw new Error(`RouterOS REST [${response.status}]: ${errText}`);
            }

            const text = await response.text();
            return text ? JSON.parse(text) : {};
        }

        async _requestWebApi(action, method = 'GET', data = null, where = null) {
            let url = `${this.config.apiUrl}?action=${encodeURIComponent(action)}`;
            if (where && Object.keys(where).length > 0) {
                url += `&${new URLSearchParams(where).toString()}`;
            }

            const fetchOpts = {
                method: method.toUpperCase(),
                headers: Object.assign({
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }, this.config.headers),
            };

            if (data) {
                fetchOpts.body = JSON.stringify(data);
            }

            const response = await fetch(url, fetchOpts);
            const json = await response.json();
            if (!json.success && json.error) {
                throw new Error(json.error);
            }
            return json.data !== undefined ? json.data : json;
        }

        // --- FULL OPERATIONAL CRUD ACTIONS (ZERO OVERHEAD) ---

        async create(path, data = {}) {
            return this.request(path, 'PUT', data);
        }

        async add(path, data = {}) {
            return this.create(path, data);
        }

        async read(path, where = {}) {
            return this.request(path, 'GET', null, where);
        }

        async get(path, where = {}) {
            return this.read(path, where);
        }

        async write(path, params = {}) {
            return this.request(path, 'POST', params);
        }

        async set(path, idOrName, params = {}) {
            const cleanPath = path.trim().replace(/^\/+/, '').replace(/\/(print|set|add)$/, '');
            let targetId = idOrName;
            if (!targetId.startsWith('*')) {
                const found = await this.findId(idOrName, cleanPath);
                if (found) targetId = found;
            }
            const updatePath = `${cleanPath}/${encodeURIComponent(targetId)}`;
            return this.request(updatePath, 'PATCH', params);
        }

        async remove(path, idOrName) {
            const cleanPath = path.trim().replace(/^\/+/, '').replace(/\/(print|remove|add)$/, '');
            let targetId = idOrName;
            if (!targetId.startsWith('*')) {
                const found = await this.findId(idOrName, cleanPath);
                if (found) targetId = found;
            }
            const deletePath = `${cleanPath}/${encodeURIComponent(targetId)}`;
            return this.request(deletePath, 'DELETE');
        }

        async toggle(path, idOrName) {
            const cleanPath = path.trim().replace(/^\/+/, '').replace(/\/(print|set|add)$/, '');
            const records = await this.read(cleanPath, { name: idOrName });
            if (records && records.length > 0) {
                const currDisabled = records[0].disabled === 'true' || records[0].disabled === true;
                return this.set(cleanPath, records[0]['.id'] || idOrName, { disabled: !currDisabled });
            }
            throw new Error(`Item '${idOrName}' not found on ${cleanPath} to toggle`);
        }

        async findId(name, path = 'ip/hotspot/user') {
            const cleanPath = path.trim().replace(/^\/+/, '').replace(/\/(print|add)$/, '');
            const records = await this.read(cleanPath, { name });
            if (Array.isArray(records) && records.length > 0 && records[0]['.id']) {
                return records[0]['.id'];
            }
            return null;
        }

        async terminal(cliCommand) {
            this.logger.info(`Terminal CLI: ${cliCommand}`);
            return this.request('terminal', 'POST', { cmd: cliCommand });
        }
    }

    /**
     * MultiRouterPool: Blazing-Fast Concurrent & Parallel Connection Manager
     */
    class MultiRouterPool {
        constructor(primaryRos) {
            this.primary = primaryRos;
            this.pool = new Map();
        }

        /**
         * Add a router to the pool.
         */
        add(name, config) {
            const merged = Object.assign({}, this.primary.config, config);
            const client = new RouterOS(merged);
            this.pool.set(name, client);
            this.primary.logger.info(`Added router [${name}] (${merged.host}) to MultiRouterPool`);
            return this;
        }

        /**
         * Get router client instance by name.
         */
        router(name) {
            if (!this.pool.has(name)) {
                throw new Error(`Router '${name}' is not registered in MultiRouterPool`);
            }
            return this.pool.get(name);
        }

        /**
         * List all registered router identifiers.
         */
        list() {
            return Array.from(this.pool.keys());
        }

        /**
         * Concurrent Parallel Read across ALL routers simultaneously.
         * Runs in parallel using Promise.allSettled without blocking.
         */
        async concurrentRead(path, where = {}) {
            const timer = this.primary.logger.startTimer(`concurrentRead on ${this.pool.size} routers`);
            const entries = Array.from(this.pool.entries());

            const promises = entries.map(async ([name, client]) => {
                try {
                    const data = await client.read(path, where);
                    return { router: name, host: client.config.host, success: true, data };
                } catch (err) {
                    return { router: name, host: client.config.host, success: false, error: err.message };
                }
            });

            const results = await Promise.allSettled(promises);
            timer.end();

            return results.map((r, i) => {
                if (r.status === 'fulfilled') return r.value;
                return { router: entries[i][0], success: false, error: r.reason?.message || 'Unknown error' };
            });
        }

        /**
         * Broadcast Write across all routers simultaneously.
         */
        async concurrentWrite(path, params = {}) {
            const entries = Array.from(this.pool.entries());
            const promises = entries.map(async ([name, client]) => {
                try {
                    const res = await client.write(path, params);
                    return { router: name, success: true, res };
                } catch (err) {
                    return { router: name, success: false, error: err.message };
                }
            });
            return Promise.all(promises);
        }

        /**
         * Parallel Health Check across all routers simultaneously.
         */
        async healthCheck() {
            const entries = Array.from(this.pool.entries());
            const checks = entries.map(async ([name, client]) => {
                const t0 = Date.now();
                try {
                    const res = await client.system.getResources();
                    return {
                        router: name,
                        host: client.config.host,
                        status: 'online',
                        latency: `${Date.now() - t0}ms`,
                        cpuLoad: res['cpu-load'] ? `${res['cpu-load']}%` : '0%',
                        uptime: res.uptime || '-',
                        board: res['board-name'] || '-',
                    };
                } catch (err) {
                    return {
                        router: name,
                        host: client.config.host,
                        status: 'offline',
                        latency: `${Date.now() - t0}ms`,
                        error: err.message,
                    };
                }
            });
            return Promise.all(checks);
        }
    }

    /**
     * Hotspot Management Sub-module
     */
    class HotspotModule {
        constructor(ros) { this.ros = ros; }

        async getUsers(profile = '') {
            return this.ros.read('ip/hotspot/user', profile ? { profile } : {});
        }
        async getActive() {
            return this.ros.read('ip/hotspot/active');
        }
        async getHosts() {
            return this.ros.read('ip/hotspot/host');
        }
        async kick(username) {
            return this.ros.remove('ip/hotspot/active', username);
        }
        async bypass(mac, ip = '', comment = 'JS-Bypass') {
            const params = { 'mac-address': mac, action: 'bypassed', comment };
            if (ip) params.address = ip;
            return this.ros.create('ip/hotspot/ip-binding', params);
        }
        async setLimit(username, uptime, bytes = null) {
            const p = {};
            if (uptime) p['limit-uptime'] = uptime;
            if (bytes) p['limit-bytes-total'] = String(bytes);
            return this.ros.set('ip/hotspot/user', username, p);
        }
        async setComment(username, comment) {
            return this.ros.set('ip/hotspot/user', username, { comment });
        }
        async setEmail(username, email) {
            return this.ros.set('ip/hotspot/user', username, { email });
        }
        async setName(oldNameOrId, newName) {
            return this.ros.set('ip/hotspot/user', oldNameOrId, { name: newName });
        }
    }

    /**
     * Voucher Generator Sub-module
     */
    class VoucherModule {
        constructor(ros) { this.ros = ros; }

        async getVouchers(filters = {}) {
            return this.ros.read('ip/hotspot/user', filters);
        }
        async generate(count = 10, options = {}) {
            const prefix = options.prefix || 'VC-';
            const profile = options.profile || 'default';
            const uptime = options.time_limit || options.uptime || '2h';
            const created = [];

            for (let i = 0; i < count; i++) {
                const rnd = Math.random().toString(36).substring(2, 7);
                const username = `${prefix}${rnd}`;
                const password = options.user_equals_password ? username : Math.random().toString(36).substring(2, 7);
                const res = await this.ros.create('ip/hotspot/user', {
                    name: username,
                    password: password,
                    profile: profile,
                    'limit-uptime': uptime,
                    comment: options.comment || 'JS-Voucher',
                });
                created.push({ username, password, profile, uptime, res });
            }
            return { count: created.length, vouchers: created };
        }
    }

    /**
     * PPPoE ISP Billing Sub-module
     */
    class PPPoEModule {
        constructor(ros) { this.ros = ros; }

        async getUsers() { return this.ros.read('ppp/secret'); }
        async getActive() { return this.ros.read('ppp/active'); }
        async suspend(username, isolateProfile = 'ISOLIR') {
            await this.ros.set('ppp/secret', username, { profile: isolateProfile });
            // Kick active session
            try { await this.ros.remove('ppp/active', username); } catch (_) {}
            return { status: 'suspended', user: username, profile: isolateProfile };
        }
        async unsuspend(username, activeProfile = 'default') {
            return this.ros.set('ppp/secret', username, { profile: activeProfile });
        }
        async bindCallerId(username, macAddress) {
            return this.ros.set('ppp/secret', username, { 'caller-id': macAddress });
        }
    }

    /**
     * Multi-PTMP Wireless Sub-module
     */
    class PtmpModule {
        constructor(ros) { this.ros = ros; }

        async detectHardware() {
            const [legacy, w60g] = await Promise.allSettled([
                this.ros.read('interface/wireless'),
                this.ros.read('interface/w60g'),
            ]);
            return {
                hasLegacyWireless: legacy.status === 'fulfilled' && Array.isArray(legacy.value) && legacy.value.length > 0,
                has60gPtmp: w60g.status === 'fulfilled' && Array.isArray(w60g.value) && w60g.value.length > 0,
            };
        }

        async getStations(iface = null) {
            const where = iface ? { interface: iface } : {};
            const records = await this.ros.read('interface/wireless/registration-table', where);
            if (!Array.isArray(records)) return [];

            return records.map(s => {
                const sig = parseInt(s['signal-strength'] || '-100', 10);
                let health = 'Excellent';
                if (sig < -80) health = 'Critical / Degraded';
                else if (sig < -72) health = 'Fair';
                else if (sig < -65) health = 'Good';

                return {
                    id: s['.id'] || '',
                    mac: s['mac-address'] || '',
                    interface: s.interface || '',
                    signal: `${sig} dBm`,
                    txRate: s['tx-rate'] || '0Mbps',
                    rxRate: s['rx-rate'] || '0Mbps',
                    uptime: s.uptime || '0s',
                    health,
                    comment: s.comment || '',
                };
            });
        }

        async getMetrics(iface = null) {
            const stations = await this.getStations(iface);
            const total = stations.length;
            if (total === 0) return { totalStations: 0, healthPercentage: 100 };

            const degraded = stations.filter(s => s.health.includes('Critical')).length;
            const healthPct = Math.round(((total - degraded) / total) * 100);
            return { totalStations: total, degradedCount: degraded, healthPercentage: healthPct };
        }

        async getAccessList() {
            return this.ros.read('interface/wireless/access-list');
        }

        async addAccessList(mac, options = {}) {
            const params = {
                'mac-address': mac.toUpperCase().replace(/[-.]/g, ':'),
                authentication: options.allow !== false ? 'yes' : 'no',
                forwarding: options.allow !== false ? 'yes' : 'no',
            };
            if (options.interface) params.interface = options.interface;
            if (options.signalRange) params['signal-range'] = options.signalRange;
            if (options.comment) params.comment = options.comment;
            return this.ros.create('interface/wireless/access-list', params);
        }

        async removeAccessList(macOrId) {
            return this.ros.remove('interface/wireless/access-list', macOrId);
        }

        async kickStation(mac) {
            return this.ros.remove('interface/wireless/registration-table', mac);
        }

        async setChannel(iface, freq, band = '5ghz-only-n', width = '20/40mhz-XX') {
            return this.ros.set('interface/wireless', iface, {
                frequency: String(freq),
                band: band,
                'channel-width': width,
            });
        }

        async setProtocol(iface, protocol = 'nv2') {
            return this.ros.set('interface/wireless', iface, { 'wireless-protocol': protocol });
        }

        async setNv2(iface, options = {}) {
            return this.ros.set('interface/wireless', iface, options);
        }
    }

    /**
     * System & Telemetry Sub-module
     */
    class SystemModule {
        constructor(ros) { this.ros = ros; }

        async getResources() {
            const res = await this.ros.read('system/resource');
            return Array.isArray(res) && res.length > 0 ? res[0] : res;
        }
        async getIdentity() {
            const id = await this.ros.read('system/identity');
            return Array.isArray(id) && id.length > 0 ? (id[0].name || 'MikroTik') : 'MikroTik';
        }
        async getTraffic(iface = 'ether1') {
            return this.ros.read(`interface/monitor-traffic?interface=${encodeURIComponent(iface)}&once=`);
        }
    }

    return {
        RouterOS,
        RouterOSClient: RouterOS,
        MultiRouterPool,
        Debugger,
        ConfigHelper,
    };
}));
