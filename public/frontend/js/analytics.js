/* Analytics dashboard orchestrator: fetch, render, export, refresh */
(function(){
    const selectors = {
        overview: '/School_Facility_Maintenance_System/api/analytics/overview',
        topRequested: '/School_Facility_Maintenance_System/api/analytics/top-requested',
        topRepaired: '/School_Facility_Maintenance_System/api/analytics/top-repaired',
        inventoryHealth: '/School_Facility_Maintenance_System/api/analytics/inventory-health'
    };

    function persistFilters(filters){
        try { localStorage.setItem('sfms.analytics.filters', JSON.stringify(filters)); } catch(e){}
    }
    function loadFilters(){
        try { return JSON.parse(localStorage.getItem('sfms.analytics.filters')||'{}'); } catch(e){return {};}
    }

    async function loadAll(){
        const filters = loadFilters();
        // Overview
        try{
            const ov = await SFMSApi.fetchJson(selectors.overview);
            const data = ov.data || {};
            const damaged = data.damaged_trends||[]; const repair = data.repair_trends||[];
            const labels = damaged.map(r=>r.ym);
            const damagedVals = damaged.map(r=>r.cnt); const repairVals = repair.map(r=>r.cnt);

            const dCanvas = document.getElementById('damagedTrendChart'); if (dCanvas){ dCanvas.style.display='block'; SFMSCharts.renderLineChart(dCanvas.getContext('2d'), labels, damagedVals, 'Damaged Reports'); document.getElementById('damagedSkeleton')?.remove(); }
            const rCanvas = document.getElementById('repairTrendChart'); if (rCanvas){ rCanvas.style.display='block'; SFMSCharts.renderLineChart(rCanvas.getContext('2d'), labels, repairVals, 'Repair Requests'); document.getElementById('repairSkeleton')?.remove(); }
        } catch (e){ console.error('overview load failed', e); }

        // Top requested
        try{
            const tr = await SFMSApi.fetchJson(selectors.topRequested);
            const list = (tr.data && tr.data.top_requested) || [];
            if (list.length){ const labels = list.map(x=>x.name); const vals = list.map(x=>x.total_requested); const c=document.getElementById('topRequestedChart'); c.style.display='block'; SFMSCharts.renderBarChart(c.getContext('2d'), labels, vals, 'Top Requested'); document.querySelector('#topRequestedWrap .ui-empty-state')?.remove(); }
        } catch(e){ console.error('top requested failed', e); }

        // Top repaired
        try{
            const tr = await SFMSApi.fetchJson(selectors.topRepaired);
            const list = (tr.data && tr.data.top_repaired) || [];
            if (list.length){ const labels = list.map(x=>x.name); const vals = list.map(x=>x.repairs_count); const c=document.getElementById('topRepairedChart'); c.style.display='block'; SFMSCharts.renderBarChart(c.getContext('2d'), labels, vals, 'Top Repaired'); document.querySelector('#topRepairedWrap .ui-empty-state')?.remove(); }
        } catch(e){ console.error('top repaired failed', e); }

        // Health
        try{
            const ih = await SFMSApi.fetchJson(selectors.inventoryHealth);
            const h = ih.data || {};
            if (h.total_items !== undefined){ const el = document.getElementById('inventoryHealth'); el.style.display='block'; el.innerHTML = `<div><strong>Total:</strong> ${h.total_items}</div><div><strong>Low:</strong> ${h.low_stock_count} (${h.low_stock_percent}%)</div><div><strong>Out:</strong> ${h.out_of_stock_count}</div>`; document.querySelector('#inventoryHealthWrap .ui-empty-state')?.remove(); }
        } catch(e){ console.error('inventory health failed', e); }
    }

    function wireButtons(){
        document.querySelectorAll('[data-analytics-export]').forEach(btn=>{
            btn.addEventListener('click', async function(){
                const what = this.getAttribute('data-analytics-export');
                try{
                    let payload = [];
                    const filters = (function(){ try{ return JSON.parse(localStorage.getItem('sfms.analytics.filters')||'{}'); }catch(e){return {};}})();
                    if (what === 'top-requested'){
                        const res = await SFMSApi.fetchJson('/School_Facility_Maintenance_System/api/analytics/top-requested'); payload = res.data.top_requested || [];
                    } else if (what === 'top-repaired'){
                        const res = await SFMSApi.fetchJson('/School_Facility_Maintenance_System/api/analytics/top-repaired'); payload = res.data.top_repaired || [];
                    } else if (what === 'overview'){
                        const res = await SFMSApi.fetchJson('/School_Facility_Maintenance_System/api/analytics/overview'); payload = res.data || {};
                    }
                    SFMSExport.downloadCsv('analytics-' + what, payload, { meta: { exported_at: new Date().toISOString(), filters: JSON.stringify(filters) } });
                } catch (e){ console.error('export failed', e); alert('Export failed: ' + (e.message||e)); }
            });
        });

        const refreshBtn = document.getElementById('analyticsRefreshBtn');
        if (refreshBtn) refreshBtn.addEventListener('click', function(){ loadAll(); });
    }

    // Auto-refresh every 2 minutes in background
    let autoTimer = null;
    function startAutoRefresh(){ autoTimer = setInterval(()=>{ loadAll(); }, 120000); }

    // Init
    function init(){ wireButtons(); loadAll(); startAutoRefresh(); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
