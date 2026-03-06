<?php
/**
 * Report Service
 * Handles maintenance report business logic
 */

class ReportService {
    private $pdo;
    private $reportModel;
    private $activityLog;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->reportModel = new MaintenanceReport($pdo);
        $this->activityLog = new ActivityLog($pdo);
    }
    
    public function createReport($data, $userId) {
        // Validate input
        $validator = new Validator();
        if (!$validator->validate($data, [
            'title' => 'required|max:255',
            'description' => 'required|max:2000',
            'location' => 'required|max:255',
            'priority' => 'in:' . implode(',', [PRIORITY_LOW, PRIORITY_MEDIUM, PRIORITY_HIGH, PRIORITY_URGENT])
        ])) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->getErrors()
            ];
        }
        
        $data['created_by'] = $userId;
        
        try {
            $reportId = $this->reportModel->create($data);
            $this->activityLog->log($userId, 'CREATE_REPORT', 'report', $reportId);
            
            Logger::info('Report created', ['report_id' => $reportId, 'user_id' => $userId]);
            
            return [
                'success' => true,
                'message' => 'Report created successfully',
                'report_id' => $reportId
            ];
        } catch (Exception $e) {
            Logger::error('Failed to create report', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to create report'
            ];
        }
    }
    
    public function updateReport($reportId, $data, $userId) {
        // Check if report exists
        $report = $this->reportModel->findById($reportId);
        if (!$report) {
            return [
                'success' => false,
                'message' => 'Report not found'
            ];
        }
        
        // Validate update
        $validator = new Validator();
        $allowedUpdates = ['title', 'description', 'location', 'priority', 'status', 'assigned_to', 'due_date'];
        $updateData = array_filter($data, function($key) use ($allowedUpdates) {
            return in_array($key, $allowedUpdates);
        }, ARRAY_FILTER_USE_KEY);
        
        if (empty($updateData)) {
            return [
                'success' => false,
                'message' => 'No valid fields to update'
            ];
        }
        
        try {
            $this->reportModel->update($reportId, $updateData);
            $this->activityLog->log($userId, 'UPDATE_REPORT', 'report', $reportId, $updateData);
            
            Logger::info('Report updated', ['report_id' => $reportId, 'user_id' => $userId]);
            
            return [
                'success' => true,
                'message' => 'Report updated successfully'
            ];
        } catch (Exception $e) {
            Logger::error('Failed to update report', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to update report'
            ];
        }
    }
    
    public function getReport($reportId) {
        $report = $this->reportModel->findById($reportId);
        
        if (!$report) {
            return [
                'success' => false,
                'message' => 'Report not found'
            ];
        }
        
        return [
            'success' => true,
            'data' => $this->formatReportDates($report)
        ];
    }
    
    public function assignReport($reportId, $assigneeId, $userId) {
        $report = $this->reportModel->findById($reportId);
        if (!$report) {
            return [
                'success' => false,
                'message' => 'Report not found'
            ];
        }
        
        try {
            $this->reportModel->update($reportId, [
                'assigned_to' => $assigneeId,
                'status' => REPORT_STATUS_ASSIGNED
            ]);
            
            $this->activityLog->log($userId, 'ASSIGN_REPORT', 'report', $reportId, 
                ['assigned_to' => $assigneeId]);
            
            Logger::info('Report assigned', ['report_id' => $reportId, 'assignee_id' => $assigneeId]);
            
            return [
                'success' => true,
                'message' => 'Report assigned successfully'
            ];
        } catch (Exception $e) {
            Logger::error('Failed to assign report', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to assign report'
            ];
        }
    }
    
    public function getAllReports($limit = 100, $offset = 0) {
        try {
            $reports = $this->reportModel->getAll($limit, $offset);
            
            // Format dates for all reports
            $formattedReports = array_map([$this, 'formatReportDates'], $reports);
            
            return [
                'success' => true,
                'data' => [
                    'reports' => $formattedReports,
                    'total' => count($formattedReports)
                ]
            ];
        } catch (Exception $e) {
            Logger::error('Failed to fetch reports', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to fetch reports'
            ];
        }
    }
    
    /**
     * Format report dates to readable format
     * Converts database DATETIME to "Feb 10, 2025" format
     */
    private function formatReportDates($report) {
        if (!is_array($report)) {
            $report = (array) $report;
        }
        
        // Format created_at
        if (!empty($report['created_at'])) {
            try {
                $date = new DateTime($report['created_at']);
                $report['created_at_formatted'] = $date->format('M d, Y');
                $report['created_at_full'] = $date->format('M d, Y h:i A');
            } catch (Exception $e) {
                $report['created_at_formatted'] = $report['created_at'];
                $report['created_at_full'] = $report['created_at'];
            }
        }
        
        // Format due_date if exists
        if (!empty($report['due_date'])) {
            try {
                $date = new DateTime($report['due_date']);
                $report['due_date_formatted'] = $date->format('M d, Y');
            } catch (Exception $e) {
                $report['due_date_formatted'] = $report['due_date'];
            }
        }
        
        // Format completed_date if exists
        if (!empty($report['completed_date'])) {
            try {
                $date = new DateTime($report['completed_date']);
                $report['completed_date_formatted'] = $date->format('M d, Y h:i A');
            } catch (Exception $e) {
                $report['completed_date_formatted'] = $report['completed_date'];
            }
        }
        
        // Format updated_at if exists
        if (!empty($report['updated_at'])) {
            try {
                $date = new DateTime($report['updated_at']);
                $report['updated_at_formatted'] = $date->format('M d, Y h:i A');
            } catch (Exception $e) {
                $report['updated_at_formatted'] = $report['updated_at'];
            }
        }
        
        return $report;
    }
}
