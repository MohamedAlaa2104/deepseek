<?php

namespace Mohamedaladdin\Deepseek;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
class DeepseekServiceProvider extends ServiceProvider
{
    public function register()
    {
        
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/deepseek.php' => config_path('deepseek.php'),
        ], 'deepseek-config');
    }
}
