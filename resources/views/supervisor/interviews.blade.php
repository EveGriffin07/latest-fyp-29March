<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Interviews - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    .interview-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; }
    .card-header { background: #f8fafc; padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title { margin: 0; font-size: 16px; color: #0f172a; font-weight: 600; display: flex; align-items: center; gap: 10px; }

    .status-pill { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-pending { background: #fef08a; color: #854d0e; }
    .status-accepted { background: #e0f2fe; color: #0369a1; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .status-evaluated { background: #dcfce7; color: #166534; }

    .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
    .info-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .info-label { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
    .info-value { font-size: 14px; color: #0f172a; font-weight: 600; }

    .section-title { margin: 0 0 12px 0; font-size: 14px; color: #0f172a; font-weight: 600; display: flex; align-items: center; gap: 8px; }

    .skill-tag { display: inline-block; background: #eff6ff; color: #1e40af; padding: 5px 12px; border-radius: 15px; font-size: 12px; margin: 3px; font-weight: 500; }
    .lang-tag { display: inline-block; background: #f0fdf4; color: #166534; padding: 5px 12px; border-radius: 15px; font-size: 12px; margin: 3px; font-weight: 500; }

    .exp-item, .edu-item { background: #f8fafc; padding: 12px 15px; border-radius: 8px; border-left: 3px solid #2563eb; margin-bottom: 8px; }
    .edu-item { border-left-color: #16a34a; }

    .textarea-custom { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 14px; outline: none; resize: vertical; margin-bottom: 15px; }
    .input-custom { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 14px; outline: none; }
    .input-custom:focus, .textarea-custom:focus { border-color: #2563eb; }

    .btn { padding: 10px 20px; font-size: 14px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    .btn-accept { background: #16a34a; color: #fff; }
    .btn-accept:hover { background: #15803d; }
    .btn-reject { background: #dc2626; color: #fff; }
    .btn-reject:hover { background: #b91c1c; }
    .btn-evaluate { background: #2563eb; color: #fff; }
    .btn-evaluate:hover { background: #1d4ed8; }

    .action-bar { display: flex; gap: 10px; justify-content: flex-end; padding-top: 15px; border-top: 1px solid #f1f5f9; }

    details > summary { cursor: pointer; color: #2563eb; font-weight: 600; font-size: 13px; padding: 8px 0; list-style: none; }
    details > summary::before { content: "▶ "; font-size: 10px; }
    details[open] > summary::before { content: "▼ "; }
  </style>
</head>
<body>
<header>
  <div class="title">Web-Based HRMS</div>
  <div class="user-info">
      <i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('supervisor.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name }}</a>
  </div>
</header>

<div class="container dashboard-shell">
  @include('employee.layout.sidebar')

  <main style="flex:1; padding:28px 32px; max-width:100%;">

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; padding:15px 20px; border-radius:8px; margin-bottom:20px; border:1px solid #bbf7d0; font-weight: 500;"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; color:#991b1b; padding:15px 20px; border-radius:8px; margin-bottom:20px; border:1px solid #fecaca; font-weight: 500;"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif

    <div style="margin-bottom: 30px;">
        <h2 style="margin:0; font-size:28px; color:#0f172a;">My Interviews</h2>
        <p style="color: #64748b; margin-top: 5px; font-size: 15px;">Review scheduled interviews, accept or reject the schedule, and submit your evaluation.</p>
    </div>

    @forelse($interviews as $interview)
        @php
            $applicant = $interview->applicant;
            $skills = $applicant->skills ?? collect();
            $experiences = $applicant->experiences ?? collect();
            $educations = $applicant->educations ?? collect();
            $languages = $applicant->languages ?? collect();
            $status = $interview->interviewer_status ?? 'Pending';
        @endphp

        <div class="interview-card">
            {{-- HEADER --}}
            <div class="card-header">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($applicant->full_name ?? 'Applicant') }}&background=E0F2FE&color=0F172A" style="width: 45px; height: 45px; border-radius: 50%;">
                    <div>
                        <h3 class="card-title">{{ $applicant->full_name ?? 'Unknown Applicant' }}</h3>
                        <span style="font-size: 13px; color: #64748b;">
                            Applying for: <strong>{{ $interview->job->title ?? $interview->job->job_title ?? 'N/A' }}</strong>
                        </span>
                    </div>
                </div>

                @if($status == 'Accepted')
                    <span class="status-pill status-accepted"><i class="fa-solid fa-check"></i> Accepted</span>
                @elseif($status == 'Rejected')
                    <span class="status-pill status-rejected"><i class="fa-solid fa-xmark"></i> Rejected</span>
                @elseif($status == 'Evaluated')
                    <span class="status-pill status-evaluated"><i class="fa-solid fa-clipboard-check"></i> Evaluated</span>
                @else
                    <span class="status-pill status-pending"><i class="fa-regular fa-clock"></i> Awaiting Your Response</span>
                @endif
            </div>

            {{-- BODY --}}
            <div style="padding: 25px;">

                {{-- Interview Schedule Info --}}
                <div class="info-grid">
                    <div class="info-box">
                        <div class="info-label"><i class="fa-regular fa-calendar"></i> Interview Date</div>
                        <div class="info-value">
                            {{ $interview->interview_datetime ? \Carbon\Carbon::parse($interview->interview_datetime)->format('d M Y') : '—' }}
                        </div>
                    </div>
                    <div class="info-box">
                        <div class="info-label"><i class="fa-regular fa-clock"></i> Time</div>
                        <div class="info-value">
                            {{ $interview->interview_datetime ? \Carbon\Carbon::parse($interview->interview_datetime)->format('h:i A') : '—' }}
                        </div>
                    </div>
                    <div class="info-box">
                        <div class="info-label"><i class="fa-solid fa-location-dot"></i> Location</div>
                        <div class="info-value">{{ $interview->interview_location ?? 'TBA' }}</div>
                    </div>
                </div>

                {{-- Contact Info --}}
                <div style="margin-bottom: 20px; padding: 12px 15px; background: #f8fafc; border-radius: 8px; display: flex; gap: 20px; flex-wrap: wrap; font-size: 13px; color: #475569;">
                    <span><i class="fa-regular fa-envelope"></i> {{ $applicant->email ?? '—' }}</span>
                    <span><i class="fa-solid fa-phone"></i> {{ $applicant->phone ?? '—' }}</span>
                    @if($applicant->linkedin_url)
                        <a href="{{ $applicant->linkedin_url }}" target="_blank" style="color: #2563eb; text-decoration: none;"><i class="fa-brands fa-linkedin"></i> LinkedIn</a>
                    @endif
                    @if($applicant->resume_path)
                        <a href="{{ asset('storage/' . $applicant->resume_path) }}" target="_blank" style="color: #dc2626; text-decoration: none;"><i class="fa-solid fa-file-pdf"></i> View Resume</a>
                    @endif
                </div>

                {{-- Personal Summary --}}
                @if($applicant->personal_summary)
                    <div style="margin-bottom: 20px;">
                        <h4 class="section-title"><i class="fa-solid fa-user"></i> Personal Summary</h4>
                        <p style="color: #475569; font-size: 14px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin: 0; font-style: italic;">"{{ $applicant->personal_summary }}"</p>
                    </div>
                @endif

                {{-- Skills --}}
                @if($skills->count() > 0)
                    <div style="margin-bottom: 20px;">
                        <h4 class="section-title"><i class="fa-solid fa-laptop-code"></i> Skills ({{ $skills->count() }})</h4>
                        <div>
                            @foreach($skills as $skill)
                                <span class="skill-tag">{{ $skill->skill_name }} <span style="opacity:0.7;">• {{ $skill->proficiency }}</span></span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Languages --}}
                @if($languages->count() > 0)
                    <div style="margin-bottom: 20px;">
                        <h4 class="section-title"><i class="fa-solid fa-language"></i> Languages</h4>
                        <div>
                            @foreach($languages as $lang)
                                <span class="lang-tag">{{ $lang->language_name }} <span style="opacity:0.7;">• {{ $lang->proficiency }}</span></span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Experience & Education (Collapsible) --}}
                @if($experiences->count() > 0 || $educations->count() > 0)
                    <details style="margin-bottom: 20px;">
                        <summary>View Full Background ({{ $experiences->count() }} experience, {{ $educations->count() }} education)</summary>

                        <div style="padding-top: 12px;">
                            {{-- Experience --}}
                            @if($experiences->count() > 0)
                                <div style="margin-bottom: 20px;">
                                    <h4 class="section-title"><i class="fa-solid fa-briefcase"></i> Experience</h4>
                                    @foreach($experiences as $exp)
                                        <div class="exp-item">
                                            <div style="font-weight: 600; color: #0f172a; font-size: 14px;">{{ $exp->job_title }}</div>
                                            <div style="color: #475569; font-size: 13px;">{{ $exp->company_name }}</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 2px;">
                                                {{ \Carbon\Carbon::parse($exp->start_date)->format('M Y') }} –
                                                {{ $exp->is_current ? 'Present' : ($exp->end_date ? \Carbon\Carbon::parse($exp->end_date)->format('M Y') : 'N/A') }}
                                            </div>
                                            @if($exp->description)
                                                <p style="color: #475569; font-size: 13px; margin: 6px 0 0 0;">{{ $exp->description }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Education --}}
                            @if($educations->count() > 0)
                                <div>
                                    <h4 class="section-title"><i class="fa-solid fa-graduation-cap"></i> Education</h4>
                                    @foreach($educations as $edu)
                                        <div class="edu-item">
                                            <div style="font-weight: 600; color: #0f172a; font-size: 14px;">
                                                {{ $edu->degree_title }}
                                                @if($edu->field_of_study)
                                                    <span style="font-weight: normal; color: #64748b;">({{ $edu->field_of_study }})</span>
                                                @endif
                                            </div>
                                            <div style="color: #475569; font-size: 13px;">{{ $edu->institution_name }}</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 2px;">
                                                {{ $edu->start_date ? \Carbon\Carbon::parse($edu->start_date)->format('Y') : '' }} –
                                                {{ $edu->is_current ? 'Present' : ($edu->end_date ? \Carbon\Carbon::parse($edu->end_date)->format('Y') : 'N/A') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </details>
                @endif

                {{-- ==================== ACTIONS BY STATUS ==================== --}}

                {{-- SCENARIO 1: Pending — Accept or Reject Schedule --}}
                @if($status == 'Pending' || !$interview->interviewer_status)
                    <div style="background: #fefce8; border: 1px solid #fde68a; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <strong style="color: #854d0e; font-size: 14px;"><i class="fa-solid fa-triangle-exclamation"></i> Please respond to this interview schedule.</strong>
                        <p style="margin: 5px 0 0 0; color: #92400e; font-size: 13px;">Accept if you're available, or reject with a reason if HR needs to reschedule.</p>
                    </div>

                    <div class="action-bar">
                        <form action="{{ route('manager.interviews.reject', $interview->application_id) }}" method="POST" style="display: flex; gap: 10px; align-items: center;" onsubmit="return confirmReject(this)">
                            @csrf
                            <input type="text" name="remarks" placeholder="Reason for rejection (optional)" class="input-custom" style="width: 280px;">
                            <button type="submit" class="btn btn-reject"><i class="fa-solid fa-xmark"></i> Reject</button>
                        </form>
                        <form action="{{ route('manager.interviews.accept', $interview->application_id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-accept"><i class="fa-solid fa-check"></i> Accept Schedule</button>
                        </form>
                    </div>

                {{-- SCENARIO 2: Accepted — Show Evaluation Form --}}
                @elseif($status == 'Accepted')
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <strong style="color: #1e40af; font-size: 14px;"><i class="fa-solid fa-info-circle"></i> You accepted this interview. Submit your evaluation after the interview is done.</strong>
                        @if($interview->interviewer_remarks)
                            <p style="margin: 5px 0 0 0; color: #1e3a8a; font-size: 13px;"><em>Your note: "{{ $interview->interviewer_remarks }}"</em></p>
                        @endif
                    </div>

                    <form action="{{ route('manager.interviews.evaluate', $interview->application_id) }}" method="POST">
                        @csrf
                        <h4 class="section-title"><i class="fa-solid fa-clipboard-check"></i> Submit Technical Evaluation</h4>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="font-size: 13px; color: #475569; font-weight: 500; display: block; margin-bottom: 5px;">Score (0–100) <span style="color:#dc2626;">*</span></label>
                                <input type="number" name="supervisor_score" class="input-custom" min="0" max="100" step="0.01" required placeholder="e.g. 85">
                            </div>
                            <div>
                                <label style="font-size: 13px; color: #475569; font-weight: 500; display: block; margin-bottom: 5px;">Recommendation <span style="color:#dc2626;">*</span></label>
                                <select name="supervisor_recommendation" class="input-custom" required>
                                    <option value="">-- Select --</option>
                                    <option value="Hire">Hire</option>
                                    <option value="Shortlist">Shortlist</option>
                                    <option value="Reject">Reject</option>
                                </select>
                            </div>
                        </div>

                        <label style="font-size: 13px; color: #475569; font-weight: 500; display: block; margin-bottom: 5px;">Evaluation Notes <span style="color:#dc2626;">*</span></label>
                        <textarea name="supervisor_notes" class="textarea-custom" rows="4" placeholder="Share your technical assessment, strengths, areas of concern, and justification for your recommendation (min. 10 characters)..." required minlength="10"></textarea>

                        <div style="text-align: right;">
                            <button type="submit" class="btn btn-evaluate"><i class="fa-solid fa-paper-plane"></i> Submit Evaluation</button>
                        </div>
                    </form>

                {{-- SCENARIO 3: Rejected — Show Reason --}}
                @elseif($status == 'Rejected')
                    <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 15px; border-radius: 8px;">
                        <strong style="color: #991b1b; font-size: 14px;"><i class="fa-solid fa-xmark-circle"></i> You rejected this schedule.</strong>
                        <p style="margin: 5px 0 0 0; color: #7f1d1d; font-size: 13px;"><em>Reason: "{{ $interview->interviewer_remarks ?? 'No reason provided.' }}"</em></p>
                        <p style="margin: 8px 0 0 0; color: #7f1d1d; font-size: 13px;">HR has been notified to reschedule this interview.</p>
                    </div>

                {{-- SCENARIO 4: Evaluated — Show Results --}}
                @elseif($status == 'Evaluated')
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 8px;">
                        <strong style="color: #166534; font-size: 14px; display: block; margin-bottom: 12px;"><i class="fa-solid fa-circle-check"></i> Evaluation Submitted</strong>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="info-box" style="background:#fff;">
                                <div class="info-label">Score</div>
                                <div class="info-value" style="font-size: 22px; color: #16a34a;">{{ number_format($interview->supervisor_score ?? 0, 1) }}/100</div>
                            </div>
                            <div class="info-box" style="background:#fff;">
                                <div class="info-label">Recommendation</div>
                                <div class="info-value">{{ $interview->supervisor_recommendation ?? '—' }}</div>
                            </div>
                        </div>

                        <div>
                            <strong style="color: #0f172a; font-size: 13px;">Your Notes:</strong>
                            <p style="color: #475569; font-size: 14px; margin: 5px 0 0 0;">{{ $interview->supervisor_notes ?? '—' }}</p>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @empty
        <div style="background: white; border-radius: 16px; border: 1px dashed #cbd5e1; text-align: center; padding: 60px 20px;">
            <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;"><i class="fa-regular fa-calendar-xmark" style="font-size: 32px; color: #94a3b8;"></i></div>
            <h3 style="margin: 0 0 10px 0; color: #0f172a;">No Interviews Scheduled</h3>
            <p style="color:#64748b; margin: 0;">You have no interviews scheduled. Once HR assigns you as an interviewer, candidates will appear here.</p>
        </div>
    @endforelse

  </main>
</div>

<script>
    function confirmReject(form) {
        return confirm('Are you sure you want to reject this interview schedule?\n\nHR will be notified to reschedule.');
    }
</script>

</body>
</html>