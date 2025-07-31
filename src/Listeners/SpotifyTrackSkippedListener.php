<?php

namespace JordanPartridge\ConduitDj\Listeners;

use Illuminate\Support\Facades\Log;
use JordanPartridge\ConduitDj\Events\PreferenceLearned;
use JordanPartridge\ConduitDj\Services\DjIntelligenceService;

class SpotifyTrackSkippedListener
{
    protected DjIntelligenceService $djService;

    public function __construct(DjIntelligenceService $djService)
    {
        $this->djService = $djService;
    }

    /**
     * Handle track skip event.
     */
    public function handle($event): void
    {
        if (!$this->djService->isSessionActive()) {
            return;
        }

        $track = $event->track;
        $playDuration = $event->playDuration ?? 0;
        $trackDuration = ($track['duration_ms'] ?? 180000) / 1000;

        // Calculate skip percentage
        $skipPercentage = $trackDuration > 0 ? ($playDuration / $trackDuration) : 0;
        $skipThreshold = config('dj.queue.skip_threshold', 0.3);

        Log::info('Track skipped', [
            'track' => $track['name'] ?? 'Unknown',
            'play_percentage' => round($skipPercentage * 100) . '%',
        ]);

        if (config('dj.queue.learn_from_skips', true)) {
            $this->learnFromSkip($track, $skipPercentage, $skipThreshold);
        }

        // Adjust queue if early skip
        if ($skipPercentage < $skipThreshold) {
            $this->adjustQueueForSkip($track);
        }
    }

    /**
     * Learn from skip behavior.
     */
    protected function learnFromSkip(array $track, float $skipPercentage, float $threshold): void
    {
        if ($skipPercentage < $threshold) {
            // Early skip indicates dislike
            $this->recordNegativePreference($track, 'early_skip');
        } elseif ($skipPercentage > 0.8) {
            // Late skip might be intentional transition
            $this->recordNeutralSkip($track);
        } else {
            // Mid-track skip
            $this->recordNegativePreference($track, 'mid_skip');
        }

        // Store in knowledge system
        if (app()->bound('conduit.knowledge')) {
            $knowledge = app('conduit.knowledge');

            $knowledge->add("User skipped {$track['name']} at {$skipPercentage}% played", [
                'tags' => ['dj', 'preferences', 'skips'],
                'metadata' => [
                    'track_id' => $track['id'],
                    'skip_percentage' => $skipPercentage,
                    'context' => [
                        'mode' => $this->djService->getCurrentMode(),
                        'session_id' => $this->djService->getSessionId(),
                    ],
                ],
            ]);
        }
    }

    /**
     * Record negative preference.
     */
    protected function recordNegativePreference(array $track, string $reason): void
    {
        // Learn that this type of track is not preferred
        if (isset($track['audio_features'])) {
            $features = $track['audio_features'];

            PreferenceLearned::dispatch(
                'energy_avoid',
                $features['energy'] ?? 0.5,
                0.7,
                ['reason' => $reason, 'track' => $track['id']]
            );

            PreferenceLearned::dispatch(
                'tempo_avoid',
                $features['tempo'] ?? 120,
                0.6,
                ['reason' => $reason, 'track' => $track['id']]
            );
        }

        // Genre preference
        if (isset($track['genres']) && !empty($track['genres'])) {
            foreach ($track['genres'] as $genre) {
                PreferenceLearned::dispatch(
                    'genre_avoid',
                    $genre,
                    0.5,
                    ['reason' => $reason]
                );
            }
        }
    }

    /**
     * Record neutral skip (likely intentional).
     */
    protected function recordNeutralSkip(array $track): void
    {
        Log::info('Late skip detected - likely intentional transition', [
            'track' => $track['name'] ?? 'Unknown',
        ]);
    }

    /**
     * Adjust queue based on skip.
     */
    protected function adjustQueueForSkip(array $skippedTrack): void
    {
        $queue = $this->djService->getQueue();

        // Remove similar tracks from queue
        $filtered = $queue->filter(function ($queuedTrack) use ($skippedTrack) {
            // Skip if same artist
            if (($queuedTrack['artist'] ?? '') === ($skippedTrack['artist'] ?? '')) {
                Log::info('Removing track from same artist from queue', [
                    'track' => $queuedTrack['name'] ?? 'Unknown',
                ]);
                return false;
            }

            // Skip if similar energy
            $energyDiff = abs(
                ($queuedTrack['audio_features']['energy'] ?? 0.5) -
                ($skippedTrack['audio_features']['energy'] ?? 0.5)
            );

            if ($energyDiff < 0.1) {
                Log::info('Removing track with similar energy from queue', [
                    'track' => $queuedTrack['name'] ?? 'Unknown',
                ]);
                return false;
            }

            return true;
        });

        // Update queue
        $this->djService->updateQueue($filtered);

        // Rebuild if queue is too small
        if ($filtered->count() < config('dj.queue.min_queue_size', 5)) {
            Log::info('Rebuilding queue after skip adjustments');
            $this->djService->rebuildQueue();
        }
    }
}