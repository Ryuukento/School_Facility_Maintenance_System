<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$type = $_GET['type'] ?? 'inventory';
$pageTitle = 'Report - ' . ucfirst($type);
include __DIR__ . '/../includes/header.php';
?>

<main class="container">
    <div class="card">
        <div class="card-body">
            <div id="report-controls" class="report-controls-grid">
                <div>
                    <label>Date from</label>
                    <input type="date" id="date_from">
                </div>
                <div>
                    <label>Date to</label>
                    <input type="date" id="date_to">
                </div>
                <div>
                    <label>Category</label>
                    <select id="filter_category"><option value="">All</option></select>
                </div>
                <div>
                    <label>Department</label>
                    <select id="filter_department"><option value="">All</option></select>
                </div>
                <div>
                    <label>Room</label>
                    <select id="filter_room"><option value="">All</option></select>
                </div>
                <div>
                    <label>Status</label>
                    <select id="filter_status"><option value="">All</option></select>
                </div>
                <div style="display:flex;align-items:flex-end;gap:8px;">
                    <button id="loadReport" class="btn btn-primary">Load</button>
                    <button id="exportCsv" class="btn btn-secondary">Export CSV</button>
                    <button id="printReport" class="btn btn-secondary">Print / PDF</button>
                </div>
            </div>
            <div id="reportResults" style="margin-top:12px; white-space:pre-wrap; font-family:monospace;"></div>
        </div>
    </div>

    <script>
        document.getElementById('loadReport').addEventListener('click', async function(){
            const type = '<?php echo htmlspecialchars($type); ?>';
                const from = document.getElementById('date_from').value;
                const to = document.getElementById('date_to').value;
                const category = document.getElementById('filter_category').value;
                const department = document.getElementById('filter_department').value;
                const room = document.getElementById('filter_room').value;
                const status = document.getElementById('filter_status').value;

                let url = '/School_Facility_Maintenance_System/api/analytics/';
            if (type === 'inventory') url += 'inventory-summary';
            else if (type === 'low_stock') url += 'low-stock';
            else if (type === 'damaged') url += 'damaged-items';
            else if (type === 'dispatch') url += 'dispatch-report';
            else if (type === 'repair') url += 'repair-report';
            else if (type === 'replacement') url += 'replacement-report';
                const params = [];
                if (from) params.push('date_from=' + encodeURIComponent(from));
                if (to) params.push('date_to=' + encodeURIComponent(to));
                if (category) params.push('category_id=' + encodeURIComponent(category));
                if (department) params.push('department_id=' + encodeURIComponent(department));
                if (room) params.push('room_id=' + encodeURIComponent(room));
                if (status) params.push('status=' + encodeURIComponent(status));
                if (params.length) url += '?' + params.join('&');
            const res = await fetch(url);
            const json = await res.json();
            document.getElementById('reportResults').innerText = JSON.stringify(json.data || {}, null, 2);
        });

            function jsonToCsv(obj){
                // Flatten simple arrays/objects to CSV for lightweight exports
                const rows = [];
                if (Array.isArray(obj)){
                    const keys = Array.from(new Set(obj.flatMap(o => Object.keys(o))));
                    rows.push(keys.join(','));
                    obj.forEach(o => rows.push(keys.map(k => '"' + String((o[k] ?? '')).replace(/"/g,'""') + '"').join(',')));
                } else if (typeof obj === 'object'){
                    const keys = Object.keys(obj);
                    rows.push(keys.join(','));
                    rows.push(keys.map(k => '"' + String(obj[k]).replace(/"/g,'""') + '"').join(','));
                } else {
                    rows.push('value');
                    rows.push('"' + String(obj).replace(/"/g,'""') + '"');
                }
                return rows.join('\n');
            }

            document.getElementById('exportCsv').addEventListener('click', function(){
                const raw = document.getElementById('reportResults').innerText || '';
                if (!raw) return alert('Load a report first');
                let data;
                try { data = JSON.parse(raw); } catch(e){ return alert('Invalid report data'); }
                // pick top-level data node if present
                const payload = data.items ?? data.replacements ?? data.dispatches ?? data.repairs ?? data.most_damaged ?? data;
                const csv = jsonToCsv(Array.isArray(payload) ? payload : (payload.data ?? payload));
                const blob = new Blob(["\uFEFF" + csv], {type: 'text/csv;charset=utf-8;'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = type + '-report-' + (new Date()).toISOString().slice(0,10) + '.csv';
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            });

            document.getElementById('printReport').addEventListener('click', function(){
                const content = document.getElementById('reportResults').innerText || 'No report loaded';
                const w = window.open('', '_blank');
                w.document.write('<pre style="font-family:monospace">' + content.replace(/</g,'&lt;') + '</pre>');
                w.document.close();
                w.focus();
                w.print();
            });

            // fetch options to populate selects
            (async function loadOptions(){
                try{
                    const res = await fetch('/School_Facility_Maintenance_System/api/analytics/options');
                    const json = await res.json();
                    const data = json.data || {};
                    const cat = document.getElementById('filter_category');
                    (data.categories || []).forEach(c => { const o = document.createElement('option'); o.value = c.id; o.text = c.name; cat.appendChild(o); });
                    const dept = document.getElementById('filter_department');
                    (data.departments || []).forEach(d => { const o = document.createElement('option'); o.value = d.department_id; o.text = d.name; dept.appendChild(o); });
                    const room = document.getElementById('filter_room');
                    (data.rooms || []).forEach(r => { const o = document.createElement('option'); o.value = r.id; o.text = r.name; room.appendChild(o); });
                    const status = document.getElementById('filter_status');
                    (data.statuses.items || []).forEach(s => { const o = document.createElement('option'); o.value = s; o.text = s; status.appendChild(o); });
                } catch (e) { console.warn('Failed to load options', e); }
            })();
    </script>

    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Report Details - SFMS';
include __DIR__ . '/../includes/header.php';

$reportId = $_GET['id'] ?? 0;
$user = $_SESSION['user'];
$isSuperAdmin = ($user['role'] ?? '') === 'super_admin';
?>

<main class="container report-view-page">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <div style="flex: 1;">
                <h2 id="report-title">Loading...</h2>
                <p class="text-muted mb-0">Report Details</p>
            </div>
            <a href="/School_Facility_Maintenance_System/frontend/pages/reports.php" class="btn btn-secondary btn-sm" style="height: fit-content; margin-top: 0;">
                ← Back to Reports
            </a>
        </div>
        
        <div class="card-body" id="report-details">
            <div class="loading">Loading report details...</div>
        </div>
    </div>
    
    <?php if ($isSuperAdmin): ?>
    <div class="card" style="margin-top: 20px;">
        <div class="card-body" style="display: flex; justify-content: flex-end;">
            <a id="assign-report-btn"
               href="/School_Facility_Maintenance_System/frontend/pages/maintenance-report-detail.php?id=<?php echo intval($reportId); ?>&back=all_reports"
               class="btn btn-primary">
                Assign Report
            </a>
        </div>
    </div>
    <?php elseif (in_array($user['role'], ['maintenance_admin', 'maintenance_staff'])): ?>
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h2>Update Report Status</h2>
        </div>
        <div class="card-body">
            <div id="update-alert"></div>
            <form id="update-form">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="submitted">Submitted</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" id="update-btn">
                    Update Status
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/report-detail.inline.css">

<script>
const reportId = <?php echo intval($reportId); ?>;

// Load report details
async function loadReport() {
    if (!reportId) {
        document.getElementById('report-details').innerHTML = 
            '<p class="text-danger">Invalid report ID</p>';
        return;
    }
    
    try {
        const response = await API.getReport(reportId);
        const report = response.data.report;
        
        // Update title
        document.getElementById('report-title').textContent = `#${report.report_id} - ${report.title}`;
        
        // Build details HTML
        const sectionWrapStyle = 'margin-top: 20px;';
        const sectionTitleStyle = 'margin-bottom: 15px; font-weight: 600; color: var(--text-light);';
        const tableStyle = 'border-collapse: collapse; width: 100%;';
        const thStyle = 'width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: var(--text-light); background: var(--muted-card); border-bottom: 1px solid var(--border);';
        const thLastStyle = 'width: 200px; padding: 12px 8px; text-align: left; font-weight: 700; color: var(--text-light); background: var(--muted-card);';
        const tdStyle = 'padding: 12px 8px; color: var(--text-light); background: var(--card-color); border-bottom: 1px solid var(--border);';
        const tdLastStyle = 'padding: 12px 8px; color: var(--text-light); background: var(--card-color);';
        const detailBoxStyle = 'background: var(--muted-card); padding: 15px; border-radius: 6px; border-left: 4px solid var(--primary-color); line-height: 1.6; color: var(--text-light);';

        let html = '<div style="display: grid; gap: 20px;">';
        
        // Basic Info
        html += '<div>';
        html += `<h3 style="${sectionTitleStyle}">Basic Information</h3>`;
        html += `<table class="table" style="${tableStyle}">`;
        html += `<tr><th style="${thStyle}">Report ID</th><td style="${tdStyle}">#${report.report_id}</td></tr>`;
        html += `<tr><th style="${thStyle}">Title</th><td style="${tdStyle}"><strong>${report.title}</strong></td></tr>`;
        html += `<tr><th style="${thStyle}">Location</th><td style="${tdStyle}">${report.location}</td></tr>`;
        html += `<tr><th style="${thStyle}">Priority</th><td style="${tdStyle}">${UI.getPriorityBadge(report.priority)}</td></tr>`;
        html += `<tr><th style="${thStyle}">Status</th><td style="${tdStyle}">${UI.getStatusBadge(report.status)}</td></tr>`;
        html += `<tr><th style="${thLastStyle}">Department</th><td style="${tdLastStyle}">${report.department_name || 'Not assigned'}</td></tr>`;
        html += '</table>';
        html += '</div>';
        
        // Description
        html += `<div style="${sectionWrapStyle}">`;
        html += `<h3 style="${sectionTitleStyle}">Description</h3>`;
        html += `<div style="${detailBoxStyle}">${report.description || 'No description provided'}</div>`;
        html += '</div>';
        
        // People
        html += `<div style="${sectionWrapStyle}">`;
        html += `<h3 style="${sectionTitleStyle}">People</h3>`;
        html += `<table class="table" style="${tableStyle}">`;
        html += `<tr><th style="${thStyle}">Created By</th><td style="${tdStyle}">${report.creator_name} ${report.creator_email ? `(${report.creator_email})` : ''}</td></tr>`;
        html += `<tr><th style="${thLastStyle}">Assigned To</th><td style="${tdLastStyle}">${report.assigned_name ? `${report.assigned_name} ${report.assigned_email ? `(${report.assigned_email})` : ''}` : 'Not assigned yet'}</td></tr>`;
        html += '</table>';
        html += '</div>';
        
        // Dates
        if (report.created_at_full || report.created_at || report.updated_at_formatted || report.updated_at) {
            html += `<div style="${sectionWrapStyle}">`;
            html += `<h3 style="${sectionTitleStyle}">Timeline</h3>`;
            html += `<table class="table" style="${tableStyle}">`;
            if (report.created_at_full || report.created_at) {
                html += `<tr><th style="${thStyle}">Created</th><td style="${tdStyle}">${report.created_at_full || report.created_at}</td></tr>`;
            }
            if (report.updated_at_formatted || report.updated_at) {
                html += `<tr><th style="${thStyle}">Last Updated</th><td style="${tdStyle}">${report.updated_at_formatted || report.updated_at}</td></tr>`;
            }
            if (report.due_date) {
                html += `<tr><th style="${thStyle}">Due Date</th><td style="${tdStyle}">${report.due_date_formatted || report.due_date}</td></tr>`;
            }
            if (report.completed_date) {
                html += `<tr><th style="${thLastStyle}">Completed</th><td style="${tdLastStyle}">${report.completed_date_formatted || report.completed_date}</td></tr>`;
            }
            html += '</table>';
            html += '</div>';
        }
        
        html += '</div>';
        
        document.getElementById('report-details').innerHTML = html;
        
        // Set current status in form
        if (document.getElementById('status')) {
            document.getElementById('status').value = report.status;
        }
        
    } catch (error) {
        document.getElementById('report-details').innerHTML = 
            '<p class="text-danger">Failed to load report details</p>';
        console.error('Failed to load report:', error);
    }
}

// Handle status update
<?php if (in_array($user['role'], ['maintenance_admin', 'maintenance_staff'])): ?>
document.getElementById('update-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const updateBtn = document.getElementById('update-btn');
    const alertDiv = document.getElementById('update-alert');
    const newStatus = document.getElementById('status').value;
    
    const originalText = updateBtn.innerHTML;
    updateBtn.innerHTML = 'Updating...';
    updateBtn.disabled = true;
    alertDiv.innerHTML = '';
    
    try {
        await API.updateReport({
            report_id: reportId,
            status: newStatus
        });
        
        alertDiv.innerHTML = '<div class="alert alert-success">Status updated successfully!</div>';
        
        // Reload report details
        setTimeout(() => {
            loadReport();
            alertDiv.innerHTML = '';
        }, 1500);
        
    } catch (error) {
        alertDiv.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    } finally {
        updateBtn.innerHTML = originalText;
        updateBtn.disabled = false;
    }
});
<?php endif; ?>

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadReport();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Ensure API and Session are defined globally - CRITICAL
window.API = window.API || {
    async getReport(id) {
        const response = await fetch(window.SFMS_PUBLIC_URL(`/api/reports/${id}`), {
            credentials: 'include'
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message);
        return data;
    },
    async updateReport(data) {
        const { report_id, ...fields } = data;
        const response = await fetch(window.SFMS_PUBLIC_URL(`/api/reports/${report_id}`), {
            method: 'PATCH',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(fields)
        });
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        return result;
    },
    async logout() {
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/auth/logout'), {
            method: 'POST',
            credentials: 'include'
        });
        const data = await response.json();
        return data;
    }
};

window.Session = window.Session || {
    get(key) { 
        const v = localStorage.getItem(key);
        return v ? JSON.parse(v) : null;
    },
    set(key, value) { localStorage.setItem(key, JSON.stringify(value)); },
    clear() { localStorage.clear(); }
};
</script>


