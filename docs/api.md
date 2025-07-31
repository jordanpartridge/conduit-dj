# Conduit DJ API Reference

## Commands

### `dj` Command

Main entry point for DJ functionality.

```bash
conduit dj {action} [options]
```

#### Arguments
- `action` - The action to perform: `start`, `stop`, `analyze`, `queue`, `status` (optional, defaults to `status`)

#### Options
- `--mode` - DJ mode: `party`, `focus`, `chill`, `workout`
- `--duration` - Session duration in minutes
- `--energy` - Target energy level (0-100)
- `--format` - Output format: `terminal`, `json`, `table`, `markdown`

#### Examples
```bash
conduit dj start --mode=party --duration=60
conduit dj status --format=json
conduit dj analyze
```

### `dj:mode` Command

Start a specific DJ mode.

```bash
conduit dj:mode {mode} [options]
```

#### Arguments
- `mode` - The DJ mode: `party`, `focus`, `chill`, `workout`

#### Options
- `--duration` - Session duration in minutes
- `--transition` - Transition time between modes in seconds

### `dj:analyze` Command

Analyze tracks for DJ compatibility.

```bash
conduit dj:analyze [track-id] [options]
```

#### Arguments
- `track-id` - Spotify track ID (optional, uses current track if not provided)

#### Options
- `--compare-to` - Compare with specific track ID
- `--detailed` - Show detailed analysis
- `--format` - Output format

### `dj:queue` Command

Manage the smart DJ queue.

```bash
conduit dj:queue {action} [options]
```

#### Arguments
- `action` - Queue action: `show`, `add`, `remove`, `clear`, `build`

#### Options
- `--size` - Queue size (for build action)
- `--genre` - Filter by genre
- `--energy` - Target energy level

### `dj:session` Command

Manage DJ sessions.

```bash
conduit dj:session {action} [options]
```

#### Arguments
- `action` - Session action: `list`, `show`, `export`, `delete`

#### Options
- `--session-id` - Specific session ID
- `--format` - Export format: `json`, `csv`, `markdown`

## Services

### DjIntelligenceService

Main orchestrator for DJ functionality.

```php
use JordanPartridge\ConduitDj\Services\DjIntelligenceService;

$dj = app(DjIntelligenceService::class);

// Start a session
$session = $dj->startSession([
    'mode' => 'party',
    'duration' => 120,
    'target_energy' => 80
]);

// Get current status
$status = $dj->getStatus();

// Analyze current track
$analysis = $dj->analyzeCurrentTrack();

// Get smart queue
$queue = $dj->getSmartQueue();
```

### BeatMatchingService

Analyzes track compatibility.

```php
use JordanPartridge\ConduitDj\Services\BeatMatchingService;

$beatMatcher = app(BeatMatchingService::class);

// Analyze compatibility between two tracks
$compatibility = $beatMatcher->analyzeCompatibility($trackId1, $trackId2);

// Returns:
[
    'bpm_compatibility' => [...],
    'key_compatibility' => [...],
    'energy_transition' => [...],
    'transition_score' => 85.5,
    'recommended_crossfade' => 8
]
```

### QueueBuilderService

Builds intelligent queues.

```php
use JordanPartridge\ConduitDj\Services\QueueBuilderService;

$queueBuilder = app(QueueBuilderService::class);

// Build smart queue
$queue = $queueBuilder->buildSmartQueue([
    'size' => 10,
    'target_energy' => 0.7,
    'genre' => 'electronic',
    'mode' => 'party'
]);
```

### MoodAnalysisService

Uses Laravel Prism for AI-powered mood analysis.

```php
use JordanPartridge\ConduitDj\Services\MoodAnalysisService;

$moodAnalyzer = app(MoodAnalysisService::class);

// Analyze track mood
$mood = $moodAnalyzer->analyzeTrack($trackId);

// Analyze mood progression
$progression = $moodAnalyzer->analyzeMoodProgression($trackIds);
```

## Events

### TrackChanged

Fired when a track changes during a DJ session.

```php
use JordanPartridge\ConduitDj\Events\TrackChanged;

Event::listen(TrackChanged::class, function ($event) {
    $previousTrack = $event->previousTrack;
    $currentTrack = $event->currentTrack;
    $context = $event->context;
});
```

### DjSessionStarted

Fired when a DJ session starts.

```php
use JordanPartridge\ConduitDj\Events\DjSessionStarted;

Event::listen(DjSessionStarted::class, function ($event) {
    $sessionId = $event->sessionId;
    $mode = $event->mode;
    $config = $event->config;
});
```

### TransitionDetected

Fired when a track transition is detected.

```php
use JordanPartridge\ConduitDj\Events\TransitionDetected;

Event::listen(TransitionDetected::class, function ($event) {
    $transitionType = $event->transitionType;
    $compatibility = $event->compatibility;
    $quality = $event->transitionQuality;
});
```

## Models

### DjSession

Represents a DJ session.

```php
use JordanPartridge\ConduitDj\Models\DjSession;

$session = DjSession::create([
    'mode' => 'party',
    'target_energy' => 0.8,
    'duration' => 120
]);

// Get session tracks
$tracks = $session->tracks;

// Get session analytics
$analytics = $session->getAnalytics();
```

### TrackAnalysis

Cached track analysis data.

```php
use JordanPartridge\ConduitDj\Models\TrackAnalysis;

$analysis = TrackAnalysis::forTrack($trackId);

// Access features
$bpm = $analysis->tempo;
$key = $analysis->key;
$energy = $analysis->energy;
```

## Configuration

### Accessing Configuration

```php
// Get DJ configuration
$config = config('dj');

// Get specific mode config
$partyMode = config('dj.modes.party');

// Get beatmatching tolerance
$tolerance = config('dj.beatmatching.bpm_tolerance');
```

### Runtime Configuration

```php
// Override configuration at runtime
$dj->setConfig([
    'beatmatching.bpm_tolerance' => 0.20,
    'queue.min_queue_size' => 10
]);
```

## Error Handling

### Common Exceptions

```php
use JordanPartridge\ConduitDj\Exceptions\NoActiveSessionException;
use JordanPartridge\ConduitDj\Exceptions\IncompatibleTracksException;
use JordanPartridge\ConduitDj\Exceptions\SpotifyNotAvailableException;

try {
    $dj->startSession($config);
} catch (SpotifyNotAvailableException $e) {
    // Handle missing Spotify
} catch (NoActiveSessionException $e) {
    // Handle no active session
}
```

## Integration

### With Conduit Knowledge

```php
// Store DJ preferences
$knowledge = app('conduit.knowledge');
$knowledge->add("User prefers high energy transitions", [
    'tags' => ['dj', 'preferences'],
    'context' => ['session_id' => $sessionId]
]);
```

### With Spotify Events

```php
// Listen to Spotify events
Event::listen('spotify.track.changed', function ($event) {
    // DJ logic here
});
```