/**
 * RouterOS Web & JavaScript SDK (Lightweight Universal Client)
 * Compatible with Vanilla JS, React, Vue, Svelte, Node.js, and Mobile WebViews.
 * Connects directly to api.php REST JSON endpoints.
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
         * @param {Object} options.headers Custom headers (e.g. Authorization tokens)
         */
        constructor(options = {}) {
            this.endpoint = (options.endpoint || 'api.php').replace(/\/+$/, '');
            this.headers = Object.assign({
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }, options.headers || {});

            // Initialize Sub-modules
            this.hotspot = new HotspotClient(this);
            this.voucher = new VoucherClient(this);
            this.pppoe = new PPPoEClient(this);
            this.system = new SystemClient(this);
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

        // --- GENERIC SUPERPOWER ACTIONS ---

        async findId(name, path = '/ip/hotspot/user') {
            return this.request('find_id', 'POST', { name, path });
        }

        async set(path, idOrName, params = {}) {
            return this.request('generic_set', 'POST', { path, id_or_name: idOrName, params });
        }

        async get(path, where = {}) {
            return this.request('generic_get', 'POST', { path, where });
        }

        async remove(path, idOrName) {
            return this.request('generic_remove', 'POST', { path, id_or_name: idOrName });
        }
    }

    class HotspotClient {
        constructor(client) {
            this.client = client;
        }

        async getUsers() {
            return this.client.request('users', 'GET');
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
            return this.client.request('generate_vouchers', 'POST', Object.assign({ count }, options));
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
