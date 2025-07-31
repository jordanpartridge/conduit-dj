<?php

namespace JordanPartridge\ConduitDj\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class QueueBuilderService
{
    protected BeatMatchingService $beatMatcher;

    protected Collection $recentTracks;

    protected Collection $queue;

    public function __construct(BeatMatchingService $beatMatcher)
    {
        $this->beatMatcher = $beatMatcher;
        $this->recentTracks = collect();
        $this->queue = collect();
    }

    /**
     * Build a smart queue based on current context.
     */
    public function buildSmartQueue(array $options = []): Collection
    {
        $size = $options['size'] ?? config('dj.queue.min_queue_size', 5);
        $mode = $options['mode'] ?? 'party';
        $currentTrack = $options['current_track'] ?? null;
        $targetEnergy = $options['target_energy'] ?? config("dj.modes.{$mode}.target_energy", 0.5);

        // Get candidate tracks
        $candidates = $this->getCandidateTracks($options);

        if ($candidates->isEmpty()) {
            Log::warning('No candidate tracks available for queue building');
            return collect();
        }

        // Build progressive queue
        $this->queue = collect();
        $lastTrack = $currentTrack;

        for ($i = 0; $i < $size; $i++) {
            $nextTrack = $this->selectNextTrack(
                $candidates,
                $lastTrack,
                $targetEnergy,
                $i,
                $size,
                $mode
            );

            if (!$nextTrack) {
                break;
            }

            $this->queue->push($nextTrack);
            $lastTrack = $nextTrack;

            // Remove from candidates to avoid duplicates
            $candidates = $candidates->reject(function ($track) use ($nextTrack) {
                return $track['id'] === $nextTrack['id'];
            });
        }

        return $this->queue;
    }

    /**
     * Get candidate tracks for queue building.
     */
    protected function getCandidateTracks(array $options): Collection
    {
        // This would integrate with Spotify API to get tracks
        // For now, return mock data
        return collect([
            [
                'id' => 'track1',
                'name' => 'Track 1',
                'artist' => 'Artist 1',
                'audio_features' => [
                    'tempo' => 128,
                    'energy' => 0.8,
                    'key' => 0,
                    'mode' => 1,
                    'danceability' => 0.7,
                    'valence' => 0.6,
                ],
                'duration_ms' => 180000,
            ],
            // Add more mock tracks...
        ]);
    }

    /**
     * Select the next track for the queue.
     */
    protected function selectNextTrack(
        Collection $candidates,
        ?array $lastTrack,
        float $targetEnergy,
        int $position,
        int $totalSize,
        string $mode
    ): ?array {
        if ($candidates->isEmpty()) {
            return null;
        }

        // Calculate scores for each candidate
        $scoredCandidates = $candidates->map(function ($candidate) use ($lastTrack, $targetEnergy, $position, $totalSize, $mode) {
            $score = $this->calculateTrackScore(
                $candidate,
                $lastTrack,
                $targetEnergy,
                $position,
                $totalSize,
                $mode
            );

            return [
                'track' => $candidate,
                'score' => $score,
            ];
        });

        // Sort by score and select best match
        $best = $scoredCandidates->sortByDesc('score')->first();

        if (!$best || $best['score'] < 30) {
            Log::warning('No suitable track found with minimum score');
            return null;
        }

        Log::info('Selected track for queue', [
            'track' => $best['track']['name'],
            'score' => $best['score'],
            'position' => $position,
        ]);

        return $best['track'];
    }

    /**
     * Calculate score for a candidate track.
     */
    protected function calculateTrackScore(
        array $candidate,
        ?array $lastTrack,
        float $targetEnergy,
        int $position,
        int $totalSize,
        string $mode
    ): float {
        $score = 0;

        // Transition compatibility (40% weight)
        if ($lastTrack) {
            $compatibility = $this->beatMatcher->analyzeCompatibility($lastTrack, $candidate);
            $score += $compatibility['transition_score'] * 0.4;
        } else {
            $score += 40; // No previous track, neutral score
        }

        // Energy progression (30% weight)
        $energyScore = $this->calculateEnergyScore(
            $candidate['audio_features']['energy'] ?? 0.5,
            $targetEnergy,
            $position,
            $totalSize,
            $mode
        );
        $score += $energyScore * 0.3;

        // Diversity factors (20% weight)
        $diversityScore = $this->calculateDiversityScore($candidate);
        $score += $diversityScore * 0.2;

        // User preferences (10% weight)
        $preferenceScore = $this->calculatePreferenceScore($candidate);
        $score += $preferenceScore * 0.1;

        return $score;
    }

    /**
     * Calculate energy score based on progression.
     */
    protected function calculateEnergyScore(
        float $trackEnergy,
        float $targetEnergy,
        int $position,
        int $totalSize,
        string $mode
    ): float {
        $energyCurve = config("dj.modes.{$mode}.energy_curve", 'steady');
        $variance = config("dj.modes.{$mode}.energy_variance", 0.1);

        // Calculate ideal energy for this position
        $idealEnergy = match ($energyCurve) {
            'ascending' => $this->calculateAscendingEnergy($targetEnergy, $position, $totalSize),
            'wave' => $this->calculateWaveEnergy($targetEnergy, $position, $totalSize),
            default => $targetEnergy,
        };

        // Calculate difference from ideal
        $difference = abs($trackEnergy - $idealEnergy);

        // Score based on how close we are to ideal
        if ($difference <= $variance) {
            return 100;
        }

        return max(0, 100 - ($difference * 200));
    }

    /**
     * Calculate ascending energy curve.
     */
    protected function calculateAscendingEnergy(float $target, int $position, int $total): float
    {
        // Start at 70% of target, build to 110%
        $start = $target * 0.7;
        $end = $target * 1.1;
        $progress = $position / max(1, $total - 1);

        return $start + (($end - $start) * $progress);
    }

    /**
     * Calculate wave energy curve.
     */
    protected function calculateWaveEnergy(float $target, int $position, int $total): float
    {
        // Create a sine wave pattern
        $amplitude = 0.2;
        $frequency = 2 * pi() / $total;
        $wave = sin($position * $frequency) * $amplitude;

        return $target + ($target * $wave);
    }

    /**
     * Calculate diversity score.
     */
    protected function calculateDiversityScore(array $candidate): float
    {
        $score = 100;
        $diversityFactor = config('dj.queue.diversity_factor', 0.3);

        // Check artist repetition
        $artistCount = $this->recentTracks
            ->where('artist', $candidate['artist'] ?? '')
            ->count();

        if ($artistCount > 0) {
            $artistLimit = config('dj.queue.artist_repeat_limit', 3);
            $penalty = min(50, ($artistCount / $artistLimit) * 50);
            $score -= $penalty * $diversityFactor;
        }

        // Check genre diversity (would need genre data)
        // For now, assume tracks have implicit diversity

        // Discovery bonus
        if ($this->isDiscoveryTrack($candidate)) {
            $discoveryRatio = config('dj.queue.discovery_ratio', 0.2);
            $score += 20 * $discoveryRatio;
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate preference score based on user history.
     */
    protected function calculatePreferenceScore(array $candidate): float
    {
        // This would integrate with knowledge system
        // For now, return neutral score
        return 70;
    }

    /**
     * Check if track qualifies as discovery.
     */
    protected function isDiscoveryTrack(array $track): bool
    {
        // Track is discovery if:
        // - Not in recent history
        // - Low play count in knowledge
        // - Different from usual preferences

        return !$this->recentTracks->contains('id', $track['id']);
    }

    /**
     * Update recent tracks history.
     */
    public function addToHistory(array $track): void
    {
        $this->recentTracks->push($track);

        // Keep only last N tracks
        $limit = config('dj.queue.artist_repeat_limit', 3) * 5;
        if ($this->recentTracks->count() > $limit) {
            $this->recentTracks = $this->recentTracks->slice(-$limit);
        }
    }

    /**
     * Get current queue.
     */
    public function getQueue(): Collection
    {
        return $this->queue;
    }

    /**
     * Clear the queue.
     */
    public function clearQueue(): void
    {
        $this->queue = collect();
    }

    /**
     * Remove track from queue.
     */
    public function removeFromQueue(string $trackId): void
    {
        $this->queue = $this->queue->reject(function ($track) use ($trackId) {
            return $track['id'] === $trackId;
        });
    }

    /**
     * Add track to queue at position.
     */
    public function addToQueue(array $track, ?int $position = null): void
    {
        if ($position === null) {
            $this->queue->push($track);
        } else {
            $this->queue->splice($position, 0, [$track]);
        }
    }

    /**
     * Reorder queue for optimal flow.
     */
    public function optimizeQueue(): void
    {
        if ($this->queue->count() < 2) {
            return;
        }

        // Get current playing track
        $currentTrack = $this->queue->first();

        // Remove from queue
        $remaining = $this->queue->slice(1);

        // Rebuild optimized queue
        $optimized = collect([$currentTrack]);
        $lastTrack = $currentTrack;

        while ($remaining->isNotEmpty()) {
            // Find best next track
            $best = null;
            $bestScore = 0;

            foreach ($remaining as $candidate) {
                $compatibility = $this->beatMatcher->analyzeCompatibility($lastTrack, $candidate);
                if ($compatibility['transition_score'] > $bestScore) {
                    $best = $candidate;
                    $bestScore = $compatibility['transition_score'];
                }
            }

            if (!$best) {
                // Add remaining tracks as-is
                $optimized = $optimized->merge($remaining);
                break;
            }

            $optimized->push($best);
            $lastTrack = $best;
            $remaining = $remaining->reject(function ($track) use ($best) {
                return $track['id'] === $best['id'];
            });
        }

        $this->queue = $optimized;
    }
}