<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$surveys = App\Models\Survey::orderByDesc('id')->take(3)->get();
foreach ($surveys as $s) {
    echo $s->id . ' | ' . $s->title . "\n";
    echo '  status=' . $s->status . ' active=' . ($s->is_active ? 'yes' : 'no') . "\n";
    echo '  age=' . ($s->target_age_band ?? 'null') . ' emp=' . ($s->target_employment ?? 'null') . ' income=' . ($s->target_income_band ?? 'null') . "\n";
    echo '  loc=' . ($s->target_location ?? 'null') . "\n";
    echo '  traits=' . json_encode($s->target_traits) . "\n";
    echo '  exclude=' . json_encode($s->exclude_traits) . "\n\n";
}
