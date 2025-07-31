# Conduit DJ Event System

## Overview

Conduit DJ uses Laravel's event system to provide real-time hooks into DJ operations. This allows other components and your own code to react to DJ activities.

## Core Events

### DJ Session Events

#### `DjSessionStarted`
Fired when a DJ session begins.

```php
use JordanPartridge\ConduitDj\Events\DjSessionStarted;

Event::listen(DjSessionStarted::class, function ($event) {
    $sessionId = $event->sessionId;
    $mode = $event->mode;
    $config = $event->config;
    $startedAt = $event->startedAt;
    
    // Log session start
    Log::info("DJ session started", [
        'session' => $sessionId,
        'mode' => $mode
    ]);
});
```

#### `DjSessionStopped`
Fired when a DJ session ends.

```php
use JordanPartridge\ConduitDj\Events\DjSessionStopped;

Event::listen(DjSessionStopped::class, function ($event) {
    $sessionId = $event->sessionId;
    $duration = $event->duration;
    $tracksPlayed = $event->tracksPlayed;
    $summary = $event->summary;
});
```

#### `DjModeChanged`
Fired when DJ mode changes during a session.

```php
use JordanPartridge\ConduitDj\Events\DjModeChanged;

Event::listen(DjModeChanged::class, function ($event) {
    $fromMode = $event->fromMode;
    $toMode = $event->toMode;
    $reason = $event->reason; // 'manual', 'scheduled', 'auto'
});
```

### Track Events

#### `TrackQueued`
Fired when a track is added to the queue.

```php
use JordanPartridge\ConduitDj\Events\TrackQueued;

Event::listen(TrackQueued::class, function ($event) {
    $track = $event->track;
    $position = $event->position;
    $score = $event->compatibilityScore;
    $reason = $event->reason; // Why this track was chosen
});
```

#### `TrackAnalyzed`
Fired when a track is analyzed.

```php
use JordanPartridge\ConduitDj\Events\TrackAnalyzed;

Event::listen(TrackAnalyzed::class, function ($event) {
    $trackId = $event->trackId;
    $features = $event->features; // BPM, key, energy, etc.
    $mood = $event->mood;         // AI-detected mood
    $compatibility = $event->compatibility;
});
```

#### `TransitionPlanned`
Fired when a transition is planned between tracks.

```php
use JordanPartridge\ConduitDj\Events\TransitionPlanned;

Event::listen(TransitionPlanned::class, function ($event) {
    $fromTrack = $event->fromTrack;
    $toTrack = $event->toTrack;
    $transitionPoint = $event->transitionPoint; // Seconds
    $crossfadeDuration = $event->crossfadeDuration;
    $technique = $event->technique; // 'beatmatch', 'fade', 'cut'
});
```

### Learning Events

#### `PreferenceLearned`
Fired when the DJ learns a new preference.

```php
use JordanPartridge\ConduitDj\Events\PreferenceLearned;

Event::listen(PreferenceLearned::class, function ($event) {
    $type = $event->type; // 'genre', 'artist', 'energy', 'tempo'
    $value = $event->value;
    $confidence = $event->confidence;
    $basedOn = $event->basedOn; // What data led to this learning
});
```

#### `SkipPatternDetected`
Fired when a skip pattern is detected.

```php
use JordanPartridge\ConduitDj\Events\SkipPatternDetected;

Event::listen(SkipPatternDetected::class, function ($event) {
    $pattern = $event->pattern;
    $tracks = $event->affectedTracks;
    $recommendation = $event->recommendation;
});
```

### Error Events

#### `DjErrorOccurred`
Fired when an error occurs during DJ operations.

```php
use JordanPartridge\ConduitDj\Events\DjErrorOccurred;

Event::listen(DjErrorOccurred::class, function ($event) {
    $error = $event->error;
    $context = $event->context;
    $severity = $event->severity; // 'low', 'medium', 'high', 'critical'
    $recoverable = $event->recoverable;
});
```

## Spotify Integration Events

Conduit DJ listens to these Spotify events:

### `spotify.track.changed`
```php
// DJ reacts to manual track changes
Event::listen('spotify.track.changed', function ($event) {
    // DJ analyzes the new track
    // Updates queue if needed
    // Learns from user choice
});
```

### `spotify.track.skipped`
```php
// DJ learns from skips
Event::listen('spotify.track.skipped', function ($event) {
    // Records skip
    // Adjusts preferences
    // Updates queue
});
```

### `spotify.playback.paused`
```php
// DJ pauses operations
Event::listen('spotify.playback.paused', function ($event) {
    // Pauses queue building
    // Saves state
});
```

## Custom Event Listeners

### Creating Listeners

```php
namespace App\Listeners;

use JordanPartridge\ConduitDj\Events\TrackQueued;

class NotifyOnTrackQueued
{
    public function handle(TrackQueued $event)
    {
        // Send notification
        Notification::send(
            auth()->user(),
            new TrackQueuedNotification($event->track)
        );
    }
}
```

### Registering Listeners

In your service provider:

```php
protected $listen = [
    TrackQueued::class => [
        NotifyOnTrackQueued::class,
    ],
    DjSessionStarted::class => [
        LogDjSession::class,
        SendDjStartNotification::class,
    ],
];
```

## Event Priorities

Some events support priorities:

```php
Event::listen(TrackQueued::class, function ($event) {
    // Normal priority
}, 10);

Event::listen(TrackQueued::class, function ($event) {
    // High priority (runs first)
}, 100);
```

## Async Events

Long-running listeners should be queued:

```php
class ProcessTrackAnalysis implements ShouldQueue
{
    use InteractsWithQueue, Queueable;
    
    public function handle(TrackAnalyzed $event)
    {
        // Long-running analysis
    }
}
```

## Event Data Structure

### Track Object
```php
[
    'id' => 'spotify:track:...',
    'name' => 'Track Name',
    'artist' => 'Artist Name',
    'album' => 'Album Name',
    'duration_ms' => 180000,
    'features' => [
        'tempo' => 128.5,
        'key' => 9,
        'energy' => 0.85,
        'danceability' => 0.75,
        'valence' => 0.65,
    ],
]
```

### Session Summary
```php
[
    'session_id' => 'uuid',
    'duration' => '01:45:30',
    'tracks_played' => 28,
    'tracks_skipped' => 3,
    'average_energy' => 0.72,
    'transitions' => 27,
    'perfect_transitions' => 22,
    'mode_changes' => 1,
    'top_genres' => ['electronic', 'house'],
]
```

## Testing Events

### Fake Events
```php
use Illuminate\Support\Facades\Event;

public function test_track_queued_event_is_fired()
{
    Event::fake();
    
    $dj = app(DjIntelligenceService::class);
    $dj->queueTrack($track);
    
    Event::assertDispatched(TrackQueued::class, function ($event) use ($track) {
        return $event->track['id'] === $track['id'];
    });
}
```

### Assert Event Data
```php
Event::assertDispatched(DjSessionStarted::class, function ($event) {
    return $event->mode === 'party' 
        && $event->config['target_energy'] === 0.8;
});
```

## Event Debugging

Enable event logging:

```php
// config/dj.php
'debug' => [
    'log_events' => true,
    'log_level' => 'debug',
],
```

View events in real-time:
```bash
conduit dj:debug --events
```

## Best Practices

1. **Keep listeners lightweight** - Queue heavy operations
2. **Don't modify event data** - Events should be immutable
3. **Use specific events** - Don't listen to all events
4. **Handle failures gracefully** - Don't break the DJ flow
5. **Test event listeners** - Ensure they work as expected

## Common Patterns

### Logging All DJ Activity
```php
Event::listen('conduit.dj.*', function ($eventName, $data) {
    Log::channel('dj')->info($eventName, $data);
});
```

### Building Analytics
```php
Event::listen([
    TrackQueued::class,
    TrackAnalyzed::class,
    PreferenceLearned::class,
], function ($event) {
    DjAnalytics::record($event);
});
```

### Integration with External Services
```php
Event::listen(DjSessionStarted::class, function ($event) {
    // Post to Discord
    Http::post('https://discord.com/api/webhooks/...', [
        'content' => "🎧 DJ Session started in {$event->mode} mode!"
    ]);
});
```