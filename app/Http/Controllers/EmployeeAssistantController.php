<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class EmployeeAssistantController extends Controller
{
    public function chat(Request $request)
    {
        set_time_limit(300);

        $userInput = trim((string) $request->input('message', ''));
        $history = $request->input('history', []);
        $today = date('Y-m-d'); 

        if ($userInput === '') {
            return response()->json(['reply' => 'Please type a question.'], 400);
        }

        // ==========================================
        // SECURITY CHECK: Employee & Supervisor Access
        // ==========================================
        $user = auth()->user();
        $role = $user->role ?? null;
        
        // Allow both employee and supervisor roles to use this controller
        if (!in_array($role, ['employee', 'supervisor'])) {
            return response()->json(['reply' => 'Forbidden: Access denied.'], 403);
        }

        $employeeRecord = DB::table('employees')->where('user_id', $user->user_id)->first();

        if (!$employeeRecord) {
            return response()->json(['reply' => 'Error: We could not find your employee profile.'], 400);
        }

        $myEmployeeId = $employeeRecord->employee_id;
        $myDepartmentId = $employeeRecord->department_id; 
        $usersPk = Schema::hasColumn('users', 'user_id') ? 'user_id' : 'id';

        // ==========================================
        // DYNAMIC ROLE-BASED TOOLS & RULES
        // ==========================================
        $supervisorToolsText = "";
        $securityRules = "";
        $roleName = strtoupper($role);

        if ($role === 'supervisor') {
            $supervisorToolsText = <<<SUP

10) Team Leave Requests:
{"action":"tool","tool":"team_leave_requests","args":{"status":"pending|approved|rejected (optional)", "employee_name":"optional name"}}

11) Team KPI Summary:
{"action":"tool","tool":"team_kpi_summary","args":{"employee_name":"optional name"}}

12) Team Onboarding Status:
{"action":"tool","tool":"team_onboarding_status","args":{"employee_name":"optional name"}}

13) List My Team Members (who reports to me):
{"action":"tool","tool":"team_members"}
SUP;
            $securityRules = "- PRIVACY SHIELD (SUPERVISOR): You can query your OWN personal data using the 'My' tools. You can ALSO query data for your TEAM MEMBERS (people in your specific department) using the 'Team' tools. If asked about an employee in another department, politely decline and state you only have access to your own department.
- WHO IS ON MY TEAM: If the supervisor asks 'who is under my supervision', 'who is on my team', 'list my staff', 'who reports to me', or similar — use the 'team_members' tool (#13) to list them.";
        } else {
            $securityRules = "- PRIVACY SHIELD (EMPLOYEE): You are a SELF-SERVICE assistant. If the user asks about another employee's private data (leave, KPIs, salary, etc.), DO NOT use a tool. Politely decline and state you only have access to their personal data.";
        }

        // ==============================================================================
        // THE MASTER SYSTEM PROMPT
        // ==============================================================================
        $system = <<<SYS
You are an HR Assistant for a {$roleName} user. Today's Date is: {$today}.

You handle TWO distinct types of questions:
1. INTERNAL (My Data & Company DB) Queries: Questions about personal data, company FAQs, internal jobs, and (if applicable) team data. For these, you MUST use a tool to fetch data.
2. EXTERNAL (General Knowledge) Queries: Questions about career advice, workplace tips, general HR definitions, or drafting professional emails. For these, DO NOT use a tool. Answer directly using your own knowledge.

If you need info from the database (INTERNAL), respond ONLY with RAW valid JSON (no markdown, no conversational text before or after), exactly like:

1) My Leave Balance:
{"action":"tool","tool":"my_leave_balance"}

2) My Leave Requests:
{"action":"tool","tool":"my_leave_requests","args":{"status":"pending|approved|rejected (optional)"}}

3) My KPI Progress:
{"action":"tool","tool":"my_kpi_summary"}

4) My Onboarding Tasks:
{"action":"tool","tool":"my_onboarding_tasks"}

5) FAQ / policy:
{"action":"tool","tool":"search_faqs","args":{"query":"..."}}

6) Company Job Posts (Internal Mobility):
{"action":"tool","tool":"search_job_posts","args":{"keyword":"optional text"}}

7) My Enrolled Trainings:
{"action":"tool","tool":"my_training"}

8) Available Trainings (Open for Enrollment):
{"action":"tool","tool":"available_training"}

9) My Announcements:
{"action":"tool","tool":"my_announcements"}{$supervisorToolsText}

IMPORTANT SECURITY & ROUTING RULES:
{$securityRules}
- EXTERNAL ADVICE: If the user asks "How do I improve my time management?", "What is a KPI?", or "Help me draft a sick leave email", DO NOT output JSON. Output a professional, direct answer in Markdown.
- INTERNAL DATA: If the user asks about their own data, announcements, available trainings, or their team, output ONLY the RAW JSON tool call.
- NEVER invent tool names. Only use the tools listed above (numbered 1-9, plus 10-13 if you are serving a supervisor). If no tool fits the request, answer directly from knowledge.

Final answer rules:
- After receiving a TOOL_RESULT, return ONLY plain text or markdown formatting. No JSON.
- CURRENCY: Always use "RM " (Ringgit Malaysia). NEVER use the "$" symbol.
SYS;

        $messages = [
            ["role" => "system", "content" => $system],
        ];

        if (is_array($history)) {
            foreach ($history as $h) {
                if (isset($h['role']) && isset($h['content'])) {
                    $messages[] = [
                        "role" => $h['role'] === 'user' ? 'user' : 'assistant',
                        "content" => (string)$h['content']
                    ];
                }
            }
        }

        $messages[] = ["role" => "user", "content" => $userInput];

        // ==========================================
        // HARDCODED PRE-AI SECURITY CHECK
        // ==========================================
        // Only regular employees are blocked from mentioning other names. Supervisors bypass this.
        if ($role === 'employee') {
            if (preg_match('/\b(harley|hakim|powderin|admin|manager|supervisor|colleague|team|other)\b/i', $userInput) && !preg_match('/\b(my|i|me)\b/i', $userInput)) {
                 return response()->json(['reply' => 'For security and privacy reasons, I can only provide information regarding your own personal employee profile. I cannot look up data for other employees or teams.']);
            }
        }

        $first = $this->ollamaChat($messages);
        $reply = $first['message']['content'] ?? '';

        $toolCall = $this->tryParseToolJson($reply);

        // ==========================================
        // SECURE TOOL IMPLEMENTATIONS
        // ==========================================
        
        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'my_leave_balance') {
            $leaveTypes = DB::table('leave_types')->get();
            $balances = [];

            foreach ($leaveTypes as $lt) {
                $usedDays = DB::table('leave_requests')
                    ->where('employee_id', $myEmployeeId)
                    ->where('leave_type_id', $lt->leave_type_id)
                    ->where('leave_status', 'approved')
                    ->sum('total_days');
                
                $balances[] = [
                    'leave_type' => $lt->leave_name, 
                    'used_days' => $usedDays, 
                    'remaining_balance' => $lt->default_days_year - $usedDays
                ];
            }

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT my_leave_balance:\n" . json_encode(["my_balances" => $balances]) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'my_leave_requests') {
            $status = trim((string) ($toolCall['args']['status'] ?? ''));

            $q = DB::table('leave_requests as lr')
                ->join('leave_types as lt', 'lr.leave_type_id', '=', 'lt.leave_type_id')
                ->where('lr.employee_id', $myEmployeeId)
                ->select('lt.leave_name', 'lr.start_date', 'lr.end_date', 'lr.total_days', 'lr.leave_status', 'lr.reason');

            if ($status !== '' && in_array(strtolower($status), ['pending', 'approved', 'rejected'])) {
                $q->where('lr.leave_status', strtolower($status));
            }

            $rows = $q->orderBy('lr.start_date', 'desc')->limit(5)->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT my_leave_requests:\n" . $rows->toJson() . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'my_kpi_summary') {
            $kpis = DB::table('employee_kpis as ek')
                ->join('kpi_templates as kt', 'ek.kpi_id', '=', 'kt.kpi_id')
                ->where('ek.employee_id', $myEmployeeId)
                ->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT my_kpi_summary:\n" . $kpis->toJson() . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'my_onboarding_tasks') {
            $onboarding = DB::table('onboarding')
                ->where('employee_id', $myEmployeeId)
                ->orderBy('start_date', 'desc')
                ->first();

            if (!$onboarding) {
                return response()->json(['reply' => "You do not have any active onboarding records."]);
            }

            $tasks = DB::table('onboarding_task')
                ->select('task_name', 'is_completed', 'completed_at', 'category', 'due_date')
                ->where('onboarding_id', $onboarding->onboarding_id)
                ->get();

            $payload = ["onboarding_status" => $onboarding->status, "tasks" => $tasks];
            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT my_onboarding_tasks:\n" . json_encode($payload) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'search_faqs') {
            $query = trim((string) ($toolCall['args']['query'] ?? ''));
            if ($query === '') $query = $userInput;

            $rows = DB::table('faqs')->select('question', 'answer')
                ->where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('question', 'like', "%{$query}%")->orWhere('answer', 'like', "%{$query}%");
                })->limit(3)->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT search_faqs:\n" . $rows->toJson() . "\n\nReturn ONLY clear text."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'search_job_posts') {
            $keyword = trim((string) ($toolCall['args']['keyword'] ?? ''));

            $q = DB::table('job_posts')
                ->select('job_title', 'job_type', 'department', 'salary_range', 'requirements')
                ->where('job_status', 'Open'); 

            if ($keyword !== '') $q->where('job_title', 'like', "%{$keyword}%");
            $rows = $q->limit(5)->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT search_job_posts:\n" . $rows->toJson() . "\n\nReturn ONLY clear text."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'my_training') {
            $trainings = DB::table('training_enrollments as te')
                ->join('training_programs as tp', 'te.training_id', '=', 'tp.training_id')
                ->where('te.employee_id', $myEmployeeId)
                ->select('tp.training_name', 'tp.start_date', 'tp.end_date', 'tp.mode', 'te.completion_status', 'te.remarks')
                ->orderBy('tp.start_date', 'desc')
                ->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT my_training:\n" . $trainings->toJson() . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'available_training') {
            $enrolledIds = DB::table('training_enrollments')
                ->where('employee_id', $myEmployeeId)
                ->pluck('training_id')
                ->toArray();
            
            $available = DB::table('training_programs')
                ->whereNotIn('training_id', $enrolledIds)
                ->where('approval_status', 'Approved')
                ->where('end_date', '>=', $today)
                ->select('training_name', 'start_date', 'end_date', 'provider', 'mode', 'location')
                ->orderBy('start_date', 'asc')
                ->limit(5)
                ->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT available_training:\n" . $available->toJson() . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'my_announcements') {
            $announcements = DB::table('announcements')
                ->where('publish_at', '<=', now())
                ->where(function ($query) use ($myDepartmentId) {
                    $query->whereIn('audience_type', ['All', 'Everyone', 'Company', 'General', 'All Employees'])
                          ->orWhereNull('audience_type')
                          ->orWhere('department_id', $myDepartmentId);
                })
                ->orderByRaw("FIELD(priority, 'Critical', 'Urgent', 'High', 'Normal')") 
                ->orderBy('publish_at', 'desc')
                ->select('title', 'priority', 'content', 'publish_at')
                ->limit(5)
                ->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT my_announcements:\n" . $announcements->toJson() . "\n\nReturn ONLY clear text with markdown. Mention the priority."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        // ==========================================
        // SUPERVISOR EXCLUSIVE TOOLS (Protected via SQL AND PHP logic)
        // ==========================================
        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'team_leave_requests') {
            if ($role !== 'supervisor') return response()->json(['reply' => 'Access Denied. Only supervisors can use this tool.']);
            
            $status = trim((string) ($toolCall['args']['status'] ?? ''));
            $empName = trim((string) ($toolCall['args']['employee_name'] ?? ''));

            $q = DB::table('leave_requests as lr')
                ->join('employees as e', 'lr.employee_id', '=', 'e.employee_id')
                ->join('users as u', "u.{$usersPk}", '=', 'e.user_id')
                ->join('leave_types as lt', 'lr.leave_type_id', '=', 'lt.leave_type_id')
                ->where('e.department_id', $myDepartmentId) // STRICT SECURITY BOUNDARY
                ->select('u.name', 'lt.leave_name', 'lr.start_date', 'lr.end_date', 'lr.total_days', 'lr.leave_status', 'lr.reason');

            if ($status !== '') $q->where('lr.leave_status', strtolower($status));
            if ($empName !== '') $q->where('u.name', 'like', "%{$empName}%");

            $rows = $q->orderBy('lr.start_date', 'desc')->limit(10)->get();

            if ($rows->count() === 0 && $empName !== '') {
                $payload = ["error" => "No leave records found. Either the employee is not in your department, or they don't have leave requests matching your criteria."];
            } else {
                $payload = $rows;
            }

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT team_leave_requests:\n" . json_encode($payload) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'team_kpi_summary') {
            if ($role !== 'supervisor') return response()->json(['reply' => 'Access Denied.']);
            
            $empName = trim((string) ($toolCall['args']['employee_name'] ?? ''));

            $q = DB::table('employee_kpis as ek')
                ->join('employees as e', 'ek.employee_id', '=', 'e.employee_id')
                ->join('users as u', "u.{$usersPk}", '=', 'e.user_id')
                ->join('kpi_templates as kt', 'ek.kpi_id', '=', 'kt.kpi_id')
                ->where('e.department_id', $myDepartmentId) // STRICT SECURITY BOUNDARY
                ->select('u.name', 'kt.kpi_name', 'ek.score', 'ek.evaluation_date', 'ek.remarks');

            if ($empName !== '') $q->where('u.name', 'like', "%{$empName}%");

            $rows = $q->orderBy('ek.evaluation_date', 'desc')->limit(10)->get();

            if ($rows->count() === 0 && $empName !== '') {
                $payload = ["error" => "No KPI records found. Ensure this employee belongs to your department."];
            } else {
                $payload = $rows;
            }

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT team_kpi_summary:\n" . json_encode($payload) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'team_onboarding_status') {
            if ($role !== 'supervisor') return response()->json(['reply' => 'Access Denied.']);
            
            $empName = trim((string) ($toolCall['args']['employee_name'] ?? ''));

            $q = DB::table('onboarding as o')
                ->join('employees as e', 'o.employee_id', '=', 'e.employee_id')
                ->join('users as u', "u.{$usersPk}", '=', 'e.user_id')
                ->where('e.department_id', $myDepartmentId) // STRICT SECURITY BOUNDARY
                ->select('o.onboarding_id', 'u.name', 'o.status', 'o.start_date');

            if ($empName !== '') $q->where('u.name', 'like', "%{$empName}%");

            $records = $q->orderBy('o.start_date', 'desc')->limit(10)->get();
            $payload = [];

            if ($records->count() === 1) {
                // If they searched a specific employee, fetch their detailed tasks
                $tasks = DB::table('onboarding_task')->where('onboarding_id', $records[0]->onboarding_id)->select('task_name', 'is_completed')->get();
                $payload = ["employee" => $records[0]->name, "status" => $records[0]->status, "tasks" => $tasks];
            } else if ($records->count() === 0 && $empName !== '') {
                $payload = ["error" => "No onboarding records found. Ensure this employee belongs to your department."];
            } else {
                $payload = $records;
            }

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT team_onboarding_status:\n" . json_encode($payload) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        // ==========================================
        // TEAM MEMBERS (Who's in my department)
        // ==========================================
        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'team_members') {
            if ($role !== 'supervisor') {
                return response()->json(['reply' => 'Access Denied. Only supervisors can view their team.']);
            }

            if (!$myDepartmentId) {
                return response()->json(['reply' => 'You are not assigned to any department, so I cannot list your team members.']);
            }

            // Build query — use leftJoin for positions/departments in case those tables don't exist or the employee lacks them
            $query = DB::table('employees as e')
                ->join('users as u', "u.{$usersPk}", '=', 'e.user_id')
                ->where('e.department_id', $myDepartmentId)
                ->where('u.role', 'employee') // Only regular staff, exclude other supervisors/admins
                ->where('e.employee_id', '!=', $myEmployeeId); // Exclude the supervisor themselves

            // Only select position/department if those tables exist in the schema
            if (Schema::hasTable('positions')) {
                $query->leftJoin('positions as p', 'e.position_id', '=', 'p.position_id');
            }
            if (Schema::hasTable('departments')) {
                $query->leftJoin('departments as d', 'e.department_id', '=', 'd.department_id');
            }

            $selectFields = ['u.name', 'u.email', 'e.employee_id'];
            if (Schema::hasTable('positions')) $selectFields[] = 'p.position_name';
            if (Schema::hasTable('departments')) $selectFields[] = 'd.department_name';

            $team = $query->select($selectFields)->orderBy('u.name', 'asc')->get();

            if ($team->count() === 0) {
                $payload = ["message" => "You currently have no team members under your supervision in your department."];
            } else {
                $payload = [
                    "total_members" => $team->count(),
                    "department" => $team->first()->department_name ?? 'Your Department',
                    "members" => $team
                ];
            }

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT team_members:\n" . json_encode($payload) . "\n\nReturn ONLY clear text with markdown. Present the team members in a nicely formatted list with their name, position (if available), and email."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        // =========================================================
        // EXTERNAL / GENERAL KNOWLEDGE RESPONSE
        // =========================================================
        return response()->json(['reply' => $this->normalizeFinalReply($reply)]);
    }

    private function isOffTopicQuery(string $text): bool
    {
        $t = strtolower($text);

        // Clearly off-topic keywords — anime, pop culture, entertainment, unrelated topics
        $offTopicPatterns = [
            // Anime / manga / fictional characters
            '/\b(gojo|satoru|naruto|goku|luffy|itachi|sasuke|anime|manga|otaku|waifu|jujutsu kaisen|one piece|dragon ball|attack on titan|demon slayer)\b/i',
            // Movies / TV / celebrities
            '/\b(harry potter|marvel|disney|netflix|movie|film|celebrity|actor|actress|tiktok|instagram)\b/i',
            // Games
            '/\b(minecraft|fortnite|valorant|genshin|pokemon|mobile legends|dota|league of legends|video game)\b/i',
            // Sports / weather / news
            '/\b(football|soccer|basketball|f1|formula 1|weather today|news today|election|politics|president|prime minister)\b/i',
            // Random trivia / cooking / travel
            '/\b(recipe|cooking|how to cook|travel to|flight to|booking hotel|restaurant recommendation)\b/i',
            // Math / homework / programming unrelated
            '/\b(solve this equation|integrate|differentiate|write python|write javascript|my homework)\b/i',
            // Personal life
            '/\b(girlfriend|boyfriend|dating|relationship advice|love advice)\b/i',
        ];

        foreach ($offTopicPatterns as $pattern) {
            if (preg_match($pattern, $t)) {
                return true;
            }
        }

        return false;
    }

    private function ollamaChat(array $messages): array
{
    try {
        $res = Http::timeout(180)
            ->withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.3,
                'stream' => false,
            ]);

        if (!$res->ok()) {
            $errorBody = $res->json();
            $errorMsg = $errorBody['error']['message'] ?? 'Unknown error';
            return ['message' => ['content' => 'OpenAI API error: ' . $errorMsg]];
        }

        $data = $res->json();

        // Convert OpenAI format → Ollama format so the rest of your code works unchanged
        $content = $data['choices'][0]['message']['content'] ?? 'No response.';
        return ['message' => ['content' => $content]];

    } catch (\Exception $e) {
        return ['message' => ['content' => 'AI Connection Error: ' . $e->getMessage()]];
    }
}

    private function tryParseToolJson(string $text): ?array
    {
        $clean = trim(preg_replace('/<think>.*?<\/think>/is', '', $text));
        
        // Using \x60 to safely escape backticks so the markdown block does not prematurely end
        $clean = trim(preg_replace('/\A\x60\x60\x60(?:json)?\s*/i', '', $clean));
        $clean = trim(preg_replace('/\s*\x60\x60\x60\z/', '', $clean));

        $start = strpos($clean, '{');
        $end   = strrpos($clean, '}');
        if ($start === false || $end === false) return null;

        $arr = json_decode(substr($clean, $start, $end - $start + 1), true);
        return is_array($arr) ? $arr : null;
    }

    private function normalizeFinalReply(string $text): string
    {
        $clean = trim(preg_replace('/<think>.*?<\/think>/is', '', $text));
        
        // Using \x60 to safely escape backticks so the markdown block does not prematurely end
        $clean = trim(preg_replace('/\A\x60\x60\x60(?:json)?\s*/i', '', $clean));
        $clean = trim(preg_replace('/\s*\x60\x60\x60\z/', '', $clean));

        $decoded = json_decode($clean, true);
        if (is_array($decoded) && isset($decoded['reply'])) return $decoded['reply'];
        return $clean;
    }
}