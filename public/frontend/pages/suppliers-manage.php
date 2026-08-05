<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/../../backend/config/settings.php';

if (!isset($_SESSION['auth_user']) && !isset($_SESSION['user'])) {
    header('Location: ' . public_url('/login'), true, 302);
    exit;
}

// TASK 21 — Stale Session After User Deletion: this page renders its own
// standalone HTML document (no sidebar/navbar), so it cannot include
// header.php without corrupting the layout. It reuses the same shared
// existence check header.php uses instead of duplicating it here.
require_once __DIR__ . '/../includes/session-guard.php';
sfms_reject_stale_session();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Suppliers Management</title>
    <link rel="stylesheet" href="/frontend/assets/styles.css">
    <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:16px}
        .container{max-width:900px;margin:0 auto}
        .card{background:#fff;border:1px solid #e5e7eb;padding:12px;border-radius:6px;margin-bottom:12px}
        .list-item{display:flex;justify-content:space-between;padding:8px;border-bottom:1px solid #f3f4f6}
        @media(max-width:600px){.grid{flex-direction:column}}
    </style>
</head>
<body>
<div class="container">
    <h1>Supplier Management</h1>
    <div class="card">
        <input id="search" placeholder="Search suppliers" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
        <div id="list" style="margin-top:8px"></div>
    </div>
    <div class="card">
        <h3>Add / Edit Supplier</h3>
        <form id="form">
            <input type="hidden" id="supplier_id">
            <div><label>Name</label><input id="name" required style="width:100%"></div>
            <div><label>Contact Email</label><input id="contact_email" style="width:100%"></div>
            <div style="margin-top:8px"><button type="submit">Save</button><button type="button" id="clear">Clear</button></div>
        </form>
    </div>
</div>
<script src="/frontend/assets/js/components.js"></script>
<script>
const apiPrefix = '/api/suppliers';
async function fetchSuppliers(){
    const q = document.getElementById('search').value;
    const res = await fetch(apiPrefix + '?search=' + encodeURIComponent(q),{credentials:'same-origin'});
    const json = await res.json();
    if(!json.success){ Components.alert(json.message || 'Failed to load suppliers', 'danger'); return }
    const data = json.data.data;
    const list = document.getElementById('list');list.innerHTML='';
    data.forEach(s=>{
        const el=document.createElement('div');el.className='list-item';
        el.innerHTML = `<div><strong>${s.name}</strong><div style='color:#6b7280'>${s.contact_email||''}</div></div><div><button data-id='${s.id}' class='edit'>Edit</button></div>`;
        list.appendChild(el);
    });
}

document.getElementById('search').addEventListener('keydown',e=>{if(e.key==='Enter'){fetchSuppliers()}});

document.getElementById('list').addEventListener('click',async(e)=>{
    if(e.target.classList.contains('edit')){
        const id = e.target.dataset.id;
        const res = await fetch('/api/suppliers/' + id,{credentials:'same-origin'});
        const json = await res.json(); if(!json.success){ Components.alert(json.message || 'Failed to load supplier', 'danger'); return }
        const s = json.data.supplier;
        document.getElementById('supplier_id').value = s.id;
        document.getElementById('name').value = s.name || '';
        document.getElementById('contact_email').value = s.contact_email || '';
    }
});

document.getElementById('form').addEventListener('submit',async(e)=>{
    e.preventDefault();
    const id = document.getElementById('supplier_id').value;
    const payload = {name:document.getElementById('name').value, contact_email:document.getElementById('contact_email').value};
    let url = apiPrefix; let method = 'POST'; if(id){url = apiPrefix + '/' + id; method='PATCH'}
    const res = await fetch(url,{method,credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const json = await res.json(); if(!json.success){ Components.alert(json.message || 'Failed to save supplier', 'danger'); return }
    Components.toast(json.message || 'Supplier saved', 'success'); document.getElementById('form').reset(); fetchSuppliers();
});

document.getElementById('clear').addEventListener('click',()=>document.getElementById('form').reset());

fetchSuppliers();
</script>
</body>
</html>