<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Status page sync: refresh maintenance state + push transitions ────────
Schedule::command('status:sync')->everyFiveMinutes()->withoutOverlapping();

// ── Subscription lifecycle: auto-transition active → past_due → canceled ───
Schedule::call(function () {
    \App\Models\Subscription::where('status', '!=', 'canceled')
        ->get()
        ->each(fn ($s) => $s->checkAndTransitionStatus());
})->dailyAt('01:00');

// ── Telegram sales reports (brand-owner digests) ──────────────────────────
// Best-effort pairing: no public webhook URL needed; owner DMs the bot a code.
Schedule::command('telegram:poll')->everyMinute()->withoutOverlapping();
// Digests only fire per-org when due, so a frequent schedule is harmless.
Schedule::command('sales:notify:send')->everyFiveMinutes()->withoutOverlapping();