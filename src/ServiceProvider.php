<?php

declare(strict_types=1);

namespace JordanPartridge\ConduitDj;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use JordanPartridge\ConduitDj\Commands\DjCommand;
use JordanPartridge\ConduitDj\Commands\DjModeCommand;
use JordanPartridge\ConduitDj\Commands\DjAnalyzeCommand;
use JordanPartridge\ConduitDj\Commands\DjQueueCommand;
use JordanPartridge\ConduitDj\Commands\DjSessionCommand;
use JordanPartridge\ConduitDj\Services\DjIntelligenceService;
use JordanPartridge\ConduitDj\Services\BeatMatchingService;
use JordanPartridge\ConduitDj\Services\QueueBuilderService;
use JordanPartridge\ConduitDj\Services\MoodAnalysisService;
use JordanPartridge\ConduitDj\Listeners\SpotifyTrackChangeListener;
use JordanPartridge\ConduitDj\Listeners\SpotifyTrackSkippedListener;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(BeatMatchingService::class);
        $this->app->singleton(QueueBuilderService::class);
        $this->app->singleton(MoodAnalysisService::class);
        
        // Register the main DJ service with dependencies
        $this->app->singleton(DjIntelligenceService::class, function ($app) {
            return new DjIntelligenceService(
                $app->make(BeatMatchingService::class),
                $app->make(QueueBuilderService::class),
                $app->make(MoodAnalysisService::class)
            );
        });
        
        // Register configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/dj.php', 'dj');
    }

    public function boot(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                DjCommand::class,
                DjModeCommand::class,
                DjAnalyzeCommand::class,
                DjQueueCommand::class,
                DjSessionCommand::class,
            ]);
        }
        
        // Register event listeners
        // Listen to Spotify events using string-based event names
        $this->app['events']->listen(
            'spotify.track.changed',
            [SpotifyTrackChangeListener::class, 'handle']
        );
        
        $this->app['events']->listen(
            'spotify.track.skipped',
            [SpotifyTrackSkippedListener::class, 'handle']
        );
        
        $this->app['events']->listen(
            'spotify.playback.paused',
            function ($event) {
                if (app()->bound(DjIntelligenceService::class)) {
                    app(DjIntelligenceService::class)->pauseSession();
                }
            }
        );
        
        $this->app['events']->listen(
            'spotify.playback.resumed',
            function ($event) {
                if (app()->bound(DjIntelligenceService::class)) {
                    app(DjIntelligenceService::class)->resumeSession();
                }
            }
        );
        
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/dj.php' => config_path('dj.php'),
        ], 'conduit-dj-config');
    }
}