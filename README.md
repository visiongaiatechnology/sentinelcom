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


<img width="675" height="675" alt="TGL Covers" src="https://github.com/user-attachments/assets/e7920fcd-a771-45d1-968a-76dcdad0a716" />



---

## 🔍 What is VGT Sentinel?

VGT Sentinel Community Edition is a **modular, zero-dependency WordPress security framework** engineered to neutralize deterministic attack vectors without sacrificing performance.

It is the open-source core of the VGT Sentinel suite — a battle-hardened, multi-layered defense system built on a **Zero-Trust architecture**. Every request is inspected, every header hardened, every upload analyzed, every file hashed.

<img width="1731" height="910" alt="{0BD1AB9F-2C98-4420-827E-B34972F0C717}" src="https://github.com/user-attachments/assets/1c7d9708-afda-440f-b4d9-99f7ec50b39a" />



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

Stream-based WAF for real-time payload inspection.

<img width="1734" height="910" alt="{BB3396A0-73EA-48D9-83E9-B996F387E50D}" src="https://github.com/user-attachments/assets/3ff5f406-5989-488d-bcea-4a8d408946cb" />




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

<img width="1731" height="908" alt="{86804D06-F36C-4FE3-BAC4-353684BB3E32}" src="https://github.com/user-attachments/assets/2d11e83f-9bfb-4e27-9f8e-5a0fbc7c3a16" />


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

<img width="1732" height="911" alt="{A6E67F8E-C5A5-421A-982B-005432DDA65B}" src="https://github.com/user-attachments/assets/fe21e604-fce3-4b3a-9ba2-215fe3fb2d28" />



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

<img width="1736" height="905" alt="{5784BE3F-BA54-4339-A930-5535EA1BE535}" src="https://github.com/user-attachments/assets/42f0844f-813c-4e01-963d-7dba4261ba88" />


| Feature | Detail |
|---|---|
| **True-IP Detection** | Native Cloudflare CIDR validation — prevents X-Forwarded-For spoofing |
| **Fail-State Tracking** | RAM/Object Cache via WordPress Transients |
| **Hook Priority** | `1` on `authenticate` — fires before any WP user logic loads |

---

### 🌑 2.5 STYX LITE — Outbound Control

Network-layer control against data exfiltration and supply-chain attacks.

<img width="1736" height="912" alt="{25C4C41C-68D4-4D0F-B99A-68298055B168}" src="https://github.com/user-attachments/assets/536e4a96-a94b-4e47-bfd0-85f5f2414442" />



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

<img width="1737" height="906" alt="{3BC2B51D-5604-499E-8927-A60BF50A11C1}" src="https://github.com/user-attachments/assets/6c7284bf-6f62-44ba-837f-0c71fcbec30f" />



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


<img width="1735" height="905" alt="{5CCBD528-98F8-42E9-B97D-CDA8E46160C0}" src="https://github.com/user-attachments/assets/c147ab1b-0d72-47b6-ac81-17d23df83299" />



<img width="1738" height="910" alt="{34C68E72-49D7-476E-B21E-81598818FF8A}" src="https://github.com/user-attachments/assets/b239a694-a0ae-4eda-a05d-6ec0f7ecd2ac" />


<img width="1738" height="908" alt="{826B7BD7-D671-4207-B8C4-9506258C1E97}" src="https://github.com/user-attachments/assets/113da143-00b6-43d7-8a0a-ab26378eba4f" />


<img width="1738" height="907" alt="{FF21E5FC-7D09-4C19-ACC7-B34866C52792}" src="https://github.com/user-attachments/assets/391325d6-f20d-447e-a1b9-2dfa96f9d8c0" />


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
| **API CRYPTO VAULT** — AES-256-GCM Database Payload Encryption | ❌ | ✅ |
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
git clone https://github.com/visiongaiatechnology/sentinelcom

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
