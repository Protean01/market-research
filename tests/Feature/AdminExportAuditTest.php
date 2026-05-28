<?php

namespace Tests\Feature;

use App\Models\ExportAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExportAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_audit_log_can_be_created_and_retrieved()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $log = ExportAuditLog::create([
            'admin_id'    => $admin->id,
            'export_type' => 'responses',
            'filters'     => ['date_from' => '2026-01-01', 'date_to' => '2026-12-31'],
            'row_count'   => 42,
        ]);

        $this->assertDatabaseHas('export_audit_logs', [
            'admin_id'    => $admin->id,
            'export_type' => 'responses',
            'row_count'   => 42,
        ]);

        $this->assertEquals(
            ['date_from' => '2026-01-01', 'date_to' => '2026-12-31'],
            $log->fresh()->filters
        );
    }

    public function test_export_audit_log_admin_relation_works()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $log = ExportAuditLog::create([
            'admin_id'    => $admin->id,
            'export_type' => 'transactions',
            'filters'     => [],
            'row_count'   => 0,
        ]);

        $this->assertEquals($admin->id, $log->admin->id);
    }

    public function test_responses_export_has_nosniff_header()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.responses'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_transactions_export_has_nosniff_header()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.transactions'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_prize_entries_export_has_nosniff_header()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.prize-entries'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_responses_export_accepts_date_range_params()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.responses', [
                'date_from' => '2026-01-01',
                'date_to'   => '2026-12-31',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_transactions_export_accepts_date_range_params()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.transactions', [
                'date_from' => '2026-01-01',
                'date_to'   => '2026-12-31',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_prize_entries_export_accepts_date_range_params()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.prize-entries', [
                'date_from' => '2026-01-01',
                'date_to'   => '2026-12-31',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_responses_export_rejects_invalid_date_format()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.responses', ['date_from' => 'not-a-date']));

        $response->assertStatus(302);
    }

    public function test_responses_export_writes_audit_log()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.responses'));

        $response->streamedContent();

        $this->assertDatabaseCount('export_audit_logs', 1);
        $log = ExportAuditLog::first();
        $this->assertEquals('responses', $log->export_type);
        $this->assertEquals($admin->id, $log->admin_id);
        $this->assertEquals(0, $log->row_count);
    }

    public function test_transactions_export_writes_audit_log()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.transactions'));

        $response->streamedContent();

        $this->assertDatabaseCount('export_audit_logs', 1);
        $this->assertEquals('transactions', ExportAuditLog::first()->export_type);
    }

    public function test_prize_entries_export_writes_audit_log()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.prize-entries'));

        $response->streamedContent();

        $this->assertDatabaseCount('export_audit_logs', 1);
        $this->assertEquals('prize_entries', ExportAuditLog::first()->export_type);
    }

    public function test_audit_log_captures_date_filters()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.responses', [
                'date_from' => '2026-01-01',
                'date_to'   => '2026-06-30',
            ]));

        $response->streamedContent();

        $log = ExportAuditLog::first();
        $this->assertEquals('2026-01-01', $log->filters['date_from']);
        $this->assertEquals('2026-06-30', $log->filters['date_to']);
    }
}
