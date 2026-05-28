<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExportAuditLog;
use App\Models\PrizeDrawEntry;
use App\Models\Response;
use App\Models\Survey;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/AdminExport', [
            'surveys' => Survey::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function exportResponses(Request $request)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $surveyId = $request->query('survey_id');
        $survey   = $surveyId ? Survey::find($surveyId) : null;
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $filename = $survey ? "survey_responses_{$survey->id}.csv" : 'all_survey_responses.csv';

        $headers = [
            'Content-Type'           => 'text/csv; charset=UTF-8',
            'Content-Disposition'    => "attachment; filename=\"{$filename}\"",
            'X-Content-Type-Options' => 'nosniff',
        ];

        $rowCount = 0;

        return new StreamedResponse(function () use ($survey, $surveyId, $dateFrom, $dateTo, &$rowCount) {
            $handle = fopen('php://output', 'w');

            $baseColumns = ['ID', 'User Name', 'User Email', 'Phone', 'Survey ID', 'Survey Title', 'Completed At'];

            $questionMap = [];
            if ($survey) {
                foreach ($survey->questions as $q) {
                    $baseColumns[] = $q['text'];
                    $questionMap[] = $q['id'];
                }
            } else {
                $baseColumns[] = 'Answers (JSON)';
            }

            fputcsv($handle, $baseColumns);

            $query = Response::with(['user', 'survey']);
            if ($survey) {
                $query->where('survey_id', $survey->id);
            }
            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            try {
                $query->chunk(100, function ($responses) use ($handle, $survey, $questionMap, &$rowCount) {
                    foreach ($responses as $response) {
                        $row = [
                            $response->id,
                            $response->user?->name ?? '',
                            $response->user?->email ?? '',
                            $response->user?->phone_number ?? '',
                            $response->survey_id,
                            $response->survey?->title ?? '',
                            $response->created_at->toDateTimeString(),
                        ];

                        if ($survey) {
                            foreach ($questionMap as $qId) {
                                $answer = $response->answers[$qId] ?? '';
                                $row[] = \is_array($answer) ? implode(', ', $answer) : $answer;
                            }
                        } else {
                            $row[] = json_encode($response->answers);
                        }

                        fputcsv($handle, array_map(fn ($v) => preg_match('/^[=+\-@|]/', (string) $v) ? "'{$v}" : $v, $row));
                        $rowCount++;
                    }
                });
            } catch (\Throwable $e) {
                fputcsv($handle, ['ERROR', 'Export failed: ' . $e->getMessage()]);
            }

            fclose($handle);

            ExportAuditLog::create([
                'admin_id'    => auth()->id(),
                'export_type' => 'responses',
                'filters'     => array_filter([
                    'survey_id' => $surveyId,
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                ]),
                'row_count' => $rowCount,
            ]);
        }, 200, $headers);
    }

    public function exportTransactions(Request $request)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $headers = [
            'Content-Type'           => 'text/csv; charset=UTF-8',
            'Content-Disposition'    => 'attachment; filename="wallet_transactions.csv"',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $rowCount = 0;

        return new StreamedResponse(function () use ($dateFrom, $dateTo, &$rowCount) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'User Name', 'User Email', 'Type', 'Points', 'Description', 'Created At']);

            $query = WalletTransaction::with(['wallet.user']);
            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            try {
                $query->chunk(100, function ($transactions) use ($handle, &$rowCount) {
                    foreach ($transactions as $transaction) {
                        $desc = $transaction->type === 'earn'
                            ? ($transaction->meta['survey_title'] ?? 'Survey Earnings')
                            : ($transaction->meta['reward_type'] ?? 'Redemption');

                        $row = [
                            $transaction->id,
                            $transaction->wallet?->user?->name ?? '',
                            $transaction->wallet?->user?->email ?? '',
                            $transaction->type,
                            $transaction->points,
                            $desc,
                            $transaction->created_at->toDateTimeString(),
                        ];

                        fputcsv($handle, array_map(fn ($v) => preg_match('/^[=+\-@|]/', (string) $v) ? "'{$v}" : $v, $row));
                        $rowCount++;
                    }
                });
            } catch (\Throwable $e) {
                fputcsv($handle, ['ERROR', 'Export failed: ' . $e->getMessage()]);
            }

            fclose($handle);

            ExportAuditLog::create([
                'admin_id'    => auth()->id(),
                'export_type' => 'transactions',
                'filters'     => array_filter([
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                ]),
                'row_count' => $rowCount,
            ]);
        }, 200, $headers);
    }

    public function exportPrizeEntries(Request $request)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $surveyId = $request->query('survey_id');
        $survey   = $surveyId ? Survey::find($surveyId) : null;
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');
        $filename = $survey ? "prize_entries_{$survey->id}.csv" : 'all_prize_entries.csv';

        $headers = [
            'Content-Type'           => 'text/csv; charset=UTF-8',
            'Content-Disposition'    => "attachment; filename=\"{$filename}\"",
            'X-Content-Type-Options' => 'nosniff',
        ];

        $rowCount = 0;

        return new StreamedResponse(function () use ($survey, $surveyId, $dateFrom, $dateTo, &$rowCount) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID', 'Survey ID', 'Survey Title',
                'User Name', 'User Email', 'Phone',
                'Is Winner', 'Prize Name', 'Prize Points', 'Prize Airtime Amount',
                'Won At', 'Prize Delivered', 'Delivered At', 'Entered At',
            ]);

            $query = PrizeDrawEntry::with(['user', 'survey']);
            if ($survey) {
                $query->where('survey_id', $survey->id);
            }
            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            try {
                $query->chunk(100, function ($entries) use ($handle, &$rowCount) {
                    foreach ($entries as $entry) {
                        $snapshot = $entry->prize_snapshot ?? [];
                        $row = [
                            $entry->id,
                            $entry->survey_id,
                            $entry->survey?->title ?? '',
                            $entry->user?->name ?? '',
                            $entry->user?->email ?? '',
                            $entry->user?->phone_number ?? '',
                            $entry->is_winner ? 'Yes' : 'No',
                            $snapshot['name'] ?? ($entry->is_winner ? 'Prize' : ''),
                            $snapshot['points'] ?? '',
                            $snapshot['amount'] ?? '',
                            $entry->won_at?->toDateTimeString() ?? '',
                            $entry->prize_delivered ? 'Yes' : 'No',
                            $entry->delivered_at?->toDateTimeString() ?? '',
                            $entry->created_at->toDateTimeString(),
                        ];
                        fputcsv($handle, array_map(fn ($v) => preg_match('/^[=+\-@|]/', (string) $v) ? "'{$v}" : $v, $row));
                        $rowCount++;
                    }
                });
            } catch (\Throwable $e) {
                fputcsv($handle, ['ERROR', 'Export failed: ' . $e->getMessage()]);
            }

            fclose($handle);

            ExportAuditLog::create([
                'admin_id'    => auth()->id(),
                'export_type' => 'prize_entries',
                'filters'     => array_filter([
                    'survey_id' => $surveyId,
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                ]),
                'row_count' => $rowCount,
            ]);
        }, 200, $headers);
    }
}
