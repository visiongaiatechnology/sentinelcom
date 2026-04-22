# ⚔️ VGT Sentinel — Community Edition (Silber Status)

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.5.0-brightgreen?style=for-the-badge)](#)
[![Platform](https://img.shields.io/badge/Platform-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![Architecture](https://img.shields.io/badge/Architecture-Zero--Trust_WAF-red?style=for-the-badge)](#)
[![Engine](https://img.shields.io/badge/Engine-Deterministic_DFA-orange?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-STABLE-brightgreen?style=for-the-badge)](#)
[![WP Marketplace](https://img.shields.io/badge/WordPress-Marketplace_Ready-21759B?style=for-the-badge&logo=wordpress)](#)
[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

> *"No external libraries. No blind trust. No compromise."*
> *AGPLv3 — Open Source Core. Built for humans, not for SaaS margins.*

---

## ⚠️ DISCLAIMER: EXPERIMENTAL R&D PROJECT

This project is a **Proof of Concept (PoC)** and part of ongoing research and development at VisionGaia Technology. It is **not** a certified or production-ready product.

**Use at your own risk.** The software may contain security vulnerabilities, bugs, or unexpected behavior. It may break your environment if misconfigured or used improperly.

**Do not deploy in critical production environments** unless you have thoroughly audited the code and understand the implications. For enterprise-grade, verified protection, we recommend established and officially certified solutions.

Found a vulnerability or have an improvement? **Open an issue or contact us.**

---

## 📋 Changelog — V1.5.0

- **NEW MODULE:** VGT Shield — SHA-256 Proof-of-Work Anti-Bot Engine (Zero-UI, Zero-Cloud, DSGVO-compliant)
- **Security:** All database outputs now fully escaped — comprehensive output escaping across the entire codebase
- **WordPress Marketplace Compliance:** All files reviewed and adapted to meet official WordPress plugin guidelines
- **MU-Deployer:** Removed one-click deployment — the plugin now generates a script that must be uploaded manually via FTP (WordPress policy requirement)
- **Red Team Validation:** 3 Python-based red team test scripts added to the repository for community validation of Sentinel CE
- **Ongoing:** New regex filter signatures added to AEGIS
- **Ongoing:** Internal red team evaluation sessions to continuously improve Community Edition coverage

---

## 🔍 What is VGT Sentinel?

VGT Sentinel Community Edition is a **modular, zero-dependency WordPress security framework** engineered to neutralize deterministic attack vectors without sacrificing performance.

It is the open-source core of the VGT Sentinel suite — a battle-hardened, multi-layered defense system built on a **Zero-Trust architecture**. Every request is inspected, every header hardened, every upload analyzed, every file hashed, and every bot challenged.

<img width="1749" height="906" alt="{5F2676BC-C375-4830-A497-B98D228ED23E}" src="https://github.com/user-attachments/assets/468784d3-4022-4fed-b563-9165f2bc4001" />

```
Traditional WordPress Security:
→ Single plugin = single point of failure
→ Shared hosting overhead
→ No outbound control
→ No filesystem integrity monitoring

VGT Sentinel ZTNA Security Stack:
→ Stream-based WAF (AEGIS)             — SQLi, XSS, RCE, LFI neutralized
→ Kernel Hardening (TITAN)             — Server fingerprint masked
→ Stealth Engine (HADES)               — WordPress architecture obfuscated
→ Access Guard (CERBERUS)              — IP-validated brute-force prevention
→ Outbound Control (STYX LITE)         — Data exfiltration blocked
→ Payload Sanitizer (AIRLOCK)          — Binary upload inspection
→ Integrity Monitor (CHRONOS)          — SHA-256 filesystem diff-hashing
→ Anti-Bot Engine (VGT SHIELD)         — Zero-UI PoW bot defense (NEW)
```

---

## 🏛️ Architecture

```
Incoming HTTP Request
        ↓
CERBERUS (Pre-Auth IP Validation)
→ Cloudflare CIDR verification
→ X-Forwarded-For spoofing prevention
→ Brute-force state via RAM/Object Cache
→ Hook Priority 1 — fires before WP user logic
        ↓
AEGIS WAF (Stream Inspection)
→ php://input scanned in 4KB binary chunks
→ Overlap-buffer for boundary-spanning patterns
→ 512KB scan limit (Memory Exhaustion prevention)
→ Tarpit: Socket-Drop + Connection: Close on critical hit
        ↓
TITAN (Kernel Hardening)
→ Security headers injected
→ X-Powered-By camouflage (Laravel / Drupal / Django)
→ XML-RPC blocked, REST API locked to auth sessions
→ .env / wp-config.php / .git access denied at .htaccess level
        ↓
HADES (Stealth Engine)
→ URL rewrites mask WordPress directory structure
→ Custom slugs for wp-admin and wp-login.php
        ↓
VGT SHIELD (Anti-Bot / PoW Engine)  ← NEW IN V1.5.0
→ SHA-256 cryptographic challenge issued by PHP server
→ Web Worker mines proof-of-work in isolated browser thread
→ X-VGT-Shield-PoW header injected into form submissions
→ Server validates hash in <10ms — replay protection (TTL 1800s)
        ↓
AIRLOCK (Upload Inspection)
→ Magic Byte analysis on 4KB header/footer chunks
→ PHP wrapper, Base64 and exec-pattern detection
→ Polyglot file prevention
        ↓
CHRONOS (Async Integrity Monitor)
→ SHA-256 against integrity_matrix.php baseline
→ mtime + size pre-filter before hash computation
→ Ghost Trap honeypot triggers IP blacklisting on access
→ Cron-sliced execution (max 20s) — PHP timeout safe
        ↓
STYX LITE (Outbound Control)
→ Telemetry Kill Switch for api.wordpress.org
→ Supply-chain exfiltration blocked
```

---

## 🧩 Module Matrix

### ⚡ 2.1 AEGIS — Web Application Firewall

<img width="1747" height="908" alt="{71C52BDB-CA2F-4A57-9919-18D402E53F60}" src="https://github.com/user-attachments/assets/ba2da2ab-b835-44d4-899d-9401818d701b" />

Stream-based WAF for real-time payload inspection.

| Parameter | Value |
|---|---|
| **Engine** | Deterministic Regex Pattern Matching |
| **Scan Limit** | 512 KB (Memory Exhaustion prevention) |
| **Read Strategy** | `php://input` binary stream in 4KB chunks with overlap buffer |
| **Protected Vectors** | SQLi, XSS, RCE, LFI, Malicious User Agents |
| **Threat Response** | Immediate socket-drop (`Connection: Close`) before header send |

---

### 🔩 2.2 TITAN — Kernel Hardening

Application-layer hardening and server signature masking.

<img width="1750" height="905" alt="{93DD0E21-02EB-4C5E-BC91-6DE083326321}" src="https://github.com/user-attachments/assets/047a7845-bc9b-4892-90f5-1847e86d1f71" />

```
Headers Enforced:
→ X-XSS-Protection
→ X-Frame-Options: SAMEORIGIN
→ X-Content-Type-Options: nosniff
→ Referrer-Policy
→ Permissions-Policy

Camouflage Engine:
→ X-Powered-By spoofed to: Laravel | Drupal | Django

API Lockdown:
→ XML-RPC:     BLOCKED (full)
→ REST API:    Auth-only sessions
→ RSS/Atom:    DISABLED

Protected Paths (.htaccess):
→ .env  |  .git  |  wp-config.php  |  composer.json  |  Vault directories
```

---

### 👻 2.3 HADES — Stealth Engine

Architecture obfuscation to prevent automated WordPress fingerprinting.

<img width="1748" height="910" alt="{612F5CF2-053A-4A04-8153-F23CBC83E0D8}" src="https://github.com/user-attachments/assets/9fcec577-5213-4734-9933-c53ad008de8a" />

**URL Rewrite Map:**

| Original Path | Masked Path |
|---|---|
| `wp-content/themes` | `content/ui` |
| `wp-content/plugins` | `content/lib` |
| `wp-content/uploads` | `storage` |
| `wp-includes` | `core` |
| `wp-admin` | *(Custom Slug)* |
| `wp-login.php` | *(Custom Slug)* |

**Webserver Support:** Apache (auto via `.htaccess`) · Nginx (static rule injection) · LiteSpeed

---

### 🐕 2.4 CERBERUS — Access Guard

Pre-authentication IP validation and brute-force defense.

<img width="1753" height="909" alt="{87791C5E-509B-49DB-9AF6-63A6148C5214}" src="https://github.com/user-attachments/assets/3c0e0556-51d0-4ad5-bea2-0d0c85d6fb14" />

| Feature | Detail |
|---|---|
| **True-IP Detection** | Native Cloudflare CIDR validation — prevents X-Forwarded-For spoofing |
| **Fail-State Tracking** | RAM/Object Cache via WordPress Transients |
| **Hook Priority** | `1` on `authenticate` — fires before any WP user logic loads |

---

### 🌑 2.5 STYX LITE — Outbound Control

Network-layer control against data exfiltration and supply-chain attacks.

<img width="1751" height="908" alt="{03D0FA24-4E7B-47B9-8CD9-5A38C9D9F66F}" src="https://github.com/user-attachments/assets/22acc9aa-beef-4895-91ed-a12d32fed1da" />

```
Telemetry Kill Switch — Blocked Domains:
→ api.wordpress.org
→ downloads.wordpress.org
→ s.w.org

Supply-Chain Protection:
→ Blocks unintended external communication from compromised plugins
```

---

### 🔒 2.6 AIRLOCK — Payload Sanitizer

Binary-level analysis of all file uploads (`multipart/form-data`).

<img width="1750" height="912" alt="{F202F832-6642-4595-8F6B-DD5EA5F54B4D}" src="https://github.com/user-attachments/assets/96b4cacf-726d-45fd-9027-5aed572369e3" />

| Feature | Detail |
|---|---|
| **File Policy** | Strict allowlist — only pre-approved safe formats |
| **Large File Strategy** | Memory-safe chunked read — 4KB header/footer scan for files >2MB |
| **Magic Byte Inspection** | Detects real file type regardless of extension |
| **Polyglot Prevention** | Blocks PHP wrappers, Base64 obfuscation, exec-patterns in image/document payloads |

---

### 🕰️ 2.7 CHRONOS — System Integrity & Ghost Trap

Asynchronous filesystem integrity monitoring with honeypot tripwire.

```
Differential Hashing:
→ SHA-256 verified against integrity_matrix.php (PHP-formatted — prevents web exposure)
→ mtime + size pre-filter: hash only runs when metadata changes

Ghost Trap:
→ Honeypot file: wp-admin-backup-restore.php
→ HTTP access = immediate IP blacklisting

Execution Safety:
→ Async State Machine — max 20s Cron-Slice
→ No PHP timeout risk on large installations
```

---

### 🤖 2.8 VGT SHIELD — Anti-Bot / Proof-of-Work Engine *(NEW — V1.5.0)*

A high-performance, DSGVO-compliant reCAPTCHA alternative for WordPress. Eliminates bot interactions through a server-validated Proof-of-Work engine that operates entirely without user interaction and without external data transfers (Zero-Cloud).

No checkbox. No "I'm not a robot". No Google requests. No cookies. Instead: invisible, mathematical bot defense directly in the browser.

| Feature | Description |
|---|---|
| **Zero-UI Bot Defense** | End users see no captchas or checkboxes — security operates invisibly in the background |
| **SHA-256 PoW Engine** | Cryptographic challenge-response via bitwise hashing, isolated in a Web Worker |
| **100% DSGVO-compliant** | No third-party requests, no cookies, no tracking |
| **Zero-Cloud** | Fully server-side — no external APIs, no CDNs |
| **Replay Protection** | Every hash is valid exactly once — TTL: 1800 seconds |
| **Deep Plugin Scanner** | AST-regex parsing detects installed form plugins and integrates them automatically |
| **Network Layer Hijacking** | Automatic interception of network requests for PoW header injection |
| **<10ms Server Validation** | Minimal latency on server-side hash verification |
| **Dark/Light Mode** | Neural Aesthetics admin dashboard with full theme support |

**How it works:**

```
Client (Browser)
       │
       ▼
GET /wp-json/vgt-shield/v1/challenge
→ PHP server issues cryptographic challenge
       │
       ▼
Web Worker (Isolated Thread)
→ SHA-256 Bitwise Hashing
→ Mines proof-of-work solution (no UI blocking)
       │
       ▼
Form Submission / AJAX Request
→ X-VGT-Shield-PoW header injected
→ Server validates hash (<10ms)
→ Hash marked as used (replay protection)
       │
       ▼
✅ Legitimate user → form processed
❌ Bot (no valid PoW) → request blocked
```

**Native integrations:** WooCommerce · Contact Form 7 · WPForms · Gravity Forms · WordPress Core Comments

---

## 🔴 Red Team Validation — Community Testing Scripts

The repository includes **3 Python-based red team test scripts** for independent validation of Sentinel CE. These tools allow the community to verify that each module is functioning as expected against their own installations.

> ⚠️ **Only use against your own servers.** Running these scripts against third-party systems without explicit authorization is illegal.

| Script | Module Tested | Technique |
|---|---|---|
| `redteam_aegis.py` | AEGIS WAF | SQLi, XSS, RCE, LFI payload injection — validates block rate and response behavior |
| `redteam_cerberus.py` | CERBERUS | Brute-force simulation with IP rotation — validates fail-state tracking and lockout |
| `redteam_shield.py` | VGT SHIELD | Bot simulation without PoW — validates challenge-response enforcement |

Run them from an isolated environment after activating Sentinel CE on a test WordPress installation. Each script outputs a structured report with block rate, response codes, and latency metrics.

---

## ⚙️ Performance Design

> **Zero performance tax. Maximum coverage.**

| Optimization | Mechanism |
|---|---|
| **Fast-Path Routing** | Static assets bypass WAF inspection entirely — saves >90% CPU cycles |
| **Stream Chunking** | Payload inspection via chunked reads — low, stable RAM footprint |
| **Async Scheduling** | CHRONOS runs in time-sliced cron — never blocks request handling |
| **Web Worker Isolation** | VGT SHIELD PoW mining runs in isolated thread — zero UI blocking |
| **Zero Dependencies** | No external libraries — no supply chain risk, no overhead |

---

## 🔌 Ecosystem Compatibility

| Component | Detail |
|---|---|
| **PHP** | 7.4+ (Recommended: 8.1+) |
| **Webserver** | Apache (auto), Nginx (manual rule injection), LiteSpeed |
| **Page Builders** | Bridge Manager auto-disables conflicting DOM/header interventions for Elementor, Divi, Oxygen |
| **VGT Ecosystem** | Native VisionLegalPro support via Shadow-Net Asset Routing |
| **VGT Myrmidon** | AEGIS Co-op Mode — whitelists Myrmidon ZTNA API endpoints automatically |
| **WordPress Marketplace** | Fully compliant with WordPress plugin guidelines as of V1.5.0 |

---

## ⚠️ WordPress Marketplace Compliance (V1.5.0)

As of V1.5.0, the entire codebase has been reviewed and adapted to meet official WordPress plugin directory guidelines:

- **Output escaping:** All database outputs and dynamic values are now fully escaped using WordPress core functions (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`) throughout the entire plugin
- **MU-Deployer:** One-click MU plugin deployment has been removed per WordPress policy. The plugin now generates a ready-to-use script that must be uploaded manually to `wp-content/mu-plugins/` via FTP or SFTP
- **Code Standards:** All files reviewed for compliance with WordPress Coding Standards

---

## ⚠️ System Boundaries — Silber vs. Platin

> **DISCLAIMER:** The Community Edition (Silber Status) operates on a deterministic rule engine. It provides a robust shield against standardized, automated botnets, scrapers, and known attack vectors.

The following capabilities are **exclusive to VGT Sentinel Pro / Platin Status:**

| Capability | Silber | Platin |
|---|---|---|
| **ORACLE AI** — Polymorphic Zero-Day Detection | ❌ | ✅ |
| **PROMETHEUS** — Dynamic Behavioral Profiling | ❌ | ✅ |
| **NEMESIS** — Deception-Engine | ❌ | ✅ |
| **ZEUS** — Pre-Boot WAF via `auto_prepend_file` | ❌ | ✅ |
| **MORPHEUS** — Hypervisor for Plugins | ❌ | ✅ |
| **GORGON** — Global Swarm Intelligence Threat Feed | ❌ | ✅ |
| **API CRYPTO VAULT** — AES-256-GCM Database Payload Encryption | ❌ | ✅ |
| Deterministic WAF (AEGIS Lite) | ✅ | ✅ |
| Kernel Hardening (TITAN Lite) | ✅ | ✅ |
| Stealth Engine (HADES Lite) | ✅ | ✅ |
| Access Guard (CERBERUS) | ✅ | ✅ |
| Outbound Control (STYX LITE) | ✅ | ✅ |
| Payload Sanitizer (AIRLOCK Lite) | ✅ | ✅ |
| Integrity Monitor (CHRONOS) | ✅ | ✅ |
| Anti-Bot PoW Engine (VGT SHIELD) | ✅ | ✅ |

---

## 🚀 Installation

```bash
# 1. Clone into WordPress plugins directory
cd /var/www/html/wp-content/plugins/
git clone https://github.com/visiongaiatechnology/sentinelcom

# 2. Activate in WordPress Admin
# Plugins → VGT Sentinel Community Edition → Activate

# 3. HADES: Configure custom login slug
# Settings → Sentinel → Stealth Engine

# 4. CHRONOS: Generate initial integrity manifest
# Settings → Sentinel → Integrity Monitor → Generate Baseline

# 5. VGT SHIELD: Activate Anti-Bot PoW
# Settings → Sentinel → Shield → Enable
```

> **MU-Deployer Note:** One-click MU deployment is no longer available (WordPress policy). After activating Sentinel, navigate to **Settings → Sentinel → MU-Deployer** to generate the deployment script, then upload it manually to `wp-content/mu-plugins/` via FTP.

On first activation, Sentinel automatically:

```
→ Injects AEGIS WAF into the request lifecycle
→ Applies TITAN security headers
→ Activates HADES URL rewrites (.htaccess / Nginx rules)
→ Initializes CERBERUS fail-state cache
→ Generates CHRONOS integrity_matrix.php baseline
→ Deploys Ghost Trap honeypot
→ Activates STYX outbound kill switch
→ Registers VGT SHIELD challenge endpoint (/wp-json/vgt-shield/v1/challenge)
```

<img width="1749" height="906" alt="{9D2A7C57-9EC6-4183-9E36-04120AA9419A}" src="https://github.com/user-attachments/assets/1199d85a-c9f6-40ad-b596-12dea0e77964" />

<img width="1750" height="911" alt="{9A9F9703-E90B-4591-A717-C5D406B6FEAA}" src="https://github.com/user-attachments/assets/0d0f7459-7a50-49ba-8ecc-2c4acd803fcd" />

<img width="1749" height="904" alt="{7C042814-E8E4-484D-A698-5CE6C5E90889}" src="https://github.com/user-attachments/assets/8bc0d18f-be99-414f-9935-22cef04d2964" />

---

## 🔗 VGT Ecosystem

| Tool | Type | Purpose |
|---|---|---|
| ⚔️ **VGT Sentinel** | **WAF / IDS Framework** | Zero-Trust WordPress security suite — you are here |
| 🛡️ **[VGT Myrmidon](https://github.com/visiongaiatechnology/vgtmyrmidon)** | **ZTNA** | Zero Trust device registry and cryptographic integrity verification |
| ⚡ **[VGT Auto-Punisher](https://github.com/visiongaiatechnology/vgt-auto-punisher)** | **IDS** | L4+L7 Hybrid IDS — attackers terminated before they even knock |
| 📊 **[VGT Dattrack](https://github.com/visiongaiatechnology/dattrack)** | **Analytics** | Sovereign analytics engine — your data, your server, no third parties |
| 🌐 **[VGT Global Threat Sync](https://github.com/visiongaiatechnology/vgt-global-threat-sync)** | **Preventive** | Daily threat feed — block known attackers before they arrive |
| 🔥 **[VGT Windows Firewall Burner](https://github.com/visiongaiatechnology/vgt-windows-burner)** | **Windows** | 280,000+ APT IPs in native Windows Firewall |

---

## 💰 Support the Project

[![Donate via PayPal](https://img.shields.io/badge/Donate-PayPal-00457C?style=for-the-badge&logo=paypal)](https://www.paypal.com/paypalme/dergoldenelotus)

| Method | Address |
|---|---|
| **PayPal** | [paypal.me/dergoldenelotus](https://www.paypal.com/paypalme/dergoldenelotus) |
| **Bitcoin** | `bc1q3ue5gq822tddmkdrek79adlkm36fatat3lz0dm` |
| **ETH** | `0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85` |
| **USDT (ERC-20)** | `0xD37DEfb09e07bD775EaaE9ccDaFE3a5b2348Fe85` |

---

## 🤝 Contributing

Pull requests are welcome. For major changes, open an issue first.

Licensed under **AGPLv3** — *"For Humans, not for SaaS Corporations."*

---

## 🏢 Built by VisionGaia Technology

[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

VisionGaia Technology builds enterprise-grade security infrastructure — engineered to the DIAMANT VGT SUPREME standard.

> *"Sentinel was built because WordPress deserved a security framework that doesn't phone home, doesn't bloat your stack, and doesn't ask you to trust a SaaS dashboard with your attack surface."*

---

*Version 1.5.0 — VGT Sentinel Community Edition // Zero-Trust WAF Framework // Deterministic DFA Engine // WordPress Marketplace Ready // AGPLv3*
