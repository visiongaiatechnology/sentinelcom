#!/usr/bin/env python3
# -*- coding: utf-8 -*-

# ==============================================================================
# VISIONGAIA TECHNOLOGY: AEGIS RED TEAM TEST 3
# PURPOSE: Automated Validation of AEGIS WAF Filters 
# STATUS: AUTHORIZED INTERNAL TESTING TOOL ONLY
# ==============================================================================
# 
# ⚠️ VISIONGAIA TECHNOLOGY: RED TEAM TESTING SUITE
# 
# WARNING: This tool contains live attack signatures (SQLi, XSS, RCE, LFI,
# JSON obfuscation). It has been developed exclusively to validate the AEGIS
# WAF component of the Vision Integrity Sentinel.
# 
# TERMS OF USE: Usage is strictly limited to local development environments
# and systems for which you possess explicit, written penetration testing
# authorization. Unauthorized scanning of third-party infrastructure is
# illegal and strictly prohibited.
# 
# DISCLAIMER: VisionGaia Technology assumes no liability for the illegal or
# unauthorized use of this code against foreign infrastructure. The user
# bears sole responsibility for compliance with applicable laws.
# 
# BOUNTY: Found a bypass that evades AEGIS? Open an issue. We will
# deconstruct it and harden the shield.
# 
# ==============================================================================

import sys
import json
import asyncio
import aiohttp
import urllib.parse
from enum import Enum
from dataclasses import dataclass, asdict
from typing import Dict, List, Tuple

class Colors:
    GREEN = '\033[92m'
    RED = '\033[91m'
    YELLOW = '\033[93m'
    CYAN = '\033[96m'
    RESET = '\033[0m'

class HTTPMethod(Enum):
    GET = "GET"
    POST_FORM = "POST_FORM"
    POST_JSON = "POST_JSON"
    POST_MULTIPART = "POST_MULTIPART"
    HEADER_INJECT = "HEADER_INJECT"

@dataclass
class ScanResult:
    category: str
    method: str
    payload: str
    mutation: str
    blocked: bool
    status_code: int
    latency_ms: float

class MutationMatrix:
    """VGT Polymorphic Mutation Engine for testing WAF normalization."""
    @staticmethod
    def raw(payload: str) -> str: 
        return payload
    
    @staticmethod
    def url_encode(payload: str) -> str: 
        return urllib.parse.quote(payload)
    
    @staticmethod
    def double_url_encode(payload: str) -> str: 
        return urllib.parse.quote(urllib.parse.quote(payload))
    
    @staticmethod
    def hex_encode(payload: str) -> str: 
        return "".join(f"%{ord(c):02x}" for c in payload)

class PayloadRegistry:
    # VGT SUPREME DIAMANT BENIGN TRIGGERS
    STANDARD_VECTORS: Dict[str, List[str]] = {
        "SQLI_TAUTOLOGY_BENIGN": [
            "' OR '1'='1",
            "1' ORDER BY 1--+",
            "' UNION SELECT NULL, NULL, NULL--"
        ],
        "SQLI_TIME_BASED_BENIGN": [
            "1 AND (SELECT * FROM (SELECT(SLEEP(1)))VGT)",
            "1'; WAITFOR DELAY '0:0:1'--"
        ],
        "XSS_NON_DESTRUCTIVE": [
            "<script>console.log('VGT_WAF_TEST')</script>",
            "\"><img src=x onerror=console.log('VGT')>",
            "javascript:prompt('VGT_TEST')"
        ],
        "LFI_STANDARD": [
            "../../../../../../../../etc/passwd",
            "....//....//....//....//etc/passwd",
            "/etc/passwd%00"
        ]
    }

    # VGT APEX: WORDPRESS SPECIFIC VECTOR PROTOCOL
    WP_APEX_VECTORS: Dict[str, List[str]] = {
        "POLYGLOT_UPLOAD_BENIGN": [
            "GIF89a\n\x00\x00\x00\x00\x00\x00\n<?php console.log('VGT_POLY_TEST'); ?>\n\x00",
            "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00\n#! /bin/bash\necho 'VGT_TEST'"
        ],
        "HEADER_RCE_BENIGN": [
            "() { :; }; echo; echo; /bin/bash -c 'console.log(\"VGT_SHELLSHOCK\")'",
            "${jndi:ldap://127.0.0.1/VGT_BENIGN_PROBE}"
        ],
        "WP_REST_API_EVASION": [
            '{"id":1, "title":"<svg/onload=console.log(\'VGT\')>"}',
            '{"author":"1\' OR \'1\'=\'1"}'
        ]
    }

class AsyncNetworkEngine:
    def __init__(self, target_url: str, concurrency_limit: int = 10):
        self.target_url = self._normalize_url(target_url)
        self.semaphore = asyncio.Semaphore(concurrency_limit)
        self.headers = {"User-Agent": "VGT-Aegis-Platin-Engine/4.0"}

    @staticmethod
    def _normalize_url(url: str) -> str:
        if not url.startswith(("http://", "https://")):
            raise ValueError("URL requires http:// or https:// schema.")
        return url if url.endswith('/') else f"{url}/"

    def _evaluate_response(self, status: int, text: str, headers: dict) -> bool:
        if status in [403, 406, 500]:
            return True
        if 'X-Aegis-Block' in headers:
            return True
        if "AEGIS" in text or "Vision Integrity Sentinel" in text:
            return True
        return False

    async def execute_request(self, session: aiohttp.ClientSession, payload: str, method: HTTPMethod, category: str, mutation_name: str, target_endpoint: str = "") -> ScanResult:
        async with self.semaphore:
            start_time = asyncio.get_event_loop().time()
            url = f"{self.target_url}{target_endpoint}"
            try:
                status = 0
                text = ""
                resp_headers = {}

                if method == HTTPMethod.GET:
                    params = {"vgt_param": payload}
                    async with session.get(url, params=params) as resp:
                        text = await resp.text()
                        status = resp.status
                        resp_headers = resp.headers

                elif method == HTTPMethod.POST_FORM:
                    data = {"vgt_param": payload}
                    async with session.post(url, data=data) as resp:
                        text = await resp.text()
                        status = resp.status
                        resp_headers = resp.headers

                elif method == HTTPMethod.POST_JSON:
                    json_headers = {**self.headers, "Content-Type": "application/json"}
                    async with session.post(url, data=payload, headers=json_headers) as resp:
                        text = await resp.text()
                        status = resp.status
                        resp_headers = resp.headers

                elif method == HTTPMethod.POST_MULTIPART:
                    data = aiohttp.FormData()
                    # Simuliere einen bösartigen Datei-Upload (z.B. Profilbild, Plugin-Upload)
                    data.add_field('file',
                                   payload.encode('utf-8'),
                                   filename='vgt_benign_polyglot.jpg',
                                   content_type='image/jpeg')
                    async with session.post(url, data=data) as resp:
                        text = await resp.text()
                        status = resp.status
                        resp_headers = resp.headers

                elif method == HTTPMethod.HEADER_INJECT:
                    # Injiziere Payload in kritische Edge-Routing Header
                    malicious_headers = {
                        **self.headers,
                        "User-Agent": payload,
                        "X-Forwarded-For": payload,
                        "Referer": payload,
                        "True-Client-IP": payload
                    }
                    async with session.get(url, headers=malicious_headers) as resp:
                        text = await resp.text()
                        status = resp.status
                        resp_headers = resp.headers

                latency = (asyncio.get_event_loop().time() - start_time) * 1000
                is_blocked = self._evaluate_response(status, text, resp_headers)
                return ScanResult(category, method.name, payload, mutation_name, is_blocked, status, round(latency, 2))

            except Exception as e:
                return ScanResult(category, method.name, payload, mutation_name, False, 0, 0.0)

class AegisOrchestrator:
    def __init__(self, engine: AsyncNetworkEngine):
        self.engine = engine
        self.results: List[ScanResult] = []

    async def run_suite(self):
        print(f"{Colors.CYAN}[*] INITIATING DIAMANT ASYNC POLYMORPHIC INJECTION...{Colors.RESET}")
        
        timeout = aiohttp.ClientTimeout(total=10)
        
        mutations = {
            "RAW": MutationMatrix.raw,
            "URL_ENC": MutationMatrix.url_encode,
            "DBL_URL_ENC": MutationMatrix.double_url_encode,
            "HEX_ENC": MutationMatrix.hex_encode
        }

        async with aiohttp.ClientSession(headers=self.engine.headers, timeout=timeout) as session:
            tasks = []
            
            # PHASE 1: STANDARD VECTORS
            for category, payloads in PayloadRegistry.STANDARD_VECTORS.items():
                for raw_payload in payloads:
                    for mut_name, mut_func in mutations.items():
                        mutated_payload = mut_func(raw_payload)
                        tasks.append(self.engine.execute_request(session, mutated_payload, HTTPMethod.GET, category, mut_name))
                        tasks.append(self.engine.execute_request(session, mutated_payload, HTTPMethod.POST_FORM, category, mut_name))

            # PHASE 2: WP APEX VECTORS
            print(f"{Colors.CYAN}[*] INITIATING WP-APEX SUBSYSTEM (MULTIPART / HEADERS / REST-API)...{Colors.RESET}")
            
            # 2.1 Multipart Uploads
            for payload in PayloadRegistry.WP_APEX_VECTORS["POLYGLOT_UPLOAD_BENIGN"]:
                tasks.append(self.engine.execute_request(session, payload, HTTPMethod.POST_MULTIPART, "POLYGLOT_UPLOAD_BENIGN", "RAW", "wp-admin/async-upload.php"))

            # 2.2 Header Injection
            for payload in PayloadRegistry.WP_APEX_VECTORS["HEADER_RCE_BENIGN"]:
                tasks.append(self.engine.execute_request(session, payload, HTTPMethod.HEADER_INJECT, "HEADER_RCE_BENIGN", "RAW"))

            # 2.3 WP REST API Exploitation
            for payload in PayloadRegistry.WP_APEX_VECTORS["WP_REST_API_EVASION"]:
                # Testen des JSON-Endpunkts, der in der WAF Ghost-POST-Immunität besitzt
                tasks.append(self.engine.execute_request(session, payload, HTTPMethod.POST_JSON, "WP_REST_API_EVASION", "RAW", "wp-json/wp/v2/users/1"))

            self.results = await asyncio.gather(*tasks)

    def generate_report(self):
        total = len(self.results)
        blocked = sum(1 for r in self.results if r.blocked)
        bypassed = total - blocked

        print(f"\n{Colors.CYAN}VGT OMEGA: ASYNC METRICS{Colors.RESET}")
        print("="*50)
        print(f"TOTAL VECTORS : {total}")
        print(f"NEUTRALIZED   : {Colors.GREEN}{blocked}{Colors.RESET}")
        print(f"PENETRATED    : {Colors.RED if bypassed > 0 else Colors.GREEN}{bypassed}{Colors.RESET}")
        print("="*50)

        for res in self.results:
            if not res.blocked:
                print(f"[{Colors.RED}BYPASS{Colors.RESET}] HTTP {res.status_code} | {res.method} | {res.mutation} | {res.category} | {res.latency_ms}ms")
            else:
                pass # Optional: Print successful blocks. Auskommentiert für SNR (Signal-to-Noise Ratio).

async def main_async():
    try:
        target_url = input(f"{Colors.YELLOW}TARGET URL: {Colors.RESET}").strip()
        engine = AsyncNetworkEngine(target_url=target_url, concurrency_limit=20)
        orchestrator = AegisOrchestrator(engine)
        
        await orchestrator.run_suite()
        orchestrator.generate_report()
    except Exception as e:
        print(f"{Colors.RED}FATAL: {e}{Colors.RESET}")

if __name__ == "__main__":
    try:
        # VGT SUPREME: Nutzung der nativen, modernen Event-Loop ohne Legacy-Policies.
        asyncio.run(main_async())
    except KeyboardInterrupt:
        print(f"\n{Colors.YELLOW}[!] EXECUTION HALTED BY OPERATOR. ORPHANED CONNECTIONS TERMINATED.{Colors.RESET}")
        sys.exit(0)
