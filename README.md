# ⚔️ VGT Sentinel — Community Edition (Silber Status)

[![License](https://img.shields.io/badge/License-AGPLv3-green?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0-brightgreen?style=for-the-badge)](#)
[![Platform](https://img.shields.io/badge/Platform-WordPress-21759B?style=for-the-badge&logo=wordpress)](#)
[![Architecture](https://img.shields.io/badge/Architecture-Zero--Trust_WAF-red?style=for-the-badge)](#)
[![Engine](https://img.shields.io/badge/Engine-Deterministic_DFA-orange?style=for-the-badge)](#)
[![Status](https://img.shields.io/badge/Status-STABLE-brightgreen?style=for-the-badge)](#)
[![VGT](https://img.shields.io/badge/VGT-VisionGaia_Technology-red?style=for-the-badge)](https://visiongaiatechnology.de)

> *"No external libraries. No blind trust. No compromise."*
> *AGPLv3 — Open Source Core. Built for humans, not for SaaS margins.*

---

## 🔍 What is VGT Sentinel?

VGT Sentinel Community Edition is a **modular, zero-dependency WordPress security framework** engineered to neutralize deterministic attack vectors without sacrificing performance.

It is the open-source core of the VGT Sentinel suite — a battle-hardened, multi-layered defense system built on a **Zero-Trust architecture**. Every request is inspected, every header hardened, every upload analyzed, every file hashed.

<img width="1737" height="914" alt="{1D2D5C45-A94D-4041-AE56-1791A8E40496}" src="https://github.com/user-attachments/assets/feef431e-a042-4e6c-81f3-fcbeeb6cc8b8" />


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

<img width="1735" height="910" alt="{11C1AE23-D3BF-4E79-8E60-EB528AE623CF}" src="https://github.com/user-attachments/assets/c3261cb4-26e1-4c5e-b6a0-dd489456cc8b" />


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

<img width="1733" height="909" alt="{63606BBB-23A0-442C-8AAB-926015A1DB45}" src="https://github.com/user-attachments/assets/ab1b7486-37b3-4e22-b720-749675a13d8a" />


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

| Feature | Detail |
|---|---|
| **True-IP Detection** | Native Cloudflare CIDR validation — prevents X-Forwarded-For spoofing |
| **Fail-State Tracking** | RAM/Object Cache via WordPress Transients |
| **Hook Priority** | `1` on `authenticate` — fires before any WP user logic loads |

---

### 🌑 2.5 STYX LITE — Outbound Control

Network-layer control against data exfiltration and supply-chain attacks.

<img width="1741" height="908" alt="{6D353779-0E75-4EA0-9DA5-72DED957C004}" src="https://github.com/user-attachments/assets/9d382291-9f97-4ba2-bf63-2662341c237a" />


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

<img width="1736" height="907" alt="{12331AA5-E93D-487F-84BD-656ADA077DF9}" src="https://github.com/user-attachments/assets/247daa1f-0901-405a-8e96-a763d1e46caa" />


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

## ⚙️ Performance Design

> **Zero performance tax. Maximum coverage.**

| Optimization | Mechanism |
|---|---|
| **Fast-Path Routing** | Static assets bypass WAF inspection entirely — saves >90% CPU cycles |
| **Stream Chunking** | Payload inspection via chunked reads — low, stable RAM footprint |
| **Async Scheduling** | CHRONOS runs in time-sliced cron — never blocks request handling |
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

---

## ⚠️ System Boundaries — Silber vs. Platin

> **DISCLAIMER:** The Community Edition (Silber Status) operates on a deterministic rule engine. It provides a robust shield against standardized, automated botnets, scrapers, and known attack vectors.

The following capabilities are **exclusive to VGT Sentinel Pro / Platin Status:**

| Capability | Silber | Platin |
|---|---|---|
| **ORACLE AI** — Polymorphic Zero-Day Detection | ❌ | ✅ |
| **PROMETHEUS** — Dynamic Behavioral Profiling | ❌ | ✅ |
| **NEMESIS** — Deception-Engine | ❌ | ✅ |
| **ZEUS** — Pre-Boot WAF via `auto_prepend_file`) | ❌ | ✅ |
| **MORPHEUS** — Hypervisor for Plugins | ❌ | ✅ |
| **GORGON** — Global Swarm Intelligence Threat Feed | ❌ | ✅ |
| **Hardware Crypto** — AES-256-GCM Database Payload Encryption | ❌ | ✅ |
| Deterministic WAF (AEGIS Lite) | ✅ | ✅ |
| Kernel Hardening (TITAN Lite)  | ✅ | ✅ |
| Stealth Engine (HADES Lite)  | ✅ | ✅ |
| Access Guard (CERBERUS) | ✅ | ✅ |
| Outbound Control (STYX LITE) | ✅ | ✅ |
| Payload Sanitizer (AIRLOCK Lite) | ✅ | ✅ |
| Integrity Monitor (CHRONOS) | ✅ | ✅ |

---

## 🚀 Installation

```bash
# 1. Clone into WordPress plugins directory
cd /var/www/html/wp-content/plugins/
git clone [https://github.com/visiongaiatechnology/vgt-sentinel](https://github.com/visiongaiatechnology/sentinelcom)

# 2. Activate in WordPress Admin
# Plugins → VGT Sentinel Community Edition → Activate

# 3. HADES: Configure custom login slug
# Settings → Sentinel → Stealth Engine

# 4. CHRONOS: Generate initial integrity manifest
# Settings → Sentinel → Integrity Monitor → Generate Baseline
```

On first activation, Sentinel automatically:

```
→ Injects AEGIS WAF into the request lifecycle
→ Applies TITAN security headers
→ Activates HADES URL rewrites (.htaccess / Nginx rules)
→ Initializes CERBERUS fail-state cache
→ Generates CHRONOS integrity_matrix.php baseline
→ Deploys Ghost Trap honeypot
→ Activates STYX outbound kill switch
```

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

*Version 1.0.0 — VGT Sentinel Community Edition // Zero-Trust WAF Framework // Deterministic DFA Engine // AGPLv3*
