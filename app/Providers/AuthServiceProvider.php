<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Plainte;
use App\Models\Attachment;
use App\Policies\PlaintePolicy;
use App\Policies\AttachmentPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Plainte::class => PlaintePolicy::class,
        Attachment::class => AttachmentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
