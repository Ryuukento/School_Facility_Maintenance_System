<!DOCTYPE html>
<html>
<head>
    <title>Script Load Test</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .ok { color: green; font-weight: bold; }
        .bad { color: red; font-weight: bold; }
        code { background: #f0f0f0; padding: 2px 5px; }
    </style>
</head>
<body>
    <h1>🔍 Script Load Diagnostic</h1>
    
    <div class="box">
        <h2>Before Loading Scripts</h2>
        <p id="before"></p>
    </div>

    <!-- Load scripts here -->
    <script src="/School_Facility_Maintenance_System/frontend/assets/js/utils.js"></script>
    <script src="/School_Facility_Maintenance_System/frontend/assets/js/api.js"></script>

    <div class="box">
        <h2>After Loading Scripts</h2>
        <p id="after"></p>
    </div>

    <script>
        // Check before scripts load
        document.getElementById('before').innerHTML = `
            typeof API: ${typeof window.API}<br>
            typeof Session: ${typeof window.Session}
        `;

        // Check after scripts load
        setTimeout(() => {
            document.getElementById('after').innerHTML = `
                typeof API: <span class="${typeof window.API !== 'undefined' ? 'ok' : 'bad'}">${typeof window.API}</span><br>
                typeof Session: <span class="${typeof window.Session !== 'undefined' ? 'ok' : 'bad'}">${typeof window.Session}</span><br>
                <br>
                ${typeof window.API !== 'undefined' ? '<span class="ok">✅ API loaded successfully!</span>' : '<span class="bad">❌ API failed to load</span>'}<br>
                ${typeof window.Session !== 'undefined' ? '<span class="ok">✅ Session loaded successfully!</span>' : '<span class="bad">❌ Session failed to load</span>'}
            `;

            if (typeof window.API === 'undefined' || typeof window.Session === 'undefined') {
                document.getElementById('after').innerHTML += '<br><br><strong>⚠️ Script loading failed!</strong><br>Check browser console (F12) for errors.';
            }
        }, 500);
    </script>
</body>
</html>
