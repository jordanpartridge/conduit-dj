<?php

namespace JordanPartridge\ConduitDj\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JordanPartridge\ConduitDj\Events\DjSessionStarted;
use JordanPartridge\ConduitDj\Events\TrackQueued;

class DjIntelligenceService
{
    protected BeatMatchingService $beatMatcher;

    protected QueueBuilderService $queueBuilder;

    protected MoodAnalysisService $moodAnalyzer;

    protected ?string $sessionId = null;

    protected ?string $currentMode = null;

    protected bool $active = false;

    protected array $sessionConfig = [];

    public function __construct(
        BeatMatchingService $beatMatcher,
        QueueBuilderService $queueBuilder,
        MoodAnalysisService $moodAnalyzer
    ) {
        $this->beatMatcher = $beatMatcher;
        $this->queueBuilder = $queueBuilder;
        $this->moodAnalyzer = $moodAnalyzer;
    }

    /**
     * Start a DJ session.
     */
    public function startSession(array $config = []): array
    {
        $this->sessionId = Str::uuid()->toString();
        $this->currentMode = $config['mode'] ?? 'party';
        $this->sessionConfig = array_merge(config("dj.modes.{$this->currentMode}", []), $config);
        $this->active = true;

        Log::info('DJ session started', [
            'session_id' => $this->sessionId,
            'mode' => $this->currentMode,
            'config' => $this->sessionConfig,
        ]);

        DjSessionStarted::dispatch($this->sessionId, $this->currentMode, $this->sessionConfig);

        // Build initial queue
        $this->buildInitialQueue();

        return [
            'session_id' => $this->sessionId,
            'mode' => $this->currentMode,
            'status' => 'active',
            'started_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Stop the DJ session.
     */
    public function stopSession(): array
    {
        if (!$this->active) {
            return ['status' => 'no_active_session'];
        }

        $sessionSummary = $this->generateSessionSummary();

        $this->active = false;
        $this->sessionId = null;
        $this->currentMode = null;
        $this->sessionConfig = [];

        Log::info('DJ session stopped', $sessionSummary);

        return $sessionSummary;
    }

    /**
     * Pause the session.
     */
    public function pauseSession(): void
    {
        if ($this->active) {
            Log::info('DJ session paused', ['session_id' => $this->sessionId]);
        }
    }

    /**
     * Resume the session.
     */
    public function resumeSession(): void
    {
        if ($this->active) {
            Log::info('DJ session resumed', ['session_id' => $this->sessionId]);
            $this->checkQueueStatus();
        }
    }

    /**
     * Check if session is active.
     */
    public function isSessionActive(): bool
    {
        return $this->active;
    }

    /**
     * Get current session ID.
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * Get current mode.
     */
    public function getCurrentMode(): ?string
    {
        return $this->currentMode;
    }

    /**
     * Analyze current track.
     */
    public function analyzeCurrentTrack(): array
    {
        // This would integrate with Spotify to get current track
        // For now, return mock data
        $currentTrack = $this->getMockCurrentTrack();

        $moodAnalysis = $this->moodAnalyzer->analyzeTrack($currentTrack);

        return [
            'track' => $currentTrack,
            'mood_analysis' => $moodAnalysis,
            'queue_compatibility' => $this->analyzeQueueCompatibility($currentTrack),
        ];
    }

    /**
     * Get smart queue.
     */
    public function getSmartQueue(): array
    {
        return $this->queueBuilder->getQueue()->toArray();
    }

    /**
     * Get current status.
     */
    public function getStatus(): array
    {
        if (!$this->active) {
            return ['status' => 'inactive'];
        }

        return [
            'status' => 'active',
            'session_id' => $this->sessionId,
            'mode' => $this->currentMode,
            'queue_size' => $this->queueBuilder->getQueue()->count(),
            'config' => $this->sessionConfig,
        ];
    }

    /**
     * Get the current queue.
     */
    public function getQueue(): Collection
    {
        return $this->queueBuilder->getQueue();
    }

    /**
     * Update the queue.
     */
    public function updateQueue(Collection $queue): void
    {
        $this->queueBuilder->clearQueue();
        foreach ($queue as $track) {
            $this->queueBuilder->addToQueue($track);
        }
    }

    /**
     * Rebuild queue based on current track.
     */
    public function rebuildQueue(?array $currentTrack = null): void
    {
        Log::info('Rebuilding DJ queue', [
            'session_id' => $this->sessionId,
            'current_track' => $currentTrack['name'] ?? 'Unknown',
        ]);

        $options = [
            'mode' => $this->currentMode,
            'current_track' => $currentTrack,
            'size' => config('dj.queue.min_queue_size', 5),
        ];

        $newQueue = $this->queueBuilder->buildSmartQueue($options);

        foreach ($newQueue as $position => $track) {
            TrackQueued::dispatch($track, $position, 85.0, 'Smart queue rebuild');
        }
    }

    /**
     * Analyze transition between tracks.
     */
    public function analyzeTransition(array $from, array $to): array
    {
        $compatibility = $this->beatMatcher->analyzeCompatibility($from, $to);
        $transitionPoint = $this->beatMatcher->findTransitionPoint($from, $to);

        return array_merge($compatibility, [
            'transition_point' => $transitionPoint,
            'score' => $compatibility['transition_score'],
            'technique' => $transitionPoint['technique'],
        ]);
    }

    /**
     * Build initial queue.
     */
    protected function buildInitialQueue(): void
    {
        $size = config('dj.queue.min_queue_size', 5);
        $queue = $this->queueBuilder->buildSmartQueue([
            'mode' => $this->currentMode,
            'size' => $size,
            'target_energy' => $this->sessionConfig['target_energy'] ?? 0.5,
        ]);

        Log::info('Initial queue built', [
            'session_id' => $this->sessionId,
            'queue_size' => $queue->count(),
        ]);

        foreach ($queue as $position => $track) {
            TrackQueued::dispatch($track, $position, 80.0, 'Initial queue');
        }
    }

    /**
     * Check queue status and refill if needed.
     */
    protected function checkQueueStatus(): void
    {
        $currentSize = $this->queueBuilder->getQueue()->count();
        $minSize = config('dj.queue.min_queue_size', 5);

        if ($currentSize < $minSize) {
            Log::info('Queue running low, adding more tracks', [
                'current_size' => $currentSize,
                'min_size' => $minSize,
            ]);

            $additionalTracks = $this->queueBuilder->buildSmartQueue([
                'mode' => $this->currentMode,
                'size' => $minSize - $currentSize,
            ]);

            foreach ($additionalTracks as $track) {
                $this->queueBuilder->addToQueue($track);
            }
        }
    }

    /**
     * Analyze queue compatibility.
     */
    protected function analyzeQueueCompatibility(array $track): array
    {
        $queue = $this->queueBuilder->getQueue();
        if ($queue->isEmpty()) {
            return ['average_compatibility' => 0, 'recommendations' => []];
        }

        $compatibilities = [];
        foreach ($queue as $queuedTrack) {
            $compatibility = $this->beatMatcher->analyzeCompatibility($track, $queuedTrack);
            $compatibilities[] = $compatibility['transition_score'];
        }

        return [
            'average_compatibility' => array_sum($compatibilities) / count($compatibilities),
            'min_compatibility' => min($compatibilities),
            'max_compatibility' => max($compatibilities),
            'recommendations' => $this->generateCompatibilityRecommendations($compatibilities),
        ];
    }

    /**
     * Generate compatibility recommendations.
     */
    protected function generateCompatibilityRecommendations(array $scores): array
    {
        $recommendations = [];

        if (min($scores) < 60) {
            $recommendations[] = 'Some tracks in queue have low compatibility';
        }

        if (array_sum($scores) / count($scores) < 75) {
            $recommendations[] = 'Consider rebuilding queue for better flow';
        }

        return $recommendations;
    }

    /**
     * Generate session summary.
     */
    protected function generateSessionSummary(): array
    {
        // This would gather real session data
        return [
            'session_id' => $this->sessionId,
            'duration' => 'N/A',
            'tracks_played' => 0,
            'mode' => $this->currentMode,
            'ended_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get mock current track for testing.
     */
    protected function getMockCurrentTrack(): array
    {
        return [
            'id' => 'mock-track-1',
            'name' => 'Mock Track',
            'artist' => 'Mock Artist',
            'audio_features' => [
                'tempo' => 128,
                'energy' => 0.8,
                'key' => 0,
                'mode' => 1,
                'danceability' => 0.7,
                'valence' => 0.6,
            ],
            'duration_ms' => 180000,
        ];
    }
}