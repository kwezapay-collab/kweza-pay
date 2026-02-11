<?php
$token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Owner Dashboard - Kweza Pay</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/paypal_ui.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --owner-primary: #0070BA;
            --owner-dark: #11295E;
            --bg-gray: #F5F7FA;
        }
        body {
            background-color: var(--bg-gray);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            color: #242729;
        }
        .navbar {
            background: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #DDE1E3;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 20px;
            color: var(--owner-dark);
            text-decoration: none;
        }
        .logo img {
            height: 40px;
        }
        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .header-section {
            margin-bottom: 30px;
        }
        .header-section h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--owner-dark);
            margin: 0;
        }
        .header-section p {
            color: #6C7378;
            margin: 8px 0 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid #DDE1E3;
        }
        .stat-label {
            font-size: 12px;
            color: #6C7378;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--owner-dark);
            margin: 10px 0;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid #DDE1E3;
            margin-bottom: 30px;
        }
        .card-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--owner-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn {
            background: var(--owner-primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .btn:hover {
            background: #005ea6;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #f8fafc;
            color: var(--owner-dark);
            border: 1px solid #DDE1E3;
        }
        .btn-secondary:hover {
            background: #f1f5f9;
        }
        .btn-danger {
            background: #fee2e2;
            color: #ef4444;
        }
        .btn-danger:hover {
            background: #fecaca;
        }
        textarea {
            width: 100%;
            height: 200px;
            padding: 15px;
            border-radius: 12px;
            border: 1px solid #DDE1E3;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            box-sizing: border-box;
            background: #fcfcfc;
        }
        textarea:focus {
            outline: none;
            border-color: var(--owner-primary);
            background: white;
        }
        .sales-table {
            width: 100%;
            border-collapse: collapse;
        }
        .sales-table th {
            text-align: left;
            padding: 15px;
            border-bottom: 2px solid #F5F7FA;
            color: #6C7378;
            font-size: 12px;
            text-transform: uppercase;
        }
        .sales-table td {
            padding: 15px;
            border-bottom: 1px solid #F5F7FA;
            font-size: 14px;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-success { background: #E7F6EC; color: #097230; }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--owner-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        @media (max-width: 992px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div id="loading" class="loading-overlay">
    <div class="spinner"></div>
</div>

<nav class="navbar">
    <a href="#" class="logo">
        <img src="assets/img/logo.png" alt="Kweza Pay" onerror="this.src='https://ui-avatars.com/api/?name=KP&background=0D8ABC&color=fff'">
        <span>Event Owner Portal</span>
    </a>
    <div style="font-size: 13px; color: #6C7378; font-weight: 600;">
        <i class="fas fa-lock"></i> Secure Access
    </div>
</nav>

<div class="main-container">
    <div class="header-section">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
            <div>
                <h1 id="event-name">Event Name</h1>
                <p id="event-details"><i class="fas fa-calendar"></i> Loading date... | <i class="fas fa-map-marker-alt"></i> Loading location...</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-secondary" onclick="fetchData()"><i class="fas fa-sync-alt"></i> Refresh</button>
                <button class="btn btn-danger" onclick="resetEvent()"><i class="fas fa-trash-alt"></i> Reset Event</button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Tickets Sold</div>
            <div class="stat-value" id="stat-sold">0</div>
            <div style="font-size: 13px; color: #6C7378;"><i class="fas fa-chart-line"></i> Total sales count</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" id="stat-revenue">MWK 0</div>
            <div style="font-size: 13px; color: #10b981;"><i class="fas fa-wallet"></i> Gross earnings</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Inventory (Available)</div>
            <div class="stat-value" id="stat-ids">0</div>
            <div style="font-size: 13px; color: #6C7378;"><i class="fas fa-tags"></i> Remaining ticket IDs</div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="main-col">
            <div class="card">
                <div class="card-title"><i class="fas fa-history"></i> Recent Sales</div>
                <div style="overflow-x: auto;">
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Serial Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="sales-body">
                            <!-- Sales rows will be injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="side-col">
            <div class="card">
                <div class="card-title"><i class="fas fa-plus-circle"></i> Add Ticket IDs</div>
                <p style="font-size: 13px; color: #6C7378; margin-top: -10px; margin-bottom: 15px;">
                    Paste one ticket serial ID per line. These will be automatically assigned to new purchases.
                </p>
                <textarea id="ticket-ids-input" placeholder="SERIAL-001&#10;SERIAL-002&#10;SERIAL-003..."></textarea>
                <button class="btn" style="width: 100%; margin-top: 15px;" onclick="updateTicketIds()">
                    <i class="fas fa-upload"></i> Upload Ticket IDs
                </button>
                <div id="upload-status" style="margin-top: 10px; font-size: 13px; text-align: center;"></div>
            </div>

            <div class="card" style="background: #f0f7ff; border-color: #0070BA;">
                <div class="card-title" style="color: #0070BA;"><i class="fas fa-info-circle"></i> Quick Guide</div>
                <ul style="font-size: 13px; color: #242729; padding-left: 20px; line-height: 1.6;">
                    <li><strong>Track:</strong> Monitor sales and revenue in real-time.</li>
                    <li><strong>IDs:</strong> Each sale picks a unique ID from your uploaded list.</li>
                    <li><strong>Receipts:</strong> The ID will appear on the customer's receipt.</li>
                    <li><strong>Reset:</strong> Use Reset carefully; it clears inventory and counters.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    const token = '<?php echo $token; ?>';
    
    if (!token) {
        alert('Invalid access token. Please contact the administrator.');
        window.location.href = 'index.php';
    }

    async function fetchData() {
        document.getElementById('loading').style.display = 'flex';
        try {
            const res = await fetch(`../backend/api/get_event_owner_data.php?token=${token}`);
            const data = await res.json();
            
            if (data.success) {
                const e = data.event;
                const stats = data.stats;
                
                document.getElementById('event-name').textContent = e.event_name;
                document.getElementById('event-details').innerHTML = `
                    <i class="fas fa-calendar"></i> ${e.event_date ? new Date(e.event_date).toLocaleString() : 'N/A'} | 
                    <i class="fas fa-map-marker-alt"></i> ${e.event_location || 'N/A'}
                `;
                
                document.getElementById('stat-sold').textContent = stats.sold.toLocaleString();
                document.getElementById('stat-revenue').textContent = 'MWK ' + stats.revenue.toLocaleString();
                document.getElementById('stat-ids').textContent = stats.remaining_ids.toLocaleString() + ' / ' + stats.total_ids.toLocaleString();
                
                const salesBody = document.getElementById('sales-body');
                if (data.recent_sales.length === 0) {
                    salesBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 40px; color: #6C7378;">No sales yet.</td></tr>';
                } else {
                    salesBody.innerHTML = data.recent_sales.map(s => `
                        <tr>
                            <td>${new Date(s.purchased_at).toLocaleString()}</td>
                            <td style="font-weight: 700;">${s.full_name}</td>
                            <td style="font-family: monospace;">${s.serial_number || '<i>Not Assigned</i>'}</td>
                            <td>MWK ${parseFloat(s.purchase_amount).toLocaleString()}</td>
                            <td><span class="badge badge-success">Completed</span></td>
                        </tr>
                    `).join('');
                }
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            console.error(err);
            alert('A network error occurred.');
        } finally {
            document.getElementById('loading').style.display = 'none';
        }
    }

    async function updateTicketIds() {
        const input = document.getElementById('ticket-ids-input');
        const ids = input.value;
        const status = document.getElementById('upload-status');
        
        if (!ids.trim()) {
            alert('Please enter at least one ticket ID.');
            return;
        }
        
        status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        
        try {
            const res = await fetch('../backend/api/owner_update_ticket_ids.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({token, ticket_ids: ids})
            });
            const data = await res.json();
            if (data.success) {
                status.innerHTML = '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Successfully uploaded!</span>';
                input.value = '';
                fetchData();
            } else {
                status.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-times-circle"></i> Error: ' + data.error + '</span>';
            }
        } catch (err) {
            status.innerHTML = '<span style="color: #ef4444;">Network error.</span>';
        }
    }

    async function resetEvent() {
        if (!confirm('Are you sure you want to RESET this event? This will clear all available ticket IDs and the sold counter. Purchased history will be preserved but links to specific IDs will be lost. This cannot be undone.')) return;
        
        document.getElementById('loading').style.display = 'flex';
        try {
            const res = await fetch('../backend/api/owner_reset_event.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({token})
            });
            const data = await res.json();
            if (data.success) {
                alert('Event reset successfully.');
                fetchData();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            alert('Network error.');
        } finally {
            document.getElementById('loading').style.display = 'none';
        }
    }

    fetchData();
</script>

</body>
</html>
