#!/usr/bin/env python3
"""
Project RouterOS - Universal Python SDK & CLI Tool
==================================================
Zero-dependency Python library for communicating with MikroTik RouterOS
across protocols: REST HTTP/HTTPS (v7+), SSH CLI, Telnet CLI, and Web JSON API.

Usage in Python:
    from routeros import RouterOS

    # Direct connection to RouterOS v7 REST
    ros = RouterOS(host="192.168.88.1", user="admin", password="", protocol="rest")

    # Or connect via the Framework REST API endpoint
    ros = RouterOS.via_api("http://localhost/project-routeros/api.php")

    # Operational Actions (CRUD)
    ros.create("/ip/address", {"address": "10.10.10.1/24", "interface": "ether2"})
    users = ros.read("/ip/hotspot/user")
    ros.set("/ip/hotspot/user", "budi", {"limit-uptime": "2h", "comment": "VIP Client"})
    ros.remove("/ip/hotspot/user", "budi")

    # Multi-PTMP Routeros Connection Management
    stations = ros.ptmp.get_stations()
    ros.ptmp.kick_station("AA:BB:CC:DD:EE:FF")
    ros.ptmp.set_channel("ptmp-ap", 5180)

Usage via CLI:
    python routeros.py status
    python routeros.py ptmp stations
    python routeros.py read /ip/hotspot/user
    python routeros.py kick username
"""

import sys
import json
import base64
import urllib.request
import urllib.parse
import urllib.error
import ssl
import subprocess
import socket
import re
from typing import Dict, List, Any, Optional, Union


class RouterOSError(Exception):
    """Custom RouterOS Exception."""
    pass


class RouterOS:
    def __init__(
        self,
        host: str = "192.168.88.1",
        user: str = "admin",
        password: str = "",
        port: Optional[int] = None,
        protocol: str = "rest",
        api_url: Optional[str] = None,
        timeout: int = 5,
        verify_ssl: bool = False,
    ):
        self.host = host
        self.user = user
        self.password = password
        self.protocol = protocol.lower()
        self.api_url = api_url.rstrip("/") if api_url else None
        self.timeout = timeout
        self.verify_ssl = verify_ssl

        # Default port by protocol
        if port is not None:
            self.port = port
        elif self.protocol == "rest":
            self.port = 443 if self.verify_ssl else 80
        elif self.protocol == "ssh":
            self.port = 22
        elif self.protocol == "telnet":
            self.port = 23
        else:
            self.port = 8728

        # Initialize Sub-modules
        self.ptmp = PtmpModule(self)
        self.hotspot = HotspotModule(self)
        self.voucher = VoucherModule(self)
        self.pppoe = PPPoEModule(self)
        self.system = SystemModule(self)
        self.interface = InterfaceModule(self)

    @classmethod
    def via_api(cls, api_url: str) -> "RouterOS":
        """Instantiate client connecting to project-routeros api.php."""
        return cls(api_url=api_url, protocol="web_api")

    # --- LOW-LEVEL REQUEST HANDLERS ---

    def _request_web_api(self, action: str, method: str = "GET", data: Optional[Dict[str, Any]] = None) -> Any:
        url = f"{self.api_url}?action={urllib.parse.quote(action)}"
        headers = {"Accept": "application/json", "Content-Type": "application/json"}
        req_data = None

        if method == "GET" and data:
            url += f"&{urllib.parse.urlencode(data)}"
        elif data:
            req_data = json.dumps(data).encode("utf-8")

        req = urllib.request.Request(url, data=req_data, headers=headers, method=method)
        try:
            ctx = ssl.create_default_context()
            ctx.check_hostname = False
            ctx.verify_mode = ssl.CERT_NONE
            with urllib.request.urlopen(req, context=ctx, timeout=self.timeout) as resp:
                res = json.loads(resp.read().decode("utf-8"))
                return res.get("data", res)
        except urllib.error.HTTPError as e:
            body = e.read().decode("utf-8")
            try:
                parsed = json.loads(body)
                raise RouterOSError(parsed.get("error", body))
            except Exception:
                raise RouterOSError(f"HTTP {e.code}: {body}")
        except Exception as e:
            raise RouterOSError(str(e))

    def _request_rest(self, method: str, path: str, data: Optional[Dict[str, Any]] = None, query: Optional[Dict[str, Any]] = None) -> Any:
        clean_path = path.strip("/").removeprefix("print").removeprefix("add").removesuffix("/print").removesuffix("/add")
        scheme = "https" if self.port in [443, 8443] else "http"
        url = f"{scheme}://{self.host}:{self.port}/rest/{clean_path}"

        if query:
            url += f"?{urllib.parse.urlencode(query)}"

        auth_str = f"{self.user}:{self.password}"
        auth_b64 = base64.b64encode(auth_str.encode("utf-8")).decode("utf-8")

        headers = {
            "Authorization": f"Basic {auth_b64}",
            "Accept": "application/json",
            "Content-Type": "application/json",
        }

        body_bytes = json.dumps(data).encode("utf-8") if data and method in ["POST", "PUT", "PATCH"] else None
        req = urllib.request.Request(url, data=body_bytes, headers=headers, method=method)

        ctx = ssl.create_default_context()
        if not self.verify_ssl:
            ctx.check_hostname = False
            ctx.verify_mode = ssl.CERT_NONE

        try:
            with urllib.request.urlopen(req, context=ctx, timeout=self.timeout) as resp:
                content = resp.read().decode("utf-8")
                return json.loads(content) if content else {}
        except urllib.error.HTTPError as e:
            body = e.read().decode("utf-8")
            try:
                parsed = json.loads(body)
                err_msg = parsed.get("detail", parsed.get("message", body))
                raise RouterOSError(f"REST [{e.code}]: {err_msg}")
            except Exception:
                raise RouterOSError(f"HTTP {e.code}: {body}")
        except Exception as e:
            raise RouterOSError(f"REST Connection Error: {str(e)}")

    def _exec_ssh(self, command: str) -> str:
        cmd = ["ssh", "-o", "StrictHostKeyChecking=no", "-o", f"ConnectTimeout={self.timeout}",
               "-p", str(self.port), f"{self.user}@{self.host}", command]
        try:
            res = subprocess.run(cmd, capture_output=True, text=True, timeout=self.timeout)
            return res.stdout or res.stderr
        except Exception as e:
            raise RouterOSError(f"SSH Execution Error: {str(e)}")

    # --- UNIVERSAL CRUD ACTIONS ---

    def create(self, path: str, data: Dict[str, Any]) -> Any:
        """Create / add a new record to any RouterOS path."""
        if self.api_url:
            return self._request_web_api("create", "POST", {"path": path, "data": data})
        elif self.protocol == "rest":
            return self._request_rest("PUT", path, data)
        elif self.protocol == "ssh":
            cli = path.strip("/").replace("/", " ") + " add"
            for k, v in data.items():
                cli += f' {k}="{v}"'
            return self._exec_ssh(cli)
        raise RouterOSError(f"Protocol '{self.protocol}' not supported for create")

    def read(self, path: str, where: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        """Read / print records from any RouterOS path with optional filters."""
        if self.api_url:
            return self._request_web_api("read", "POST", {"path": path, "where": where or {}})
        elif self.protocol == "rest":
            res = self._request_rest("GET", path, query=where)
            return res if isinstance(res, list) else [res]
        elif self.protocol == "ssh":
            cli = path.strip("/").replace("/", " ") + " print detail"
            if where:
                cli += " where"
                for k, v in where.items():
                    cli += f' {k}="{v}"'
            raw = self._exec_ssh(cli)
            return self._parse_cli_output(raw)
        raise RouterOSError(f"Protocol '{self.protocol}' not supported for read")

    def get(self, path: str, where: Optional[Dict[str, Any]] = None) -> List[Dict[str, Any]]:
        """Alias of read()."""
        return self.read(path, where)

    def set(self, path: str, id_or_name: str, params: Dict[str, Any]) -> Any:
        """Update properties of an item by name or .id."""
        if self.api_url:
            return self._request_web_api("generic_set", "POST", {"path": path, "id_or_name": id_or_name, "params": params})
        elif self.protocol == "rest":
            target_id = id_or_name if id_or_name.startswith("*") else self.find_id(id_or_name, path) or id_or_name
            rest_path = f"{path.strip('/')}/{urllib.parse.quote(target_id)}"
            return self._request_rest("PATCH", rest_path, params)
        elif self.protocol == "ssh":
            cli = f"{path.strip('/').replace('/', ' ')} set \"{id_or_name}\""
            for k, v in params.items():
                cli += f' {k}="{v}"'
            return self._exec_ssh(cli)
        raise RouterOSError(f"Protocol '{self.protocol}' not supported for set")

    def remove(self, path: str, id_or_name: str) -> Any:
        """Remove record by name or .id."""
        if self.api_url:
            return self._request_web_api("generic_remove", "POST", {"path": path, "id_or_name": id_or_name})
        elif self.protocol == "rest":
            target_id = id_or_name if id_or_name.startswith("*") else self.find_id(id_or_name, path) or id_or_name
            rest_path = f"{path.strip('/')}/{urllib.parse.quote(target_id)}"
            return self._request_rest("DELETE", rest_path)
        elif self.protocol == "ssh":
            return self._exec_ssh(f"{path.strip('/').replace('/', ' ')} remove \"{id_or_name}\"")
        raise RouterOSError(f"Protocol '{self.protocol}' not supported for remove")

    def find_id(self, name: str, path: str = "/ip/hotspot/user") -> Optional[str]:
        """Find internal RouterOS .id by item name."""
        if self.api_url:
            res = self._request_web_api("find_id", "POST", {"name": name, "path": path})
            return res.get("id") if isinstance(res, dict) else None

        records = self.read(path, {"name": name})
        if records and isinstance(records, list) and len(records) > 0:
            return records[0].get(".id")
        return None

    def terminal(self, cli_command: str) -> str:
        """Execute raw terminal command."""
        if self.api_url:
            res = self._request_web_api("terminal", "POST", {"cmd": cli_command})
            return res.get("output", "") if isinstance(res, dict) else str(res)
        return self._exec_ssh(cli_command)

    def _parse_cli_output(self, output: str) -> List[Dict[str, str]]:
        records = []
        current = {}
        for line in output.splitlines():
            line = line.strip()
            if not line or line.startswith("Flags:"):
                continue
            if re.match(r"^\d+\s+", line) or re.match(r"^\*\w+", line):
                if current:
                    records.append(current)
                    current = {}
            pairs = re.findall(r'([a-zA-Z0-9_\-\.]+)=([^\s"]+|"[^"]*")', line)
            for k, v in pairs:
                current[k] = v.strip('"')
        if current:
            records.append(current)
        return records


class PtmpModule:
    """Multi-PTMP Routeros Connection Sub-module."""
    def __init__(self, ros: RouterOS):
        self.ros = ros

    def get_stations(self, interface: Optional[str] = None) -> List[Dict[str, Any]]:
        if self.ros.api_url:
            data = {"interface": interface} if interface else None
            return self.ros._request_web_api("ptmp_stations", "GET", data)
        where = {"interface": interface} if interface else {}
        return self.ros.read("/interface/wireless/registration-table", where)

    def get_metrics(self, interface: Optional[str] = None) -> Dict[str, Any]:
        if self.ros.api_url:
            data = {"interface": interface} if interface else None
            return self.ros._request_web_api("ptmp_metrics", "GET", data)
        stations = self.get_stations(interface)
        total = len(stations)
        return {"total_stations": total, "status": "active" if total > 0 else "empty"}

    def kick_station(self, mac_address: str) -> Any:
        if self.ros.api_url:
            return self.ros._request_web_api("ptmp_kick", "POST", {"mac": mac_address})
        return self.ros.remove("/interface/wireless/registration-table", mac_address)

    def set_channel(self, interface: str, frequency: Union[int, str], band: str = "5ghz-only-n", width: str = "20/40mhz-XX") -> Any:
        if self.ros.api_url:
            return self.ros._request_web_api("ptmp_set_channel", "POST", {
                "interface": interface, "frequency": frequency, "band": band, "width": width
            })
        return self.ros.set("/interface/wireless", interface, {
            "frequency": str(frequency), "band": band, "channel-width": width
        })

    def set_protocol(self, interface: str, protocol: str = "nv2") -> Any:
        if self.ros.api_url:
            return self.ros._request_web_api("ptmp_set_protocol", "POST", {
                "interface": interface, "wireless_protocol": protocol
            })
        return self.ros.set("/interface/wireless", interface, {"wireless-protocol": protocol})


class HotspotModule:
    """Hotspot Management Sub-module."""
    def __init__(self, ros: RouterOS):
        self.ros = ros

    def get_users(self, profile: Optional[str] = None) -> List[Dict[str, Any]]:
        where = {"profile": profile} if profile else {}
        return self.ros.read("/ip/hotspot/user", where)

    def kick_user(self, username: str) -> Any:
        if self.ros.api_url:
            return self.ros._request_web_api("kick", "POST", {"username": username})
        return self.ros.remove("/ip/hotspot/active", username)

    def set_limit(self, username: str, limit_uptime: Optional[str] = None, limit_bytes: Optional[int] = None) -> Any:
        params = {}
        if limit_uptime:
            params["limit-uptime"] = limit_uptime
        if limit_bytes:
            params["limit-bytes-total"] = str(limit_bytes)
        return self.ros.set("/ip/hotspot/user", username, params)

    def set_comment(self, username: str, comment: str) -> Any:
        return self.ros.set("/ip/hotspot/user", username, {"comment": comment})

    def setup_mikhmon_profile(self, name: str, rate_limit: str = "3M/2M", validity: str = "24h", price: int = 8000, options: Optional[Dict[str, Any]] = None) -> Any:
        opts = options or {}
        sprice = opts.get("selling_price", 10000)
        mode = opts.get("expiry_mode", "remc")
        lock = opts.get("lock_user", "Disable")
        queue = opts.get("parent_queue", "none")
        pool = opts.get("address_pool", "none")
        shared = str(opts.get("shared_users", "1"))
        mode_char = "N" if "n" in mode.lower() else "R"
        session_id = opts.get("session_id", random.randint(1000000, 9999999))
        val_days = int(validity[:-1]) if validity.endswith("d") and validity[:-1].isdigit() else 0
        source = "$month$year" if val_days >= 30 else "$date"

        on_login = (
            f':put (",{mode},{price},{validity},{sprice},,{lock},,mikhmon,{session_id},"); :local mode "{mode_char}"; '
            '{:local itv do={:local getITV [:pic  $v 0 ([ :len $v ] - 1)]; :return $getITV;}; '
            ':local ndays do={:local mdays  {31;28;31;30;31;30;31;31;30;31;30;31}; :local months {"jan"=1;"feb"=2;"mar"=3;"apr"=4;"may"=5;"jun"=6;"jul"=7;"aug"=8;"sep"=9;"oct"=10;"nov"=11;"dec"=12}; :local monthr  {"jan";"feb";"mar";"apr";"may";"jun";"jul";"aug";"sep";"oct";"nov";"dec"}; :local dd  [:tonum [:pick $date 4 6]]; :local yy [:tonum [:pick $date 7 11]]; :local month [:pick $date 0 3]; :local mm (:$months->$month); :set dd ($dd+$days); :local dm [:pick $mdays ($mm-1)]; :if ($mm=2 && (($yy&3=0 && ($yy/100*100 != $yy)) || $yy/400*400=$yy) ) do={ :set dm 29 }; :while ($dd>$dm) do={  :set dd ($dd-$dm);  :set mm ($mm+1);  :if ($mm>12) do={    :set mm 1;    :set yy ($yy+1);  }; :set dm [:pick $mdays ($mm-1)]; :if ($mm=2 &&  (($yy&3=0 && ($yy/100*100 != $yy)) || $yy/400*400=$yy) ) do={ :set dm 29 };}; :local res "$[:pick $monthr ($mm-1)]/"; :if ($dd<10) do={ :set res ($res."0") }; :set $res "$res$dd/$yy"; :return $res;}; '
            ':local convert do={:local monthr  {"jan";"feb";"mar";"apr";"may";"jun";"jul";"aug";"sep";"oct";"nov";"dec"};:local dd [:pick $date 8 11];:local yy [:tonum [:pick $date 0 4]];:local mm [:tonum [:pick $date 5 7]];:local mmm "$[:pick $monthr ($mm-1)]";:local newdate "$mmm/$dd/$yy";:return $newdate;}; '
            f':local validity "{validity}";  :local date [ /system clock get date ]; if ([:pick $date 4 5] = "-" and [:pick $date 7 8] = "-" ) do={{:set date [$convert date=$date];}} else={{:set date $date;}};  :local year [ :pick $date 7 11 ];:local month [ :pick $date 0 3 ];  :local time [ /system clock get time ]; :local comment [/ip hotspot user get $user comment]; :local ucode [:pic $comment 0 2]; '
            ':if ($ucode = "vc" or $ucode = "up" or $comment = "") do={ :local days ""; :local ndate ""; :local ctime ""; :local getDT [:pic  $validity ([ :len $validity ] - 1) [ :len $validity ]]; '
            ':if ($getDT= "d") do={    :set days [$itv v=$validity];    :set ndate [$ndays date=$date days=$days ];    :set ctime $time;}; '
            ':if ($getDT = "h" or $getDT = "m") do={    :local curt ([/system clock get time]+$validity);    :if ([:len $curt] > 8) do={    :set validity [:pic  $curt 0 ([ :len $curt ] - 8) ];    :set curt [:pic  $curt [ :len $validity ] [ :len $curt ] ];    :set days [$itv v=$validity];    :set ndate [$ndays date=$date days=$days ];    :set ctime $curt;    } else={    :set days 0;    :set ndate $date;    :set ctime $curt;    }}; '
            f'/ip hotspot user set $user comment="$ndate $ctime $mode"; ; :local mac $"mac-address"; :local time [/system clock get time ]; /system script add name="$date-|-$time-|-$user-|-{price}-|-$address-|-$mac-|-{validity}-|-{name}-|-$comment" owner="$month$year" source="{source}" comment="mikhmon"}}}}'
        )

        params = {
            "name": name,
            "parent-queue": queue,
            "shared-users": shared,
            "rate-limit": rate_limit,
            "address-pool": pool,
            "on-login": on_login,
        }
        return self.ros.create("/ip/hotspot/user/profile", params)


class VoucherModule:
    """Voucher Generator Sub-module."""
    def __init__(self, ros: RouterOS):
        self.ros = ros

    def generate(self, count: int = 10, profile: str = "default", time_limit: str = "1h", price: float = 5000) -> Any:
        if self.ros.api_url:
            return self.ros._request_web_api("generate_voucher", "POST", {
                "count": count, "profile": profile, "time_limit": time_limit, "price": price
            })
        raise NotImplementedError("Direct socket voucher generation requires api.php or batch creation")


class PPPoEModule:
    """PPPoE ISP Billing Sub-module."""
    def __init__(self, ros: RouterOS):
        self.ros = ros

    def get_users(self) -> List[Dict[str, Any]]:
        return self.ros.read("/ppp/secret")

    def suspend(self, username: str, profile: str = "ISOLIR") -> Any:
        return self.ros.set("/ppp/secret", username, {"profile": profile})

    def unsuspend(self, username: str, profile: str = "default") -> Any:
        return self.ros.set("/ppp/secret", username, {"profile": profile})


class SystemModule:
    """System & Health Sub-module."""
    def __init__(self, ros: RouterOS):
        self.ros = ros

    def get_resource(self) -> Dict[str, Any]:
        res = self.ros.read("/system/resource")
        return res[0] if res and isinstance(res, list) else {}

    def get_identity(self) -> str:
        res = self.ros.read("/system/identity")
        return res[0].get("name", "MikroTik") if res and isinstance(res, list) else "MikroTik"


class InterfaceModule:
    """Enterprise Server Interface Sub-module (SFP, Bonding, VRRP, VLANs, WireGuard)."""
    def __init__(self, ros: RouterOS):
        self.ros = ros

    def get_all(self) -> List[Dict[str, Any]]:
        return self.ros.read("/interface")

    def get_ethernet(self) -> List[Dict[str, Any]]:
        return self.ros.read("/interface/ethernet")

    def get_sfp(self) -> List[Dict[str, Any]]:
        return self.ros.read("/interface/ethernet", {"combo-mode": "sfp"})

    def get_bridges(self) -> List[Dict[str, Any]]:
        return self.ros.read("/interface/bridge")

    def get_vlans(self) -> List[Dict[str, Any]]:
        return self.ros.read("/interface/vlan")

    def get_bonding(self) -> List[Dict[str, Any]]:
        return self.ros.read("/interface/bonding")

    def get_vrrp(self) -> List[Dict[str, Any]]:
        return self.ros.read("/interface/vrrp")

    def get_wireguard(self) -> List[Dict[str, Any]]:
        return self.ros.read("/interface/wireguard")

    def get_traffic(self, interface: str = "ether1") -> Any:
        return self.ros.read(f"/interface/monitor-traffic?interface={urllib.parse.quote(interface)}&once=")


# --- CLI ENTRY POINT ---
if __name__ == "__main__":
    args = sys.argv[1:]
    command = args[0].lower() if args else "help"

    # Auto-load config if available
    ros = RouterOS(host="192.168.88.1", user="admin", password="", protocol="rest")

    if command == "status":
        print("⚡ MikroTik RouterOS Python SDK")
        print(f"Host: {ros.host}:{ros.port} [{ros.protocol.upper()}]")
        try:
            res = ros.system.get_resource()
            print(f"Board: {res.get('board-name', '-')}")
            print(f"CPU Load: {res.get('cpu-load', '0')}%")
            print(f"Uptime: {res.get('uptime', '-')}")
        except Exception as e:
            print(f"Offline / Connect error: {e}")

    elif command == "ptmp":
        sub = args[1].lower() if len(args) > 1 else "stations"
        if sub == "stations":
            try:
                stations = ros.ptmp.get_stations()
                print(f"📡 Connected Stations ({len(stations)}):")
                print(json.dumps(stations, indent=2))
            except Exception as e:
                print(f"Error: {e}")
        elif sub == "kick":
            mac = args[2] if len(args) > 2 else ""
            print(ros.ptmp.kick_station(mac))

    elif command in ["read", "get"]:
        path = args[1] if len(args) > 1 else "/system/resource"
        try:
            print(json.dumps(ros.read(path), indent=2))
        except Exception as e:
            print(f"Error: {e}")

    else:
        print("⚡ MikroTik RouterOS Python SDK & CLI")
        print("Usage:")
        print("  python routeros.py status")
        print("  python routeros.py ptmp stations")
        print("  python routeros.py ptmp kick <mac>")
        print("  python routeros.py read <path>")
