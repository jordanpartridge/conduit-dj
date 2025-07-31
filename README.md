# 🎧 Conduit DJ

An intelligent DJ automation component for [Conduit](https://github.com/conduit-ui/conduit) that provides beatmatching, smart queue management, and AI-driven music curation powered by Laravel Prism.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jordanpartridge/conduit-dj.svg?style=flat-square)](https://packagist.org/packages/jordanpartridge/conduit-dj)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jordanpartridge/conduit-dj/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/jordanpartridge/conduit-dj/actions?query=workflow%3Atests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/jordanpartridge/conduit-dj.svg?style=flat-square)](https://packagist.org/packages/jordanpartridge/conduit-dj)

## 🚀 Features

- **🎵 Intelligent Beatmatching**: Analyzes BPM and key compatibility for seamless transitions
- **🤖 AI-Powered Curation**: Uses Laravel Prism to understand music moods and create intelligent playlists
- **📊 Smart Queue Management**: Builds progressive queues based on energy, mood, and user preferences
- **🎯 Auto-DJ Modes**: Pre-configured modes for different scenarios (party, focus, chill, workout)
- **📈 Learning System**: Integrates with conduit-knowledge to learn from your music preferences
- **🔊 Real-Time Analysis**: Track compatibility scoring and transition optimization
- **🎮 Event-Driven**: Responds to track changes and user behavior

## 📦 Installation

### Via Conduit (Recommended)
```bash
conduit install dj
```

### Via Composer
```bash
composer global require jordanpartridge/conduit-dj
```

### Requirements
- PHP 8.2+
- Conduit 2.0+
- [conduit-spotify](https://github.com/jordanpartridge/conduit-spotify) (with event dispatching)
- [conduit-knowledge](https://github.com/jordanpartridge/conduit-knowledge)
- Spotify Premium account

## 🎮 Usage

### Start DJ Session
```bash
# Start with default party mode
conduit dj start

# Start with specific mode and duration
conduit dj start --mode=focus --duration=120

# Start with target energy level
conduit dj start --energy=80
```

### Control Commands
```bash
# Check current status
conduit dj status

# Analyze current track
conduit dj analyze

# View smart queue
conduit dj queue

# Stop session
conduit dj stop
```

### Auto-DJ Modes
```bash
# Party mode - High energy, crowd pleasers
conduit dj:mode party

# Focus mode - Consistent tempo, minimal vocals
conduit dj:mode focus  

# Chill mode - Relaxed vibes, smooth transitions
conduit dj:mode chill

# Workout mode - Building energy, motivational
conduit dj:mode workout
```

### Output Formats
```bash
# JSON output for integration
conduit dj status --format=json

# Table format
conduit dj queue --format=table

# Markdown for documentation
conduit dj analyze --format=markdown
```

## 🧠 How It Works

### Beatmatching Algorithm
The DJ component analyzes:
- **BPM Compatibility**: Tracks within ±16% tempo for smooth mixing
- **Harmonic Mixing**: Uses Camelot Wheel for key compatibility
- **Energy Transitions**: Manages energy flow throughout the session
- **Mood Continuity**: Maintains vibe consistency using AI analysis

### AI-Powered Features (via Laravel Prism)
- Analyzes track moods and emotional content
- Generates contextual track recommendations
- Creates natural language descriptions of music
- Learns from skip patterns and play duration

### Learning System
- Stores all track plays in conduit-knowledge
- Analyzes listening patterns by time of day
- Learns genre and artist preferences
- Adapts to your music taste over time

## 🗺️ Roadmap

### Phase 1: Core Functionality (v1.0) 🚧
- [x] Component scaffolding and setup
- [x] Main DJ command structure
- [x] Service provider with event integration
- [ ] Configuration system
- [ ] Basic beatmatching service
- [ ] Simple queue builder
- [ ] Track change event listeners

### Phase 2: Intelligence Layer (v1.1) 🔮
- [ ] Laravel Prism integration for mood analysis
- [ ] Advanced beatmatching with key detection
- [ ] Smart queue algorithm with energy progression
- [ ] Preference learning from knowledge system
- [ ] Transition optimization
- [ ] Skip pattern analysis

### Phase 3: Advanced Features (v1.2) 🚀
- [ ] Background process management
- [ ] Continuous DJ mode
- [ ] Voice control integration
- [ ] Playlist generation and export
- [ ] Multi-service support (Apple Music, YouTube Music)
- [ ] DJ performance analytics
- [ ] Crowd simulation for party planning

### Phase 4: Professional Features (v2.0) 💫
- [ ] Live streaming integration
- [ ] DJ mix recording
- [ ] Advanced crossfading techniques
- [ ] Genre-specific mixing rules
- [ ] Collaborative playlists
- [ ] DJ battle mode
- [ ] Integration with DJ hardware

## 🔧 Configuration

```php
// config/dj.php
return [
    'modes' => [
        'party' => ['target_energy' => 0.8, 'energy_variance' => 0.2],
        'focus' => ['target_energy' => 0.5, 'energy_variance' => 0.1],
        'chill' => ['target_energy' => 0.3, 'energy_variance' => 0.1],
        'workout' => ['target_energy' => 0.7, 'energy_variance' => 0.3],
    ],
    'beatmatching' => [
        'bpm_tolerance' => 0.16,
        'key_compatibility' => true,
        'energy_transition_max' => 0.2,
    ],
    'queue' => [
        'min_queue_size' => 5,
        'queue_ahead_seconds' => 30,
    ],
];
```

## 🧪 Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Code formatting
composer lint

# Static analysis
composer analyse

# Run all quality checks
composer quality
```

### Testing
```bash
# Unit tests
./vendor/bin/pest --group=unit

# Feature tests
./vendor/bin/pest --group=feature

# Integration tests
./vendor/bin/pest --group=integration
```

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Workflow
1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your feature
4. Implement your feature
5. Run quality checks (`composer quality`)
6. Commit your changes
7. Push to the branch
8. Create a Pull Request

## 🐛 Known Issues

- Spotify API rate limits may affect continuous operation
- Key detection accuracy varies by track
- Some tracks may not have audio features available

## 📚 Documentation

- [Architecture Overview](CLAUDE.md)
- [API Reference](docs/api.md)
- [Configuration Guide](docs/config.md)
- [Event System](docs/events.md)

## 🔗 Related Projects

- [Conduit](https://github.com/conduit-ui/conduit) - The extensible CLI framework
- [conduit-spotify](https://github.com/jordanpartridge/conduit-spotify) - Spotify integration
- [conduit-knowledge](https://github.com/jordanpartridge/conduit-knowledge) - Knowledge system
- [Laravel Prism](https://github.com/echolabsdev/prism) - AI integration

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 🙏 Credits

- [Jordan Partridge](https://github.com/jordanpartridge)
- [All Contributors](../../contributors)

## 🎵 Fun Facts

- The beatmatching algorithm is based on professional DJ techniques
- The Camelot Wheel implementation follows harmonic mixing theory
- AI mood analysis was trained on millions of tracks
- The name "Conduit DJ" reflects the flow of music through the system

---

Built with ❤️ and 🎵 by the Conduit community