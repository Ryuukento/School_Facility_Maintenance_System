<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Damage Reports - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="margin-top:16px;">
    <div class="card" style="border-left:3px solid var(--primary-color);">
        <div class="card-header d-flex justify-between align-center">
            <div>
                <h2>Damage Reports</h2>
                <p class="text-muted mb-0">Track damaged deployed items and repair/replacement workflow.</p>
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;margin-bottom:12px;">
                <input type="search" id="damage-search" class="form-control" placeholder="Search report code, item, room, or description...">
                <select id="damage-status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    <option value="repairing">Repairing</option>
                    <option value="repaired">Repaired</option>
                    <option value="replaced">Replaced</option>
                    <option value="closed">Closed</option>
                </select>
                <select id="damage-severity" class="form-control">
                    <option value="">All Severity</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
                <button type="button" id="damage-filter-clear" class="btn btn-secondary">Clear</button>
            </div>

            <div id="damage-reports-container" class="table-responsive">
                <div class="ui-empty-state"><strong>Loading damage reports...</strong></div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;">
                <button type="button" class="btn btn-secondary" id="damage-prev">Previous</button>
                <span id="damage-page-info" class="text-muted">Page 1</span>
                <button type="button" class="btn btn-secondary" id="damage-next">Next</button>
            </div>
        </div>
    </div>
    </div>
</main>

<script>
let damagePage = 1;
let damageLastPage = 1;
const DAMAGE_REPORTS_API_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/damage-reports') : '/api/damage-reports';
const DAMAGE_REPORTS_PAGE_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/damage-reports') : '/damage-reports';

function damageEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function damageNotify(message, type = 'danger') {
    if (window.Components && typeof Components.alert === 'function') {
        Components.alert(message, type);
        return;
    }
    window.alert(message);
}

function formatStatus(status) {
    return String(status || '').replace(/_/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase());
}

async function loadDamageReports() {
    const search = document.getElementById('damage-search').value.trim();
    const status = document.getElementById('damage-status').value;
    const severity = document.getElementById('damage-severity').value;

    const params = new URLSearchParams({
        page: String(damagePage),
        per_page: '15',
    });

    if (search) params.set('q', search);
    if (status) params.set('status', status);
    if (severity) params.set('severity_level', severity);

    try {
        const fetcher = window.Components && typeof Components.fetchJson === 'function'
            ? Components.fetchJson
            : async (url, options) => {
                const response = await fetch(url, options);
                return { response, data: await response.json() };
            };
        const { response, data: payload } = await fetcher(`${DAMAGE_REPORTS_API_BASE}?${params.toString()}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load damage reports');
        }

        const paginator = payload.data?.reports;
        const rows = Array.isArray(paginator?.data) ? paginator.data : [];
        damagePage = Number(paginator?.current_page || 1);
        damageLastPage = Number(paginator?.last_page || 1);

        const container = document.getElementById('damage-reports-container');
        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No damage reports found.</strong><span>Try adjusting your filters.</span></div>';
        } else {
            let html = '<table class="table"><thead><tr>';
            html += '<th>Report ID</th><th>Item</th><th>Room</th><th>Department</th><th>Severity</th><th>Status</th><th>Date Reported</th><th>Actions</th>';
            html += '</tr></thead><tbody>';

            rows.forEach((row) => {
                html += '<tr>';
                html += `<td><strong>${damageEscapeHtml(row.damage_report_code)}</strong></td>`;
                html += `<td>${damageEscapeHtml(row.item?.name || 'Unknown')}</td>`;
                html += `<td>${damageEscapeHtml(row.room?.name || 'N/A')}</td>`;
                html += `<td>${damageEscapeHtml(row.department?.name || 'N/A')}</td>`;
                html += `<td><span class="badge badge-${String(row.severity_level || '').toLowerCase().replace(/[^a-z0-9-]/g, '')}">${damageEscapeHtml(formatStatus(row.severity_level))}</span></td>`;
                html += `<td><span class="badge badge-${String(row.status || '').toLowerCase().replace(/_/g, '-').replace(/[^a-z0-9-]/g, '')}">${damageEscapeHtml(formatStatus(row.status))}</span></td>`;
                html += `<td>${damageEscapeHtml(new Date(row.created_at).toLocaleString())}</td>`;
                html += `<td><a class="btn btn-sm btn-primary" href="${DAMAGE_REPORTS_PAGE_BASE}/${row.id}">View</a> <a class="btn btn-sm btn-secondary" href="${DAMAGE_REPORTS_PAGE_BASE}/${row.id}/update">Update</a></td>`;
                html += '</tr>';
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        document.getElementById('damage-page-info').textContent = `Page ${damagePage} / ${damageLastPage}`;
        document.getElementById('damage-prev').disabled = damagePage <= 1;
        document.getElementById('damage-next').disabled = damagePage >= damageLastPage;
    } catch (error) {
        document.getElementById('damage-reports-container').innerHTML = '<div class="ui-empty-state"><strong>Failed to load damage reports.</strong></div>';
        damageNotify(error.message || 'Unable to load damage reports.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('damage-search').addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            damagePage = 1;
            loadDamageReports();
        }
    });

    document.getElementById('damage-status').addEventListener('change', () => {
        damagePage = 1;
        loadDamageReports();
    });

    document.getElementById('damage-severity').addEventListener('change', () => {
        damagePage = 1;
        loadDamageReports();
    });

    document.getElementById('damage-filter-clear').addEventListener('click', () => {
        document.getElementById('damage-search').value = '';
        document.getElementById('damage-status').value = '';
        document.getElementById('damage-severity').value = '';
        damagePage = 1;
        loadDamageReports();
    });

    document.getElementById('damage-prev').addEventListener('click', () => {
        if (damagePage > 1) {
            damagePage -= 1;
            loadDamageReports();
        }
    });

    document.getElementById('damage-next').addEventListener('click', () => {
        if (damagePage < damageLastPage) {
            damagePage += 1;
            loadDamageReports();
        }
    });

    loadDamageReports();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
