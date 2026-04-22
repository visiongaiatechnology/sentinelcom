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
            const modal = document.getElementById('vgts-approve-modal');
            const cancelBtn = document.getElementById('vgts-modal-cancel');
            const confirmBtn = document.getElementById('vgts-modal-confirm');

            if (scanBtn) {
                scanBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.startScan();
                });
            }

            if (approveBtn && modal) {
                approveBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    modal.style.display = 'flex';
                });
            }

            if (cancelBtn && modal) {
                cancelBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    modal.style.display = 'none';
                });
            }

            if (confirmBtn && modal) {
                confirmBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = '<span class="dashicons dashicons-update vgts-spin"></span>';
                    this.executeApproval();
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
                        // Scan finished (clean or warning) - avoid POST resubmission popup
                        window.location.href = window.location.href.split('#')[0];
                    }
                } else {
                    alert('VGT SCAN ERROR: ' + (data.data || 'Unknown failure.'));
                    window.location.href = window.location.href.split('#')[0];
                }
            })
            .catch(err => {
                console.error('Integrity Scan Failure:', err);
                alert('CRITICAL: Network disruption during scan.');
            });
        },

        executeApproval() {
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
                    // Avoid POST resubmission popup
                    window.location.href = window.location.href.split('#')[0];
                } else {
                    alert('Approval failed: ' + (data.data.message || 'Unknown error'));
                    const confirmBtn = document.getElementById('vgts-modal-confirm');
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<span class="dashicons dashicons-yes"></span> AUTHORIZE';
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert('CRITICAL: Network disruption during approval.');
            });
        }
    };

    IntegrityUI.init();
});
