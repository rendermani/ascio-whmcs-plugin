<?php
/**
 * Ascio WHMCS Registrar Module - Domain History Tracking
 *
 * Provides structured domain status history storage and retrieval.
 * Logs all domain status changes with Ascio order details for audit trail.
 */
namespace ascio;

use WHMCS\Database\Capsule;

class DomainHistory
{
    /** @var string Table name for domain history */
    const TABLE_NAME = 'tblascio_domain_history';

    /**
     * Log a domain status change to the history table
     *
     * @param int $domainId WHMCS domain ID
     * @param string $domainName Domain name (e.g., example.com)
     * @param string $ascioStatus Ascio order status (e.g., "Completed", "Pending_End_User_Action")
     * @param string $whmcsStatus WHMCS domain status (e.g., "Active", "Pending")
     * @param string|null $orderId Ascio order ID
     * @param string|null $orderType Ascio order type (e.g., "Register_Domain", "Transfer_Domain")
     * @param string $message Additional message or error details
     * @return int|bool The inserted row ID or false on failure
     */
    public static function log(
        int $domainId,
        string $domainName,
        string $ascioStatus,
        string $whmcsStatus,
        ?string $orderId,
        ?string $orderType,
        string $message
    ) {
        self::ensureTable();

        try {
            return Capsule::table(self::TABLE_NAME)->insertGetId([
                'domain_id' => $domainId,
                'domain_name' => $domainName,
                'ascio_status' => $ascioStatus,
                'whmcs_status' => $whmcsStatus,
                'order_id' => $orderId,
                'order_type' => $orderType,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the flow
            error_log("DomainHistory::log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get domain history for a specific domain
     *
     * @param int $domainId WHMCS domain ID
     * @param int $limit Maximum number of records to return
     * @return array Array of history records (newest first)
     */
    public static function getHistory(int $domainId, int $limit = 50): array
    {
        self::ensureTable();

        try {
            $results = Capsule::table(self::TABLE_NAME)
                ->where('domain_id', $domainId)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            // Convert to array of arrays for easier consumption
            return array_map(function ($row) {
                return (array) $row;
            }, $results->toArray());
        } catch (\Exception $e) {
            error_log("DomainHistory::getHistory error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get domain history by domain name
     *
     * @param string $domainName Domain name
     * @param int $limit Maximum number of records to return
     * @return array Array of history records (newest first)
     */
    public static function getHistoryByName(string $domainName, int $limit = 50): array
    {
        self::ensureTable();

        try {
            $results = Capsule::table(self::TABLE_NAME)
                ->where('domain_name', $domainName)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            return array_map(function ($row) {
                return (array) $row;
            }, $results->toArray());
        } catch (\Exception $e) {
            error_log("DomainHistory::getHistoryByName error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get latest status for a domain
     *
     * @param int $domainId WHMCS domain ID
     * @return array|null Latest history record or null
     */
    public static function getLatest(int $domainId): ?array
    {
        $history = self::getHistory($domainId, 1);
        return $history[0] ?? null;
    }

    /**
     * Ensure the domain history table exists
     *
     * Creates the table if it doesn't exist. Safe to call multiple times.
     */
    public static function ensureTable(): void
    {
        static $tableChecked = false;

        // Only check once per request
        if ($tableChecked) {
            return;
        }

        try {
            if (!Capsule::schema()->hasTable(self::TABLE_NAME)) {
                Capsule::schema()->create(self::TABLE_NAME, function ($table) {
                    $table->increments('id');
                    $table->integer('domain_id')->index();
                    $table->string('domain_name', 255)->index();
                    $table->string('ascio_status', 100);
                    $table->string('whmcs_status', 50);
                    $table->string('order_id', 100)->nullable()->index();
                    $table->string('order_type', 100)->nullable();
                    $table->text('message')->nullable();
                    $table->timestamp('created_at')->useCurrent();
                });
            }
            $tableChecked = true;
        } catch (\Exception $e) {
            // Table might already exist from parallel request
            if (strpos($e->getMessage(), 'already exists') === false) {
                error_log("DomainHistory::ensureTable error: " . $e->getMessage());
            }
            $tableChecked = true;
        }
    }

    /**
     * Delete history for a domain (for testing/cleanup)
     *
     * @param int $domainId WHMCS domain ID
     * @return int Number of deleted records
     */
    public static function deleteHistory(int $domainId): int
    {
        self::ensureTable();

        try {
            return Capsule::table(self::TABLE_NAME)
                ->where('domain_id', $domainId)
                ->delete();
        } catch (\Exception $e) {
            error_log("DomainHistory::deleteHistory error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Format history for display in admin area
     *
     * @param array $history Array of history records
     * @return string HTML formatted history
     */
    public static function formatForDisplay(array $history): string
    {
        if (empty($history)) {
            return '<p class="text-muted">No status history available.</p>';
        }

        $html = '<table class="table table-striped table-sm">';
        $html .= '<thead><tr>';
        $html .= '<th>Date</th>';
        $html .= '<th>Ascio Status</th>';
        $html .= '<th>WHMCS Status</th>';
        $html .= '<th>Order Type</th>';
        $html .= '<th>Order ID</th>';
        $html .= '<th>Message</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($history as $record) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($record['created_at'] ?? '') . '</td>';
            $html .= '<td>' . self::formatStatus($record['ascio_status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($record['whmcs_status'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($record['order_type'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($record['order_id'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($record['message'] ?? '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Format Ascio status with appropriate styling
     *
     * @param string $status Ascio status
     * @return string HTML formatted status
     */
    private static function formatStatus(string $status): string
    {
        $statusClasses = [
            'Completed' => 'success',
            'Failed' => 'danger',
            'Invalid' => 'danger',
            'Pending' => 'info',
            'Pending_End_User_Action' => 'warning',
            'Pending_Documentation' => 'warning',
            'NotReady' => 'secondary',
        ];

        $class = $statusClasses[$status] ?? 'secondary';
        $displayStatus = str_replace('_', ' ', $status);

        return '<span class="badge badge-' . $class . ' bg-' . $class . '">'
             . htmlspecialchars($displayStatus) . '</span>';
    }
}
