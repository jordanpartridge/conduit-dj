<?php

namespace JordanPartridge\ConduitDj\Listeners;

use Illuminate\Support\Facades\Log;
use JordanPartridge\ConduitDj\Events\PreferenceLearned;
use JordanPartridge\ConduitDj\Services\DjIntelligenceService;

class SpotifyTrackChangeListener
{
    protected DjIntelligenceService $djService;

    public function __construct(DjIntelligenceService $djService)
    {
        $this->djService = $djService;
    }

    /**
     * Handle Spotify track change event.
     */
    public function handle($event): void
    {
        // Check if DJ session is active
        if (!$this->djService->isSessionActive()) {
            return;
        }

        Log::info('DJ detected track change', [
            'track' => $event->track['name'] ?? 'Unknown',
            'artist' => $event->track['artist'] ?? 'Unknown',
        ]);

        // Analyze the track change
        $this->analyzeTrackChange($event);

        // Update queue if needed
        $this->updateQueueIfNeeded($event);

        // Learn from user behavior
        $this->learnFromTrackChange($event);
    }

    /**
     * Analyze track change for DJ insights.
     */
    protected function analyzeTrackChange($event): void
    {
        $track = $event->track;
        $previousTrack = $event->previousTrack ?? null;

        if (!$previousTrack) {
            return;
        }

        // Analyze if this was a good transition
        $transitionQuality = $this->djService->analyzeTransition($previousTrack, $track);

        if ($transitionQuality['score'] > 85) {
            Log::info('User made excellent transition', [
                'score' => $transitionQuality['score'],
                'technique' => $transitionQuality['technique'],
            ]);

            // Learn from good transitions
            PreferenceLearned::dispatch(
                'transition_style',
                $transitionQuality['technique'],
                0.8,
                ['tracks' => [$previousTrack['id'], $track['id']]]
            );
        }
    }

    /**
     * Update DJ queue based on track change.
     */
    protected function updateQueueIfNeeded($event): void
    {
        $currentMode = $this->djService->getCurrentMode();
        $respectUserQueue = config('dj.queue.respect_user_queue', true);

        if (!$respectUserQueue) {
            return;
        }

        // Check if user manually selected a different track
        $queue = $this->djService->getQueue();
        if ($queue->isNotEmpty() && $queue->first()['id'] !== $event->track['id']) {
            Log::info('User overrode DJ queue selection');

            // Rebuild queue based on new track
            $this->djService->rebuildQueue($event->track);
        }
    }

    /**
     * Learn from track change behavior.
     */
    protected function learnFromTrackChange($event): void
    {
        $track = $event->track;
        $timeOfDay = date('H');
        $dayOfWeek = date('w');

        // Store knowledge about track preferences
        if (app()->bound('conduit.knowledge')) {
            $knowledge = app('conduit.knowledge');

            $knowledge->add("User played {$track['name']} by {$track['artist']} at {$timeOfDay}:00 on day {$dayOfWeek}", [
                'tags' => ['dj', 'preferences', 'track-history'],
                'metadata' => [
                    'track_id' => $track['id'],
                    'energy' => $track['audio_features']['energy'] ?? null,
                    'tempo' => $track['audio_features']['tempo'] ?? null,
                    'context' => [
                        'mode' => $this->djService->getCurrentMode(),
                        'session_id' => $this->djService->getSessionId(),
                    ],
                ],
            ]);
        }

        // Update preference factors
        $this->updatePreferenceFactor('time_of_day', $timeOfDay, 0.3);
        $this->updatePreferenceFactor('day_of_week', $dayOfWeek, 0.2);

        if (isset($track['audio_features'])) {
            $this->updatePreferenceFactor('energy', $track['audio_features']['energy'], 0.5);
            $this->updatePreferenceFactor('tempo', $track['audio_features']['tempo'], 0.4);
        }
    }

    /**
     * Update preference factor.
     */
    protected function updatePreferenceFactor(string $type, mixed $value, float $weight): void
    {
        // This would update a preference model
        // For now, just dispatch event
        PreferenceLearned::dispatch($type, $value, $weight, [
            'session_id' => $this->djService->getSessionId(),
            'timestamp' => time(),
        ]);
    }
}