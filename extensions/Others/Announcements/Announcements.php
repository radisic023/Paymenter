<?php

namespace Paymenter\Extensions\Others\Announcements;

use App\Classes\Extension\Extension;
use App\Livewire\Auth\Register;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Paymenter\Extensions\Others\Announcements\Admin\Resources\AnnouncementResource;
use Paymenter\Extensions\Others\Announcements\Models\Announcement;
use App\Attributes\ExtensionMeta;

#[ExtensionMeta(
    name: 'Announcements',
    description: 'Publish announcements to client area',
    version: '1.0.0',
    author: 'Paymenter',
    icon: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAyNi4xLjAsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iRGlzY29yZF9Ob3RpZmljYXRpb24iIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiDQoJIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iMTI4cHgiIGhlaWdodD0iMTI4cHgiIHZpZXdCb3g9IjAgMCAxMjggMTI4IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxMjggMTI4OyINCgkgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+DQo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPg0KCS5zdDB7ZmlsbDojMjMyMzJCO30NCgkuc3Qxe2ZpbGw6I0ZGRkZGRjt9DQo8L3N0eWxlPg0KPGcgaWQ9IlJlY3RhbmdsZSI+DQoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTEwOCwxMjhIMjBDOSwxMjgsMCwxMTksMCwxMDhWMjBDMCw5LDksMCwyMCwwaDg4YzExLjEsMCwyMCw5LDIwLDIwdjg4QzEyOCwxMTksMTE5LDEyOCwxMDgsMTI4eiIvPg0KPC9nPg0KPHBhdGggaWQ9Ik1lZ2FwaG9uZSIgY2xhc3M9InN0MSIgZD0iTTk4LjIsNTUuM1YzMGMwLTIuMy0xLjktNC4yLTQuMi00LjJoLTQuMmMtOC4zLDguMy0yMy44LDEyLjktMzMuNCwxNS4xdjQ1DQoJYzkuNiwyLjIsMjUuMiw2LjgsMzMuNCwxNS4xaDQuMmMyLjMsMCw0LjItMS45LDQuMi00LjJWNzEuNWMzLjYtMC45LDYuMy00LjIsNi4zLTguMVMxMDEuOCw1Ni4yLDk4LjIsNTUuM3ogTTMxLjQsNDIuNQ0KCWMtNC42LDAtOC40LDMuNy04LjQsOC40djI1LjFjMCw0LjYsMy43LDguNCw4LjQsOC40aDQuMmw0LjIsMjAuOWg4LjRWNDIuNUgzMS40eiIvPg0KPC9zdmc+DQo=',
)]

class Announcements extends Extension
{
    public function getConfig($values = [])
    {
        // If announcement resource is not installed, return placeholder
        try {
            return [
                [
                    'name' => 'Notice',
                    'type' => 'placeholder',
                    'label' => new HtmlString('You can use this extension to display announcements on the client area. To create a new announcement, go to <a class="text-primary-600" href="' . AnnouncementResource::getUrl() . '">Announcements</a>.'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                [
                    'name' => 'Notice',
                    'type' => 'placeholder',
                    'label' => new HtmlString('You can use this extension to display announcements on the client area. You\'ll need to enable this extension above to get started.'),
                ],
            ];
        }
    }

    public function enabled()
    {
        // Run migrations
        Artisan::call('migrate', ['--path' => 'extensions/Others/Announcements/database/migrations/2024_10_19_095356_create_ext_announcements_table.php', '--force' => true]);
    }

    public function boot()
    {
        // Register routes
        require __DIR__ . '/routes/web.php';
        View::addNamespace('announcements', __DIR__ . '/resources/views');
        Lang::addNamespace('announcements', __DIR__ . '/resources/lang');

        // Register livewire
        \Livewire\Livewire::component('announcements.index', \Paymenter\Extensions\Others\Announcements\Livewire\Announcements\Index::class);
        \Livewire\Livewire::component('announcements.show', \Paymenter\Extensions\Others\Announcements\Livewire\Announcements\Show::class);
        \Livewire\Livewire::component('announcements.widget', \Paymenter\Extensions\Others\Announcements\Livewire\Announcements\Widget::class);

        Event::listen('navigation', function () {
            if (Announcement::where('is_active', true)->where('published_at', '<=', now())->count() == 0) {
                return;
            }

            return [
                'name' => __('announcements::announcement.announcements'),
                'route' => 'announcements.index',
                'icon' => 'ri-megaphone',
                'separator' => true,
                'children' => [],
            ];
        });

        Event::listen('pages.home', function () {
            return [
                'view' => view('announcements::index', [
                    'announcements' => Announcement::where('is_active', true)->where('published_at', '<=', now())->orderBy('published_at', 'desc')->get(),
                ]),
            ];
        });

        Event::listen('pages.dashboard', function () {
            return [
                'view' => view('announcements::widget', [
                    'announcements' => Announcement::where('is_active', true)
                        ->where('published_at', '<=', now())
                        ->orderBy('published_at', 'desc')
                        ->get(),
                ]),
            ];
        });
    }
}
