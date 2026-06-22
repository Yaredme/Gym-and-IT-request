<?php
include 'include/db.php';

// Global alert status trackers
$success_alert = "";
$error_alert = "";

// --- TASK A: FETCH DEPARTMENTS FOR THE DROPDOWN ---
// Aligned with the exact 'departments' schema table and column structures
$dept_query = "SELECT department_code, department_name FROM departments ORDER BY department_name ASC";
$dept_result = $conn->query($dept_query);

$departments_list = [];
if ($dept_result) {
    while ($row = $dept_result->fetch_assoc()) {
        $departments_list[] = $row;
    }
}

// --- TASK B: INTERCEPT FORM SUBMISSION & INSERT DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispatch_request'])) {
    
    $full_name       = trim($_POST['full_name'] ?? '');
    $department_code = intval($_POST['department_code'] ?? 0); // Forcing integer alignment for foreign key match
    $issue_category  = trim($_POST['issue_category'] ?? '');
    $location_num    = trim($_POST['location_number'] ?? '');
    $urgency_level   = trim($_POST['urgency'] ?? 'standard');
    $description     = trim($_POST['description'] ?? '');

    // Server-side validation check
    if (!empty($full_name) && $department_code > 0 && !empty($issue_category) && !empty($location_num)) {
        
        // Prepared statement maps strictly to structural database table definition
        $stmt = $conn->prepare("INSERT INTO support_requests (full_name, department_code, issue_category, location_number, urgency_level, issue_description) VALUES (?, ?, ?, ?, ?, ?)");
        
        // Types binding representation: s = string, i = integer
        $stmt->bind_param("sissss", $full_name, $department_code, $issue_category, $location_num, $urgency_level, $description);

        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?alert=dispatched#add-request-form");
            exit;
        } else {
            $error_alert = "System Error: Unable to log your support ticket. Please retry. (" . $stmt->error . ")";
        }
        $stmt->close();
    } else {
        $error_alert = "Validation Error: All structural fields except description are required.";
    }
}

if (isset($_GET['alert']) && $_GET['alert'] === 'dispatched') {
    $success_alert = "Support dispatch successful! The IT and Maintenance log team has been notified.";
}

// 1. FETCH REQUEST STATUS METRICS (packages table)
$packageQuery = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
FROM support_requests";

$packageResult = $conn->query($packageQuery);
$packages = $packageResult ? $packageResult->fetch_assoc() : ['pending' => 0, 'in_progress' => 0, 'completed' => 0];



// 2. FETCH GYM MEMBERSHIP METRICS (memberships table)
$membershipQuery = "SELECT 
    COUNT(*) as all_members,
    COUNT(CASE WHEN status = 'expired' OR end_date < CURRENT_DATE THEN 1 END) as expired
FROM memberships";

$membershipResult = $conn->query($membershipQuery);
$memberships = $membershipResult ? $membershipResult->fetch_assoc() : ['all_members' => 0, 'expired' => 0];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elgel Hotel and Spa - Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Smooth fallback for background images and gradients */
        body {
            background: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95)), 
                        url('https://images.unsplash.com/photo-1566073771259-6a8506099945?q=80&w=1920&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
        }
    </style>
</head>
<body class="text-slate-100 font-sans min-h-screen flex flex-col justify-between">

    <!-- HEADER SECTION -->
    <header class="w-full bg-white/10 backdrop-blur-md border-b border-white/10 sticky top-0 z-50 px-6 py-4 transition-all">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            
            <!-- Left Side: Title & Subtitles -->
            <div class="text-center md:text-left">
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-wide text-amber-400 drop-shadow-md">
                    Elgel HOTEL AND SPA
                </h1>
                <p class="text-xs md:text-sm text-slate-300 font-medium mt-0.5 tracking-wider uppercase">
                    MOTHERHOOD & SERVICE <BR>IT ALL BEGINS HERE
                </p>
            </div>

            <!-- Right Side: Action Navigation Links -->
            <nav class="flex items-center gap-4">
                <a href="#add-request-form" class="bg-amber-500 hover:bg-amber-600 text-slate-900 px-4 py-2 rounded-lg font-semibold text-sm transition-all shadow-lg hover:shadow-amber-500/20 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Add Request
                </a>
                <a href="LOGIN.html" class="border border-slate-300 hover:border-amber-400 hover:text-amber-400 px-4 py-2 rounded-lg font-semibold text-sm transition-all backdrop-blur-sm">
                    Login
                </a>
            </nav>
        </div>
    </header>
<!-- SECTION 1: REPORT DASHBOARD (REQUEST STATUS) -->
         <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold tracking-wide border-l-4 border-amber-400 pl-3">Request Status Dashboard</h2>
                <span class="text-xs bg-white/5 px-2 py-1 rounded text-slate-400">Live Metrics</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Pending Card -->
                <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-6 flex items-center justify-between shadow-xl">
                    <div>
                        <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">Pending</p>
                        <h3 class="text-3xl font-bold text-amber-400 mt-1">
                            <?= intval($packages['pending'] ?? 0) ?>
                        </h3>
                    </div>
                    <div class="p-3 bg-amber-500/10 rounded-lg text-amber-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>

                <!-- In Progress Card -->
                <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-6 flex items-center justify-between shadow-xl">
                    <div>
                        <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">In Progress</p>
                        <h3 class="text-3xl font-bold text-blue-400 mt-1">
                            <?= intval($packages['in_progress'] ?? 0) ?>
                        </h3>
                    </div>
                    <div class="p-3 bg-blue-500/10 rounded-lg text-blue-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.253 8H18" /></svg>
                    </div>
                </div>

                <!-- Completed Card -->
                <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-6 flex items-center justify-between shadow-xl">
                    <div>
                        <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">Completed</p>
                        <h3 class="text-3xl font-bold text-emerald-400 mt-1">
                            <?= intval($packages['completed'] ?? 0) ?>
                        </h3>
                    </div>
                    <div class="p-3 bg-emerald-500/10 rounded-lg text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 2: GYM MEMBER STATUS DASHBOARD -->
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold tracking-wide border-l-4 border-amber-400 pl-3">Gym Membership Dashboard</h2>
                <span class="text-xs bg-white/5 px-2 py-1 rounded text-slate-400">Overview Panel</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Member Number Card -->
                <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-6 flex items-center justify-between shadow-xl">
                    <div>
                        <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">All Members</p>
                        <h3 class="text-3xl font-bold text-emerald-400 mt-1">
                            <?= intval($memberships['all_members'] ?? 0) ?>
                        </h3>
                    </div>
                    <div class="p-3 bg-emerald-500/10 rounded-lg text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                </div>

                <!-- Expired Membership Number Card -->
                <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-6 flex items-center justify-between shadow-xl">
                    <div>
                        <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">Expired Memberships</p>
                        <h3 class="text-3xl font-bold text-rose-400 mt-1">
                            <?= intval($memberships['expired'] ?? 0) ?>
                        </h3>
                    </div>
                    <div class="p-3 bg-rose-500/10 rounded-lg text-rose-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                </div>

                <!-- Go To Expired Card -->
                <a href="#expired-table-section" class="bg-white/5 backdrop-blur-md border border-dashed border-white/20 rounded-xl p-6 shadow-xl flex items-center justify-between hover:bg-white/10 hover:border-amber-400/50 transition-all group cursor-pointer">
                    <div>
                        <p class="text-sm font-medium text-slate-400 uppercase tracking-wider group-hover:text-amber-400 transition-all">Action Directory</p>
                        <h3 class="text-xl font-bold text-white mt-1">Go to Expired List</h3>
                    </div>
                    <div class="p-3 bg-amber-500/10 rounded-lg text-amber-400 group-hover:bg-amber-500 group-hover:text-slate-900 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                    </div>
                </a>
            </div>
        </section>
    <!-- MAIN BODY SECTION -->
    <main class="max-w-7xl w-full mx-auto p-6 flex-grow space-y-12 my-6">

        <!-- SECTION 1: RENDERING INTERFACE LOG LAYER -->
        <section id="add-request-form" class="scroll-mt-24 text-white max-w-4xl mx-auto p-4">
            
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold tracking-wide border-l-4 border-amber-400 pl-3">Submit Support & Maintenance Request</h2>
                <span class="text-xs bg-white/5 px-2 py-1 rounded text-slate-400">IT & Maintenance Logs</span>
            </div>

            <!-- Feedback Notification Banners -->
            <?php if (!empty($success_alert)): ?>
                <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-400 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($success_alert); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_alert)): ?>
                <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-400 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($error_alert); ?></p>
                </div>
            <?php endif; ?>

            <div class="w-full bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-6 md:p-8 shadow-2xl">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <input type="hidden" name="dispatch_request" value="1">
                    
                    <!-- Full Name -->
                    <div class="space-y-1.5">
                        <label for="full_name" class="text-xs font-semibold uppercase tracking-wider text-slate-300">Full Name</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Staff or Guest Name" required
                            class="w-full bg-slate-950/40 border border-white/10 rounded-lg py-2.5 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all text-sm">
                    </div>

                    <!-- DYNAMIC DEPARTMENT SELECT DROPDOWN -->
                    <div class="space-y-1.5">
                        <label for="department_code" class="text-xs font-semibold uppercase tracking-wider text-slate-300">Department / Division</label>
                        <div class="relative">
                            <select id="department_code" name="department_code" required class="w-full bg-slate-950/70 border border-white/10 rounded-lg py-2.5 px-4 text-white focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all text-sm appearance-none cursor-pointer">
                                <option value="" disabled selected>Select department...</option>
                                <?php if (!empty($departments_list)): ?>
                                    <?php foreach ($departments_list as $dept): ?>
                                        <!-- Storing the department_code integer to honor database schema mapping -->
                                        <option value="<?php echo intval($dept['department_code']); ?>">
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No departments configured in DB</option>
                                <?php endif; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Issue Category Dropdown -->
                    <div class="space-y-1.5">
                        <label for="issue_category" class="text-xs font-semibold uppercase tracking-wider text-slate-300">Issue Category</label>
                        <div class="relative">
                            <select id="issue_category" name="issue_category" required class="w-full bg-slate-950/70 border border-white/10 rounded-lg py-2.5 px-4 text-white focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all text-sm appearance-none">
                                <option value="" disabled selected>Select the issue nature...</option>
                                <option value="internet">Internet / Wi-Fi Network</option>
                                <option value="tv">Television / IPTV Services</option>
                                <option value="cnet">CNET System Management</option>
                                <option value="software">Software / OS Support</option>
                                <option value="other">Other Technical / Maintenance Issue</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Room / Location Number -->
                    <div class="space-y-1.5">
                        <label for="location_number" class="text-xs font-semibold uppercase tracking-wider text-slate-300">Room / Location Number</label>
                        <input type="text" id="location_number" name="location_number" placeholder="e.g., Room 304, Front Desk, Office 2" required
                            class="w-full bg-slate-950/40 border border-white/10 rounded-lg py-2.5 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all text-sm">
                    </div>

                    <!-- Urgency Level Selection -->
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300 block mb-2">Urgency Level</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="flex items-center gap-3 p-3 bg-slate-950/30 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5 transition-all">
                                <input type="radio" name="urgency" value="standard" checked class="accent-amber-400 h-4 w-4">
                                <div>
                                    <span class="text-sm font-semibold text-slate-200 block">Standard Priority</span>
                                    <span class="text-xs text-slate-400">Regular queue processing</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3 bg-slate-950/30 border border-white/10 rounded-lg cursor-pointer hover:bg-white/5 transition-all">
                                <input type="radio" name="urgency" value="urgent" class="accent-rose-500 h-4 w-4">
                                <div>
                                    <span class="text-sm font-semibold text-rose-400 block">Urgent Action Required</span>
                                    <span class="text-xs text-slate-400">Critical operational bottleneck</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Optional Description Area -->
                    <div class="space-y-1.5 md:col-span-2">
                        <label for="description" class="text-xs font-semibold uppercase tracking-wider text-slate-300">Brief Description of Issue</label>
                        <textarea id="description" name="description" rows="3" placeholder="Describe the problem details here..." 
                            class="w-full bg-slate-950/40 border border-white/10 rounded-lg py-2.5 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all text-sm resize-none"></textarea>
                    </div>

                    <!-- Form Action Button -->
                    <div class="md:col-span-2 pt-2 flex justify-end">
                        <button type="submit" 
                            class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-3 px-8 rounded-lg shadow-lg hover:shadow-amber-500/10 transition-all text-sm tracking-wide">
                            Dispatch Request
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- GYM MEMBERSHIP DASHBOARD -->
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold tracking-wide border-l-4 border-amber-400 pl-3">Gym Membership Dashboard</h2>
                <span class="text-xs bg-white/5 px-2 py-1 rounded text-slate-400">Overview Panel</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Member Number Card -->
                <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-6 flex items-center justify-between shadow-xl">
                    <div>
                        <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">All Members</p>
                        <h3 class="text-3xl font-bold text-emerald-400 mt-1">248</h3>
                    </div>
                    <div class="p-3 bg-emerald-500/10 rounded-lg text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                </div>

                <!-- Expired Membership Number Card -->
                <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-xl p-6 flex items-center justify-between shadow-xl">
                    <div>
                        <p class="text-sm font-medium text-slate-400 uppercase tracking-wider">Expired Memberships</p>
                        <h3 class="text-3xl font-bold text-rose-400 mt-1">34</h3>
                    </div>
                    <div class="p-3 bg-rose-500/10 rounded-lg text-rose-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                </div>

                <!-- Go To Expired Card -->
                <a href="#expired-table-section" class="bg-white/5 backdrop-blur-md border border-dashed border-white/20 rounded-xl p-6 shadow-xl flex items-center justify-between hover:bg-white/10 hover:border-amber-400/50 transition-all group cursor-pointer">
                    <div>
                        <p class="text-sm font-medium text-slate-400 uppercase tracking-wider group-hover:text-amber-400 transition-all">Action Directory</p>
                        <h3 class="text-xl font-bold text-white mt-1">Go to Expired List</h3>
                    </div>
                    <div class="p-3 bg-amber-500/10 rounded-lg text-amber-400 group-hover:bg-amber-500 group-hover:text-slate-900 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                    </div>
                </a>
            </div>
        </section>

        <!-- SECTION 3: GUEST & VISITOR FEEDBACK SECTION -->
        <section id="guest-feedback-form" class="scroll-mt-24">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold tracking-wide border-l-4 border-amber-400 pl-3">Guest Experience & Feedback</h2>
                <span class="text-xs bg-white/5 px-2 py-1 rounded text-slate-400">Quality Assurance</span>
            </div>

            <div class="w-full bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl p-6 md:p-8 shadow-2xl">
                <form class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Guest Name or Anonymous Toggle combo -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300">Name / Room Number</label>
                        <input type="text" placeholder="John Doe or Room 204 (Optional)" 
                            class="w-full bg-slate-950/40 border border-white/10 rounded-lg py-2.5 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all text-sm">
                    </div>

                    <!-- Service Evaluated -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300">Service Evaluated</label>
                        <select required class="w-full bg-slate-950/70 border border-white/10 rounded-lg py-2.5 px-4 text-white focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all text-sm appearance-none">
                            <option value="" disabled selected>Which service did you use?</option>
                            <option value="hotel">Hotel Stay & Accommodation</option>
                            <option value="spa">Spa & Wellness Center</option>
                            <option value="gym">Gym & Fitness Services</option>
                            <option value="dining">Restaurant & Bar Experience</option>
                            <option value="staff">Staff Hospitality Overall</option>
                        </select>
                    </div>

                    <!-- Rating System Selector (Visual CSS Stars mapping) -->
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300 block mb-1">Overall Rating</label>
                        <div class="flex items-center gap-2 bg-slate-950/30 border border-white/10 p-3 rounded-lg w-fit">
                            <span class="text-sm text-slate-400 mr-2">Select Score:</span>
                            <div class="flex flex-row-reverse justify-end gap-1">
                                <input type="radio" id="star5" name="rating" value="5" class="peer hidden" />
                                <label for="star5" class="text-slate-600 peer-hover:text-amber-400 peer-checked:text-amber-400 cursor-pointer text-2xl transition-all">★</label>
                                
                                <input type="radio" id="star4" name="rating" value="4" class="peer hidden" />
                                <label for="star4" class="text-slate-600 peer-hover:text-amber-400 peer-checked:text-amber-400 cursor-pointer text-2xl transition-all">★</label>
                                
                                <input type="radio" id="star3" name="rating" value="3" class="peer hidden" />
                                <label for="star3" class="text-slate-600 peer-hover:text-amber-400 peer-checked:text-amber-400 cursor-pointer text-2xl transition-all">★</label>
                                
                                <input type="radio" id="star2" name="rating" value="2" class="peer hidden" />
                                <label for="star2" class="text-slate-600 peer-hover:text-amber-400 peer-checked:text-amber-400 cursor-pointer text-2xl transition-all">★</label>
                                
                                <input type="radio" id="star1" name="rating" value="1" class="peer hidden" />
                                <label for="star1" class="text-slate-600 peer-hover:text-amber-400 peer-checked:text-amber-400 cursor-pointer text-2xl transition-all">★</label>
                            </div>
                        </div>
                    </div>

                    <!-- Feedback Comments -->
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-300">Your Review & Suggestions</label>
                        <textarea rows="4" placeholder="Let us know what went well or how we can improve our services..." required
                            class="w-full bg-slate-950/40 border border-white/10 rounded-lg py-2.5 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all text-sm resize-none"></textarea>
                    </div>

                    <!-- Feedback Submit Button -->
                    <div class="md:col-span-2 pt-2 flex justify-end">
                        <button type="submit" 
                            class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold py-3 px-8 rounded-lg shadow-lg hover:shadow-emerald-500/10 transition-all text-sm tracking-wide">
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </section>
        
    </main>

    <!-- FOOTER SECTION -->
    <footer class="w-full bg-slate-950/40 backdrop-blur-md border-t border-white/5 py-8 px-6 mt-12 text-sm text-slate-400">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6">
            
            <!-- Technical Developer Signature -->
            <div class="space-y-1 text-center md:text-left">
                <p class="text-xs text-slate-500 uppercase tracking-wider font-bold">System Developer</p>
                <p class="text-base font-semibold text-slate-200">Yared Habtamu</p>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 text-xs text-slate-400 pt-1">
                    <a href="mailto:yaredtadele09@gmail.com" class="hover:text-amber-400 transition-all flex items-center gap-1 justify-center md:justify-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        yaredtadele09@gmail.com
                    </a>
                    <a href="tel:0940144639" class="hover:text-amber-400 transition-all flex items-center gap-1 justify-center md:justify-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                        0940144639
                    </a>
                </div>
            </div>

            <!-- Copyright Notice -->
            <div class="text-center md:text-right text-xs text-slate-500">
                <p>&copy; 2026 Elgel Hotel and Spa. All rights reserved.</p>
                <p class="mt-0.5">Internal IT Administration Platform</p>
            </div>
        </div>
    </footer>

</body>
</html>