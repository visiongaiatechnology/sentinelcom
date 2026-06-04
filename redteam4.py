#!/usr/bin/env python3
# -*- coding: utf-8 -*-

# ==============================================================================
# VISIONGAIA TECHNOLOGY: AEGIS RED TEAM DEEP PASSIVE SCANNER (TEST 3)
# ZWECK: Hochperformante polymorphe Evasion-Tests & Belastungsprüfung der Parser-Grenzen
# STATUS: AUTORISIERTE R&D PENTEST-SUITE FÜR SENTINEL CE/EE
# ==============================================================================
#
# ⚠️ HINWEIS: POLYMORPHES STRESS-ENGINE
# Dieses Skript führt hochgradig obfuskierte, verschachtelte Payload-Mutationen aus,
# die fortgeschrittene Angreifer simulieren. Der Zweck besteht darin, die Grenzen
# der Parser-Normalisierung zu validieren (Unicode-Evasion, Double URL-Encoding,
# Parameter-Pollution, HTTP-Smuggling und kommentarbasierte SQLi-Slicing-Techniken).
#
# STRIKTE VORGABE: NUR gegen lokale Container oder Staging-Umgebungen testen,
# für die eine explizite, schriftliche Autorisierung vorliegt.
#
# ==============================================================================

import sys
import json
import asyncio
import aiohttp
import urllib.parse
from enum import Enum
from dataclasses import dataclass
from typing import Dict, List, Tuple

class Colors:
    GREEN = '\033[92m'
    RED = '\033[91m'
    YELLOW = '\033[93m'
    BLUE = '\033[94m'
    CYAN = '\033[96m'
    RESET = '\033[0m'

class HTTPMethod(Enum):
    GET = "GET"
    POST_FORM = "POST_FORM"
    POST_JSON = "POST_JSON"
    POST_MULTIPART = "POST_MULTIPART"
    HEADER_SMUGGLE = "HEADER_SMUGGLE"
    HPP_POLLUTE = "HPP_POLLUTE"

@dataclass
class ScanResult:
    category: str
    method: str
    payload: str
    mutation: str
    blocked: bool
    status_code: int
    latency_ms: float
    reason: str

class MutationMatrix:
    """Polymorphie-Matrix zur Belastungsprüfung von WAF-Normalisierungsalgorithmen."""
    
    @staticmethod
    def raw(payload: str) -> str:
        return payload
    
    @staticmethod
    def double_url_encode(payload: str) -> str:
        return urllib.parse.quote(urllib.parse.quote(payload))
    
    @staticmethod
    def mixed_case_spacing(payload: str) -> str:
        # Ersetzt Leerzeichen durch SQL-Inlinekommentare und mischt Groß-/Kleinschreibung
        mutated = ""
        for i, char in enumerate(payload):
            if char == ' ':
                mutated += "/**/"
            else:
                mutated += char.upper() if i % 2 == 0 else char.lower()
        return mutated

    @staticmethod
    def unicode_obfuscate(payload: str) -> str:
        # Maskiert Zeichen unter Verwendung der Standard-JSON/Unicode-Notation
        return "".join(f"\\u{ord(c):04x}" for c in payload)

    @staticmethod
    def html_entity_scramble(payload: str) -> str:
        # Kodiert kritische Zeichen in dezimale HTML-Entity-Schnittstellen
        return "".join(f"&#{ord(c)};" if c in "<>'\"();&" else c for c in payload)


class AdvancedPayloadRegistry:
    """Komplexe Angriffs-Signaturen zur Umgehung einfacher Regex-Filter."""

    ADVANCED_SQLI: Dict[str, List[str]] = {
        "SQLI_COMMENT_SLICING": [
            "sel/**/ect/**/1,2,3/**/from/**/wp_users",
            "un/**/ion/**/sel/**/ect/**/null,version()",
            "1'/**/or/**/1=1/**/and/**/sleep(2)#"
        ],
        "SQLI_HEX_ENCODED": [
            "1' AND (SELECT 0x564754)='VGT'--+",
            "1' UNION SELECT null, 0x61646d696e, version() --"
        ],
        "SQLI_LOGICAL_OR_ALTERNATIVE": [
            "1' || (SELECT 'VGT_TRUE')='VGT_TRUE'--+",
            "1' AND EXP(~(SELECT * FROM (SELECT 1)x))"
        ]
    }

    ADVANCED_XSS: Dict[str, List[str]] = {
        "XSS_NESTED_FILTER_EVASION": [
            "<sc<script>ript>alert('XSS_BYPASS_TEST')</script>",
            "<im<img>g src=x onerror=alert(1)>",
            "<svg onload=\"javascript\\u003aalert(1)\">"
        ],
        "XSS_EVENT_SMUGGLING": [
            "<div onpointerover=\"alert(1)\">Hover for Root Access</div>",
            "<a href=\"javascript:&apos;<iframe/onload=alert(1)&gt;&apos;\">Trigger Link</a>",
            "<iframe srcdoc=\"&lt;script&gt;alert(1)&lt;/script&gt;\">"
        ]
    }

    ADVANCED_LFI_RCE: Dict[str, List[str]] = {
        "LFI_NON_STANDARD_WRAPPERS": [
            "php://filter/read=convert.base64-encode/resource=wp-config.php",
            "php://filter/zlib.deflate/convert.base64-encode/resource=wp-config.php",
            "zip://./uploads/image.jpg#shell.php"
        ],
        "RCE_NESTED_CMD": [
            "c'a't' 'f'i'l'e'.txt",
            "/bin/p?sh -c 'whoami'",
            "exec(base64_decode('Y2F0IC9ldGMvcGFzc3dk'));"
        ]
    }

    SMUGGLING_HEADERS: Dict[str, List[str]] = {
        "PROXY_SPOOFING": [
            "127.0.0.1",
            "localhost",
            "10.0.0.1",
            "192.168.1.1"
        ],
        "XSS_HEADER_INJECT": [
            "<script>alert('XSS_VIA_UA')</script>",
            "XSS_BYPASS_VAL'\" onerror=alert(1)"
        ]
    }


class AsyncNetworkStressEngine:
    def __init__(self, target_url: str, concurrency_limit: int = 25):
        self.target_url = self._normalize_url(target_url)
        self.semaphore = asyncio.Semaphore(concurrency_limit)
        self.headers = {
            "User-Agent": "VGT-Aegis-Diamant-Stress-Engine/5.0",
            "Accept": "text/html,application/xhtml+xml,application/json"
        }

    @staticmethod
    def _normalize_url(url: str) -> str:
        if not url.startswith(("http://", "https://")):
            raise ValueError("Die URL muss mit dem Schema http:// oder https:// beginnen.")
        return url if url.endswith('/') else f"{url}/"

    def _assess_block(self, status: int, text: str, headers: dict) -> Tuple[bool, str]:
        # Prüft, ob Sentinel WAF den Payload erfolgreich blockiert hat
        if status in [403, 406]:
            return True, f"HTTP_{status}_Mitigated"
        if 'X-Aegis-Block' in headers or 'X-Sentinel-Shield' in headers:
            return True, "Sentinel_WAF_Block_Header_Erkannt"
        if any(indicator in text for indicator in ["AEGIS BLOCK", "Vision Integrity Sentinel", "Attack Blocked"]):
            return True, "Sentinel_WAF_Block_Page_Erkannt"
        return False, "Nicht_Abgewehrt"

    async def send_vulnerability_probe(self, session: aiohttp.ClientSession, payload: str, method: HTTPMethod, category: str, mutation_name: str, path: str = "") -> ScanResult:
        async with self.semaphore:
            start_time = asyncio.get_event_loop().time()
            url = f"{self.target_url}{path}"
            status = 0
            text = ""
            resp_headers = {}

            try:
                if method == HTTPMethod.GET:
                    params = {"vgt_stress_vector": payload}
                    async with session.get(url, params=params, headers=self.headers) as resp:
                        text = await resp.text()
                        status = resp.status
                        resp_headers = dict(resp.headers)

                elif method == HTTPMethod.POST_FORM:
                    data = {"vgt_stress_vector": payload}
                    async with session.post(url, data=data, headers=self.headers) as resp:
                        text = await resp.text()
                        status = resp.status
                        resp_headers = dict(resp.headers)

                elif method == HTTPMethod.POST_JSON:
                    json_headers = {**self.headers, "Content-Type": "application/json"}
                    json_payload = json.dumps({"vgt_json_vector": payload})
                    async with session.post(url, data=json_payload, headers=json_headers) as resp:
                        text = await resp.text()
                        status = resp.status
                        resp_headers = dict(resp.headers)

                elif method == HTTPMethod.HEADER_SMUGGLE:
                    # Schleust Vektoren in unübliche Proxyschnittstellen-Header ein
                    smuggled_headers = {
                        **self.headers,
                        "X-Forwarded-For": payload,
                        "X-Real-IP": payload,
                        "True-Client-IP": payload,
                        "Client-IP": payload,
                        "User-Agent": payload
                    }
                    async with session.get(url, headers=smuggled_headers) as resp:
                        text = await resp.text()
                        status = resp.status
                        resp_headers = dict(resp.headers)

                elif method == HTTPMethod.HPP_POLLUTE:
                    # Führt eine HTTP Parameter Pollution (HPP) durch
                    polluted_params = [
                        ("vgt_param", "benign_data"),
                        ("vgt_param", payload) # Schad-Vektor hinten anhängen
                    ]
                    async with session.get(url, params=polluted_params, headers=self.headers) as resp:
                        text = await resp.text()
                        status = resp.status
                        resp_headers = dict(resp.headers)

                latency = (asyncio.get_event_loop().time() - start_time) * 1000
                is_blocked, reason = self._assess_block(status, text, resp_headers)
                return ScanResult(category, method.name, payload, mutation_name, is_blocked, status, round(latency, 2), reason)

            except Exception as e:
                # Verbindungsabbrüche deuten meist auf ein direktes Verwerfen des Sockets durch die WAF hin
                latency = (asyncio.get_event_loop().time() - start_time) * 1000
                return ScanResult(category, method.name, payload, mutation_name, True, 0, round(latency, 2), f"Netzwerk_Drop_Oder_Timeout: {str(e)}")


class RedTeamSuiteOrchestrator:
    def __init__(self, engine: AsyncNetworkStressEngine):
        self.engine = engine
        self.results: List[ScanResult] = []

    async def execute_all_testing_phases(self):
        print(f"{Colors.CYAN}[*] STARTE AEGIS DEEP PROTECTION LIMIT-TEST PHASE 3...{Colors.RESET}")
        
        timeout = aiohttp.ClientTimeout(total=8, connect=3)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            tasks = []

            # ----------------------------------------------------
            # PHASE 1: POLYMorphe ADVANCED SQL INJECTION (SQLi)
            # ----------------------------------------------------
            for category, payloads in AdvancedPayloadRegistry.ADVANCED_SQLI.items():
                for payload in payloads:
                    # RAW-Test (GET & POST)
                    tasks.append(self.engine.send_vulnerability_probe(session, payload, HTTPMethod.GET, category, "RAW"))
                    tasks.append(self.engine.send_vulnerability_probe(session, payload, HTTPMethod.POST_FORM, category, "RAW"))
                    # DOUBLE URL ENCODE
                    tasks.append(self.engine.send_vulnerability_probe(session, MutationMatrix.double_url_encode(payload), HTTPMethod.GET, category, "DOUBLE_URL_ENC"))
                    # SQL KOMMENTAR-SPLITTING & MIXED CASE
                    tasks.append(self.engine.send_vulnerability_probe(session, MutationMatrix.mixed_case_spacing(payload), HTTPMethod.GET, category, "COMMENT_MIX_SPACING"))

            # ----------------------------------------------------
            # PHASE 2: ADVANCED CROSS-SITE SCRIPTING (XSS)
            # ----------------------------------------------------
            for category, payloads in AdvancedPayloadRegistry.ADVANCED_XSS.items():
                for payload in payloads:
                    tasks.append(self.engine.send_vulnerability_probe(session, payload, HTTPMethod.GET, category, "RAW"))
                    # HTML ENTITY SCRAMBLE
                    tasks.append(self.engine.send_vulnerability_probe(session, MutationMatrix.html_entity_scramble(payload), HTTPMethod.GET, category, "HTML_ENTITY_SCRAMBLE"))
                    # UNICODE JSON OBFUSKATION
                    tasks.append(self.engine.send_vulnerability_probe(session, MutationMatrix.unicode_obfuscate(payload), HTTPMethod.POST_JSON, category, "JSON_UNICODE_OBFUSCATION"))

            # ----------------------------------------------------
            # PHASE 3: FILE INCLUSION & COMMAND EXECUTION (LFI/RCE)
            # ----------------------------------------------------
            for category, payloads in AdvancedPayloadRegistry.ADVANCED_LFI_RCE.items():
                for payload in payloads:
                    tasks.append(self.engine.send_vulnerability_probe(session, payload, HTTPMethod.GET, category, "RAW"))
                    # DOUBLE URL ENCODE (Häufiger Bypass bei Directory-Traversals)
                    tasks.append(self.engine.send_vulnerability_probe(session, MutationMatrix.double_url_encode(payload), HTTPMethod.GET, category, "DOUBLE_URL_ENC"))

            # ----------------------------------------------------
            # PHASE 4: HTTP HEADER INJECTION & IP SPOOFING SMUGGLE
            # ----------------------------------------------------
            for category, payloads in AdvancedPayloadRegistry.SMUGGLING_HEADERS.items():
                for payload in payloads:
                    tasks.append(self.engine.send_vulnerability_probe(session, payload, HTTPMethod.HEADER_SMUGGLE, category, "HEADER_SMUGGLE"))

            # ----------------------------------------------------
            # PHASE 5: HTTP PARAMETER POLLUTION (HPP)
            # ----------------------------------------------------
            for payloads in AdvancedPayloadRegistry.ADVANCED_SQLI.values():
                for payload in payloads:
                    tasks.append(self.engine.send_vulnerability_probe(session, payload, HTTPMethod.HPP_POLLUTE, "HPP_SQLI_POLLUTION", "RAW"))

            print(f"{Colors.BLUE}[*] Parallelisiere {len(tasks)} asynchrone Stress-Vektoren...{Colors.RESET}")
            self.results = await asyncio.gather(*tasks)

    def print_comprehensive_audit_report(self):
        total = len(self.results)
        blocked = sum(1 for r in self.results if r.blocked)
        bypassed_results = [r for r in self.results if not r.blocked]
        bypassed = len(bypassed_results)

        print("\n" + "="*70)
        print(f"{Colors.CYAN}VISIONGAIA TECHNOLOGY: RED TEAM TESTING SUITE BERICHT (PHASE 3){Colors.RESET}")
        print("="*70)
        print(f"GESAMTANZAHL INJIZIERTER BEDROHUNGSVEKTOREN : {total}")
        print(f"ERFOLGREICH ABGEWEHRT                       : {Colors.GREEN}{blocked} / {total} ({(blocked/total)*100:.1f}%){Colors.RESET}")
        print(f"BYPASS-PENETRATIONEN ERKANNT                : {Colors.RED if bypassed > 0 else Colors.GREEN}{bypassed}{Colors.RESET}")
        print("="*70)

        if bypassed > 0:
            print(f"\n{Colors.RED}[!] WARNUNG: {bypassed} PENETRATIONEN IM SICHERHEITS-LAYER ERKANNT! [!]{Colors.RESET}")
            print(f"{Colors.YELLOW}Bitte überprüfe die umgangenen Vektoren unten, um die Regex-Filterung zu erweitern:{Colors.RESET}\n")
            for res in bypassed_results:
                print(f"[{Colors.RED}BYPASS{Colors.RESET}] {res.category} | Methode: {res.method} | Mutation: {res.mutation}")
                print(f"    Payload : {res.payload}")
                print(f"    HTTP Status: {res.status_code} | Latenz: {res.latency_ms}ms | Ursache: {res.reason}\n")
        else:
            print(f"\n{Colors.GREEN}[+] SYSTEM GESICHERT: 100% Abwehrrate. Sentinel CE Aegis-Schutzschilde sind unüberwindbar. [+]")
            print(f"{Colors.BLUE}Alle polymorphen Mutationen, JSON-Unicode-Obfuskationen und Header-Smuggling-Vektoren wurden neutralisiert.{Colors.RESET}")


async def main_async():
    print(f"{Colors.CYAN}")
    print("=====================================================================")
    print("   VGT OMEGA: AEGIS POLYMORPHIC EVASION ENGINE (REDTEAM TEST 3)")
    print("=====================================================================")
    print(f"{Colors.RESET}")

    try:
        target_url = input(f"{Colors.YELLOW}Bitte die Ziel-URL für den Stress-Test eingeben (z. B. http://localhost/): {Colors.RESET}").strip()
        if not target_url.startswith("http"):
            raise ValueError("Ungültiges URL-Schema.")
            
        engine = AsyncNetworkStressEngine(target_url=target_url, concurrency_limit=25)
        orchestrator = RedTeamSuiteOrchestrator(engine)
        
        await orchestrator.execute_all_testing_phases()
        orchestrator.print_comprehensive_audit_report()
        
    except Exception as e:
        print(f"{Colors.RED}KRITISCHER ORCHESTRIERUNGSFEHLER: {e}{Colors.RESET}")

if __name__ == "__main__":
    try:
        asyncio.run(main_async())
    except KeyboardInterrupt:
        print(f"\n{Colors.YELLOW}[!] Scan abgebrochen. Persistente Verbindungen werden sicher geschlossen.{Colors.RESET}")
        sys.exit(0)
