#!/usr/bin/env python3
# -*- coding: utf-8 -*-

# ==============================================================================
# VISIONGAIA TECHNOLOGY: AEGIS RED TEAM JSON TESTER
# PURPOSE: Automated Validation of AEGIS WAF Filters JSON Bypass
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

import requests
import time
import json

class Colors:
    GREEN = '\033[92m'
    RED = '\033[91m'
    YELLOW = '\033[93m'
    CYAN = '\033[96m'
    RESET = '\033[0m'

# --- JSON OBFUSCATION PAYLOADS ---
# Diese Strings simulieren Angreifer, die WAFs durch Unicode-Encoding umgehen wollen.
# Das AEGIS Diamant-Upgrade MUSS diese entschlüsseln und blockieren.
JSON_PAYLOADS = {
    "JSON_XSS_UNICODE": [
        # \u003c = < | \u003e = >
        '{"input": "\\u003cscript\\u003ealert(\'VGT\')\\u003c/script\\u003e"}',
        '{"data": {"nested": "\\u003cimg src=x onerror=alert(1)\\u003e"}}'
    ],
    "JSON_SQLI_OBFUSCATED": [
        # \u004f\u0052\u0044\u0045\u0052 \u0042\u0059 = ORDER BY
        '{"query": "1\' \\u004f\\u0052\\u0044\\u0045\\u0052 \\u0042\\u0059 1--+"}',
        '{"user": "admin\' \\u002d\\u002d"}' # \u002d\u002d = --
    ],
    "JSON_LFI_TRAVERSAL": [
        # \u002e\u002e\u002f = ../
        '{"file": "\\u002e\\u002e\\u002f\\u002e\\u002e\\u002fetc/passwd"}',
        '{"path": {"deep": "\\u002e\\u002e\\\\\\u002e\\u002e\\\\windows\\\\win.ini"}}'
    ],
    "JSON_MALFORMED_EVASION": [
        # Absichtlich kaputtes JSON, das WP reparieren könnte, um die WAF abzustürzen
        # Hier muss der raw_fallback Scanner von AEGIS greifen.
        '{"cmd": "\\u003cscript\\u003ealert(1)\\u003c/script\\u003e", "broken_key": }'
    ]
}

def print_banner():
    print(f"{Colors.CYAN}")
    print("="*65)
    print(" VGT OMEGA: AEGIS RED TEAM STRESS TESTER v2.0 (JSON DPI)")
    print(" Warnung: Nur auf autorisierten lokalen/Staging-Systemen nutzen!")
    print("="*65)
    print(f"{Colors.RESET}")

def test_json_payload(url, category, raw_json_string):
    """
    Sendet den Payload hartcodiert als application/json.
    """
    headers = {
        "User-Agent": "VGT-RedTeam-Tester/2.0",
        "Content-Type": "application/json"
    }
    
    try:
        # Wir senden absichtlich den rohen String, damit requests nicht 
        # unsere manuellen \u Escapes durch echtes utf-8 ersetzt.
        response = requests.post(url, data=raw_json_string.encode('utf-8'), headers=headers, timeout=5)
        status = response.status_code
        
        # Prüfung auf VGT Block-Reaktion (HTTP 403 oder JSON-Block-Response)
        if status == 403 or 'X-Aegis-Block' in response.headers or 'VISIONGAIATECHNOLOGY AEGIS PROTOCOL' in response.text:
            print(f"[{Colors.GREEN}BLOCKIERT{Colors.RESET}] {category} -> AEGIS DPI hat die Maskierung zerstört. (HTTP {status})")
            return True
        else:
            print(f"[{Colors.RED}BYPASS WARNUNG{Colors.RESET}] {category} -> Payload durchgedrungen! (HTTP {status})")
            print(f"   {Colors.YELLOW}Gesendeter Payload: {raw_json_string}{Colors.RESET}")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"[{Colors.YELLOW}FEHLER{Colors.RESET}] Verbindung fehlgeschlagen: {e}")
        return False

def main():
    print_banner()
    
    target_url = input(f"{Colors.YELLOW}Bitte Ziel-URL eingeben (z.B. http://localhost/): {Colors.RESET}").strip()
    
    if not target_url.startswith("http"):
        print(f"{Colors.RED}Ungültige URL. Muss mit http:// oder https:// beginnen.{Colors.RESET}")
        return

    print(f"\n{Colors.CYAN}[*] Starte AEGIS JSON DPI Validierungs-Sequenz...{Colors.RESET}\n")
    
    total_tests = 0
    blocked = 0

    for category, payloads in JSON_PAYLOADS.items():
        print(f"-"*50)
        print(f"TESTE KATEGORIE: {category}")
        print(f"-"*50)
        
        for payload in payloads:
            total_tests += 1
            if test_json_payload(target_url, category, payload):
                blocked += 1
            time.sleep(0.5)

    bypassed = total_tests - blocked

    print("\n" + "="*65)
    print(f"{Colors.CYAN}VGT OMEGA: JSON DPI TESTBERICHT{Colors.RESET}")
    print("="*65)
    print(f"Gesamtanzahl Tests: {total_tests}")
    print(f"Erfolgreich abgewehrt (DPI aktiv): {Colors.GREEN}{blocked}{Colors.RESET}")
    print(f"Bypass (DPI Blindspot): {Colors.RED if bypassed > 0 else Colors.GREEN}{bypassed}{Colors.RESET}")
    
    if bypassed == 0:
        print(f"\n{Colors.GREEN}SYSTEMSTATUS:{Colors.RESET} PLATINUM/DIAMANT LEVEL BESTÄTIGT. AEGIS dekonstruiert Unicode-Obfuscation deterministisch.")

if __name__ == "__main__":
    main()
