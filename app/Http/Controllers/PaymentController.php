<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChurchSetting;
use App\Models\ContributionCampaign;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function storeContributionCampaign(Request $request)
    {
        $request->validate([
            'department_id'      => 'required|exists:departments,id',
            'title'              => 'required|string|max:255',
            'amount_per_member'  => 'required|numeric|min:100',
            'due_date'           => 'required|date',
            'notes'              => 'nullable|string|max:2000',
        ]);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (!$this->canManageContributionDepartment($currentUser, (int) $request->department_id)) {
            return redirect()->back()->with('error', 'Huna ruhusa ya kuunda mchango kwa idara hii.');
        }

        $campaign = ContributionCampaign::create([
            'department_id' => $request->department_id,
            'created_by' => $currentUser->id,
            'title' => $request->title,
            'amount_per_member' => $request->amount_per_member,
            'due_date' => $request->due_date,
            'notes' => $request->notes,
            'status' => 'active',
        ]);

        AuditLog::create([
            'user_id'     => $currentUser->id,
            'action'      => 'CONTRIBUTION_CREATED',
            'description' => "Mchango '{$campaign->title}' umeanzishwa kwa idara.",
            'ip_address'  => $request->ip(),
            'document_id' => (string) $campaign->id,
        ]);

        return redirect()->back()->with('success', 'Mchango wa idara umeanzishwa kikamilifu.');
    }

    // Record payment
    public function store(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:100',
            'type'           => 'nullable|string',
            'contribution_campaign_id' => 'nullable|exists:contribution_campaigns,id',
            'payment_method' => 'required|string',
            'payment_date'   => 'required|date',
            'transaction_id' => 'nullable|string',
            'notes'          => 'nullable|string',
            'user_id'        => 'nullable|exists:users,id',
        ]);

        /** @var User $currentUser */
        $currentUser = Auth::user();
        $targetUserId = $currentUser->id;

        if (($currentUser->isMchungaji() || $currentUser->isKiongozi() || $currentUser->isAccountant()) && $request->filled('user_id')) {
            $targetUserId = $request->user_id;
        }

        $campaign = null;
        $paymentType = $request->type ?: 'Malipo ya Idara';

        if ($request->filled('contribution_campaign_id')) {
            $campaign = ContributionCampaign::with('department')->findOrFail($request->contribution_campaign_id);

            if (!$this->canRecordContributionPayments($currentUser, (int) $campaign->department_id)) {
                return redirect()->back()->with('error', 'Huna ruhusa ya kurekodi malipo ya mchango huu.');
            }

            if ($campaign->due_date && $campaign->due_date->isPast()) {
                return redirect()->back()->with('error', 'Muda wa mchango huu umeisha; huwezi kurekodi malipo mapya.');
            }

            $targetUser = User::findOrFail($targetUserId);
            if (!$targetUser->departments()->where('department_id', $campaign->department_id)->exists()) {
                return redirect()->back()->with('error', 'Mshirika huyu hayupo kwenye idara ya mchango huu.');
            }

            $paymentType = $campaign->title;
        }

        $payment = Payment::create([
            'user_id'        => $targetUserId,
            'contribution_campaign_id' => $campaign?->id,
            'amount'         => $request->amount,
            'type'           => $paymentType,
            'payment_method' => $request->payment_method,
            'payment_date'   => $request->payment_date,
            'transaction_id' => $request->transaction_id,
            'notes'          => $request->notes,
            'status'         => 'approved',
        ]);

        // Generate receipt
        $this->generateReceipt($payment);

        AuditLog::create([
            'user_id'     => $currentUser->id,
            'action'      => 'PAYMENT_RECORDED',
            'description' => "Malipo ya Tsh " . number_format($payment->amount) . " ({$payment->type}) yamehifadhiwa.",
            'ip_address'  => $request->ip(),
            'document_id' => (string)$payment->id,
        ]);

        return redirect()->back()->with('success', 'Malipo yamehifadhiwa kikamilifu na Risiti imetengenezwa!');
    }

    /** Remove a contribution campaign from the active contributions list. */
    public function destroyContributionCampaign(Request $request, ContributionCampaign $campaign)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (!$this->canManageContributionDepartment($currentUser, (int) $campaign->department_id)) {
            abort(403, 'Huna ruhusa ya kufuta mchango huu.');
        }

        $title = $campaign->title;
        $campaign->delete();

        AuditLog::create([
            'user_id' => $currentUser->id,
            'action' => 'CONTRIBUTION_DELETED',
            'description' => "Mchango '{$title}' umefutwa.",
            'ip_address' => $request->ip(),
            'document_id' => (string) $campaign->id,
        ]);

        return redirect()->back()->with('success', 'Mchango umefutwa kikamilifu.');
    }

    /** Update only the most recently entered payment for a member in a campaign. */
    public function updateContributionPayment(Request $request, ContributionCampaign $campaign, Payment $payment)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);
        $currentUser = Auth::user();

        if (!$this->canRecordContributionPayments($currentUser, (int) $campaign->department_id)) {
            abort(403, 'Huna ruhusa ya kurekebisha malipo ya mchango huu.');
        }

        $this->ensureLatestContributionPayment($campaign, $payment);
        $oldAmount = $payment->amount;
        $payment->update(['amount' => $request->amount]);

        AuditLog::create([
            'user_id' => $currentUser->id,
            'action' => 'CONTRIBUTION_PAYMENT_UPDATED',
            'description' => "Kiasi cha mwisho cha mchango kimebadilishwa kutoka Tsh " . number_format($oldAmount) . " hadi Tsh " . number_format($payment->amount) . '.',
            'ip_address' => $request->ip(),
            'document_id' => (string) $payment->id,
        ]);

        return redirect()->back()->with('success', 'Kiasi cha mwisho kilichoingizwa kimerekebishwa kikamilifu.');
    }

    /** Delete only the most recently entered payment for a member in a campaign. */
    public function destroyContributionPayment(Request $request, ContributionCampaign $campaign, Payment $payment)
    {
        $currentUser = Auth::user();

        if (!$this->canRecordContributionPayments($currentUser, (int) $campaign->department_id)) {
            abort(403, 'Huna ruhusa ya kufuta malipo ya mchango huu.');
        }

        $this->ensureLatestContributionPayment($campaign, $payment);
        $amount = $payment->amount;
        $memberName = $payment->user?->name ?? 'mwanachama';

        Receipt::where('payment_id', $payment->id)->get()->each(function (Receipt $receipt) {
            if ($receipt->pdf_path) {
                Storage::disk('public')->delete($receipt->pdf_path);
            }
            $receipt->delete();
        });
        $payment->delete();

        AuditLog::create([
            'user_id' => $currentUser->id,
            'action' => 'CONTRIBUTION_PAYMENT_DELETED',
            'description' => "Kiasi cha mwisho cha Tsh " . number_format($amount) . " cha {$memberName} kimefutwa kwenye mchango '{$campaign->title}'.",
            'ip_address' => $request->ip(),
            'document_id' => (string) $payment->id,
        ]);

        return redirect()->back()->with('success', 'Kiasi cha mwisho kilichoingizwa kimefutwa kikamilifu.');
    }

    /** Stream or download a report for one contribution campaign only. */
    public function contributionReportPdf(Request $request, ContributionCampaign $campaign)
    {
        $currentUser = Auth::user();
        if (!$this->canViewContributionDepartment($currentUser, (int) $campaign->department_id)) {
            abort(403, 'Huna ruhusa ya kuona ripoti ya mchango huu.');
        }

        $campaign->load([
            'department.users' => fn ($query) => $query->whereNotIn('users.role', ['admin', 'mchungaji']),
            'payments' => fn ($query) => $query->where('status', 'approved'),
        ]);
        $members = $campaign->department?->users ?? collect();
        $paymentsByMember = $campaign->payments->groupBy('user_id');
        $amountPerMember = (float) $campaign->amount_per_member;
        $rows = $members->map(function (User $member) use ($paymentsByMember, $amountPerMember) {
            $paid = (float) ($paymentsByMember->get($member->id, collect())->sum('amount'));
            $balance = max(0, $amountPerMember - $paid);
            $percent = $amountPerMember > 0 ? min(100, round(($paid / $amountPerMember) * 100)) : 0;
            $status = $paid <= 0 ? 'Hajaanza' : ($balance <= 0 ? 'Amemaliza' : 'Amepunguza');

            return compact('member', 'paid', 'balance', 'percent', 'status');
        });
        $target = $amountPerMember * $members->count();
        $paid = $rows->sum('paid');
        $summary = [
            'target' => $target,
            'paid' => $paid,
            'balance' => max(0, $target - $paid),
            'percent' => $target > 0 ? min(100, round(($paid / $target) * 100)) : 0,
            'partialCount' => $rows->where('status', 'Amepunguza')->count(),
            'completeCount' => $rows->where('status', 'Amemaliza')->count(),
            'notStartedCount' => $rows->where('status', 'Hajaanza')->count(),
        ];

        $filename = 'mchango-' . $campaign->id . '-' . now()->format('Y-m-d') . '.pdf';
        $pdf = Pdf::loadView('pdfs.contribution-report', compact('campaign', 'rows', 'summary', 'amountPerMember'))->setPaper('a4', 'landscape');

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    private function ensureLatestContributionPayment(ContributionCampaign $campaign, Payment $payment): void
    {
        abort_unless((int) $payment->contribution_campaign_id === (int) $campaign->id && $payment->status === 'approved', 404);
        $latestPayment = $campaign->payments()
            ->where('user_id', $payment->user_id)
            ->where('status', 'approved')
            ->latest('id')
            ->first();

        abort_unless($latestPayment && $latestPayment->id === $payment->id, 422, 'Unaweza kurekebisha au kufuta kiasi cha mwisho kilichoingizwa pekee.');
    }

    /** Only the accountant may create or remove contribution campaigns. */
    private function canManageContributionDepartment(User $user, int $departmentId): bool
    {
        return $user->isDepartmentAccountant($departmentId);
    }

    /** Only the accountant may enter, change, or delete contribution amounts. */
    private function canRecordContributionPayments(User $user, int $departmentId): bool
    {
        return $user->isDepartmentAccountant($departmentId);
    }

    /** Leaders may view their department's contribution statement but cannot edit it. */
    private function canViewContributionDepartment(User $user, int $departmentId): bool
    {
        return $user->isAccountant()
            || $user->isAdmin()
            || $user->isMchungaji()
            || $user->departments()
                ->where('department_id', $departmentId)
                ->wherePivot('role', 'kiongozi')
                ->exists();
    }

    // Helper: Generate PDF Receipt
    public function generateReceipt(Payment $payment)
    {
        $payment->load('user');

        $year = date('Y');
        $count = Receipt::whereYear('created_at', $year)->count() + 1;
        $receiptNumber = sprintf("EAGT-KBP-RCPT-%s-%04d", $year, $count);

        $receipt = Receipt::create([
            'receipt_number' => $receiptNumber,
            'payment_id'     => $payment->id,
            'user_id'        => $payment->user_id,
        ]);

        $churchName  = ChurchSetting::where('key', 'church_name')->value('value') ?? 'E.A.G.T KIBURUGWA PHILADELPHIA CHURCH';
        $address     = ChurchSetting::where('key', 'address')->value('value') ?? 'P.O. BOX 3236, DAR ES SALAAM';
        $logoPath    = ChurchSetting::where('key', 'logo_path')->value('value');
        $stampPath   = ChurchSetting::where('key', 'stamp_path')->value('value');

        $data = [
            'payment'       => $payment,
            'receipt'       => $receipt,
            'churchName'    => $churchName,
            'address'       => $address,
            'logoPath'      => $logoPath ? public_path('storage/' . $logoPath) : null,
            'stampPath'     => $stampPath ? public_path('storage/' . $stampPath) : null,
        ];

        $pdf = Pdf::loadView('pdfs.receipt', $data)->setPaper('a5', 'landscape');
        $filename = "receipts/EAGT_KBP_RCPT_" . str_replace('-', '_', $receiptNumber) . ".pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        $receipt->update(['pdf_path' => $filename]);
    }

    // Download Receipt PDF
    public function downloadReceipt($id)
    {
        $receipt = Receipt::with('payment')->findOrFail($id);
        $user    = Auth::user();

        if ($receipt->user_id !== $user->id && !$user->isAccountant()) {
            abort(403, 'Hauruhusiwi kuipakua risiti hii.');
        }

        if (!$receipt->pdf_path || !Storage::disk('public')->exists($receipt->pdf_path)) {
            $this->generateReceipt($receipt->payment);
        }

        return Storage::disk('public')->download($receipt->pdf_path);
    }
}
