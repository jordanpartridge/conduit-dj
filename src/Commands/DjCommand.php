<?php

declare(strict_types=1);

namespace JordanPartridge\ConduitDj\Commands;

use LaravelZero\Framework\Commands\Command;
use JordanPartridge\ConduitDj\Services\DjIntelligenceService;
use JordanPartridge\ConduitDj\Services\BeatMatchingService;
use JordanPartridge\ConduitDj\Services\QueueBuilderService;

class DjCommand extends Command
{
    protected $signature = 'dj 
                           {action? : start, stop, analyze, queue, status}
                           {--mode= : party, focus, chill, workout}
                           {--duration= : Session duration in minutes}
                           {--energy= : Target energy level 0-100}
                           {--format=terminal : Output format (terminal, json, table, markdown)}';

    protected $description = '🎧 Intelligent DJ automation with real-time beatmatching and AI curation';

    public function handle(): int
    {
        $action = $this->argument('action') ?? 'status';
        
        // Check if Spotify is available
        if (!$this->isSpotifyAvailable()) {
            $this->error('Spotify component is not installed. Run: conduit install spotify');
            return self::FAILURE;
        }
        
        $djService = app(DjIntelligenceService::class);
        
        return match($action) {
            'start' => $this->startDjSession($djService),
            'stop' => $this->stopDjSession($djService),
            'analyze' => $this->analyzeCurrentTrack($djService),
            'queue' => $this->showSmartQueue($djService),
            'status' => $this->showStatus($djService),
            default => $this->showHelp()
        };
    }

    private function startDjSession(DjIntelligenceService $dj): int
    {
        $mode = $this->option('mode') ?? 'party';
        $duration = (int) $this->option('duration') ?? 60;
        $energy = (int) $this->option('energy') ?? 70;

        $this->info("🎧 Starting DJ session: {$mode} mode");
        $this->line("Duration: {$duration} minutes | Target Energy: {$energy}%");
        
        $session = $dj->startSession([
            'mode' => $mode,
            'duration' => $duration,
            'target_energy' => $energy,
            'auto_queue' => true,
            'beatmatch' => true,
        ]);

        if ($this->option('format') === 'json') {
            $this->line(json_encode($session, JSON_PRETTY_PRINT));
        } else {
            $this->table(['Property', 'Value'], [
                ['Session ID', $session['id']],
                ['Mode', $session['mode']],
                ['Duration', $session['duration'] . ' minutes'],
                ['Target Energy', $session['target_energy'] . '%'],
                ['Auto-Queue', $session['auto_queue'] ? 'Enabled' : 'Disabled'],
                ['Beat Matching', $session['beatmatch'] ? 'Enabled' : 'Disabled'],
            ]);
        }
        
        $this->info("✅ DJ session started! Use 'conduit dj status' to monitor.");

        return self::SUCCESS;
    }

    private function stopDjSession(DjIntelligenceService $dj): int
    {
        $this->warn('⏹️ Stopping DJ session...');
        
        $result = $dj->stopSession();
        
        if ($result['success']) {
            $this->info('✅ DJ session stopped.');
            
            // Show session summary
            if (isset($result['summary'])) {
                $this->line("\n📊 Session Summary:");
                $this->table(['Metric', 'Value'], [
                    ['Duration', $result['summary']['duration']],
                    ['Tracks Played', $result['summary']['tracks_played']],
                    ['Average Energy', $result['summary']['avg_energy'] . '%'],
                    ['Transitions', $result['summary']['transitions']],
                ]);
            }
        } else {
            $this->error('❌ ' . ($result['message'] ?? 'Failed to stop session'));
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function analyzeCurrentTrack(DjIntelligenceService $dj): int
    {
        $this->info('🔍 Analyzing current track...');
        
        $analysis = $dj->analyzeCurrentTrack();
        
        if (!$analysis) {
            $this->error('No track currently playing');
            return self::FAILURE;
        }

        if ($this->option('format') === 'json') {
            $this->line(json_encode($analysis, JSON_PRETTY_PRINT));
        } else {
            $this->line("\n🎵 Track Analysis:");
            $this->table(['Property', 'Value'], [
                ['Track', $analysis['track']['name']],
                ['Artist', $analysis['track']['artist']],
                ['BPM', $analysis['features']['tempo'] ?? 'N/A'],
                ['Key', $analysis['features']['key_name'] ?? 'N/A'],
                ['Energy', round(($analysis['features']['energy'] ?? 0) * 100) . '%'],
                ['Danceability', round(($analysis['features']['danceability'] ?? 0) * 100) . '%'],
                ['Mood', $analysis['mood'] ?? 'N/A'],
            ]);
            
            if (isset($analysis['compatibility'])) {
                $this->line("\n🔗 Next Track Compatibility:");
                $this->table(['Track', 'Score', 'BPM Match', 'Key Match'], 
                    array_map(function($track) {
                        return [
                            $track['name'],
                            $track['score'] . '%',
                            $track['bpm_compatible'] ? '✅' : '❌',
                            $track['key_compatible'] ? '✅' : '❌',
                        ];
                    }, array_slice($analysis['compatibility'], 0, 5))
                );
            }
        }

        return self::SUCCESS;
    }

    private function showSmartQueue(DjIntelligenceService $dj): int
    {
        $this->info('📋 Smart Queue:');
        
        $queue = $dj->getSmartQueue();
        
        if (empty($queue)) {
            $this->warn('Queue is empty. Start a DJ session to build a smart queue.');
            return self::SUCCESS;
        }

        if ($this->option('format') === 'json') {
            $this->line(json_encode($queue, JSON_PRETTY_PRINT));
        } else {
            $this->table(
                ['#', 'Track', 'Artist', 'Energy', 'Transition'],
                array_map(function($track, $index) {
                    return [
                        $index + 1,
                        $track['name'],
                        $track['artist'],
                        round($track['energy'] * 100) . '%',
                        $track['transition_type'] ?? 'smooth',
                    ];
                }, $queue, array_keys($queue))
            );
        }

        return self::SUCCESS;
    }

    private function showStatus(DjIntelligenceService $dj): int
    {
        $status = $dj->getStatus();
        
        if ($this->option('format') === 'json') {
            $this->line(json_encode($status, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info('🎧 DJ Status');
        
        if (!$status['active']) {
            $this->warn('No active DJ session. Use "conduit dj start" to begin.');
            return self::SUCCESS;
        }

        $this->table(['Property', 'Value'], [
            ['Session ID', $status['session_id']],
            ['Mode', $status['mode']],
            ['Uptime', $status['uptime']],
            ['Tracks Played', $status['tracks_played']],
            ['Current Energy', $status['current_energy'] . '%'],
            ['Target Energy', $status['target_energy'] . '%'],
            ['Queue Size', $status['queue_size']],
        ]);
        
        if (isset($status['current_track'])) {
            $this->line("\n🎵 Now Playing:");
            $this->line("   " . $status['current_track']['name'] . " - " . $status['current_track']['artist']);
        }
        
        if (isset($status['next_track'])) {
            $this->line("\n⏭️ Up Next:");
            $this->line("   " . $status['next_track']['name'] . " - " . $status['next_track']['artist']);
            $this->line("   Transition in: " . $status['next_transition_seconds'] . "s");
        }

        return self::SUCCESS;
    }

    private function showHelp(): int
    {
        $this->line('Available actions:');
        $this->line('  start   - Start an intelligent DJ session');
        $this->line('  stop    - Stop the current DJ session');
        $this->line('  analyze - Analyze the current track');
        $this->line('  queue   - Show the smart queue');
        $this->line('  status  - Show DJ session status');
        
        return self::SUCCESS;
    }

    private function isSpotifyAvailable(): bool
    {
        // Check if spotify commands exist
        return class_exists('JordanPartridge\ConduitSpotify\ServiceProvider');
    }
}