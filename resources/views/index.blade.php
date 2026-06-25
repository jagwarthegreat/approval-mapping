<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Mapping</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; color: #111827; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .muted { color: #6b7280; font-size: 14px; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Approval Mapping Package</h1>
        <p class="muted">Blade fallback UI is active. You can now use approval mapping out of the box.</p>
    </div>

    <div class="card">
        <h3>API Endpoints</h3>
        <ul>
            <li><code>GET /api/v1/approval-mapping/versions</code></li>
            <li><code>POST /api/v1/approval-mapping/versions</code></li>
            <li><code>GET /api/v1/approval-mapping/versions/{version}</code></li>
            <li><code>PUT /api/v1/approval-mapping/versions/{version}/activate</code></li>
        </ul>
    </div>
</body>
</html>
