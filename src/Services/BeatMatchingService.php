<?php

namespace JordanPartridge\ConduitDj\Services;

use Illuminate\Support\Facades\Log;

class BeatMatchingService
{
    /**
     * Analyze compatibility between two tracks.
     */
    public function analyzeCompatibility(array $track1, array $track2): array
    {
        $bpmCompatibility = $this->analyzeBpmCompatibility(
            $track1['audio_features']['tempo'] ?? 0,
            $track2['audio_features']['tempo'] ?? 0
        );

        $keyCompatibility = $this->analyzeKeyCompatibility(
            $track1['audio_features']['key'] ?? 0,
            $track2['audio_features']['mode'] ?? 0,
            $track2['audio_features']['key'] ?? 0,
            $track2['audio_features']['mode'] ?? 0
        );

        $energyTransition = $this->analyzeEnergyTransition(
            $track1['audio_features']['energy'] ?? 0,
            $track2['audio_features']['energy'] ?? 0
        );

        $transitionScore = $this->calculateTransitionScore(
            $bpmCompatibility,
            $keyCompatibility,
            $energyTransition
        );

        return [
            'bpm_compatibility' => $bpmCompatibility,
            'key_compatibility' => $keyCompatibility,
            'energy_transition' => $energyTransition,
            'transition_score' => $transitionScore,
            'recommended_crossfade' => $this->recommendCrossfadeDuration($transitionScore),
        ];
    }

    /**
     * Analyze BPM compatibility between tracks.
     */
    protected function analyzeBpmCompatibility(float $bpm1, float $bpm2): array
    {
        if ($bpm1 === 0.0 || $bpm2 === 0.0) {
            return [
                'compatible' => false,
                'difference' => 0,
                'percentage' => 0,
                'technique' => 'unknown',
                'score' => 0,
            ];
        }

        $difference = abs($bpm1 - $bpm2);
        $percentage = $difference / $bpm1;
        $tolerance = config('dj.beatmatching.bpm_tolerance', 0.16);

        // Check direct BPM compatibility
        if ($percentage <= $tolerance) {
            return [
                'compatible' => true,
                'difference' => $difference,
                'percentage' => $percentage,
                'technique' => 'beatmatch',
                'score' => 100 - ($percentage * 100),
            ];
        }

        // Check double/half tempo compatibility
        if (config('dj.beatmatching.allow_double_tempo', true)) {
            $doubleDiff = abs($bpm1 - ($bpm2 * 2));
            $halfDiff = abs($bpm1 - ($bpm2 / 2));

            if ($doubleDiff / $bpm1 <= $tolerance) {
                return [
                    'compatible' => true,
                    'difference' => $doubleDiff,
                    'percentage' => $doubleDiff / $bpm1,
                    'technique' => 'double_tempo',
                    'score' => 85,
                ];
            }

            if ($halfDiff / $bpm1 <= $tolerance) {
                return [
                    'compatible' => true,
                    'difference' => $halfDiff,
                    'percentage' => $halfDiff / $bpm1,
                    'technique' => 'half_tempo',
                    'score' => 85,
                ];
            }
        }

        return [
            'compatible' => false,
            'difference' => $difference,
            'percentage' => $percentage,
            'technique' => 'fade',
            'score' => max(0, 50 - ($percentage * 100)),
        ];
    }

    /**
     * Analyze key compatibility using Camelot Wheel.
     */
    protected function analyzeKeyCompatibility(int $key1, int $mode1, int $key2, int $mode2): array
    {
        if (!config('dj.beatmatching.key_compatibility', true)) {
            return [
                'compatible' => true,
                'relationship' => 'disabled',
                'score' => 100,
            ];
        }

        // Convert to Camelot notation
        $camelot1 = $this->toCamelot($key1, $mode1);
        $camelot2 = $this->toCamelot($key2, $mode2);

        // Check compatibility
        $compatible = $this->isCamelotCompatible($camelot1, $camelot2);
        $relationship = $this->getCamelotRelationship($camelot1, $camelot2);

        return [
            'compatible' => $compatible,
            'camelot1' => $camelot1,
            'camelot2' => $camelot2,
            'relationship' => $relationship,
            'score' => $this->getCamelotScore($relationship),
        ];
    }

    /**
     * Convert key and mode to Camelot notation.
     */
    protected function toCamelot(int $key, int $mode): string
    {
        // Camelot Wheel mapping
        $majorMap = [
            8 => '1B',  // C
            3 => '2B',  // C#/Db
            10 => '3B', // D
            5 => '4B',  // D#/Eb
            0 => '5B',  // E
            7 => '6B',  // F
            2 => '7B',  // F#/Gb
            9 => '8B',  // G
            4 => '9B',  // G#/Ab
            11 => '10B', // A
            6 => '11B', // A#/Bb
            1 => '12B', // B
        ];

        $minorMap = [
            5 => '1A',  // Am
            0 => '2A',  // A#m/Bbm
            7 => '3A',  // Bm
            2 => '4A',  // Cm
            9 => '5A',  // C#m/Dbm
            4 => '6A',  // Dm
            11 => '7A', // D#m/Ebm
            6 => '8A',  // Em
            1 => '9A',  // Fm
            8 => '10A', // F#m/Gbm
            3 => '11A', // Gm
            10 => '12A', // G#m/Abm
        ];

        return $mode === 1 ? ($majorMap[$key] ?? '1B') : ($minorMap[$key] ?? '1A');
    }

    /**
     * Check if two Camelot keys are compatible.
     */
    protected function isCamelotCompatible(string $key1, string $key2): bool
    {
        if ($key1 === $key2) {
            return true;
        }

        // Extract number and letter
        preg_match('/(\d+)([AB])/', $key1, $matches1);
        preg_match('/(\d+)([AB])/', $key2, $matches2);

        if (!$matches1 || !$matches2) {
            return false;
        }

        $num1 = (int) $matches1[1];
        $letter1 = $matches1[2];
        $num2 = (int) $matches2[1];
        $letter2 = $matches2[2];

        // Same number (relative major/minor)
        if ($num1 === $num2 && config('dj.beatmatching.allow_relative_keys', true)) {
            return true;
        }

        // Adjacent numbers (same letter)
        if ($letter1 === $letter2) {
            $diff = abs($num1 - $num2);
            if ($diff === 1 || $diff === 11) {
                return true;
            }
        }

        // Strict mode only allows these
        if (config('dj.beatmatching.camelot_strict', false)) {
            return false;
        }

        // Energy boost (+2)
        if ($letter1 === $letter2 && (($num1 + 2) % 12 === $num2 % 12)) {
            return true;
        }

        // Energy drop (-2)
        if ($letter1 === $letter2 && (($num1 - 2 + 12) % 12 === $num2 % 12)) {
            return true;
        }

        return false;
    }

    /**
     * Get the relationship between two Camelot keys.
     */
    protected function getCamelotRelationship(string $key1, string $key2): string
    {
        if ($key1 === $key2) {
            return 'same';
        }

        preg_match('/(\d+)([AB])/', $key1, $matches1);
        preg_match('/(\d+)([AB])/', $key2, $matches2);

        $num1 = (int) $matches1[1];
        $letter1 = $matches1[2];
        $num2 = (int) $matches2[1];
        $letter2 = $matches2[2];

        if ($num1 === $num2) {
            return 'relative';
        }

        if ($letter1 === $letter2) {
            $diff = ($num2 - $num1 + 12) % 12;
            return match ($diff) {
                1 => 'up_fifth',
                11 => 'down_fifth',
                2 => 'up_tone',
                10 => 'down_tone',
                default => 'other',
            };
        }

        return 'incompatible';
    }

    /**
     * Get compatibility score for Camelot relationship.
     */
    protected function getCamelotScore(string $relationship): int
    {
        return match ($relationship) {
            'same' => 100,
            'relative' => 95,
            'up_fifth', 'down_fifth' => 90,
            'up_tone' => 80,
            'down_tone' => 75,
            'other' => 50,
            'incompatible' => 25,
        };
    }

    /**
     * Analyze energy transition between tracks.
     */
    protected function analyzeEnergyTransition(float $energy1, float $energy2): array
    {
        $difference = $energy2 - $energy1;
        $maxTransition = config('dj.beatmatching.energy_transition_max', 0.2);

        $compatible = abs($difference) <= $maxTransition;
        $direction = $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'steady');

        return [
            'compatible' => $compatible,
            'difference' => $difference,
            'direction' => $direction,
            'percentage' => $difference * 100,
            'score' => max(0, 100 - (abs($difference) * 200)),
        ];
    }

    /**
     * Calculate overall transition score.
     */
    protected function calculateTransitionScore(array $bpm, array $key, array $energy): float
    {
        $weights = [
            'bpm' => config('dj.beatmatching.prefer_harmonic', true) ? 0.3 : 0.5,
            'key' => config('dj.beatmatching.prefer_harmonic', true) ? 0.5 : 0.3,
            'energy' => 0.2,
        ];

        $score = ($bpm['score'] * $weights['bpm']) +
                 ($key['score'] * $weights['key']) +
                 ($energy['score'] * $weights['energy']);

        return round($score, 1);
    }

    /**
     * Recommend crossfade duration based on compatibility.
     */
    protected function recommendCrossfadeDuration(float $score): int
    {
        $baseDuration = config('dj.beatmatching.crossfade_duration', 8);

        if ($score >= 90) {
            return $baseDuration + 4; // Long crossfade for perfect matches
        }

        if ($score >= 75) {
            return $baseDuration;
        }

        if ($score >= 60) {
            return $baseDuration - 2;
        }

        return max(2, $baseDuration - 4); // Quick fade for poor matches
    }

    /**
     * Find transition point between tracks.
     */
    public function findTransitionPoint(array $track1, array $track2): array
    {
        $outro1 = $this->detectOutro($track1);
        $intro2 = $this->detectIntro($track2);

        return [
            'start_fade' => $outro1['start'],
            'end_fade' => $outro1['end'],
            'skip_to' => $intro2['end'],
            'technique' => $this->determineTransitionTechnique($track1, $track2),
        ];
    }

    /**
     * Detect outro section of track.
     */
    protected function detectOutro(array $track): array
    {
        if (!config('dj.beatmatching.outro_detection', true)) {
            // Default to last 30 seconds
            $duration = $track['duration_ms'] ?? 180000;
            return [
                'start' => ($duration - 30000) / 1000,
                'end' => $duration / 1000,
            ];
        }

        // Simplified outro detection
        $duration = $track['duration_ms'] ?? 180000;
        $energy = $track['audio_features']['energy'] ?? 0.5;

        // High energy tracks typically have longer outros
        $outroLength = $energy > 0.7 ? 45000 : 30000;

        return [
            'start' => ($duration - $outroLength) / 1000,
            'end' => $duration / 1000,
        ];
    }

    /**
     * Detect intro section of track.
     */
    protected function detectIntro(array $track): array
    {
        if (!config('dj.beatmatching.intro_skip', true)) {
            return [
                'start' => 0,
                'end' => 0,
            ];
        }

        // Simplified intro detection
        $energy = $track['audio_features']['energy'] ?? 0.5;

        // Skip longer intros for high energy tracks
        $introLength = $energy > 0.7 ? 15 : 10;

        return [
            'start' => 0,
            'end' => $introLength,
        ];
    }

    /**
     * Determine best transition technique.
     */
    protected function determineTransitionTechnique(array $track1, array $track2): string
    {
        $compatibility = $this->analyzeCompatibility($track1, $track2);

        if ($compatibility['transition_score'] >= 85) {
            return 'beatmatch';
        }

        if ($compatibility['key_compatibility']['compatible']) {
            return 'harmonic_fade';
        }

        if ($compatibility['energy_transition']['direction'] === 'up') {
            return 'build';
        }

        return 'fade';
    }
}