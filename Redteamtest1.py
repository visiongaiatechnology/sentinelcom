#!/usr/bin/env python3
# -*- coding: utf-8 -*-

# ==============================================================================
# VISIONGAIA TECHNOLOGY: AEGIS RED TEAM STRESS TESTER
# PURPOSE: Automated Validation of AEGIS WAF Filters (SQLi, XSS, LFI, RCE)
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
import urllib.parse

# --- FARBEN FÜR DIE KONSOLENAUSGABE ---
class Colors:
    GREEN = '\033[92m'
    RED = '\033[91m'
    YELLOW = '\033[93m'
    CYAN = '\033[96m'
    RESET = '\033[0m'

# --- PAYLOAD-DATENBANK (BEKANNTE SIGNATUREN ZUM TESTEN VON AEGIS) ---
# Diese Strings sollten von class-vis-aegis.php abgefangen werden.
PAYLOADS = {
    "SQL_INJECTION": [
        "' OR '1'='1",
        "1; DROP TABLE wp_users",
        "' UNION SELECT null, version() --",
        "admin' --",
        "1' ORDER BY 1--+"
    ],
    "CROSS_SITE_SCRIPTING": [
        "<script>alert('VGT_XSS')</script>",
        "\"><svg/onload=alert(1)>",
        "javascript:alert(document.cookie)",
        "<img src=x onerror=alert('XSS')>",
        "'\"><iframe src=javascript:alert(1)>"
    ],
    "LOCAL_FILE_INCLUSION": [
        "../../../etc/passwd",
        "..\\..\\..\\windows\\win.ini",
        "php://filter/convert.base64-encode/resource=index.php",
        "/etc/passwd%00",
        "../../../../../wp-config.php"
    ],
    "COMMAND_INJECTION": [
        "; cat /etc/passwd",
        "| whoami",
        "`id`",
        "$(whoami)",
        "&& net user"
    ]
}

def print_banner():
    """Zeigt das VGT Omega Protokoll Banner an."""
    print(f"{Colors.CYAN}")
    print("="*60)
    print(" VGT OMEGA: AEGIS RED TEAM STRESS TESTER v1.0")
    print(" Warnung: Nur auf autorisierten lokalen/Staging-Systemen nutzen!")
    print("="*60)
    print(f"{Colors.RESET}")

def test_payload(url, category, payload, method="GET"):
    """
    Sendet den Payload an die Ziel-URL und bewertet die WAF-Reaktion.
    Ein 403 Forbidden oder ein WAF-Block-Screen ist ein ERFOLG (AEGIS greift).
    Ein 200 OK ist ein FEHLSCHLAG (AEGIS wurde umgangen).
    """
    headers = {
        "User-Agent": "VGT-RedTeam-Tester/1.0"
    }
    
    # Payload-Injektion in einen Dummy-Parameter
    params = {"vgt_test_param": payload}
    
    try:
        if method == "GET":
            response = requests.get(url, params=params, headers=headers, timeout=5)
        else:
            response = requests.post(url, data=params, headers=headers, timeout=5)
            
        status = response.status_code
        
        # AEGIS sollte idealerweise mit einem 403 antworten oder die Ausführung stoppen (wp_die).
        if status in [403, 406, 500]:
            print(f"[{Colors.GREEN}BLOCKIERT{Colors.RESET}] {category} -> AEGIS hat den Angriff abgewehrt. (HTTP {status})")
            return True
        elif "AEGIS" in response.text or "Vision Integrity Sentinel" in response.text:
            print(f"[{Colors.GREEN}BLOCKIERT{Colors.RESET}] {category} -> AEGIS Block-Screen erkannt. (HTTP {status})")
            return True
        else:
            print(f"[{Colors.RED}BYPASS WARNUNG{Colors.RESET}] {category} -> Payload ignoriert! (HTTP {status}) | Payload: {payload}")
            return False
            
    except requests.exceptions.RequestException as e:
        print(f"[{Colors.YELLOW}FEHLER{Colors.RESET}] Verbindung fehlgeschlagen: {e}")
        return False

def main():
    print_banner()
    
    # ZIEL-URL HIER EINTRAGEN (Beispiel: http://localhost/wordpress/)
    target_url = input(f"{Colors.YELLOW}Bitte Ziel-URL eingeben (z.B. http://localhost/): {Colors.RESET}").strip()
    
    if not target_url.startswith("http"):
        print(f"{Colors.RED}Ungültige URL. Muss mit http:// oder https:// beginnen.{Colors.RESET}")
        return

    print(f"\n{Colors.CYAN}[*] Starte AEGIS Validierungs-Sequenz...{Colors.RESET}\n")
    
    total_tests = 0
    blocked = 0
    bypassed = 0

    # Iteriere durch alle Kategorien und Payloads
    for category, payload_list in PAYLOADS.items():
        print(f"-"*40)
        print(f"TESTE KATEGORIE: {category}")
        print(f"-"*40)
        
        for payload in payload_list:
            total_tests += 1
            # Teste als GET-Parameter
            success_get = test_payload(target_url, f"{category} (GET)", payload, "GET")
            
            # Kurze Verzögerung, um Server-Überlastung/Rate Limiting zu vermeiden
            time.sleep(0.5)
            
            # Teste als POST-Parameter
            success_post = test_payload(target_url, f"{category} (POST)", payload, "POST")
            
            if success_get and success_post:
                blocked += 1
            else:
                bypassed += 1
            
            time.sleep(0.5)

    # --- BERICHT ---
    print("\n" + "="*60)
    print(f"{Colors.CYAN}VGT OMEGA: TESTBERICHT{Colors.RESET}")
    print("="*60)
    print(f"Gesamtanzahl Tests: {total_tests * 2} (GET & POST)")
    print(f"Erfolgreich abgewehrt (AEGIS aktiv): {Colors.GREEN}{blocked * 2}{Colors.RESET}")
    print(f"Bypass (AEGIS Blindspot): {Colors.RED if bypassed > 0 else Colors.GREEN}{bypassed}{Colors.RESET}")
    
    if bypassed > 0:
        print(f"\n{Colors.RED}KRITISCHE DIREKTIVE:{Colors.RESET} Überprüfe class-vis-aegis.php. Die Regex-Filter greifen bei einigen Payloads nicht. Erweitere die Signatur-Datenbank.")
    else:
        print(f"\n{Colors.GREEN}SYSTEMSTATUS:{Colors.RESET} AEGIS operiert auf PLATIN-Status. Alle Standard-Vektoren wurden vernichtet.")

if __name__ == "__main__":
    main()
