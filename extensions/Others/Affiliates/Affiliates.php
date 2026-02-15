<?php

namespace Paymenter\Extensions\Others\Affiliates;

use App\Classes\Extension\Extension;
use App\Events\Invoice\Paid as InvoicePaid;
use App\Events\Order\Created as OrderCreated;
use App\Events\User\Created as UserCreated;
use App\Helpers\ExtensionHelper;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Paymenter\Extensions\Others\Affiliates\Listeners\AssociateOrderWithAffiliate;
use Paymenter\Extensions\Others\Affiliates\Listeners\IncreamentAffiliateSignups;
use Paymenter\Extensions\Others\Affiliates\Listeners\RewardAffiliate;
use Paymenter\Extensions\Others\Affiliates\Livewire\Affiliates\Affiliate as AffiliateComponent;
use Paymenter\Extensions\Others\Affiliates\Middleware\AffiliatesMiddleware;
use Paymenter\Extensions\Others\Affiliates\Models\Affiliate;
use App\Attributes\ExtensionMeta;

#[ExtensionMeta(
    name: 'Affiliates',
    description: 'Manage user referrals and rewards',
    version: '1.0.0',
    author: 'Paymenter',
    icon: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAyNi4xLjAsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iRGlzY29yZF9Ob3RpZmljYXRpb24iIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiDQoJIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iMTI4cHgiIGhlaWdodD0iMTI4cHgiIHZpZXdCb3g9IjAgMCAxMjggMTI4IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxMjggMTI4OyINCgkgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+DQo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPg0KCS5zdDB7ZmlsbDojMjMyMzJCO30NCgkuc3Qxe2ZpbGw6I0ZGRkZGRjt9DQo8L3N0eWxlPg0KPGcgaWQ9IlJlY3RhbmdsZSI+DQoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTEwOCwxMjhIMjBDOSwxMjgsMCwxMTksMCwxMDhWMjBDMCw5LDksMCwyMCwwaDg4YzExLjEsMCwyMCw5LDIwLDIwdjg4QzEyOCwxMTksMTE5LDEyOCwxMDgsMTI4eiIvPg0KPC9nPg0KPHBhdGggaWQ9IkFmZmlsaWF0ZXMiIGNsYXNzPSJzdDEiIGQ9Ik01Mi42LDY1bDguOSwwQzcxLjcsNjUsODAsNzMuMyw4MCw4My41SDUxLjJsMCw0LjFsMzIuOCwwdi00LjFjMC00LjQtMS4zLTguNi0zLjYtMTIuM2wxMS44LDANCgljOC4yLDAsMTUuMiw0LjgsMTguNSwxMS43QzEwMS4xLDk1LjcsODUuNCwxMDQsNjcuNywxMDRjLTExLjMsMC0yMC45LTIuNC0yOC43LTYuN2wwLTM4LjJDNDQsNTkuOSw0OC43LDYxLjksNTIuNiw2NXogTTM0LjgsOTUuOA0KCWMwLDIuMy0xLjgsNC4xLTQuMSw0LjFoLTguMmMtMi4zLDAtNC4xLTEuOC00LjEtNC4xVjU4LjhjMC0yLjMsMS44LTQuMSw0LjEtNC4xaDguMmMyLjMsMCw0LjEsMS44LDQuMSw0LjFWOTUuOHogTTg4LjIsMzguMw0KCWM2LjgsMCwxMi4zLDUuNSwxMi4zLDEyLjNjMCw2LjgtNS41LDEyLjMtMTIuMywxMi4zYy02LjgsMC0xMi4zLTUuNS0xMi4zLTEyLjNDNzUuOSw0My44LDgxLjQsMzguMyw4OC4yLDM4LjN6IE01OS40LDI2DQoJYzYuOCwwLDEyLjMsNS41LDEyLjMsMTIuM2MwLDYuOC01LjUsMTIuMy0xMi4zLDEyLjNjLTYuOCwwLTEyLjMtNS41LTEyLjMtMTIuM0M0Ny4xLDMxLjUsNTIuNiwyNiw1OS40LDI2eiIvPg0KPC9zdmc+DQo=',
)]

class Affiliates extends Extension
{
    public function __construct(public $config = []) {}

    /**
     * Get all the configuration for the extension
     *
     * @param  array  $values
     * @return array
     */
    public function getConfig($values = [])
    {
        return [
            [
                'name' => 'default_reward',
                'label' => 'Default Affiliate Reward',
                'type' => 'number',
                'description' => 'Percentage of the purchase amount the affiliated user would receive as a reward.',
                'required' => true,
                'suffix' => '%',
                'validation' => 'integer|min:0|max:100',
            ],
            [
                'name' => 'cookie_max_age',
                'label' => 'Referral Cookie Max-Age',
                'type' => 'number',
                'description' => 'Amount of days for which the referral cookie be valid. (Set 0 for infinite)',
                'required' => true,
                'validation' => 'integer|min:0',
            ],
            [
                'name' => 'type',
                'label' => 'Affiliate Code Type',
                'type' => 'select',
                'default' => 'random',
                'description' => 'How the affiliate would be assigned.',
                'required' => true,
                'options' => [
                    'random' => 'Random',
                    'custom' => 'Custom',
                ],
            ],
        ];
    }

    public function enabled()
    {
        // Run migrations
        Artisan::call('migrate', ['--path' => 'extensions/Others/Affiliates/database/migrations/2024_12_25_075634_create_ext_affiliates_table.php', '--force' => true]);
        Artisan::call('migrate', ['--path' => 'extensions/Others/Affiliates/database/migrations/2025_01_31_155928_create_ext_affiliate_orders_table.php', '--force' => true]);
    }

    public function disabled() {}

    public function boot()
    {
        require __DIR__ . '/routes/web.php';
        View::addNamespace('affiliates', __DIR__ . '/resources/views');
        Lang::addNamespace('affiliates', __DIR__ . '/resources/lang');

        Livewire::component('affiliate', AffiliateComponent::class);

        User::resolveRelationUsing('affiliate', function (User $userModel) {
            return $userModel->hasOne(Affiliate::class, 'user_id');
        });

        ExtensionHelper::registerMiddleware(AffiliatesMiddleware::class);

        // Listen for UserCreated and InvoicePaid events
        Event::listen(
            UserCreated::class,
            IncreamentAffiliateSignups::class,
        );
        Event::listen(
            InvoicePaid::class,
            RewardAffiliate::class,
        );
        Event::listen(
            OrderCreated::class,
            AssociateOrderWithAffiliate::class,
        );

        // Event::listen('navigation.dashboard', function ($routes) {
        //     dd($routes);
        //     return [
        //         'name' => __('affiliates::affiliate.affiliate'),
        //         'route' => 'affiliate.index',
        //         'icon' => 'heroicon-o-banknotes',
        //         'group' => 'Administration',
        //     ];
        // });

        // Hook onto account navigation
        Event::listen('navigation.account', function () {
            return [
                'name' => __('affiliates::affiliate.affiliate'),
                'route' => 'affiliate.index',
            ];
        });
    }
}
