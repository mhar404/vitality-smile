<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display the clinic automated reports dashboard.
     */
    public function index(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'dentist', 'staff', 'secretary']), 403);

        $datePreset = $request->input('date_preset', 'this_month');
        $tab = $request->input('tab', 'overview');
        $search = $request->input('search', '');

        // Resolve Date Range
        [$startDate, $endDate] = $this->resolveDateRange($datePreset, $request->input('date_from'), $request->input('date_to'));

        // 1. Executive Summary KPIs
        $summary = $this->computeExecutiveSummary($startDate, $endDate);

        // 2. Patient Analytics
        $patientAnalytics = $this->computePatientAnalytics($startDate, $endDate, $search);

        // 3. Treatment Analytics
        $treatmentAnalytics = $this->computeTreatmentAnalytics($startDate, $endDate, $search);

        // 4. Billing Analytics
        $billingAnalytics = $this->computeBillingAnalytics($startDate, $endDate, $search);

        // 5. Formal Tabular Reports (Patient, Appointment, Treatment, Revenue) with Weekly/Monthly Grouping
        $patientReport = $this->computePatientReport($startDate, $endDate, $search);
        $appointmentReport = $this->computeAppointmentReport($startDate, $endDate, $search);
        $treatmentReport = $this->computeTreatmentReport($startDate, $endDate, $search);
        $revenueReport = $this->computeRevenueReport($startDate, $endDate, $search);

        // 6. Automated Operational Insights
        $insights = $this->generateAutomatedInsights($summary, $patientAnalytics, $treatmentAnalytics, $billingAnalytics);

        return Inertia::render('Reports/Index', [
            'summary' => $summary,
            'patientAnalytics' => $patientAnalytics,
            'treatmentAnalytics' => $treatmentAnalytics,
            'billingAnalytics' => $billingAnalytics,
            'patientReport' => $patientReport,
            'appointmentReport' => $appointmentReport,
            'treatmentReport' => $treatmentReport,
            'revenueReport' => $revenueReport,
            'insights' => $insights,
            'filters' => [
                'date_preset' => $datePreset,
                'date_from' => $startDate->toDateString(),
                'date_to' => $endDate->toDateString(),
                'formatted_from' => $startDate->format('F d, Y'),
                'formatted_to' => $endDate->format('F d, Y'),
                'tab' => $tab,
                'search' => $search,
            ],
            'clinicInfo' => [
                'name' => 'Vitality Smile Dental Clinic',
                'tagline' => 'General Dentistry & Orthodontics',
                'address' => 'Unit 1 Threo Commercial Building B2 L3A Casan Residencia Subdivision Barangay Malitlit, Sta Rosa Laguna 4026',
                'phone' => '0977-328-1243',
                'email' => 'vitalitysmile2025@gmail.com',
                'dentist' => 'Dr. Valdez',
                'generated_at' => now()->format('F d, Y h:i A'),
                'generated_by' => auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')',
            ],
        ]);
    }

    /**
     * Export reports as CSV for documentation, offline analysis, and spreadsheets.
     */
    public function export(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'dentist', 'staff', 'secretary']), 403);

        $type = $request->input('type', 'summary');
        $datePreset = $request->input('date_preset', 'this_month');
        [$startDate, $endDate] = $this->resolveDateRange($datePreset, $request->input('date_from'), $request->input('date_to'));

        $filename = "vitality_smile_{$type}_report_" . now()->format('Ymd_His') . ".csv";

        return new StreamedResponse(function () use ($type, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for proper Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            switch ($type) {
                case 'patients':
                    $this->exportPatientsCsv($handle, $startDate, $endDate);
                    break;
                case 'appointments':
                    $this->exportAppointmentsCsv($handle, $startDate, $endDate);
                    break;
                case 'treatments':
                    $this->exportTreatmentsCsv($handle, $startDate, $endDate);
                    break;
                case 'revenue':
                    $this->exportRevenueCsv($handle, $startDate, $endDate);
                    break;
                case 'billing':
                    $this->exportBillingCsv($handle, $startDate, $endDate);
                    break;
                case 'summary':
                default:
                    $this->exportSummaryCsv($handle, $startDate, $endDate);
                    break;
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Resolve start and end dates based on selected preset.
     */
    protected function resolveDateRange(string $preset, ?string $from, ?string $to): array
    {
        $today = Carbon::today();

        switch ($preset) {
            case 'today':
                return [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
            case 'this_week':
                return [$today->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(), $today->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay()];
            case 'last_week':
                $lastWeek = $today->copy()->subWeek();
                return [$lastWeek->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(), $lastWeek->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay()];
            case 'this_month':
                return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
            case 'last_month':
                $lastMonth = $today->copy()->subMonth();
                return [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()];
            case 'this_quarter':
                return [$today->copy()->firstOfQuarter()->startOfDay(), $today->copy()->lastOfQuarter()->endOfDay()];
            case 'this_year':
                return [$today->copy()->startOfYear(), $today->copy()->endOfYear()];
            case 'all_time':
                return [Carbon::parse('2020-01-01')->startOfDay(), $today->copy()->endOfDay()];
            case 'custom':
                if ($from && $to) {
                    try {
                        return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
                    } catch (\Throwable $e) {
                        // Fallback
                    }
                }
                return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
            default:
                return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
        }
    }

    /**
     * Compute Executive Summary metrics across all clinic departments.
     */
    protected function computeExecutiveSummary(Carbon $start, Carbon $end): array
    {
        $totalPatients = Patient::where('status', '!=', 'Archived')->count();
        $newPatients = Patient::whereBetween('created_at', [$start, $end])->count();

        $appointmentsQuery = Appointment::whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()]);
        $totalAppointments = (clone $appointmentsQuery)->count();
        $completedAppointments = (clone $appointmentsQuery)->where('status', 'completed')->count();

        $treatmentsQuery = Treatment::whereBetween('treatment_date', [$start->toDateString(), $end->toDateString()]);
        $totalTreatments = (clone $treatmentsQuery)->count();
        $completedTreatments = (clone $treatmentsQuery)->where('status', 'completed')->count();

        $invoicesQuery = Invoice::whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', 'cancelled');
        $grossBilled = (float) (clone $invoicesQuery)->sum('total_amount');
        $discountsGiven = (float) (clone $invoicesQuery)->sum('discount');
        $netBilled = (float) (clone $invoicesQuery)->sum('net_amount');

        $totalCollected = (float) Payment::whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $totalOutstanding = (float) Invoice::whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->sum('balance');

        $collectionRate = $netBilled > 0 ? round(($totalCollected / $netBilled) * 100, 1) : 0.0;

        return [
            'total_patients' => $totalPatients,
            'new_patients' => $newPatients,
            'total_appointments' => $totalAppointments,
            'completed_appointments' => $completedAppointments,
            'total_treatments' => $totalTreatments,
            'completed_treatments' => $completedTreatments,
            'gross_billed' => $grossBilled,
            'discounts_given' => $discountsGiven,
            'net_billed' => $netBilled,
            'total_collected' => $totalCollected,
            'total_outstanding' => $totalOutstanding,
            'collection_rate' => $collectionRate,
        ];
    }

    /**
     * Compute detailed patient demographics and clinical alert analytics.
     */
    protected function computePatientAnalytics(Carbon $start, Carbon $end, string $search): array
    {
        $allPatients = Patient::where('status', '!=', 'Archived')->get();
        $totalPatients = $allPatients->count();

        // Demographics: Gender Breakdown
        $genderCounts = [
            'female' => 0,
            'male' => 0,
            'other' => 0,
        ];

        // Demographics: Age Groups
        $ageGroups = [
            'pediatric' => 0, // 0 - 12
            'teens' => 0,     // 13 - 19
            'adults' => 0,    // 20 - 59
            'seniors' => 0,   // 60+
            'unknown' => 0,
        ];

        foreach ($allPatients as $p) {
            $g = strtolower($p->gender ?? '');
            if ($g === 'female') {
                $genderCounts['female']++;
            } elseif ($g === 'male') {
                $genderCounts['male']++;
            } else {
                $genderCounts['other']++;
            }

            $age = $p->age;
            if ($age === null) {
                $ageGroups['unknown']++;
            } elseif ($age <= 12) {
                $ageGroups['pediatric']++;
            } elseif ($age <= 19) {
                $ageGroups['teens']++;
            } elseif ($age <= 59) {
                $ageGroups['adults']++;
            } else {
                $ageGroups['seniors']++;
            }
        }

        // Medical Alerts Prevalence
        $medicalHistories = MedicalHistory::all();
        $alertsCount = [
            'allergies' => $medicalHistories->filter(fn($m) => $m->has_allergies || !empty($m->allergies))->count(),
            'hypertension' => $medicalHistories->where('hypertension', true)->count(),
            'diabetes' => $medicalHistories->where('diabetes', true)->count(),
            'heart_disease' => $medicalHistories->where('heart_disease', true)->count(),
            'asthma' => $medicalHistories->where('asthma', true)->count(),
            'bleeding_disorders' => $medicalHistories->where('bleeding_disorders', true)->count(),
            'pregnant' => $medicalHistories->where('is_pregnant', true)->count(),
            'epilepsy' => $medicalHistories->where('epilepsy', true)->count(),
        ];

        // Patient Ledger Query
        $patientQuery = Patient::with(['medicalHistory'])
            ->withCount(['appointments', 'treatments'])
            ->where('status', '!=', 'Archived');

        if (!empty($search)) {
            $patientQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_id', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        $patientList = $patientQuery->orderBy('created_at', 'desc')->paginate(10, ['*'], 'patients_page')->withQueryString()->through(function ($patient) {
            return [
                'id' => $patient->id,
                'patient_id' => $patient->patient_id,
                'full_name' => $patient->full_name,
                'age' => $patient->age,
                'gender' => $patient->gender,
                'contact_number' => $patient->contact_number,
                'status' => $patient->status,
                'appointments_count' => $patient->appointments_count,
                'treatments_count' => $patient->treatments_count,
                'critical_alerts' => $patient->medicalHistory?->critical_alerts ?? [],
                'registered_date' => $patient->created_at->format('M d, Y'),
            ];
        });

        return [
            'total_active' => $totalPatients,
            'new_in_period' => Patient::whereBetween('created_at', [$start, $end])->count(),
            'gender_distribution' => $genderCounts,
            'age_distribution' => $ageGroups,
            'medical_alerts' => $alertsCount,
            'records' => $patientList,
        ];
    }

    /**
     * Compute treatment statistics, top procedures, and dentist operational volume.
     */
    protected function computeTreatmentAnalytics(Carbon $start, Carbon $end, string $search): array
    {
        $treatmentsQuery = Treatment::whereBetween('treatment_date', [$start->toDateString(), $end->toDateString()]);

        $totalCount = (clone $treatmentsQuery)->count();
        $completedCount = (clone $treatmentsQuery)->where('status', 'completed')->count();
        $inProgressCount = (clone $treatmentsQuery)->where('status', 'in_progress')->count();
        $totalRevenue = (float) (clone $treatmentsQuery)->where('status', 'completed')->sum('cost');

        // Top Procedures Ranking
        $procedureGroups = (clone $treatmentsQuery)
            ->select('procedure_name', DB::raw('count(*) as count'), DB::raw('sum(cost) as total_revenue'), DB::raw('avg(cost) as avg_cost'))
            ->groupBy('procedure_name')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) use ($totalCount) {
                return [
                    'procedure_name' => $item->procedure_name,
                    'count' => (int) $item->count,
                    'percentage' => $totalCount > 0 ? round(($item->count / $totalCount) * 100, 1) : 0,
                    'total_revenue' => (float) $item->total_revenue,
                    'avg_cost' => (float) $item->avg_cost,
                ];
            });

        // Top Treated Teeth
        $toothDistribution = (clone $treatmentsQuery)
            ->whereNotNull('tooth_number')
            ->where('tooth_number', '!=', '')
            ->select('tooth_number', DB::raw('count(*) as count'))
            ->groupBy('tooth_number')
            ->orderBy('count', 'desc')
            ->take(8)
            ->get();

        // Attending Dentist Volume
        $dentists = User::whereIn('role', ['dentist', 'admin'])->get();
        $dentistPerformance = $dentists->map(function ($dentist) use ($start, $end) {
            $count = Treatment::where('dentist_id', $dentist->id)
                ->whereBetween('treatment_date', [$start->toDateString(), $end->toDateString()])
                ->count();
            $completed = Treatment::where('dentist_id', $dentist->id)
                ->where('status', 'completed')
                ->whereBetween('treatment_date', [$start->toDateString(), $end->toDateString()])
                ->count();
            $rev = (float) Treatment::where('dentist_id', $dentist->id)
                ->where('status', 'completed')
                ->whereBetween('treatment_date', [$start->toDateString(), $end->toDateString()])
                ->sum('cost');

            return [
                'name' => $dentist->name,
                'total_procedures' => $count,
                'completed' => $completed,
                'revenue' => $rev,
            ];
        });

        // Detailed Treatments Ledger
        $ledgerQuery = Treatment::with(['patient', 'dentist', 'invoice'])
            ->whereBetween('treatment_date', [$start->toDateString(), $end->toDateString()]);

        if (!empty($search)) {
            $ledgerQuery->where(function ($q) use ($search) {
                $q->where('procedure_name', 'like', "%{$search}%")
                    ->orWhere('tooth_number', 'like', "%{$search}%")
                    ->orWhere('diagnosis', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_id', 'like', "%{$search}%");
                    });
            });
        }

        $records = $ledgerQuery->orderBy('treatment_date', 'desc')->paginate(10, ['*'], 'treatments_page')->withQueryString()->through(function ($t) {
            return [
                'id' => $t->id,
                'treatment_date' => $t->treatment_date ? Carbon::parse($t->treatment_date)->format('M d, Y') : 'N/A',
                'patient_name' => $t->patient ? $t->patient->first_name . ' ' . $t->patient->last_name : 'Unknown',
                'patient_id' => $t->patient?->patient_id ?? 'N/A',
                'procedure_name' => $t->procedure_name,
                'tooth_number' => $t->tooth_number,
                'dentist_name' => $t->dentist?->name ?? 'Dr. Valdez',
                'diagnosis' => $t->diagnosis,
                'status' => $t->status,
                'cost' => (float) $t->cost,
                'invoice_number' => $t->invoice?->invoice_number,
            ];
        });

        return [
            'total_count' => $totalCount,
            'completed_count' => $completedCount,
            'in_progress_count' => $inProgressCount,
            'total_revenue' => $totalRevenue,
            'top_procedures' => $procedureGroups,
            'tooth_distribution' => $toothDistribution,
            'dentist_performance' => $dentistPerformance,
            'records' => $records,
        ];
    }

    /**
     * Compute billing, invoicing, payments, and receivables aging.
     */
    protected function computeBillingAnalytics(Carbon $start, Carbon $end, string $search): array
    {
        $invoicesQuery = Invoice::whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()]);

        $totalInvoices = (clone $invoicesQuery)->count();
        $grossAmount = (float) (clone $invoicesQuery)->where('status', '!=', 'cancelled')->sum('total_amount');
        $discountAmount = (float) (clone $invoicesQuery)->where('status', '!=', 'cancelled')->sum('discount');
        $taxAmount = (float) (clone $invoicesQuery)->where('status', '!=', 'cancelled')->sum('tax');
        $netAmount = (float) (clone $invoicesQuery)->where('status', '!=', 'cancelled')->sum('net_amount');
        $paidAmount = (float) (clone $invoicesQuery)->where('status', '!=', 'cancelled')->sum('paid_amount');
        $balanceAmount = (float) (clone $invoicesQuery)->where('status', '!=', 'cancelled')->sum('balance');

        // Invoice Status Breakdown
        $statusBreakdown = [
            'paid' => [
                'count' => (clone $invoicesQuery)->where('status', 'paid')->count(),
                'amount' => (float) (clone $invoicesQuery)->where('status', 'paid')->sum('net_amount'),
            ],
            'partially_paid' => [
                'count' => (clone $invoicesQuery)->where('status', 'partially_paid')->count(),
                'amount' => (float) (clone $invoicesQuery)->where('status', 'partially_paid')->sum('net_amount'),
            ],
            'unpaid' => [
                'count' => (clone $invoicesQuery)->where('status', 'unpaid')->count(),
                'amount' => (float) (clone $invoicesQuery)->where('status', 'unpaid')->sum('net_amount'),
            ],
            'cancelled' => [
                'count' => (clone $invoicesQuery)->where('status', 'cancelled')->count(),
                'amount' => (float) (clone $invoicesQuery)->where('status', 'cancelled')->sum('net_amount'),
            ],
        ];

        // Payment Methods Breakdown (From actual payments in period)
        $paymentsInPeriod = Payment::whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])->get();
        $totalCollected = (float) $paymentsInPeriod->sum('amount');

        $methodBreakdown = [
            'Cash' => ['count' => 0, 'amount' => 0.0],
            'GCash' => ['count' => 0, 'amount' => 0.0],
            'Other' => ['count' => 0, 'amount' => 0.0],
        ];

        foreach ($paymentsInPeriod as $pay) {
            $method = $pay->payment_method ?: 'Cash';
            if (isset($methodBreakdown[$method])) {
                $methodBreakdown[$method]['count']++;
                $methodBreakdown[$method]['amount'] += (float) $pay->amount;
            } else {
                $methodBreakdown['Other']['count']++;
                $methodBreakdown['Other']['amount'] += (float) $pay->amount;
            }
        }

        // Receivables Aging (Based on Due Date for unpaid/partially paid invoices)
        $now = Carbon::today();
        $aging = [
            'current' => 0.0,   // Due in future or <= 30 days old
            'overdue_30' => 0.0, // 31 - 60 days overdue
            'overdue_60' => 0.0, // > 60 days overdue
        ];

        $unpaidInvoices = Invoice::whereIn('status', ['unpaid', 'partially_paid'])->get();
        foreach ($unpaidInvoices as $inv) {
            $dueDate = $inv->due_date ? Carbon::parse($inv->due_date) : Carbon::parse($inv->invoice_date);
            $diffDays = $now->diffInDays($dueDate, false); // negative if overdue

            if ($diffDays >= 0 || $diffDays >= -30) {
                $aging['current'] += (float) $inv->balance;
            } elseif ($diffDays >= -60) {
                $aging['overdue_30'] += (float) $inv->balance;
            } else {
                $aging['overdue_60'] += (float) $inv->balance;
            }
        }

        // Invoices Ledger List
        $invoiceListQuery = Invoice::with(['patient', 'treatment', 'payments'])
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()]);

        if (!empty($search)) {
            $invoiceListQuery->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_id', 'like', "%{$search}%");
                    });
            });
        }

        $records = $invoiceListQuery->orderBy('invoice_date', 'desc')->paginate(10, ['*'], 'billing_page')->withQueryString()->through(function ($inv) {
            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'invoice_date' => $inv->invoice_date ? Carbon::parse($inv->invoice_date)->format('M d, Y') : 'N/A',
                'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('M d, Y') : 'N/A',
                'patient_name' => $inv->patient ? $inv->patient->first_name . ' ' . $inv->patient->last_name : 'Unknown',
                'patient_id' => $inv->patient?->patient_id ?? 'N/A',
                'procedure_name' => $inv->treatment?->procedure_name ?? 'Dental Consultation / Service',
                'total_amount' => (float) $inv->total_amount,
                'discount' => (float) $inv->discount,
                'net_amount' => (float) $inv->net_amount,
                'paid_amount' => (float) $inv->paid_amount,
                'balance' => (float) $inv->balance,
                'status' => $inv->status,
            ];
        });

        return [
            'total_invoices' => $totalInvoices,
            'gross_amount' => $grossAmount,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'net_amount' => $netAmount,
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount,
            'total_collected' => $totalCollected,
            'status_breakdown' => $statusBreakdown,
            'payment_methods' => $methodBreakdown,
            'aging' => $aging,
            'records' => $records,
        ];
    }

    /**
     * Generate dynamic automated observations and operational takeaways.
     */
    protected function generateAutomatedInsights(array $summary, array $patients, array $treatments, array $billing): array
    {
        $insights = [];

        // 1. Revenue & Collections Insight
        $collectionRate = $summary['collection_rate'];
        if ($collectionRate >= 85) {
            $insights[] = [
                'type' => 'success',
                'icon' => 'pi-check-circle',
                'title' => 'Healthy Collection Efficiency',
                'message' => "The clinic achieved an outstanding collection rate of {$collectionRate}%, collecting ₱" . number_format($summary['total_collected'], 2) . " against ₱" . number_format($summary['net_billed'], 2) . " billed.",
            ];
        } elseif ($collectionRate > 50) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'pi-exclamation-triangle',
                'title' => 'Moderate Receivables Pipeline',
                'message' => "Collection rate currently sits at {$collectionRate}%. Outstanding receivables total ₱" . number_format($summary['total_outstanding'], 2) . ". Timely follow-up on unpaid invoices is recommended.",
            ];
        } else {
            $insights[] = [
                'type' => 'info',
                'icon' => 'pi-info-circle',
                'title' => 'New Billing Period',
                'message' => "Total net billings for the active window stand at ₱" . number_format($summary['net_billed'], 2) . " with ₱" . number_format($summary['total_collected'], 2) . " collected to date.",
            ];
        }

        // 2. Clinical Procedures Insight
        $topProcedure = $treatments['top_procedures']->first();
        if ($topProcedure) {
            $insights[] = [
                'type' => 'primary',
                'icon' => 'pi-heart',
                'title' => 'Highest Clinical Demand',
                'message' => "\"{$topProcedure['procedure_name']}\" is the leading clinical procedure, representing {$topProcedure['percentage']}% of all documented treatments with ₱" . number_format($topProcedure['total_revenue'], 2) . " in service revenue.",
            ];
        }

        // 3. Medical Safety & Allergies Insight
        $allergiesCount = $patients['medical_alerts']['allergies'] ?? 0;
        $hypertensionCount = $patients['medical_alerts']['hypertension'] ?? 0;
        if ($allergiesCount > 0 || $hypertensionCount > 0) {
            $insights[] = [
                'type' => 'danger',
                'icon' => 'pi-shield',
                'title' => 'Clinical Safety Vigilance',
                'message' => "{$allergiesCount} patient(s) have documented drug/latex allergies and {$hypertensionCount} have hypertension records. Anesthesia screening protocols remain actively flagged.",
            ];
        }

        // 4. Patient Acquisition Insight
        $newPatients = $summary['new_patients'];
        $insights[] = [
            'type' => 'info',
            'icon' => 'pi-users',
            'title' => 'Patient Roster & Growth',
            'message' => "{$newPatients} new patient registration(s) documented in this monitoring window, bringing the active clinic roster to {$patients['total_active']} individuals.",
        ];

        return $insights;
    }

    /**
     * CSV Exporter: Executive Summary
     */
    protected function exportSummaryCsv($handle, Carbon $start, Carbon $end): void
    {
        $summary = $this->computeExecutiveSummary($start, $end);

        fputcsv($handle, ['VITALITY SMILE DENTAL CLINIC - EXECUTIVE CLINICAL & FINANCIAL REPORT']);
        fputcsv($handle, ['Monitoring Period', $start->format('M d, Y') . ' to ' . $end->format('M d, Y')]);
        fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s')]);
        fputcsv($handle, ['Generated By', auth()->user()->name]);
        fputcsv($handle, []);

        fputcsv($handle, ['Metric Category', 'Metric Indicator', 'Value']);
        fputcsv($handle, ['Patients', 'Total Active Patients', $summary['total_patients']]);
        fputcsv($handle, ['Patients', 'New Patients in Period', $summary['new_patients']]);
        fputcsv($handle, ['Appointments', 'Total Appointments', $summary['total_appointments']]);
        fputcsv($handle, ['Appointments', 'Completed Appointments', $summary['completed_appointments']]);
        fputcsv($handle, ['Treatments', 'Total Procedures Documented', $summary['total_treatments']]);
        fputcsv($handle, ['Treatments', 'Completed Procedures', $summary['completed_treatments']]);
        fputcsv($handle, ['Financials', 'Gross Invoiced Amount (PHP)', number_format($summary['gross_billed'], 2, '.', '')]);
        fputcsv($handle, ['Financials', 'Discounts Granted (PHP)', number_format($summary['discounts_given'], 2, '.', '')]);
        fputcsv($handle, ['Financials', 'Net Invoiced Revenue (PHP)', number_format($summary['net_billed'], 2, '.', '')]);
        fputcsv($handle, ['Financials', 'Total Collections Received (PHP)', number_format($summary['total_collected'], 2, '.', '')]);
        fputcsv($handle, ['Financials', 'Outstanding Receivables (PHP)', number_format($summary['total_outstanding'], 2, '.', '')]);
        fputcsv($handle, ['Financials', 'Collection Efficiency Rate (%)', $summary['collection_rate'] . '%']);
    }

    /**
     * CSV Exporter: Patient Records
     */
    protected function exportPatientsCsv($handle, Carbon $start, Carbon $end): void
    {
        fputcsv($handle, ['VITALITY SMILE DENTAL CLINIC - PATIENT RECORDS REPORT']);
        fputcsv($handle, ['Export Period', $start->format('M d, Y') . ' to ' . $end->format('M d, Y')]);
        fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s')]);
        fputcsv($handle, []);

        fputcsv($handle, [
            'Patient ID',
            'Full Name',
            'Date of Birth',
            'Age',
            'Gender',
            'Contact Number',
            'Email Address',
            'Address',
            'Medical Alerts',
            'Total Appointments',
            'Total Treatments',
            'Registration Date',
            'Status',
        ]);

        $patients = Patient::with(['medicalHistory'])
            ->withCount(['appointments', 'treatments'])
            ->where('status', '!=', 'Archived')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($patients as $p) {
            $alerts = collect($p->medicalHistory?->critical_alerts ?? [])->pluck('label')->join('; ');

            fputcsv($handle, [
                $p->patient_id,
                $p->full_name,
                $p->date_of_birth,
                $p->age ?? 'N/A',
                $p->gender,
                $p->contact_number,
                $p->email,
                $p->address,
                $alerts ?: 'None',
                $p->appointments_count,
                $p->treatments_count,
                $p->created_at->format('Y-m-d'),
                $p->status,
            ]);
        }
    }

    /**
     * CSV Exporter: Treatments
     */
    protected function exportTreatmentsCsv($handle, Carbon $start, Carbon $end): void
    {
        fputcsv($handle, ['VITALITY SMILE DENTAL CLINIC - CLINICAL TREATMENTS REPORT']);
        fputcsv($handle, ['Monitoring Period', $start->format('M d, Y') . ' to ' . $end->format('M d, Y')]);
        fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s')]);
        fputcsv($handle, []);

        fputcsv($handle, [
            'Treatment ID',
            'Treatment Date',
            'Patient ID',
            'Patient Name',
            'Procedure Performed',
            'Tooth Number',
            'Attending Dentist',
            'Clinical Diagnosis',
            'Prescription Notes',
            'Status',
            'Procedure Fee (PHP)',
            'Linked Invoice',
        ]);

        $treatments = Treatment::with(['patient', 'dentist', 'invoice'])
            ->whereBetween('treatment_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('treatment_date', 'desc')
            ->get();

        foreach ($treatments as $t) {
            fputcsv($handle, [
                $t->id,
                $t->treatment_date ? Carbon::parse($t->treatment_date)->format('Y-m-d') : 'N/A',
                $t->patient?->patient_id ?? 'N/A',
                $t->patient ? $t->patient->first_name . ' ' . $t->patient->last_name : 'Unknown',
                $t->procedure_name,
                $t->tooth_number ?: 'N/A',
                $t->dentist?->name ?? 'Dr. Valdez',
                $t->diagnosis ?: 'N/A',
                $t->prescription ?: 'N/A',
                ucfirst($t->status),
                number_format($t->cost, 2, '.', ''),
                $t->invoice?->invoice_number ?? 'Unbilled',
            ]);
        }
    }

    /**
     * CSV Exporter: Billing
     */
    protected function exportBillingCsv($handle, Carbon $start, Carbon $end): void
    {
        fputcsv($handle, ['VITALITY SMILE DENTAL CLINIC - BILLING & FINANCIAL COLLECTIONS REPORT']);
        fputcsv($handle, ['Monitoring Period', $start->format('M d, Y') . ' to ' . $end->format('M d, Y')]);
        fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s')]);
        fputcsv($handle, []);

        fputcsv($handle, [
            'Invoice Number',
            'Date Issued',
            'Due Date',
            'Patient ID',
            'Patient Name',
            'Gross Amount (PHP)',
            'Discount (PHP)',
            'Tax (PHP)',
            'Net Amount (PHP)',
            'Paid Amount (PHP)',
            'Remaining Balance (PHP)',
            'Status',
            'Notes / Terms',
        ]);

        $invoices = Invoice::with(['patient'])
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('invoice_date', 'desc')
            ->get();

        foreach ($invoices as $inv) {
            fputcsv($handle, [
                $inv->invoice_number,
                $inv->invoice_date ? Carbon::parse($inv->invoice_date)->format('Y-m-d') : 'N/A',
                $inv->due_date ? Carbon::parse($inv->due_date)->format('Y-m-d') : 'N/A',
                $inv->patient?->patient_id ?? 'N/A',
                $inv->patient ? $inv->patient->first_name . ' ' . $inv->patient->last_name : 'Unknown',
                number_format($inv->total_amount, 2, '.', ''),
                number_format($inv->discount, 2, '.', ''),
                number_format($inv->tax, 2, '.', ''),
                number_format($inv->net_amount, 2, '.', ''),
                number_format($inv->paid_amount, 2, '.', ''),
                number_format($inv->balance, 2, '.', ''),
                ucfirst(str_replace('_', ' ', $inv->status)),
                $inv->notes ?: 'N/A',
            ]);
        }
    }

    /**
     * Compute formal Patient Report grouped by date and week with subtotals.
     */
    protected function computePatientReport(Carbon $start, Carbon $end, string $search): array
    {
        $query = Patient::where('status', '!=', 'Archived')
            ->whereBetween('created_at', [$start, $end]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('patient_id', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        $patients = $query->orderBy('created_at', 'asc')->get();

        $records = $patients->map(function ($p) {
            $date = $p->created_at ? Carbon::parse($p->created_at) : null;
            return [
                'id' => $p->id,
                'patient_id' => $p->patient_id,
                'patient_name' => $p->full_name,
                'registration_date' => $date ? $date->format('M d, Y') : 'N/A',
                'registration_time' => $date ? $date->format('h:i A') : '',
                'date_raw' => $date ? $date->format('Y-m-d') : '',
                'day_name' => $date ? $date->format('l') : '',
                'contact_number' => $p->contact_number ?: 'N/A',
                'gender' => $p->gender ?: 'N/A',
                'age' => $p->age,
                'status' => $p->status,
            ];
        });

        // Group by Date (Monday to Sunday)
        $groupedByDate = $records->groupBy('date_raw')->map(function ($group, $dateKey) {
            $first = $group->first();
            $d = $dateKey ? Carbon::parse($dateKey) : null;
            return [
                'date' => $dateKey,
                'formatted_date' => $d ? $d->format('l, F d, Y') : 'Unknown Date',
                'day_name' => $first['day_name'] ?? '',
                'records' => $group->values()->all(),
                'subtotal_count' => $group->count(),
            ];
        })->values()->all();

        // Group by Week
        $groupedByWeek = $records->groupBy(function ($item) {
            if (empty($item['date_raw'])) return 'unknown';
            $d = Carbon::parse($item['date_raw']);
            $mon = $d->copy()->startOfWeek(Carbon::MONDAY)->format('M d');
            $sun = $d->copy()->endOfWeek(Carbon::SUNDAY)->format('M d, Y');
            return "Week {$d->weekOfYear} ({$mon} – {$sun})";
        })->map(function ($group, $weekLabel) {
            return [
                'week_label' => $weekLabel,
                'records' => $group->values()->all(),
                'subtotal_count' => $group->count(),
            ];
        })->values()->all();

        return [
            'records' => $records->values()->all(),
            'grouped_by_date' => $groupedByDate,
            'grouped_by_week' => $groupedByWeek,
            'grand_total' => [
                'total_patients' => $records->count(),
            ],
        ];
    }

    /**
     * Compute formal Appointment Report grouped by date and week with subtotals.
     */
    protected function computeAppointmentReport(Carbon $start, Carbon $end, string $search): array
    {
        $query = Appointment::with(['patient', 'dentist'])
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('service', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('dentist', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $appointments = $query->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $records = $appointments->map(function ($a) {
            $time = $a->appointment_time ? Carbon::parse($a->appointment_time)->format('h:i A') : 'N/A';
            $date = $a->appointment_date ? Carbon::parse($a->appointment_date) : null;
            return [
                'id' => $a->id,
                'appointment_id' => 'APT-' . str_pad($a->id, 4, '0', STR_PAD_LEFT),
                'patient_name' => $a->patient ? $a->patient->full_name : 'Unknown Patient',
                'patient_id' => $a->patient?->patient_id ?? 'N/A',
                'dental_service' => $a->service,
                'appointment_date' => $date ? $date->format('M d, Y') : 'N/A',
                'appointment_time' => $time,
                'time_window' => $a->formatted_time_window,
                'date_raw' => $a->appointment_date,
                'day_name' => $date ? $date->format('l') : '',
                'status' => $a->status,
                'dentist_name' => $a->dentist?->name ?? 'Dr. Valdez',
            ];
        });

        // Group by Date (Monday to Sunday)
        $groupedByDate = $records->groupBy('date_raw')->map(function ($group, $dateKey) {
            $first = $group->first();
            $d = $dateKey ? Carbon::parse($dateKey) : null;
            return [
                'date' => $dateKey,
                'formatted_date' => $d ? $d->format('l, F d, Y') : 'Unknown Date',
                'day_name' => $first['day_name'] ?? '',
                'records' => $group->values()->all(),
                'subtotal_count' => $group->count(),
                'completed_count' => $group->where('status', 'completed')->count(),
                'confirmed_count' => $group->where('status', 'confirmed')->count(),
            ];
        })->values()->all();

        // Group by Week
        $groupedByWeek = $records->groupBy(function ($item) {
            if (empty($item['date_raw'])) return 'unknown';
            $d = Carbon::parse($item['date_raw']);
            $mon = $d->copy()->startOfWeek(Carbon::MONDAY)->format('M d');
            $sun = $d->copy()->endOfWeek(Carbon::SUNDAY)->format('M d, Y');
            return "Week {$d->weekOfYear} ({$mon} – {$sun})";
        })->map(function ($group, $weekLabel) {
            return [
                'week_label' => $weekLabel,
                'records' => $group->values()->all(),
                'subtotal_count' => $group->count(),
                'completed_count' => $group->where('status', 'completed')->count(),
                'confirmed_count' => $group->where('status', 'confirmed')->count(),
            ];
        })->values()->all();

        return [
            'records' => $records->values()->all(),
            'grouped_by_date' => $groupedByDate,
            'grouped_by_week' => $groupedByWeek,
            'grand_total' => [
                'total_appointments' => $records->count(),
                'completed_count' => $records->where('status', 'completed')->count(),
                'confirmed_count' => $records->where('status', 'confirmed')->count(),
                'other_count' => $records->whereNotIn('status', ['completed', 'confirmed'])->count(),
            ],
        ];
    }

    /**
     * Compute formal Treatment Report grouped by date and week with subtotals.
     */
    protected function computeTreatmentReport(Carbon $start, Carbon $end, string $search): array
    {
        $query = Treatment::with(['patient', 'dentist'])
            ->whereBetween('treatment_date', [$start->toDateString(), $end->toDateString()]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('procedure_name', 'like', "%{$search}%")
                    ->orWhere('diagnosis', 'like', "%{$search}%")
                    ->orWhere('tooth_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('dentist', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $treatments = $query->orderBy('treatment_date', 'asc')->get();

        $records = $treatments->map(function ($t) {
            $date = $t->treatment_date ? Carbon::parse($t->treatment_date) : null;
            return [
                'id' => $t->id,
                'treatment_id' => 'TRT-' . str_pad($t->id, 4, '0', STR_PAD_LEFT),
                'patient_name' => $t->patient ? $t->patient->full_name : 'Unknown Patient',
                'patient_id' => $t->patient?->patient_id ?? 'N/A',
                'treatment_procedure' => $t->procedure_name,
                'dentist' => $t->dentist?->name ?? 'Dr. Valdez',
                'treatment_date' => $date ? $date->format('M d, Y') : 'N/A',
                'date_raw' => $date ? $date->format('Y-m-d') : '',
                'day_name' => $date ? $date->format('l') : '',
                'tooth_number' => $t->tooth_number ?: 'N/A',
                'cost' => (float) $t->cost,
                'status' => $t->status,
            ];
        });

        // Group by Date (Monday to Sunday)
        $groupedByDate = $records->groupBy('date_raw')->map(function ($group, $dateKey) {
            $first = $group->first();
            $d = $dateKey ? Carbon::parse($dateKey) : null;
            return [
                'date' => $dateKey,
                'formatted_date' => $d ? $d->format('l, F d, Y') : 'Unknown Date',
                'day_name' => $first['day_name'] ?? '',
                'records' => $group->values()->all(),
                'subtotal_count' => $group->count(),
                'subtotal_cost' => (float) $group->sum('cost'),
            ];
        })->values()->all();

        // Group by Week
        $groupedByWeek = $records->groupBy(function ($item) {
            if (empty($item['date_raw'])) return 'unknown';
            $d = Carbon::parse($item['date_raw']);
            $mon = $d->copy()->startOfWeek(Carbon::MONDAY)->format('M d');
            $sun = $d->copy()->endOfWeek(Carbon::SUNDAY)->format('M d, Y');
            return "Week {$d->weekOfYear} ({$mon} – {$sun})";
        })->map(function ($group, $weekLabel) {
            return [
                'week_label' => $weekLabel,
                'records' => $group->values()->all(),
                'subtotal_count' => $group->count(),
                'subtotal_cost' => (float) $group->sum('cost'),
            ];
        })->values()->all();

        return [
            'records' => $records->values()->all(),
            'grouped_by_date' => $groupedByDate,
            'grouped_by_week' => $groupedByWeek,
            'grand_total' => [
                'total_treatments' => $records->count(),
                'total_cost' => (float) $records->sum('cost'),
                'completed_count' => $records->where('status', 'completed')->count(),
            ],
        ];
    }

    /**
     * Compute formal Revenue Report grouped by date and week with subtotals.
     */
    protected function computeRevenueReport(Carbon $start, Carbon $end, string $search): array
    {
        $query = Payment::with(['patient', 'invoice', 'receiver'])
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('patient_id', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->orderBy('payment_date', 'asc')->get();

        $records = $payments->map(function ($pay) {
            $date = $pay->payment_date ? Carbon::parse($pay->payment_date) : null;
            $status = $pay->invoice?->status ? ucfirst(str_replace('_', ' ', $pay->invoice->status)) : 'Paid';
            return [
                'id' => $pay->id,
                'transaction_id' => $pay->payment_number ?: ('TXN-' . str_pad($pay->id, 5, '0', STR_PAD_LEFT)),
                'patient_name' => $pay->patient ? $pay->patient->full_name : 'Unknown Patient',
                'patient_id' => $pay->patient?->patient_id ?? 'N/A',
                'payment_date' => $date ? $date->format('M d, Y') : 'N/A',
                'date_raw' => $date ? $date->format('Y-m-d') : '',
                'day_name' => $date ? $date->format('l') : '',
                'payment_method' => $pay->payment_method ?: 'Cash',
                'amount_paid' => (float) $pay->amount,
                'payment_status' => $status,
                'reference_number' => $pay->reference_number ?: 'N/A',
                'received_by' => $pay->receiver?->name ?? 'Front Desk',
            ];
        });

        // Group by Date (Monday to Sunday)
        $groupedByDate = $records->groupBy('date_raw')->map(function ($group, $dateKey) {
            $first = $group->first();
            $d = $dateKey ? Carbon::parse($dateKey) : null;
            return [
                'date' => $dateKey,
                'formatted_date' => $d ? $d->format('l, F d, Y') : 'Unknown Date',
                'day_name' => $first['day_name'] ?? '',
                'records' => $group->values()->all(),
                'subtotal_count' => $group->count(),
                'subtotal_amount' => (float) $group->sum('amount_paid'),
            ];
        })->values()->all();

        // Group by Week
        $groupedByWeek = $records->groupBy(function ($item) {
            if (empty($item['date_raw'])) return 'unknown';
            $d = Carbon::parse($item['date_raw']);
            $mon = $d->copy()->startOfWeek(Carbon::MONDAY)->format('M d');
            $sun = $d->copy()->endOfWeek(Carbon::SUNDAY)->format('M d, Y');
            return "Week {$d->weekOfYear} ({$mon} – {$sun})";
        })->map(function ($group, $weekLabel) {
            return [
                'week_label' => $weekLabel,
                'records' => $group->values()->all(),
                'subtotal_count' => $group->count(),
                'subtotal_amount' => (float) $group->sum('amount_paid'),
            ];
        })->values()->all();

        $methodsBreakdown = [
            'Cash' => (float) $records->where('payment_method', 'Cash')->sum('amount_paid'),
            'GCash' => (float) $records->where('payment_method', 'GCash')->sum('amount_paid'),
            'Other' => (float) $records->whereNotIn('payment_method', ['Cash', 'GCash'])->sum('amount_paid'),
        ];

        return [
            'records' => $records->values()->all(),
            'grouped_by_date' => $groupedByDate,
            'grouped_by_week' => $groupedByWeek,
            'grand_total' => [
                'total_transactions' => $records->count(),
                'total_amount_paid' => (float) $records->sum('amount_paid'),
                'methods' => $methodsBreakdown,
            ],
        ];
    }

    /**
     * CSV Exporter: Appointments Report
     */
    protected function exportAppointmentsCsv($handle, Carbon $start, Carbon $end): void
    {
        fputcsv($handle, ['VITALITY SMILE DENTAL CLINIC - APPOINTMENTS REPORT']);
        fputcsv($handle, ['Monitoring Period', $start->format('M d, Y') . ' to ' . $end->format('M d, Y')]);
        fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s')]);
        fputcsv($handle, []);

        fputcsv($handle, [
            'Appointment ID',
            'Patient ID',
            'Patient Name',
            'Dental Service',
            'Appointment Date',
            'Appointment Time',
            'Attending Dentist',
            'Status',
        ]);

        $appointments = Appointment::with(['patient', 'dentist'])
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        foreach ($appointments as $a) {
            $time = $a->appointment_time ? Carbon::parse($a->appointment_time)->format('h:i A') : 'N/A';
            fputcsv($handle, [
                'APT-' . str_pad($a->id, 4, '0', STR_PAD_LEFT),
                $a->patient?->patient_id ?? 'N/A',
                $a->patient ? $a->patient->full_name : 'Unknown Patient',
                $a->service,
                $a->appointment_date,
                $time,
                $a->dentist?->name ?? 'Dr. Valdez',
                ucfirst($a->status),
            ]);
        }
    }

    /**
     * CSV Exporter: Revenue Report
     */
    protected function exportRevenueCsv($handle, Carbon $start, Carbon $end): void
    {
        fputcsv($handle, ['VITALITY SMILE DENTAL CLINIC - REVENUE & COLLECTIONS REPORT']);
        fputcsv($handle, ['Monitoring Period', $start->format('M d, Y') . ' to ' . $end->format('M d, Y')]);
        fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s')]);
        fputcsv($handle, []);

        fputcsv($handle, [
            'Transaction ID',
            'Patient ID',
            'Patient Name',
            'Payment Date',
            'Payment Method',
            'Reference Number',
            'Amount Paid (PHP)',
            'Payment Status',
            'Received By',
        ]);

        $payments = Payment::with(['patient', 'invoice', 'receiver'])
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('payment_date', 'asc')
            ->get();

        $grandTotal = 0;
        foreach ($payments as $pay) {
            $grandTotal += (float) $pay->amount;
            $status = $pay->invoice?->status ? ucfirst(str_replace('_', ' ', $pay->invoice->status)) : 'Paid';
            fputcsv($handle, [
                $pay->payment_number ?: ('TXN-' . str_pad($pay->id, 5, '0', STR_PAD_LEFT)),
                $pay->patient?->patient_id ?? 'N/A',
                $pay->patient ? $pay->patient->full_name : 'Unknown Patient',
                $pay->payment_date ? Carbon::parse($pay->payment_date)->format('Y-m-d') : 'N/A',
                $pay->payment_method ?: 'Cash',
                $pay->reference_number ?: 'N/A',
                number_format($pay->amount, 2, '.', ''),
                $status,
                $pay->receiver?->name ?? 'Front Desk',
            ]);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['GRAND TOTAL (PHP)', '', '', '', '', '', number_format($grandTotal, 2, '.', '')]);
    }
}
