/**
 * VGT SENTINEL - INTEGRITY DASHBOARD LOGIC
 * STATUS: PLATIN STATUS
 */
"use strict";

document.addEventListener('DOMContentLoaded', () => {
    const IntegrityUI = {
        init() {
            this.bindEvents();
            console.log('VGT INTEGRITY: Monitoring System active.');
        },

        bindEvents() {
            const scanBtn = document.getElementById('vgts-btn-scan');
            const approveBtn = document.getElementById('vgts-btn-approve');

            if (scanBtn) {
                scanBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.startScan();
                });
            }

            if (approveBtn) {
                approveBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.approveChanges();
                });
            }
        },

        startScan() {
            const progress = document.getElementById('vgts-scan-progress');
            const statusText = document.getElementById('vgts-scan-status-text');
            const scanBtn = document.getElementById('vgts-btn-scan');

            if (progress) progress.style.display = 'block';
            if (scanBtn) scanBtn.disabled = true;

            this.runBatch(0, {}, statusText);
        },

        runBatch(offset, state, statusText) {
            const formData = new FormData();
            formData.append('action', 'vgts_run_scan');
            formData.append('offset', offset);
            formData.append('current_state', JSON.stringify(state));
            
            if (typeof vgtsConfig !== 'undefined') {
                formData.append('nonce', vgtsConfig.nonce);
            }

            fetch(vgtsConfig.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const result = data.data;
                    if (result.status === 'processing') {
                        statusText.innerText = result.message;
                        this.runBatch(result.offset, result.current_state, statusText);
                    } else {
                        // Scan finished (clean or warning)
                        location.reload();
                    }
                } else {
                    alert('VGT SCAN ERROR: ' + (data.data || 'Unknown failure.'));
                    location.reload();
                }
            })
            .catch(err => {
                console.error('Integrity Scan Failure:', err);
                alert('CRITICAL: Network disruption during scan.');
            });
        },

        approveChanges() {
            if (!confirm('VGT SECURITY ALERT:\nAre you sure you want to authorize all current changes? This will set a new baseline for the entire system.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'vgts_approve_changes');
            if (typeof vgtsConfig !== 'undefined') {
                formData.append('nonce', vgtsConfig.nonce);
            }

            fetch(vgtsConfig.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Approval failed: ' + (data.data.message || 'Unknown error'));
                }
            });
        }
    };

    IntegrityUI.init();
});