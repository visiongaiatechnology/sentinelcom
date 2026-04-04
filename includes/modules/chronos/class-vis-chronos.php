<?php
if (!defined('ABSPATH')) exit;

/**
 * MODULE: CHRONOS (APEX FUSION V3.0)
 * Status: ROBUST / TIME-SLICING
 * Logic: Asynchrone State-Machine. Zerlegt Scans in mikroskopische Batches, 
 * um PHP-Timeouts zu umgehen. Resistent gegen Server-Restarts.
 */
class VIS_Chronos {

    const EVENT_HOURLY = 'vis_hourly_scan_event';
    const EVENT_STEP   = 'vis_scan_step_event';
    const LOCK_NAME    = 'vis_scan_active_lock';
    const STATE_NAME   = 'vis_scan_progress_state';
    
    // Maximale Laufzeit pro Cron-Slice in Sekunden (Konservativ)
    private $time_budget = 20; 

    public function __construct() {
        add_filter('cron_schedules', [$this, 'add_custom_intervals']);
        
        // Der stündliche Trigger (Startschuss)
        add_action(self::EVENT_HOURLY, [$this, 'initiate_scan']);
        
        // Der Worker-Step (Die eigentliche Arbeit)
        add_action(self::EVENT_STEP, [$this, 'process_scan_slice']);
        
        // Selbstheilung bei Initialisierung
        if (!wp_next_scheduled(self::EVENT_HOURLY)) {
            wp_schedule_event(time(), 'vis_hourly', self::EVENT_HOURLY);
        }
    }

    public function add_custom_intervals($schedules) {
        $schedules['vis_hourly'] = [
            'interval' => 3600,
            'display'  => __('Jede Stunde (VisionGaia)')
        ];
        return $schedules;
    }

    /**
     * INITIALISIERUNG (Trigger)
     * Prüft Locks und startet den Prozess.
     */
    public function initiate_scan() {
        // 1. Check Lock: Läuft bereits ein Scan?
        $lock = get_transient(self::LOCK_NAME);
        
        if ($lock) {
            // Selbstheilung: Wenn Lock älter als 30 Minuten, ist der Prozess wohl gestorben.
            if ((time() - $lock) > 1800) {
                error_log('VISIONGAIA CHRONOS: Stale lock detected. Resetting system.');
                $this->reset_system();
            } else {
                return; // Scan läuft noch aktiv, wir brechen ab.
            }
        }

        // 2. Lock setzen & State resetten
        set_transient(self::LOCK_NAME, time(), 3600); // Max 1 Stunde Lock
        delete_option(self::STATE_NAME);

        // 3. Ersten Schritt anstoßen (sofort)
        $this->schedule_next_step();
    }

    /**
     * WORKER (Slice)
     * Arbeitet so viel ab wie möglich und plant sich dann selbst neu.
     */
    public function process_scan_slice() {
        // Safety: Timeout Limits anheben, falls möglich
        @set_time_limit(60);
        @ignore_user_abort(true);

        // Load State
        $state_data = get_option(self::STATE_NAME, [
            'offset' => 0, 
            'current_state' => []
        ]);

        $offset = $state_data['offset'];
        $current_state = $state_data['current_state'];

        // Initialisiere Scanner Engine
        if (!class_exists('VIS_Scanner_Engine')) {
            require_once VIS_PATH . 'includes/scanner/class-vis-scanner-engine.php';
        }
        $scanner = new VIS_Scanner_Engine();

        $start_time = microtime(true);
        $finished = false;
        $final_result = null;

        // BATCH LOOP innerhalb des Time-Budgets
        while (true) {
            // Check Time Budget
            if ((microtime(true) - $start_time) > $this->time_budget) {
                // Zeit abgelaufen -> Zustand speichern und raus
                break;
            }

            // Update Lock Timestamp (Heartbeat)
            set_transient(self::LOCK_NAME, time(), 3600);

            // Run Batch
            $result = $scanner->perform_scan_batch($offset, $current_state);

            if ($result['status'] === 'processing') {
                $offset = $result['offset'];
                $current_state = $result['current_state'];
                // Kurze Pause für CPU-Entlastung
                usleep(50000); 
            } else {
                // DONE
                $finished = true;
                $final_result = $result;
                break;
            }
        }

        if ($finished) {
            // Scan beendet -> Aufräumen
            $this->finalize_process($final_result);
        } else {
            // Scan nicht beendet -> Zustand speichern und Wiedervorlage
            update_option(self::STATE_NAME, [
                'offset' => $offset,
                'current_state' => $current_state // Hinweis: Bei >100k Dateien sollte dies in eine Temp-DB-Tabelle ausgelagert werden
            ], false); // No Autoload!
            
            $this->schedule_next_step();
        }
    }

    private function schedule_next_step() {
        // Schedule single event as soon as possible
        wp_schedule_single_event(time() + 1, self::EVENT_STEP);
    }

    private function finalize_process($result) {
        $this->reset_system();

        // Alarmierung nur bei echten Problemen
        if ($result && $result['status'] === 'warning') {
            $this->trigger_alert($result);
        }
    }

    private function reset_system() {
        delete_transient(self::LOCK_NAME);
        delete_option(self::STATE_NAME);
        wp_clear_scheduled_hook(self::EVENT_STEP);
    }

    private function trigger_alert($result) {
        $to = get_option('admin_email');
        $subject = '[ALARM] VisionGaia Integrity: Anomalie erkannt';
        
        $body  = "ACHTUNG: System-Integrität verletzt.\n";
        $body .= "Zeitpunkt: " . ($result['timestamp'] ?? current_time('mysql')) . "\n\n";
        
        $count = isset($result['changes']) ? count($result['changes']) : 0;
        $body .= "Anzahl veränderter Dateien: " . $count . "\n";
        $body .= "Bitte sofort im Dashboard prüfen: " . admin_url('admin.php?page=vis-sentinel') . "\n";

        wp_mail($to, $subject, $body, ['Content-Type: text/plain; charset=UTF-8', 'X-Priority: 1']);
    }
}