<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appraisal Details - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    .back-link { display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 20px; transition: 0.2s; }
    .back-link:hover { color: #2563eb; }

    .section-container { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 25px; }
    .section-header { padding: 18px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
    .section-header h3 { margin: 0; font-size: 16px; color: #0f172a; display: flex; align-items: center; gap: 10px; font-weight: 600; }
    .section-body { padding: 25px; }

    /* Employee Profile Header */
    .profile-header { display: flex; align-items: center; gap: 20px; }
    .avatar-lg { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .profile-name { margin: 0 0 4px 0; font-size: 22px; color: #0f172a; font-weight: 700; }
    .profile-meta { color: #64748b; font-size: 14px; margin: 0; }

    /* Info Grid */
    .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px; }
    .info-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .info-label { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .info-value { font-size: 15px; color: #0f172a; font-weight: 600; }

    /* Score Summary */
    .score-summary { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; }
    .score-hero { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); padding: 30px; border-radius: 12px; color: #fff; text-align: center; display: flex; flex-direction: column; justify-content: center; }
    .score-hero-label { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; margin-bottom: 8px; }
    .score-hero-value { font-size: 48px; font-weight: 700; line-height: 1; margin: 10px 0; }
    .score-hero-max { font-size: 18px; opacity: 0.8; }
    .score-hero-label-rating { font-size: 14px; margin-top: 8px; opacity: 0.95; font-weight: 500; }

    /* Competency Grid */
    .competency-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .competency-card { background: #f8fafc; padding: 18px; border-radius: 10px; border: 1px solid #e2e8f0; }
    .competency-title { font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
    .competency-score-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .competency-score { font-size: 28px; font-weight: 700; color: #0f172a; }
    .competency-rating { font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 12px; }

    /* Progress Bar */
    .progress-bar { width: 100%; height: 8px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 10px; transition: width 0.3s; }

    /* Comments */
    .comment-box { background: #f8fafc; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #2563eb; }
    .comment-box.employee { border-left-color: #16a34a; }
    .comment-box.manager { border-left-color: #2563eb; }
    .comment-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .comment-avatar { width: 36px; height: 36px; border-radius: 50%; }
    .comment-author { font-weight: 600; color: #0f172a; font-size: 14px; }
    .comment-role { font-size: 12px; color: #64748b; }
    .comment-text { color: #475569; font-size: 14px; line-height: 1.6; margin: 0; font-style: italic; }

    /* Status Pill */
    .status-pill { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .status-completed { background: #dcfce7; color: #166534; }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-draft { background: #f1f5f9; color: #475569; }

    /* Color helpers */
    .bg-excellent { background: #16a34a; }
    .bg-good { background: #0ea5e9; }
    .bg-average { background: #f59e0b; }
    .bg-poor { background: #dc2626; }

    .rating-excellent { background: #dcfce7; color: #166534; }
    .rating-good { background: #e0f2fe; color: #0369a1; }
    .rating-average { background: #fef3c7; color: #b45309; }
    .rating-poor { background: #fee2e2; color: #b91c1c; }

    /* Responsive */
    @media (max-width: 768px) {
      .score-summary, .info-grid, .competency-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>

<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
      </a>
    </div>
  </header>

  <div class="container">
    @include('admin.layout.sidebar')

    <main>
      {{-- BACK LINK --}}
      <a href="{{ route('admin.appraisal') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Back to Appraisals
      </a>

      {{-- PAGE HEADER --}}
      <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:25px;">
        <div>
          <div class="breadcrumb" style="color: #64748b; font-size: 14px; margin-bottom: 5px;">
            Home > <a href="{{ route('admin.appraisal') }}" style="color: #64748b; text-decoration: none;">Performance</a> > <span style="color: #0f172a; font-weight: 500;">Details</span>
          </div>
          <h2 style="margin:0; font-size:28px; color:#0f172a;">Appraisal Details</h2>
          <p style="color: #64748b; margin-top: 5px; font-size: 15px;">Full breakdown of this performance review.</p>
        </div>

        <div>
          @if($appraisal->status == 'pending_self_eval')
            <span class="status-pill status-draft"><i class="fa-regular fa-clock"></i> Awaiting Employee</span>
          @elseif($appraisal->status == 'pending_manager')
            <span class="status-pill status-pending"><i class="fa-solid fa-hourglass-half"></i> Pending Manager Review</span>
          @else
            <span class="status-pill status-completed"><i class="fa-solid fa-circle-check"></i> Completed</span>
          @endif
        </div>
      </div>

      {{-- EMPLOYEE PROFILE SECTION --}}
      <div class="section-container">
        <div class="section-body">
          <div class="profile-header">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($appraisal->employee->user->name ?? 'User') }}&background=2563eb&color=fff&size=160" class="avatar-lg" alt="Avatar">
            <div style="flex: 1;">
              <h2 class="profile-name">{{ $appraisal->employee->user->name ?? 'Unknown Employee' }}</h2>
              <p class="profile-meta">
                <i class="fa-solid fa-briefcase"></i> {{ $appraisal->employee->position->position_name ?? 'N/A' }}
                &nbsp;·&nbsp;
                <i class="fa-regular fa-envelope"></i> {{ $appraisal->employee->user->email ?? '—' }}
              </p>
            </div>
          </div>

          <div class="info-grid">
            <div class="info-box">
              <div class="info-label"><i class="fa-regular fa-calendar"></i> Review Period</div>
              <div class="info-value">{{ $appraisal->review_period ?? '—' }}</div>
            </div>
            <div class="info-box">
              <div class="info-label"><i class="fa-solid fa-user-tie"></i> Evaluator</div>
              <div class="info-value">{{ $appraisal->evaluator->user->name ?? 'N/A' }}</div>
            </div>
            <div class="info-box">
              <div class="info-label"><i class="fa-regular fa-clock"></i> Last Updated</div>
              <div class="info-value">{{ $appraisal->updated_at ? $appraisal->updated_at->format('d M Y, h:i A') : '—' }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- SCORE SUMMARY (only if appraisal is completed) --}}
      @if($appraisal->status == 'completed' || $appraisal->overall_score)
        <div class="section-container">
          <div class="section-header">
            <h3><i class="fa-solid fa-chart-column" style="color: #2563eb;"></i> Performance Score Summary</h3>
          </div>
          <div class="section-body">
            <div class="score-summary">
              {{-- Overall Score Hero --}}
              <div class="score-hero">
                <div class="score-hero-label">Overall Score</div>
                <div class="score-hero-value">{{ number_format($appraisal->overall_score ?? 0, 1) }}<span class="score-hero-max">/5.0</span></div>
                <div class="score-hero-label-rating">
                  @php
                    $overall = $appraisal->overall_score ?? 0;
                    if ($overall >= 4.5) $ratingText = '⭐ Outstanding';
                    elseif ($overall >= 4.0) $ratingText = '👍 Excellent';
                    elseif ($overall >= 3.0) $ratingText = '✓ Satisfactory';
                    elseif ($overall >= 2.0) $ratingText = '⚠ Needs Improvement';
                    else $ratingText = '✗ Unacceptable';
                  @endphp
                  {{ $ratingText }}
                </div>
              </div>

              {{-- Competency Breakdown --}}
              <div class="competency-grid">
                @php
                  $competencies = [
                    ['label' => 'Attendance & Punctuality', 'score' => $appraisal->score_attendance ?? 0, 'icon' => 'fa-clock'],
                    ['label' => 'Teamwork & Collaboration', 'score' => $appraisal->score_teamwork ?? 0, 'icon' => 'fa-users'],
                    ['label' => 'Productivity & Quality', 'score' => $appraisal->score_productivity ?? 0, 'icon' => 'fa-chart-line'],
                    ['label' => 'Communication Skills', 'score' => $appraisal->score_communication ?? 0, 'icon' => 'fa-comments'],
                  ];
                @endphp

                @foreach($competencies as $comp)
                  @php
                    $pct = ($comp['score'] / 5) * 100;
                    if ($comp['score'] >= 4.0) { $barClass = 'bg-excellent'; $pillClass = 'rating-excellent'; $label = 'Excellent'; }
                    elseif ($comp['score'] >= 3.0) { $barClass = 'bg-good'; $pillClass = 'rating-good'; $label = 'Good'; }
                    elseif ($comp['score'] >= 2.0) { $barClass = 'bg-average'; $pillClass = 'rating-average'; $label = 'Average'; }
                    else { $barClass = 'bg-poor'; $pillClass = 'rating-poor'; $label = 'Poor'; }
                  @endphp
                  <div class="competency-card">
                    <div class="competency-title"><i class="fa-solid {{ $comp['icon'] }}"></i> {{ $comp['label'] }}</div>
                    <div class="competency-score-row">
                      <div class="competency-score">{{ number_format($comp['score'], 1) }}<span style="font-size: 14px; color: #94a3b8; font-weight: 500;">/5.0</span></div>
                      <span class="competency-rating {{ $pillClass }}">{{ $label }}</span>
                    </div>
                    <div class="progress-bar">
                      <div class="progress-fill {{ $barClass }}" style="width: {{ $pct }}%"></div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      @else
        {{-- IF NOT YET COMPLETED --}}
        <div class="section-container">
          <div class="section-body" style="text-align: center; padding: 50px 20px;">
            <i class="fa-regular fa-hourglass-half" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
            <h3 style="margin: 0 0 8px 0; color: #0f172a;">Scores Not Available Yet</h3>
            <p style="color: #64748b; margin: 0;">
              @if($appraisal->status == 'pending_self_eval')
                Waiting for the employee to submit their self-evaluation.
              @else
                Waiting for the manager to review and score.
              @endif
            </p>
          </div>
        </div>
      @endif

      {{-- COMMENTS SECTION --}}
      @if($appraisal->employee_comments || $appraisal->manager_comments)
        <div class="section-container">
          <div class="section-header">
            <h3><i class="fa-solid fa-comments" style="color: #2563eb;"></i> Feedback & Comments</h3>
          </div>
          <div class="section-body">
            <div style="display: flex; flex-direction: column; gap: 18px;">

              {{-- Employee Comment --}}
              @if($appraisal->employee_comments)
                <div class="comment-box employee">
                  <div class="comment-header">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($appraisal->employee->user->name ?? 'User') }}&background=16a34a&color=fff" class="comment-avatar">
                    <div>
                      <div class="comment-author">{{ $appraisal->employee->user->name ?? 'Employee' }}</div>
                      <div class="comment-role">Self-Reflection</div>
                    </div>
                  </div>
                  <p class="comment-text">"{{ $appraisal->employee_comments }}"</p>
                </div>
              @endif

              {{-- Manager Comment --}}
              @if($appraisal->manager_comments)
                <div class="comment-box manager">
                  <div class="comment-header">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($appraisal->evaluator->user->name ?? 'Manager') }}&background=2563eb&color=fff" class="comment-avatar">
                    <div>
                      <div class="comment-author">{{ $appraisal->evaluator->user->name ?? 'Manager' }}</div>
                      <div class="comment-role">Final Manager Feedback</div>
                    </div>
                  </div>
                  <p class="comment-text">"{{ $appraisal->manager_comments }}"</p>
                </div>
              @endif

            </div>
          </div>
        </div>
      @endif

      {{-- ACTION BAR --}}
      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px;">
        <a href="{{ route('admin.appraisal') }}" style="padding: 10px 20px; background: #f1f5f9; color: #475569; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 14px;">
          <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer;">
          <i class="fa-solid fa-print"></i> Print Report
        </button>
      </div>

      <footer style="margin-top: 40px;">© 2026 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>
</body>
</html>