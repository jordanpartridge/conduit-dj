<?php

namespace JordanPartridge\ConduitDj\Services;

use EchoLabs\Prism\Prism;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MoodAnalysisService
{
    protected Prism $prism;

    public function __construct()
    {
        $this->prism = Prism::text()
            ->using(config('dj.ai.provider', 'openai'), config('dj.ai.model', 'gpt-4'))
            ->withSystemPrompt(config('dj.ai.recommendation_prompt', 'You are an expert DJ analyzing music for smooth transitions and mood compatibility.'));
    }

    /**
     * Analyze track mood using AI.
     */
    public function analyzeTrack(array $track): array
    {
        $cacheKey = "dj.mood.{$track['id']}";

        if (config('dj.ai.cache_analysis', true)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        try {
            $analysis = $this->performAiAnalysis($track);

            if (config('dj.ai.cache_analysis', true)) {
                Cache::put($cacheKey, $analysis, config('dj.ai.cache_ttl', 86400));
            }

            return $analysis;
        } catch (\Exception $e) {
            Log::error('Mood analysis failed', [
                'track' => $track['id'],
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackAnalysis($track);
        }
    }

    /**
     * Perform AI analysis on track.
     */
    protected function performAiAnalysis(array $track): array
    {
        $prompt = $this->buildAnalysisPrompt($track);

        $response = $this->prism
            ->withPrompt($prompt)
            ->generate();

        return $this->parseAiResponse($response, $track);
    }

    /**
     * Build analysis prompt for AI.
     */
    protected function buildAnalysisPrompt(array $track): string
    {
        $features = $track['audio_features'] ?? [];

        $prompt = "Analyze this track for DJ mixing and mood:\n\n";
        $prompt .= "Track: {$track['name']} by {$track['artist']}\n";

        if (!empty($features)) {
            $prompt .= "Audio Features:\n";
            $prompt .= "- Tempo: {$features['tempo']} BPM\n";
            $prompt .= "- Energy: " . ($features['energy'] * 100) . "%\n";
            $prompt .= "- Danceability: " . ($features['danceability'] * 100) . "%\n";
            $prompt .= "- Valence: " . ($features['valence'] * 100) . "%\n";
            $prompt .= "- Key: {$this->getKeyName($features['key'], $features['mode'])}\n";
        }

        $prompt .= "\nProvide a JSON response with:\n";
        $prompt .= "1. mood: primary mood category (happy, sad, energetic, relaxed, aggressive, romantic, melancholic)\n";
        $prompt .= "2. secondary_moods: array of other applicable moods\n";
        $prompt .= "3. vibe: brief description of the track's vibe (max 10 words)\n";
        $prompt .= "4. mixing_notes: specific tips for mixing this track\n";
        $prompt .= "5. energy_profile: 'building', 'peak', 'cooling', or 'steady'\n";
        $prompt .= "6. crowd_response: expected crowd reaction (1-10)\n";

        if (config('dj.ai.detect_explicit', true)) {
            $prompt .= "7. explicit: boolean indicating if content is explicit\n";
        }

        return $prompt;
    }

    /**
     * Parse AI response into structured data.
     */
    protected function parseAiResponse(string $response, array $track): array
    {
        try {
            // Extract JSON from response
            $json = $this->extractJson($response);
            $data = json_decode($json, true);

            return [
                'track_id' => $track['id'],
                'mood' => $data['mood'] ?? 'unknown',
                'secondary_moods' => $data['secondary_moods'] ?? [],
                'vibe' => $data['vibe'] ?? 'energetic dance track',
                'mixing_notes' => $data['mixing_notes'] ?? '',
                'energy_profile' => $data['energy_profile'] ?? 'steady',
                'crowd_response' => $data['crowd_response'] ?? 7,
                'explicit' => $data['explicit'] ?? false,
                'confidence' => 0.9,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to parse AI response', ['error' => $e->getMessage()]);
            return $this->getFallbackAnalysis($track);
        }
    }

    /**
     * Extract JSON from AI response.
     */
    protected function extractJson(string $response): string
    {
        // Look for JSON block
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            return $matches[1];
        }

        // Look for JSON object
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            return $matches[0];
        }

        throw new \Exception('No JSON found in response');
    }

    /**
     * Get fallback analysis based on audio features.
     */
    protected function getFallbackAnalysis(array $track): array
    {
        $features = $track['audio_features'] ?? [];
        $energy = $features['energy'] ?? 0.5;
        $valence = $features['valence'] ?? 0.5;

        // Determine mood from audio features
        $mood = $this->determineMoodFromFeatures($energy, $valence);

        return [
            'track_id' => $track['id'],
            'mood' => $mood,
            'secondary_moods' => [],
            'vibe' => $this->generateVibe($energy, $valence),
            'mixing_notes' => 'Standard transition recommended',
            'energy_profile' => $this->determineEnergyProfile($energy),
            'crowd_response' => round($energy * 10),
            'explicit' => false,
            'confidence' => 0.5,
        ];
    }

    /**
     * Determine mood from audio features.
     */
    protected function determineMoodFromFeatures(float $energy, float $valence): string
    {
        if ($energy > 0.7 && $valence > 0.7) {
            return 'happy';
        }
        if ($energy > 0.7 && $valence < 0.3) {
            return 'aggressive';
        }
        if ($energy < 0.3 && $valence < 0.3) {
            return 'sad';
        }
        if ($energy < 0.3 && $valence > 0.7) {
            return 'relaxed';
        }
        if ($valence > 0.6) {
            return 'energetic';
        }
        if ($valence < 0.4) {
            return 'melancholic';
        }

        return 'romantic';
    }

    /**
     * Generate vibe description.
     */
    protected function generateVibe(float $energy, float $valence): string
    {
        $energyDesc = $energy > 0.7 ? 'high energy' : ($energy < 0.3 ? 'chill' : 'moderate');
        $valenceDesc = $valence > 0.7 ? 'uplifting' : ($valence < 0.3 ? 'dark' : 'neutral');

        return "$energyDesc $valenceDesc track";
    }

    /**
     * Determine energy profile.
     */
    protected function determineEnergyProfile(float $energy): string
    {
        if ($energy > 0.8) {
            return 'peak';
        }
        if ($energy > 0.6) {
            return 'building';
        }
        if ($energy < 0.4) {
            return 'cooling';
        }

        return 'steady';
    }

    /**
     * Analyze mood progression across multiple tracks.
     */
    public function analyzeMoodProgression(array $tracks): array
    {
        if (empty($tracks)) {
            return [
                'coherence' => 0,
                'energy_flow' => 'none',
                'mood_consistency' => 0,
                'recommendations' => [],
            ];
        }

        $moods = [];
        $energies = [];

        foreach ($tracks as $track) {
            $analysis = $this->analyzeTrack($track);
            $moods[] = $analysis['mood'];
            $energies[] = $track['audio_features']['energy'] ?? 0.5;
        }

        return [
            'coherence' => $this->calculateCoherence($moods),
            'energy_flow' => $this->analyzeEnergyFlow($energies),
            'mood_consistency' => $this->calculateMoodConsistency($moods),
            'recommendations' => $this->generateProgressionRecommendations($moods, $energies),
        ];
    }

    /**
     * Calculate mood coherence score.
     */
    protected function calculateCoherence(array $moods): float
    {
        if (count($moods) < 2) {
            return 100;
        }

        $transitions = 0;
        $smoothTransitions = 0;

        for ($i = 1; $i < count($moods); $i++) {
            $transitions++;
            if ($this->isSmoothMoodTransition($moods[$i - 1], $moods[$i])) {
                $smoothTransitions++;
            }
        }

        return ($smoothTransitions / $transitions) * 100;
    }

    /**
     * Check if mood transition is smooth.
     */
    protected function isSmoothMoodTransition(string $from, string $to): bool
    {
        $smoothTransitions = [
            'happy' => ['energetic', 'relaxed'],
            'energetic' => ['happy', 'aggressive'],
            'aggressive' => ['energetic', 'melancholic'],
            'melancholic' => ['sad', 'romantic', 'aggressive'],
            'sad' => ['melancholic', 'romantic'],
            'romantic' => ['relaxed', 'sad', 'melancholic'],
            'relaxed' => ['happy', 'romantic'],
        ];

        return $from === $to || in_array($to, $smoothTransitions[$from] ?? []);
    }

    /**
     * Analyze energy flow pattern.
     */
    protected function analyzeEnergyFlow(array $energies): string
    {
        if (count($energies) < 2) {
            return 'steady';
        }

        $differences = [];
        for ($i = 1; $i < count($energies); $i++) {
            $differences[] = $energies[$i] - $energies[$i - 1];
        }

        $avgDiff = array_sum($differences) / count($differences);

        if ($avgDiff > 0.1) {
            return 'ascending';
        }
        if ($avgDiff < -0.1) {
            return 'descending';
        }

        $variance = array_sum(array_map(function ($d) use ($avgDiff) {
            return pow($d - $avgDiff, 2);
        }, $differences)) / count($differences);

        return $variance > 0.05 ? 'wave' : 'steady';
    }

    /**
     * Calculate mood consistency.
     */
    protected function calculateMoodConsistency(array $moods): float
    {
        if (empty($moods)) {
            return 0;
        }

        $moodCounts = array_count_values($moods);
        $dominantMoodCount = max($moodCounts);

        return ($dominantMoodCount / count($moods)) * 100;
    }

    /**
     * Generate progression recommendations.
     */
    protected function generateProgressionRecommendations(array $moods, array $energies): array
    {
        $recommendations = [];

        // Check energy flow
        $energyFlow = $this->analyzeEnergyFlow($energies);
        if ($energyFlow === 'steady' && count($energies) > 5) {
            $recommendations[] = 'Consider adding energy variation to maintain interest';
        }

        // Check mood variety
        $uniqueMoods = count(array_unique($moods));
        if ($uniqueMoods === 1 && count($moods) > 3) {
            $recommendations[] = 'Add mood variety to create dynamic progression';
        }

        // Check for harsh transitions
        for ($i = 1; $i < count($moods); $i++) {
            if (!$this->isSmoothMoodTransition($moods[$i - 1], $moods[$i])) {
                $recommendations[] = "Consider smoother transition between tracks $i and " . ($i + 1);
                break;
            }
        }

        return $recommendations;
    }

    /**
     * Get key name from Spotify key notation.
     */
    protected function getKeyName(int $key, int $mode): string
    {
        $keys = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
        $keyName = $keys[$key] ?? 'Unknown';

        return $keyName . ($mode === 1 ? ' Major' : ' Minor');
    }

    /**
     * Generate track recommendations based on mood.
     */
    public function recommendTracksForMood(string $targetMood, array $currentContext = []): array
    {
        $prompt = "Recommend 5 tracks that match the mood '$targetMood' for DJ mixing.\n";

        if (!empty($currentContext)) {
            $prompt .= "Current context:\n";
            $prompt .= "- Last track: {$currentContext['last_track']['name']} by {$currentContext['last_track']['artist']}\n";
            $prompt .= "- Current energy: " . ($currentContext['energy'] * 100) . "%\n";
            $prompt .= "- DJ mode: {$currentContext['mode']}\n";
        }

        $prompt .= "\nProvide recommendations as JSON array with: track_name, artist, reason";

        try {
            $response = $this->prism
                ->withPrompt($prompt)
                ->generate();

            $json = $this->extractJson($response);
            return json_decode($json, true) ?? [];
        } catch (\Exception $e) {
            Log::error('Track recommendation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}