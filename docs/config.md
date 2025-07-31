# Conduit DJ Configuration Guide

## Overview

Conduit DJ uses a flexible configuration system that allows you to customize DJ behavior, modes, and algorithms.

## Configuration File

The main configuration file is `config/dj.php`. You can publish it to your Conduit installation:

```bash
conduit vendor:publish --tag=conduit-dj-config
```

## Configuration Options

### DJ Modes

Define different DJ modes with their characteristics:

```php
'modes' => [
    'party' => [
        'target_energy' => 0.8,      // High energy (0.0-1.0)
        'energy_variance' => 0.2,     // Allow 20% variance
        'tempo_range' => [120, 140],  // BPM range
        'genres' => ['pop', 'dance', 'electronic'],
        'vocal_preference' => 'high', // high, medium, low
        'transition_style' => 'quick' // quick, smooth, dramatic
    ],
    
    'focus' => [
        'target_energy' => 0.5,
        'energy_variance' => 0.1,     // Keep it consistent
        'tempo_range' => [110, 130],
        'genres' => ['ambient', 'chillstep', 'lo-fi'],
        'vocal_preference' => 'low',  // Minimal vocals
        'transition_style' => 'smooth'
    ],
    
    'chill' => [
        'target_energy' => 0.3,
        'energy_variance' => 0.1,
        'tempo_range' => [90, 115],
        'genres' => ['chillout', 'downtempo', 'jazz'],
        'vocal_preference' => 'medium',
        'transition_style' => 'smooth'
    ],
    
    'workout' => [
        'target_energy' => 0.7,
        'energy_variance' => 0.3,     // Building energy
        'tempo_range' => [128, 150],
        'genres' => ['electronic', 'hip-hop', 'rock'],
        'vocal_preference' => 'high',
        'transition_style' => 'quick',
        'energy_curve' => 'ascending' // ascending, steady, wave
    ]
],
```

### Beatmatching Configuration

Control how tracks are matched:

```php
'beatmatching' => [
    // BPM matching
    'bpm_tolerance' => 0.16,          // 16% tempo difference allowed
    'prefer_harmonic' => true,        // Prioritize key compatibility
    'allow_double_tempo' => true,     // Allow 2x/0.5x tempo matching
    
    // Key matching
    'key_compatibility' => true,      // Enable harmonic mixing
    'camelot_strict' => false,        // Strict Camelot wheel rules
    'allow_relative_keys' => true,    // Major/minor transitions
    
    // Energy transitions
    'energy_transition_max' => 0.2,   // Max 20% energy jump
    'energy_boost_tracks' => 3,       // Tracks to boost energy
    'energy_cool_tracks' => 2,        // Tracks to cool down
    
    // Transition timing
    'crossfade_duration' => 8,        // Default crossfade seconds
    'outro_detection' => true,        // Detect track outros
    'intro_skip' => true,            // Skip long intros
],
```

### Queue Management

Configure smart queue behavior:

```php
'queue' => [
    'min_queue_size' => 5,           // Minimum tracks in queue
    'max_queue_size' => 20,          // Maximum queue size
    'queue_ahead_seconds' => 30,     // Queue next track X seconds before
    'check_interval' => 10,          // Check queue every X seconds
    
    // Smart queue building
    'diversity_factor' => 0.3,       // Genre diversity (0-1)
    'artist_repeat_limit' => 3,      // Tracks before repeat artist
    'discovery_ratio' => 0.2,        // Ratio of new tracks
    
    // User preferences
    'respect_user_queue' => true,    // Don't override manual queue
    'learn_from_skips' => true,      // Learn from skip behavior
    'skip_threshold' => 0.3,         // Skip if <30% played
],
```

### AI Configuration (Laravel Prism)

Configure AI-powered features:

```php
'ai' => [
    'provider' => env('DJ_AI_PROVIDER', 'openai'),
    'model' => env('DJ_AI_MODEL', 'gpt-4'),
    
    // Mood analysis
    'analyze_lyrics' => true,        // Analyze lyrical content
    'detect_explicit' => true,       // Flag explicit content
    'mood_categories' => [
        'happy', 'sad', 'energetic', 'relaxed',
        'aggressive', 'romantic', 'melancholic'
    ],
    
    // Recommendations
    'recommendation_prompt' => 'You are an expert DJ...',
    'context_window' => 10,          // Consider last N tracks
    
    // Performance
    'cache_analysis' => true,        // Cache AI responses
    'cache_ttl' => 86400,           // 24 hours
],
```

### Learning System

Configure preference learning:

```php
'learning' => [
    'enabled' => true,
    'min_plays_to_learn' => 3,       // Plays before learning
    
    // Factors to track
    'track_factors' => [
        'play_count' => 1.0,         // Weight multipliers
        'skip_count' => -2.0,
        'play_duration' => 0.5,
        'time_of_day' => 0.3,
        'day_of_week' => 0.2,
    ],
    
    // Knowledge integration
    'store_in_knowledge' => true,
    'knowledge_tags' => ['dj', 'preferences', 'learning'],
],
```

### Session Management

Configure DJ sessions:

```php
'sessions' => [
    'auto_stop_idle' => 300,         // Stop after 5 min idle
    'save_history' => true,          // Save session history
    'history_retention' => 90,       // Days to keep history
    
    // Background processing
    'background_enabled' => true,
    'process_timeout' => 3600,       // 1 hour max runtime
    'memory_limit' => '256M',
    
    // Notifications
    'notify_on_start' => true,
    'notify_on_error' => true,
    'notification_driver' => 'desktop', // desktop, slack, discord
],
```

### Performance Tuning

Optimize for your system:

```php
'performance' => [
    // Caching
    'cache_driver' => 'redis',       // redis, file, array
    'cache_analysis' => true,        // Cache track analysis
    'cache_ttl' => 86400,           // 24 hours
    
    // API limits
    'spotify_rate_limit' => 180,     // Requests per minute
    'batch_size' => 50,             // Tracks per API call
    
    // Processing
    'async_analysis' => true,        // Async track analysis
    'worker_count' => 2,            // Background workers
],
```

## Environment Variables

Key environment variables:

```bash
# AI Configuration
DJ_AI_PROVIDER=openai
DJ_AI_MODEL=gpt-4
DJ_AI_API_KEY=your-key

# Performance
DJ_CACHE_DRIVER=redis
DJ_ASYNC_ENABLED=true

# Notifications
DJ_NOTIFY_DRIVER=desktop
DJ_SLACK_WEBHOOK=https://...

# Debug
DJ_DEBUG=false
DJ_LOG_LEVEL=info
```

## Mode-Specific Overrides

Override configuration per mode:

```php
// In your command or service
$dj->startSession([
    'mode' => 'party',
    'config' => [
        'beatmatching.bpm_tolerance' => 0.20,
        'queue.diversity_factor' => 0.5,
    ]
]);
```

## Custom Modes

Create custom DJ modes:

```php
// config/dj.php
'modes' => [
    'custom_study' => [
        'target_energy' => 0.4,
        'energy_variance' => 0.05,
        'tempo_range' => [100, 120],
        'genres' => ['classical', 'ambient', 'piano'],
        'vocal_preference' => 'instrumental',
        'transition_style' => 'smooth',
        'custom_rules' => [
            'no_sudden_changes' => true,
            'prefer_instrumental' => true,
        ]
    ]
],
```

## Validation

The configuration is validated on load:

```php
// Validation rules
'validation' => [
    'modes.*.target_energy' => 'required|numeric|between:0,1',
    'modes.*.energy_variance' => 'required|numeric|between:0,1',
    'beatmatching.bpm_tolerance' => 'required|numeric|between:0,0.5',
    'queue.min_queue_size' => 'required|integer|min:1',
],
```

## Best Practices

1. **Start with defaults**: The default configuration works well for most users
2. **Adjust gradually**: Make small changes and test
3. **Mode-specific**: Use different modes rather than changing global config
4. **Monitor performance**: Watch API limits and caching
5. **User feedback**: Let the learning system adapt to preferences

## Troubleshooting

### Common Issues

**High CPU usage**
```php
'performance.async_analysis' => false,
'performance.worker_count' => 1,
```

**API rate limits**
```php
'performance.spotify_rate_limit' => 60, // Reduce requests
'performance.batch_size' => 20,         // Smaller batches
```

**Poor transitions**
```php
'beatmatching.bpm_tolerance' => 0.10,  // Stricter matching
'beatmatching.prefer_harmonic' => true, // Prioritize key
```

## Examples

### Nightclub Configuration
```php
'modes.club' => [
    'target_energy' => 0.85,
    'energy_variance' => 0.15,
    'tempo_range' => [124, 132],
    'genres' => ['house', 'techno', 'edm'],
    'transition_style' => 'dramatic',
    'energy_curve' => 'wave',
],
```

### Radio Station Configuration
```php
'queue' => [
    'artist_repeat_limit' => 10,
    'diversity_factor' => 0.7,
    'discovery_ratio' => 0.3,
    'respect_user_queue' => false,
],
```