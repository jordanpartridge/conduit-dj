<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DJ Modes Configuration
    |--------------------------------------------------------------------------
    |
    | Define different DJ modes with their characteristics. Each mode
    | controls energy levels, tempo ranges, genre preferences, and
    | transition styles for different listening scenarios.
    |
    */

    'modes' => [
        'party' => [
            'target_energy' => 0.8,
            'energy_variance' => 0.2,
            'tempo_range' => [120, 140],
            'genres' => ['pop', 'dance', 'electronic'],
            'vocal_preference' => 'high',
            'transition_style' => 'quick',
        ],

        'focus' => [
            'target_energy' => 0.5,
            'energy_variance' => 0.1,
            'tempo_range' => [110, 130],
            'genres' => ['ambient', 'chillstep', 'lo-fi'],
            'vocal_preference' => 'low',
            'transition_style' => 'smooth',
        ],

        'chill' => [
            'target_energy' => 0.3,
            'energy_variance' => 0.1,
            'tempo_range' => [90, 115],
            'genres' => ['chillout', 'downtempo', 'jazz'],
            'vocal_preference' => 'medium',
            'transition_style' => 'smooth',
        ],

        'workout' => [
            'target_energy' => 0.7,
            'energy_variance' => 0.3,
            'tempo_range' => [128, 150],
            'genres' => ['electronic', 'hip-hop', 'rock'],
            'vocal_preference' => 'high',
            'transition_style' => 'quick',
            'energy_curve' => 'ascending',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Beatmatching Configuration
    |--------------------------------------------------------------------------
    |
    | Control how tracks are matched for seamless transitions. Includes
    | BPM tolerance, harmonic mixing rules, energy transitions, and
    | crossfade timing parameters.
    |
    */

    'beatmatching' => [
        // BPM matching
        'bpm_tolerance' => 0.16,
        'prefer_harmonic' => true,
        'allow_double_tempo' => true,

        // Key matching
        'key_compatibility' => true,
        'camelot_strict' => false,
        'allow_relative_keys' => true,

        // Energy transitions
        'energy_transition_max' => 0.2,
        'energy_boost_tracks' => 3,
        'energy_cool_tracks' => 2,

        // Transition timing
        'crossfade_duration' => 8,
        'outro_detection' => true,
        'intro_skip' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configure smart queue behavior including size limits, timing,
    | diversity factors, and user preference handling.
    |
    */

    'queue' => [
        'min_queue_size' => 5,
        'max_queue_size' => 20,
        'queue_ahead_seconds' => 30,
        'check_interval' => 10,

        // Smart queue building
        'diversity_factor' => 0.3,
        'artist_repeat_limit' => 3,
        'discovery_ratio' => 0.2,

        // User preferences
        'respect_user_queue' => true,
        'learn_from_skips' => true,
        'skip_threshold' => 0.3,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Configuration (Laravel Prism)
    |--------------------------------------------------------------------------
    |
    | Configure AI-powered features for mood analysis, recommendations,
    | and intelligent track selection using Laravel Prism.
    |
    */

    'ai' => [
        'provider' => env('DJ_AI_PROVIDER', 'openai'),
        'model' => env('DJ_AI_MODEL', 'gpt-4'),

        // Mood analysis
        'analyze_lyrics' => true,
        'detect_explicit' => true,
        'mood_categories' => [
            'happy', 'sad', 'energetic', 'relaxed',
            'aggressive', 'romantic', 'melancholic',
        ],

        // Recommendations
        'recommendation_prompt' => 'You are an expert DJ analyzing music for smooth transitions and mood compatibility.',
        'context_window' => 10,

        // Performance
        'cache_analysis' => true,
        'cache_ttl' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Learning System Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the DJ learns from user behavior and builds
    | preference profiles over time.
    |
    */

    'learning' => [
        'enabled' => true,
        'min_plays_to_learn' => 3,

        // Factors to track
        'track_factors' => [
            'play_count' => 1.0,
            'skip_count' => -2.0,
            'play_duration' => 0.5,
            'time_of_day' => 0.3,
            'day_of_week' => 0.2,
        ],

        // Knowledge integration
        'store_in_knowledge' => true,
        'knowledge_tags' => ['dj', 'preferences', 'learning'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configure DJ session behavior, history tracking, background
    | processing, and notification settings.
    |
    */

    'sessions' => [
        'auto_stop_idle' => 300,
        'save_history' => true,
        'history_retention' => 90,

        // Background processing
        'background_enabled' => true,
        'process_timeout' => 3600,
        'memory_limit' => '256M',

        // Notifications
        'notify_on_start' => true,
        'notify_on_error' => true,
        'notification_driver' => 'desktop',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Tuning Configuration
    |--------------------------------------------------------------------------
    |
    | Optimize performance with caching, API rate limits, and
    | background processing settings.
    |
    */

    'performance' => [
        // Caching
        'cache_driver' => 'redis',
        'cache_analysis' => true,
        'cache_ttl' => 86400,

        // API limits
        'spotify_rate_limit' => 180,
        'batch_size' => 50,

        // Processing
        'async_analysis' => true,
        'worker_count' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    |
    | Control debug output and logging for development and
    | troubleshooting.
    |
    */

    'debug' => [
        'log_events' => env('DJ_DEBUG', false),
        'log_level' => env('DJ_LOG_LEVEL', 'info'),
        'log_channel' => 'dj',
    ],
];