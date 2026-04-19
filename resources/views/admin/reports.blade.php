@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Str;

    // 1. Capture Filter Parameters (Defaults to current month/year)
    $selectedMonth = request('month', date('m'));
    $selectedYear = request('year', date('Y'));
    
    // Calculate Previous Month for Trend Analysis
    $currentDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
    $previousDate = $currentDate->copy()->subMonth();
    $prevMonth = $previousDate->format('m');
    $prevYear = $previousDate->format('Y');

    // Initialize Data Structures
    $kpis = [
        'employees' => ['current' => 0, 'prev' => 0, 'trend' => 0],
        'active_jobs' => ['current' => 0, 'prev' => 0, 'trend' => 0],
        'applicants' => ['current' => 0, 'prev' => 0, 'trend' => 0],
        'pending_appraisals' => ['current' => 0, 'prev' => 0, 'trend' => 0],
        'trainings' => ['current' => 0, 'prev' => 0, 'trend' => 0],
        'onboarding' => ['current' => 0, 'prev' => 0, 'trend' => 0],
    ];

    $chartData = [
        'appraisal_labels' => ['Excellent (>4.5)', 'Good (3.5-4.4)', 'Average (2.5-3.4)', 'Poor (<2.5)'],
        'appraisal_data' => [0, 0, 0, 0],
        'training_labels' => [],
        'training_data' => [],
        'recruitment_labels' => ['Applied', 'Reviewing', 'Interview', 'Hired', 'Rejected'],
        'recruitment_data' => [0, 0, 0, 0, 0],
        'onboarding_data' => [0, 0, 0] 
    ];

    // Detailed Report Tables Data
    $departmentStats = [];
    $detailedJobs = [];
    $detailedTrainings = [];
    $detailedOnboarding = [];

    // Helper function to calculate percentage trend
    $calculateTrend = function($current, $previous) {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    };

    try {
        // --- 1. WORKFORCE & DEPARTMENTS ---
        if (class_exists('\App\Models\Employee')) {
            $kpis['employees']['current'] = \App\Models\Employee::whereYear('hire_date', '<=', $selectedYear)->whereMonth('hire_date', '<=', $selectedMonth)->count();
            $kpis['employees']['prev'] = \App\Models\Employee::whereYear('hire_date', '<=', $prevYear)->whereMonth('hire_date', '<=', $prevMonth)->count();
            $kpis['employees']['trend'] = $calculateTrend($kpis['employees']['current'], $kpis['employees']['prev']);

            // Department Breakdown Table
            if (Schema::hasTable('departments')) {
                $departmentStats = DB::table('departments')
                    ->leftJoin('employees', 'departments.department_id', '=', 'employees.department_id')
                    ->select('departments.department_name', DB::raw('COUNT(employees.employee_id) as headcount'))
                    ->groupBy('departments.department_id', 'departments.department_name')
                    ->orderByDesc('headcount')
                    ->get();
            }
        }

        // --- 2. RECRUITMENT & JOBS ---
        if (class_exists('\App\Models\JobPost')) {
            $kpis['active_jobs']['current'] = \App\Models\JobPost::where('job_status', 'Open')->whereYear('created_at', '<=', $selectedYear)->whereMonth('created_at', '<=', $selectedMonth)->count();
            $kpis['active_jobs']['prev'] = \App\Models\JobPost::where('job_status', 'Open')->whereYear('created_at', '<=', $prevYear)->whereMonth('created_at', '<=', $prevMonth)->count();
            $kpis['active_jobs']['trend'] = $calculateTrend($kpis['active_jobs']['current'], $kpis['active_jobs']['prev']);

            // Detailed Jobs Table
            $detailedJobs = \App\Models\JobPost::where('job_status', 'Open')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        // --- 3. APPLICANTS ---
        if (Schema::hasTable('applications')) {
            $kpis['applicants']['current'] = DB::table('applications')->whereYear('created_at', $selectedYear)->whereMonth('created_at', $selectedMonth)->count();
            $kpis['applicants']['prev'] = DB::table('applications')->whereYear('created_at', $prevYear)->whereMonth('created_at', $prevMonth)->count();
            $kpis['applicants']['trend'] = $calculateTrend($kpis['applicants']['current'], $kpis['applicants']['prev']);

            $apps = DB::table('applications')->whereYear('created_at', $selectedYear)->whereMonth('created_at', $selectedMonth)
                ->select('app_stage', DB::raw('count(*) as count'))->groupBy('app_stage')->pluck('count', 'app_stage')->toArray();

            $chartData['recruitment_data'] = [
                $apps['Applied'] ?? 0, $apps['Reviewing'] ?? 0, $apps['Interview'] ?? 0, $apps['Hired'] ?? 0, $apps['Rejected'] ?? 0,
            ];
        }

        // --- 4. APPRAISALS ---
        if (class_exists('\App\Models\Appraisal')) {
            $kpis['pending_appraisals']['current'] = \App\Models\Appraisal::where('status', 'pending_manager')->whereYear('created_at', $selectedYear)->whereMonth('created_at', $selectedMonth)->count();
            $kpis['pending_appraisals']['prev'] = \App\Models\Appraisal::where('status', 'pending_manager')->whereYear('created_at', $prevYear)->whereMonth('created_at', $prevMonth)->count();
            $kpis['pending_appraisals']['trend'] = $calculateTrend($kpis['pending_appraisals']['current'], $kpis['pending_appraisals']['prev']);
            
            $appraisals = \App\Models\Appraisal::where('status', 'completed')->whereYear('created_at', $selectedYear)->whereMonth('created_at', $selectedMonth)->get();

            $chartData['appraisal_data'] = [
                $appraisals->where('overall_score', '>=', 4.5)->count(),
                $appraisals->whereBetween('overall_score', [3.5, 4.49])->count(),
                $appraisals->whereBetween('overall_score', [2.5, 3.49])->count(),
                $appraisals->where('overall_score', '<', 2.5)->count(),
            ];
        }

        // --- 5. TRAINING ---
        if (class_exists('\App\Models\TrainingProgram')) {
            $kpis['trainings']['current'] = \App\Models\TrainingProgram::whereYear('start_date', $selectedYear)->whereMonth('start_date', $selectedMonth)->count();
            $kpis['trainings']['prev'] = \App\Models\TrainingProgram::whereYear('start_date', $prevYear)->whereMonth('start_date', $prevMonth)->count();
            $kpis['trainings']['trend'] = $calculateTrend($kpis['trainings']['current'], $kpis['trainings']['prev']);

            $trainings = \App\Models\TrainingProgram::withCount(['enrollments'])
                ->whereYear('start_date', $selectedYear)->whereMonth('start_date', $selectedMonth)
                ->orderBy('start_date', 'asc')
                ->get();

            $detailedTrainings = $trainings->take(5);

            foreach($trainings->sortByDesc('enrollments_count')->take(5) as $t) {
                $chartData['training_labels'][] = Str::limit($t->training_name, 15);
                $chartData['training_data'][] = $t->enrollments_count;
            }
        }

        // --- 6. ONBOARDING ---
        if (class_exists('\App\Models\Onboarding')) {
            $kpis['onboarding']['current'] = \App\Models\Onboarding::whereYear('start_date', $selectedYear)->whereMonth('start_date', $selectedMonth)->count();
            $kpis['onboarding']['prev'] = \App\Models\Onboarding::whereYear('start_date', $prevYear)->whereMonth('start_date', $prevMonth)->count();
            $kpis['onboarding']['trend'] = $calculateTrend($kpis['onboarding']['current'], $kpis['onboarding']['prev']);

            $chartData['onboarding_data'] = [
                \App\Models\Onboarding::where('status', 'completed')->whereYear('start_date', $selectedYear)->whereMonth('start_date', $selectedMonth)->count(),
                \App\Models\Onboarding::where('status', 'in_progress')->whereYear('start_date', $selectedYear)->whereMonth('start_date', $selectedMonth)->count(),
                \App\Models\Onboarding::where('status', 'pending')->whereYear('start_date', $selectedYear)->whereMonth('start_date', $selectedMonth)->count(),
            ];

            // Detail Table Data
            $usersPk = Schema::hasColumn('users', 'user_id') ? 'user_id' : 'id';
            $detailedOnboarding = DB::table('onboarding as o')
                ->join('employees as e', 'o.employee_id', '=', 'e.employee_id')
                ->join('users as u', 'e.user_id', '=', "u.{$usersPk}")
                ->leftJoin('departments as d', 'e.department_id', '=', 'd.department_id')
                ->select('o.onboarding_id', 'o.start_date', 'o.end_date', 'o.status', 'u.name as employee_name', 'd.department_name')
                ->whereYear('o.start_date', $selectedYear)
                ->whereMonth('o.start_date', $selectedMonth)
                ->orderBy('o.start_date', 'desc')
                ->take(5)
                ->get();
        }
    } catch(\Throwable $e) {
        Log::error("Detailed Report Error: " . $e->getMessage());
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detailed HR Report - {{ date("F Y", mktime(0, 0, 0, $selectedMonth, 10)) }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* CRM / Report Styling */
    body { background: #f4f7f9; font-family: 'Inter', sans-serif; margin: 0; padding: 0; color: #334155; }
    .main-content { padding: 30px 40px; max-width: 1600px; margin: 0 auto; }

    /* Report Header & Toolbar */
    .report-header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
    .report-title-section h1 { font-size: 26px; font-weight: 700; color: #0f172a; margin: 0 0 5px 0; letter-spacing: -0.5px; }
    .report-title-section p { margin: 0; font-size: 14px; color: #64748b; font-weight: 500; }
    
    .toolbar-actions { display: flex; align-items: center; gap: 12px; background: #fff; padding: 10px 15px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .date-picker-mock { border: none; background: transparent; font-size: 14px; color: #0f172a; font-weight: 600; outline: none; cursor: pointer; }
    .toolbar-divider { width: 1px; height: 24px; background: #e2e8f0; margin: 0 5px; }
    .btn-export { background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(37,99,235,0.2); }
    .btn-export:hover { background: #1d4ed8; transform: translateY(-1px); }

    /* Detailed KPI Grid */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #2563eb; border-radius: 12px 0 0 12px; }
    .kpi-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .kpi-label { font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
    .kpi-icon { width: 32px; height: 32px; background: #f1f5f9; color: #475569; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
    .kpi-value { font-size: 32px; font-weight: 700; color: #0f172a; line-height: 1; margin-bottom: 10px; }
    
    .trend-indicator { font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; }
    .trend-up { background: #dcfce7; color: #166534; }
    .trend-down { background: #fee2e2; color: #991b1b; }
    .trend-neutral { background: #f1f5f9; color: #475569; }

    /* Report Sections & Data Grids */
    .report-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; }
    .section-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
    .section-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px; }
    .section-title i { color: #2563eb; }
    
    .section-body { display: grid; grid-template-columns: 1fr 2fr; gap: 0; }
    .chart-container { padding: 24px; border-right: 1px solid #f1f5f9; position: relative; min-height: 300px; }
    .table-container { padding: 0; overflow-x: auto; }
    
    @media (max-width: 1100px) {
        .section-body { grid-template-columns: 1fr; }
        .chart-container { border-right: none; border-bottom: 1px solid #f1f5f9; }
    }

    /* Dense Report Tables */
    .data-table { width: 100%; border-collapse: collapse; text-align: left; }
    .data-table th { padding: 12px 20px; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; }
    .data-table td { padding: 14px 20px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: #f8fafc; }
    
    /* NEW: Clickable Report Links */
    .report-link { color: #2563eb; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
    .report-link:hover { color: #1d4ed8; text-decoration: underline; }
    .report-link i { font-size: 11px; opacity: 0.7; }
    
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
    .status-open { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
    .status-active { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .status-planned { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }

    /* Print Styles */
    @media print {
        body { background: #fff; }
        .container { display: block; }
        .sidebar, header, .toolbar-actions { display: none !important; }
        .main-content { padding: 0; max-width: 100%; margin: 0; }
        .report-section { box-shadow: none; border: 1px solid #ccc; page-break-inside: avoid; }
        .kpi-card { border: 1px solid #ccc; box-shadow: none; break-inside: avoid; }
        .section-body { grid-template-columns: 1fr; } 
        .chart-container { border-right: none; border-bottom: 1px solid #ccc; height: 250px; }
        .data-table th { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; }
        .report-link { color: #0f172a; text-decoration: none !important; } /* Remove link styles for printing */
        .report-link i { display: none; } /* Hide the popout icon when printing */
    }
  </style>
</head>
<body>
  
  <header class="no-print">
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="color:inherit; text-decoration:none;">
        <i class="fa-regular fa-user"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
      </a>
    </div>
  </header>

  <div class="container dashboard-shell">
    @include('admin.layout.sidebar')

    <main class="main-content">
      
      {{-- Report Header --}}
      <div class="report-header">
        <div class="report-title-section">
            <h1>Comprehensive HR & Workforce Report</h1>
            <p>Generated on {{ date('F d, Y \a\t H:i A') }} • Reporting Period: <strong>{{ date("F Y", mktime(0, 0, 0, $selectedMonth, 10)) }}</strong></p>
        </div>
        <div class="toolbar-actions">
            <form action="{{ request()->url() }}" method="GET" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                <i class="fa-regular fa-calendar" style="color: #64748b;"></i>
                <select name="month" class="date-picker-mock" onchange="this.form.submit()">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ sprintf('%02d', $m) }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                            {{ date("M", mktime(0, 0, 0, $m, 10)) }}
                        </option>
                    @endforeach
                </select>
                <select name="year" class="date-picker-mock" onchange="this.form.submit()">
                    @foreach(range(date('Y'), date('Y') - 5) as $y)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <div class="toolbar-divider"></div>
            <button type="button" class="btn-export" onclick="window.print()">
                <i class="fa-solid fa-file-pdf"></i> Export Report
            </button>
        </div>
      </div>

      {{-- Executive Summary (KPIs with Trends) --}}
      <div class="kpi-grid">
          @foreach([
              ['label' => 'Total Workforce', 'key' => 'employees', 'icon' => 'fa-users', 'color' => '#2563eb'],
              ['label' => 'Active Job Openings', 'key' => 'active_jobs', 'icon' => 'fa-briefcase', 'color' => '#8b5cf6'],
              ['label' => 'Total Applicants', 'key' => 'applicants', 'icon' => 'fa-file-lines', 'color' => '#10b981'],
              ['label' => 'Pending Appraisals', 'key' => 'pending_appraisals', 'icon' => 'fa-star-half-stroke', 'color' => '#f59e0b'],
              ['label' => 'Training Programs', 'key' => 'trainings', 'icon' => 'fa-graduation-cap', 'color' => '#0ea5e9'],
              ['label' => 'Active Onboarding', 'key' => 'onboarding', 'icon' => 'fa-person-walking-luggage', 'color' => '#ef4444'],
          ] as $metric)
          <div class="kpi-card" style="--kpi-color: {{ $metric['color'] }};">
              <style>.kpi-card:nth-child({{ $loop->iteration }})::before { background: {{ $metric['color'] }}; }</style>
              <div class="kpi-header">
                  <h3 class="kpi-label">{{ $metric['label'] }}</h3>
                  <div class="kpi-icon"><i class="fa-solid {{ $metric['icon'] }}"></i></div>
              </div>
              <div class="kpi-value">{{ number_format($kpis[$metric['key']]['current']) }}</div>
              
              @php
                  $trend = $kpis[$metric['key']]['trend'];
                  $trendClass = $trend > 0 ? 'trend-up' : ($trend < 0 ? 'trend-down' : 'trend-neutral');
                  $trendIcon = $trend > 0 ? 'fa-arrow-trend-up' : ($trend < 0 ? 'fa-arrow-trend-down' : 'fa-minus');
              @endphp
              <div class="trend-indicator {{ $trendClass }}">
                  <i class="fa-solid {{ $trendIcon }}"></i> 
                  {{ abs($trend) }}% vs Prev Month
              </div>
          </div>
          @endforeach
      </div>

      {{-- Section 1: Recruitment & Talent Acquisition --}}
      <div class="report-section">
          <div class="section-header">
              <h2 class="section-title"><i class="fa-solid fa-crosshairs"></i> Recruitment & Talent Acquisition Analysis</h2>
          </div>
          <div class="section-body">
              <div class="chart-container">
                  <canvas id="recruitmentChart"></canvas>
              </div>
              <div class="table-container">
                  <table class="data-table">
                      <thead>
                          <tr>
                              <th>Job Title</th>
                              <th>Department</th>
                              <th>Type</th>
                              <th>Status</th>
                          </tr>
                      </thead>
                      <tbody>
                          @forelse($detailedJobs as $job)
                          <tr>
                              <td>
                                  {{-- DRILL-DOWN LINK: Clicking this goes to the specific Job details --}}
                                  <a href="{{ url('/admin/recruitment/jobs/' . $job->job_id) }}" class="report-link" title="View Job Details">
                                      {{ $job->job_title }} <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                  </a>
                                  <br><span style="font-size:11px; color:#94a3b8;">Posted: {{ $job->created_at->format('d M Y') }}</span>
                              </td>
                              <td>{{ $job->department }}</td>
                              <td>{{ $job->job_type }}</td>
                              <td><span class="status-badge status-open">Open</span></td>
                          </tr>
                          @empty
                          <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">No open positions for this period.</td></tr>
                          @endforelse
                      </tbody>
                  </table>
              </div>
          </div>
      </div>

      {{-- Section 2: Workforce & Performance --}}
      <div class="report-section">
          <div class="section-header">
              <h2 class="section-title"><i class="fa-solid fa-chart-pie"></i> Department Headcount & Appraisal Distribution</h2>
          </div>
          <div class="section-body">
              <div class="chart-container">
                  <canvas id="appraisalChart"></canvas>
              </div>
              <div class="table-container">
                  <table class="data-table">
                      <thead>
                          <tr>
                              <th>Department Name</th>
                              <th style="text-align: center;">Total Headcount</th>
                              <th style="text-align: right;">% of Workforce</th>
                          </tr>
                      </thead>
                      <tbody>
                          @php $totalEmps = $kpis['employees']['current'] ?: 1; @endphp
                          @forelse($departmentStats as $dept)
                          <tr>
                              <td>
                                  {{-- DRILL-DOWN LINK: Filter the employee list by this department --}}
                                  <a href="{{ url('/admin/employees?department=' . urlencode($dept->department_name)) }}" class="report-link" title="View Employees in Department">
                                      {{ $dept->department_name }} <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                  </a>
                              </td>
                              <td style="text-align: center;">{{ $dept->headcount }}</td>
                              <td style="text-align: right;">{{ round(($dept->headcount / $totalEmps) * 100, 1) }}%</td>
                          </tr>
                          @empty
                          <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 30px;">Department breakdown data unavailable.</td></tr>
                          @endforelse
                      </tbody>
                  </table>
              </div>
          </div>
      </div>

      {{-- Section 3: Training & Development --}}
      <div class="report-section">
          <div class="section-header">
              <h2 class="section-title"><i class="fa-solid fa-chalkboard-user"></i> Training & Learning Operations</h2>
          </div>
          <div class="section-body">
              <div class="chart-container">
                  <canvas id="trainingChart"></canvas>
              </div>
              <div class="table-container">
                  <table class="data-table">
                      <thead>
                          <tr>
                              <th>Program Name</th>
                              <th>Dates</th>
                              <th style="text-align: center;">Mode</th>
                              <th style="text-align: right;">Enrolled</th>
                          </tr>
                      </thead>
                      <tbody>
                          @forelse($detailedTrainings as $training)
                          <tr>
                              <td>
                                  {{-- DRILL-DOWN LINK: Route directly to the Training Show page --}}
                                  <a href="{{ route('admin.training.show', $training->training_id) }}" class="report-link" title="View Training Details">
                                      {{ $training->training_name }} <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                  </a>
                                  <br><span style="font-size:11px; color:#94a3b8;">{{ $training->provider }}</span>
                              </td>
                              <td style="font-size: 12px;">{{ \Carbon\Carbon::parse($training->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($training->end_date)->format('d M Y') }}</td>
                              <td style="text-align: center;">
                                  <span class="status-badge {{ $training->mode == 'Online' ? 'status-open' : 'status-active' }}">{{ $training->mode }}</span>
                              </td>
                              <td style="text-align: right; font-weight: 600;">
                                  {{ $training->enrollments_count }}
                                  @if($training->max_participants) / {{ $training->max_participants }} @endif
                              </td>
                          </tr>
                          @empty
                          <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">No training programs scheduled for this period.</td></tr>
                          @endforelse
                      </tbody>
                  </table>
              </div>
          </div>
      </div>

      {{-- Section 4: Employee Onboarding --}}
      <div class="report-section">
          <div class="section-header">
              <h2 class="section-title"><i class="fa-solid fa-person-walking-luggage"></i> Employee Onboarding Status</h2>
          </div>
          <div class="section-body">
              <div class="chart-container">
                  <canvas id="onboardingChart"></canvas>
              </div>
              <div class="table-container">
                  <table class="data-table">
                      <thead>
                          <tr>
                              <th>Employee Name</th>
                              <th>Department</th>
                              <th>Schedule</th>
                              <th>Status</th>
                          </tr>
                      </thead>
                      <tbody>
                          @forelse($detailedOnboarding as $onb)
                          <tr>
                              <td>
                                  {{-- DRILL-DOWN LINK: Route to this specific employee's onboarding page --}}
                                  <a href="{{ url('/admin/onboarding/' . $onb->onboarding_id) }}" class="report-link" title="View Onboarding Details">
                                      {{ $onb->employee_name }} <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                  </a>
                              </td>
                              <td>{{ $onb->department_name ?? 'N/A' }}</td>
                              <td style="font-size: 12px;">{{ \Carbon\Carbon::parse($onb->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($onb->end_date)->format('d M Y') }}</td>
                              <td>
                                  @if($onb->status == 'completed')
                                      <span class="status-badge status-active">Completed</span>
                                  @elseif($onb->status == 'in_progress')
                                      <span class="status-badge status-open">In Progress</span>
                                  @else
                                      <span class="status-badge status-planned">Pending</span>
                                  @endif
                              </td>
                          </tr>
                          @empty
                          <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">No onboarding sessions scheduled for this period.</td></tr>
                          @endforelse
                      </tbody>
                  </table>
              </div>
          </div>
      </div>

    </main>
  </div>

  {{-- Safely Load Data For Charts --}}
  <script id="chartDataJson" type="application/json">
    {!! json_encode($chartData) !!}
  </script>

  <script>
    let chartData = {};
    try {
        chartData = JSON.parse(document.getElementById('chartDataJson').textContent);
    } catch (e) {
        console.error('Could not parse chart data', e);
        // Fallback empty data structure to prevent charting errors
        chartData = {
            recruitment_labels: [], recruitment_data: [],
            appraisal_labels: [], appraisal_data: [],
            training_labels: [], training_data: [],
            onboarding_data: []
        };
    }

    // Global Chart Formatting
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.scale.grid.color = '#f1f5f9';
    Chart.defaults.plugins.tooltip.backgroundColor = '#0f172a';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.titleFont = { size: 13, weight: 'bold' };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };

    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Recruitment Pipeline (Funnel-like Bar Chart)
        new Chart(document.getElementById('recruitmentChart'), {
            type: 'bar',
            data: {
                labels: chartData.recruitment_labels,
                datasets: [{
                    label: 'Applicants at Stage',
                    data: chartData.recruitment_data,
                    backgroundColor: ['#e2e8f0', '#cbd5e1', '#94a3b8', '#10b981', '#ef4444'], 
                    borderRadius: 6,
                    barPercentage: 0.7
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false },
                    title: { display: true, text: 'Applicant Conversion Pipeline', padding: {bottom: 20}, font: {size: 14} }
                },
                scales: { y: { beginAtZero: true, border: { display: false } }, x: { grid: { display: false } } }
            }
        });

        // 2. Appraisal Scores (Line Chart)
        new Chart(document.getElementById('appraisalChart'), {
            type: 'line',
            data: {
                labels: chartData.appraisal_labels,
                datasets: [{
                    label: 'Employees',
                    data: chartData.appraisal_data,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false },
                    title: { display: true, text: 'Performance Curve', padding: {bottom: 20}, font: {size: 14} }
                },
                scales: { y: { beginAtZero: true, border: { display: false } }, x: { grid: { display: false } } }
            }
        });

        // 3. Top Training Programs (Horizontal Bar)
        new Chart(document.getElementById('trainingChart'), {
            type: 'bar',
            data: {
                labels: chartData.training_labels.length > 0 ? chartData.training_labels : ['No Data'],
                datasets: [{
                    label: 'Total Enrollments',
                    data: chartData.training_data.length > 0 ? chartData.training_data : [0],
                    backgroundColor: '#0ea5e9',
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: { 
                indexAxis: 'y', // Horizontal bar for long names
                responsive: true, maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false },
                    title: { display: true, text: 'Most Popular Training Programs', padding: {bottom: 20}, font: {size: 14} }
                },
                scales: { x: { beginAtZero: true, border: { display: false } }, y: { grid: { display: false } } }
            }
        });

        // 4. Onboarding Status (Doughnut Chart)
        new Chart(document.getElementById('onboardingChart'), {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Pending'],
                datasets: [{
                    data: chartData.onboarding_data,
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'], // Green, Yellow, Red
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                cutout: '75%',
                plugins: { 
                    legend: { position: 'right', labels: { usePointStyle: true, padding: 20 } },
                    title: { display: true, text: 'Onboarding Task Completion', padding: {bottom: 20}, font: {size: 14} }
                }
            }
        });
    });
  </script>
</body>
</html>