/**
 * RouterOS Web & JavaScript SDK (Lightweight Universal Client)
 * Compatible with Vanilla JS, React, Vue, Svelte, Node.js, and Mobile WebViews.
 * Connects directly to api.php REST JSON endpoints or MikroTik v7 REST API.
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.RouterOSClient = factory();
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    class RouterOSClient {
        /**
         * @param {Object} options
         * @param {string} options.endpoint Base URL to api.php (default: 'api.php')
         * @param {string} options.protocol Transport protocol: 'api'|'rest'|'ssh'|'telnet'|'auto'
         * @param {Object} options.headers Custom headers (e.g. Authorization tokens)
         */
        constructor(options = {}) {
            this.endpoint = (options.endpoint || 'api.php').replace(/\/+$/, '');
            this.protocol = options.protocol || null;
            this.headers = Object.assign({
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }, options.headers || {});

            // Initialize Sub-modules
            this.hotspot = new HotspotClient(this);
            this.voucher = new VoucherClient(this);
            this.pppoe = new PPPoEClient(this);
            this.ptmp = new PtmpClient(this);
            this.system = new SystemClient(this);
        }

        /**
         * Set or switch transport protocol.
         * @param {string} protocol 'api', 'rest', 'ssh', 'telnet', 'auto'
         */
        setProtocol(protocol) {
            this.protocol = protocol;
            return this;
        }

        /**
         * Low-level fetch wrapper for api.php.
         *
         * @param {string} action
         * @param {string} method 'GET' or 'POST'
         * @param {Object} [data]
         * @returns {Promise<any>}
         */
        async request(action, method = 'GET', data = null) {
            let url = `${this.endpoint}?action=${encodeURIComponent(action)}`;
            if (this.protocol) {
                url += `&protocol=${encodeURIComponent(this.protocol)}`;
            }

            const fetchOpts = {
                method: method.toUpperCase(),
                headers: this.headers,
            };

            if (method.toUpperCase() === 'GET' && data) {
                const params = new URLSearchParams(data).toString();
                url += `&${params}`;
            } else if (data) {
                fetchOpts.body = JSON.stringify(data);
            }

            const response = await fetch(url, fetchOpts);
            const json = await response.json();

            if (!json.success && json.error) {
                throw new Error(json.error);
            }

            return json.data !== undefined ? json.data : json;
        }

        // --- FULL OPERATIONAL CRUD ACTIONS ---

        async create(path, data = {}) {
            return this.request('create', 'POST', { path, data });
        }

        async write(path, params = {}) {
            return this.request('write', 'POST', { path, params });
        }

        async read(path, where = {}) {
            return this.request('read', 'POST', { path, where });
        }

        async get(path, where = {}) {
            return this.read(path, where);
        }

        async set(path, idOrName, params = {}) {
            return this.request('generic_set', 'POST', { path, id_or_name: idOrName, params });
        }

        async remove(path, idOrName) {
            return this.request('generic_remove', 'POST', { path, id_or_name: idOrName });
        }

        async toggle(path, idOrName) {
            return this.request('generic_toggle', 'POST', { path, id_or_name: idOrName });
        }

        async findId(name, path = '/ip/hotspot/user') {
            return this.request('find_id', 'POST', { name, path });
        }

        async terminal(cmd) {
            return this.request('terminal', 'POST', { cmd });
        }
    }

    class HotspotClient {
        constructor(client) {
            this.client = client;
        }

        async getUsers(profile = '') {
            return this.client.request('users', 'GET', profile ? { profile } : null);
        }

        async getActive() {
            return this.client.request('active', 'GET');
        }

        async getHosts() {
            return this.client.request('hosts', 'GET');
        }

        async kick(username) {
            return this.client.request('kick', 'POST', { username });
        }

        async bypass(mac, ip = '', comment = '') {
            return this.client.request('bypass', 'POST', { mac, ip, comment });
        }

        async setLimit(username, limitUptime, limitBytes = null) {
            return this.client.request('set_limit', 'POST', { username, limit_uptime: limitUptime, limit_bytes: limitBytes });
        }

        async setComment(username, comment) {
            return this.client.request('set_comment', 'POST', { username, comment });
        }

        async setEmail(username, email) {
            return this.client.request('set_email', 'POST', { username, email });
        }

        async setName(oldNameOrId, newName) {
            return this.client.request('set_name', 'POST', { old_name: oldNameOrId, new_name: newName });
        }
    }

    class VoucherClient {
        constructor(client) {
            this.client = client;
        }

        async getVouchers(filters = {}) {
            return this.client.request('vouchers', 'GET', filters);
        }

        async generate(count = 10, options = {}) {
            return this.client.request('generate_voucher', 'POST', Object.assign({ count }, options));
        }

        async getSalesReport() {
            return this.client.request('sales_report', 'GET');
        }
    }

    class PPPoEClient {
        constructor(client) {
            this.client = client;
        }

        async getUsers() {
            return this.client.request('pppoe_users', 'GET');
        }

        async getActive() {
            return this.client.request('pppoe_active', 'GET');
        }

        async suspend(username, isolateProfile = 'ISOLIR') {
            return this.client.request('pppoe_suspend', 'POST', { username, profile: isolateProfile });
        }

        async unsuspend(username, activeProfile = 'default') {
            return this.client.request('pppoe_unsuspend', 'POST', { username, profile: activeProfile });
        }

        async bindCallerId(username, macAddress) {
            return this.client.request('pppoe_bind_caller', 'POST', { username, mac: macAddress });
        }
    }

    class PtmpClient {
        constructor(client) {
            this.client = client;
        }

        async detectHardware() {
            return this.client.request('ptmp_detect', 'GET');
        }

        async getStations(iface = null) {
            return this.client.request('ptmp_stations', 'GET', iface ? { interface: iface } : null);
        }

        async getMetrics(iface = null) {
            return this.client.request('ptmp_metrics', 'GET', iface ? { interface: iface } : null);
        }

        async getAccessList() {
            return this.client.request('ptmp_access_list', 'GET');
        }

        async addAccessList(mac, options = {}) {
            return this.client.request('ptmp_add_access', 'POST', Object.assign({ mac }, options));
        }

        async removeAccessList(macOrId) {
            return this.client.request('ptmp_remove_access', 'POST', { id: macOrId });
        }

        async kickStation(mac) {
            return this.client.request('ptmp_kick', 'POST', { mac });
        }

        async setChannel(iface, freq, band = '5ghz-only-n', width = '20/40mhz-XX') {
            return this.client.request('ptmp_set_channel', 'POST', { interface: iface, frequency: freq, band, width });
        }

        async setProtocol(iface, protocol = 'nv2') {
            return this.client.request('ptmp_set_protocol', 'POST', { interface: iface, wireless_protocol: protocol });
        }

        async setNv2(iface, options = {}) {
            return this.client.request('ptmp_set_nv2', 'POST', { interface: iface, options });
        }
    }

    class SystemClient {
        constructor(client) {
            this.client = client;
        }

        async getStatus() {
            return this.client.request('status', 'GET');
        }

        async getTraffic() {
            return this.client.request('traffic', 'GET');
        }
    }

    return RouterOSClient;
}));
